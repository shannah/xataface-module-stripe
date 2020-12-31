<?php
class actions_stripe_success {
    function handle($params) {
        $sessionId = $_GET['session_id'];
        if ($sessionId) {
            $mt = Dataface_ModuleTool::getInstance();
            $mod = $mt->loadModule('modules_stripe');
            $mod->setStripeKey();
            $checkout_session = \Stripe\Checkout\Session::retrieve($sessionId);
            if (!$checkout_session) {
                die("No session found");
            }
            $customerId = $checkout_session['customer'];
            if (!$customerId) {
                die("No customer found");
            }
            $username = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
            if (!$username) {
                return Dataface_Error::permissionDenied("Please login");
            }
            
            $existingCustomer = df_get_record('stripe_customers', ['customer_id' => '='.$customerId]);
            if (!$existingCustomer) {
                $newCustomer = new Dataface_Record('stripe_customers', []);
                $newCustomer->setValues([
                    'username' => $username,
                    'customer_id' => $customerId,
					'currency' => $checkout_session['currency']
                ]);
                $res = $newCustomer->save();
                if (PEAR::isError($res)) {
                    die("Error saving customer: ". $res->getMessage());
                }
            }
            
            
        }
		
		$config = xf_stripe()->getConfig();
		if (@$config['success_action']) {
			$redirectUrl = DATAFACE_SITE_HREF . '?-action=' . urlencode($config['success_action']);
			header('Location: '.$redirectUrl);
			exit;
		}
        Dataface_ModuleTool::getInstance()->loadModule('modules_stripe')->addPaths();
        df_display([], 'stripe/success.html');
    }
}
?>