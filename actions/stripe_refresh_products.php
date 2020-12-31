<?php
class actions_stripe_refresh_products {
    function handle($params) {
        try {
            if (xf_stripe()->refreshProducts(true)) {
                http_response_code(201);
            } else {
                http_response_code(202);
            }
            
        } catch (Exception $ex) {
            http_response_code(500);
        }
        
        
    }
}
?>