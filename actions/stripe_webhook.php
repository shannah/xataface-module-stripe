<?php
import(XFROOT.'xf/logging/Log.php');
use function xf\logging\xf_debug;
use function xf\logging\xf_info;
use function xf\logging\xf_error;


class actions_stripe_webhook {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $del = $app->getDelegate();
        include(dirname(__FILE__).'/../shared.php');
        $event = null;

        try {
        	// Make sure the event is coming from Stripe by checking the signature header
        	$event = \Stripe\Webhook::constructEvent($input, $_SERVER['HTTP_STRIPE_SIGNATURE'], $config['stripe_webhook_secret']);
        }
        catch (Exception $e) {
        	http_response_code(403);
			xf_debug("Stripe webhook failed signature check", null, $e);
        	echo json_encode([ 'error' => $e->getMessage() ]);
        	exit;
        }

        $details = '';

        $type = $event['type'];
        $object = $event['data']['object'];
        $customer = null;
		if (@$event['data']['customer']) {
			$customer = $event['data']['customer'];
		} else if (@$object['customer']) {
			$customer = $object['customer'];
		}
        $record = new Dataface_Record('stripe_transactions', []);
        $record->setValues([
            'customer_id' => $customer,
            'type' => $type,
            'data' => json_encode($event['data']),
            'date_created' => date('Y-m-d H:i:s')
        ]);
        $record->save();
		
		if ($type == 'checkout.session.completed') {
			if (@$object['subscription']) {
				xf_info('Received subscription '.$object['subscription'].' for checkout.session.completed event');
	            $mt = Dataface_ModuleTool::getInstance();
	            $mod = $mt->loadModule('modules_stripe');
	            $mod->setStripeKey();
	            $checkout_session = \Stripe\Checkout\Session::retrieve($object['id']);
                $username = null;
                if ($checkout_session and @$checkout_session['metadata'] and @$checkout_session['metadata']['username']) {
                    $username = $checkout_session['metadata']['username'];
                }
                if ($username) {
                    // Checkout session had a username assigned to it.  We will connect it to the stripe customer
					//$username = $checkout_session['metadata']['username'];
		            $existingCustomer = df_get_record('stripe_customers', ['customer_id' => '='.$customer]);
		            if (!$existingCustomer) {
						xf_info("Inserting stripe customer ".$customer." bound to user ".$username, '#stripe_webhook');
		                $newCustomer = new Dataface_Record('stripe_customers', []);
		                $newCustomer->setValues([
		                    'username' => $username,
		                    'customer_id' => $customer,
							'currency' => $checkout_session['currency']
		                ]);
						try {
			                $res = $newCustomer->save();
			                if (\PEAR::isError($res)) {
			                    xf_error("Error saving customer: ". $res->getMessage().". data: ".json_encode($event), '#stripe_webhook');
			                }
							return;
						} catch (\Exception $ex) {
							xf_error("Error saving customer: ". $ex->getMessage().". data: ".json_encode($event), '#stripe_webhook');
							return;
						}
		                
		            }
				} else {
					xf_error("Failed to retrieve session ".$checkout_session['subscription'].". data: ".json_encode($event), '#stripe_webhook');
					return;
				}
			} else {
				xf_error('No subscription found in checkout.session.completed event: '.json_encode($event), '#stripe_webhook');
			}
		}

		
        if ($del and method_exists($del, 'stripe_webhook')) {
            $del->stripe_webhook($event, $record);
        }
		xf_info("Received webhook ".$type." for customer ".$customer);


        $output = [
        	'status' => 'success'
        ];

        echo json_encode($output, JSON_PRETTY_PRINT);
        
    }
}
