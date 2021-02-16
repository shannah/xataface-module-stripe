<?php
class actions_stripe_canceled {
    function handle($params) {
		$config = xf_stripe()->getConfig();
		if (@$config['cancel_action']) {
			$redirectUrl = DATAFACE_SITE_HREF . '?-action=' . urlencode($config['cancel_action']);
			Dataface_Application::getInstance()->redirect($redirectUrl);
			exit;
		}
        Dataface_Application::getInstance()->redirect(DATAFACE_SITE_HREF. '?-action=stripe_plans');
        exit;
    }
}
?>