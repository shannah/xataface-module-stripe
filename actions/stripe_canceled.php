<?php
class actions_stripe_canceled {
    function handle($params) {
		$config = xf_stripe()->getConfig();
		if (@$config['cancel_action']) {
			$redirectUrl = DATAFACE_SITE_HREF . '?-action=' . urlencode($config['cancel_action']);
			header('Location: '.$redirectUrl);
			exit;
		}
        header('Location: '.DATAFACE_SITE_HREF. '?-action=stripe_plans');
        exit;
    }
}
?>