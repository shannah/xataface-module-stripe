<?php
class actions_stripe_get_checkout_session {
    function handle($params) {
        import(dirname(__FILE__).'/../shared.php');

        // Fetch the Checkout Session to display the JSON result on the success page
        $id = $_GET['sessionId'];
        $checkout_session = \Stripe\Checkout\Session::retrieve($id);

        echo json_encode($checkout_session);
    }
}

