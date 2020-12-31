<?php
class actions_stripe_customer_portal {
    function handle($params) {
        //require '../vendor/autoload.php';
        $app = Dataface_Application::getInstance();
        $mt = Dataface_ModuleTool::getInstance();
        $mod = $mt->loadModule('modules_stripe');
        $config = $mod->getConfig();
        $query = $app->getQuery();

        if (!$config) {
        	http_response_code(500);
        	echo json_encode([ 'error' => 'Internal server error.' ]);
        	exit;
        }

        \Stripe\Stripe::setApiKey($config['stripe_secret_key']);

        //$input = file_get_contents('php://input');
        //$body = json_decode($input);

        // This is the ID of the Stripe Customer. Typically this is stored alongside
        // the authenticated user in your database. For demonstration, we're using the
        // config.
        
        $username = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
        if (!$username) {
            if (@$query['-response'] == 'json') {
                $this->out(['code' => 400, 'message' => 'Please login to manage billing']);
                return;
            }
            return Dataface_Error::permissionDenied("Please login to manage billing");
        }
        
        
        
        $stripe_customer_id = $mod->getCustomerId();
        
        if (!$stripe_customer_id) {
            if (@$query['-response'] == 'json') {
                $this->out(['code' => 400, 'message' => 'No billing plan found']);
                return;
            }
            return Dataface_Error::permissionDenied("No billing plan found.");
        }

        // This is the URL to which users are redirected after managing their billing
        // with the customer portal.
        $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
        $return_url = $user->getPublicLink();

        $session = \Stripe\BillingPortal\Session::create([
          'customer' => $stripe_customer_id,
          'return_url' => $return_url,
        ]);
        if (@$query['-response'] == 'json') {
            $this->out(['url' => $session->url]);
            return;
        }
        //print_r($session);exit;
        header('Location: '.$session->url);
        exit;
        
    }
    
    function out($data) {
        header('Content-type: application/json; charset="utf-8"');
        echo json_encode($data);
    }
}

