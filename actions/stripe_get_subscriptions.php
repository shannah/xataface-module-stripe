<?php
class actions_stripe_get_subscriptions {
    function handle($params) {
        header('Content-type: application/json; charset="UTF-8"');
        if (xf_stripe()->getCustomerId()) {
            $customerData = xf_stripe()->client()->customers->retrieve(xf_stripe()->getCustomerId(), ['expand' => ['subscriptions']]);
            $subscriptions = $customerData['subscriptions'];
            $out = [];
            foreach ($subscriptions['data'] as $sub) {
                //print_r($sub);
                $items = [];
                foreach ($sub['items']['data'] as $item) {
                    $items[] = [
                        'price' => $item['price']['id'],
                        'product' => $item['price']['product']
                    ];
                }
                
                
                $row = [
                    'id' => $sub['id'],
                    'created' => $sub['created'],
                    'current_period_start' => $sub['current_period_start'],
                    'current_period_end' => $sub['current_period_end'],
                    'cancel_at_period_end' => $sub['cancel_at_period_end'],
                    'items' => $items
                ];
                $out[] = $row;
            }
            echo json_encode($out);
        } else {
            echo '[]';
        }
    }
}
?>