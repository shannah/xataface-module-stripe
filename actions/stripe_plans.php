<?php
class actions_stripe_plans {
    function handle($params) {
        // Now work on our dependencies
		$mt = Dataface_ModuleTool::getInstance();

		$mod = $mt->loadModule('modules_stripe');
        $mod->addPaths();
		/*
        $products = [
            [
                "id" => "basic-plan",
                "name" => "Basic Subscription",
                "description" => "1 photo per week",
                "image" => "https://picsum.photos/280/320?random=4",
                "price" => "$5.00 per month",
                //"stripe_price_id" => "price_1Hb6xBEivx02AHkmQhirTe2h"
                "priceId" => "price_1HbQjqEivx02AHkm5orUiUZG"
            ],
            [
                "id" => "pro-plan",
                "name" => "Pro Subscription",
                "description" => "3 photo per week",
                "image" => "https://picsum.photos/280/320?random=4",
                "price" => "$12.00 per week",
                "priceId" => "price_456"
            ]
        ];
        */
        
        try {
            
            $products = $mod->getPlans();
            xf_script('stripe/script.js');
            
            $app = Dataface_Application::getInstance();
            
            
            if (count($products) > 0) {
                $conf = $mod->getConfig();
                $title = 'Select Plan';
                if (@$conf['actions.stripe_plans.title']) {
                    $title = $conf['actions.stripe_plans.title'];
                }
                $title = df_translate('modules.stripe.actions.stripe_plans.title', $title);
                $app->setPageTitle($title);
                df_display(['products' => $products], 'stripe/plans.html');
            } else {
                 df_display([], 'stripe/no-plans.html');
            }
            
        } catch (Exception $ex) {
            echo "Error: ".$ex->getMessage();
            error_log("Failed to get plans for stripe.  Likely a configuration problem.");
            df_display([], 'stripe/no-plans.html');
        }

        
    }
}
?>