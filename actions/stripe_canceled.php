<?php
class actions_stripe_canceled {
    function handle($params) {
        df_display([], 'stripe/canceled.html');
    }
}
?>