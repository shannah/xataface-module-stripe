<?php
class actions_stripe_config {
    function handle($params) {
        require_once dirname(__FILE__).'/../shared.php';

        echo json_encode([
          'publishableKey' => $config['stripe_publishable_key']
        ]);
    }
}

