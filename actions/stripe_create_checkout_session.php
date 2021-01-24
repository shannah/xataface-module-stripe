<?php
class actions_stripe_create_checkout_session {
    
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $mt = Dataface_ModuleTool::getInstance();
        $mod = $mt->loadModule('modules_stripe');
        
        $jt = Dataface_JavascriptTool::getInstance();
        
        include(dirname(__FILE__).'/../shared.php');


        $domain_url = df_absolute_url(DATAFACE_SITE_HREF);

        // Create new Checkout Session for the order
        // Other optional params include:
        // [billing_address_collection] - to display billing address details on the page
        // [customer] - if you have an existing Stripe Customer ID
        // [payment_intent_data] - lets capture the payment later
        // [customer_email] - lets you prefill the email input in the form
        // For full details see https://stripe.com/docs/api/checkout/sessions/create

        // ?session_id={CHECKOUT_SESSION_ID} means the redirect will have the session ID set as a query param
        $successUrl = $domain_url . '?-action=stripe_success&session_id={CHECKOUT_SESSION_ID}';
        $customerId = $mod->getCustomerId();
        $cancelUrl = $domain_url . '?-action=stripe_canceled';
		$metadata = [];

		if (class_exists('Dataface_AuthenticationTool')) {
			$auth = Dataface_AuthenticationTool::getInstance();
			$username = $auth->getLoggedInUsername();
			$metadata['username'] = $username;
            
		}
		
        $sessionData = [
        	'success_url' => $successUrl,
        	'cancel_url' => $cancelUrl,
        	'payment_method_types' => ['card'],
        	'mode' => 'subscription',
        	'line_items' => [[
        	  'price' => $body->priceId,
        	  'quantity' => 1
          	]],
			'metadata' => $metadata
        ];
        if ($customerId) {
            $sessionData['customer'] = $customerId;
        } 
		$sessionData['client_reference_id'] = 'foobarfoo';
		if (class_exists('Dataface_AuthenticationTool')) {
			$auth = Dataface_AuthenticationTool::getInstance();
            $user = $auth->getLoggedInUser();
			$username = $auth->getLoggedInUsername();
            if ($username) {
                $sessionData['client_reference_id'] = $username;
            }
			
            if (!$customerId) {
                $emailColumn = $auth->getEmailColumn();
                if ($user and $emailColumn) {
                    $email = $user->val($emailColumn);
                    if ($email) {
                        $sessionData['customer_email'] = $email;
                    }
                }
            }
            
		}
        $checkout_session = \Stripe\Checkout\Session::create($sessionData);

        echo json_encode(['sessionId' => $checkout_session['id']]);
        
    }
}
