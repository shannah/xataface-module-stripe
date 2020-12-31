<?php
class actions_stripe_update_subscription {
    public function handle($params) {
        $mod = xf_stripe();
        $mod->setStripeKey();
        $subscriptionId = @$_POST['subscription_id'];
        if (!$subscriptionId) {
            $this->out(['code' => 500, 'message' => 'Missing subscription ID']);
            return;
        }
        $priceId = @$_POST['price_id'];
        if (!$priceId) {
            $this->out(['code' => 500, 'message' => 'Missing price ID']);
            return;
        }
        
        try {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            \Stripe\Subscription::update($subscriptionId, [
              'cancel_at_period_end' => false,
              'proration_behavior' => 'create_prorations',
              'items' => [
                [
                  'id' => $subscription->items->data[0]->id,
                  'price' => $priceId,
                ],
              ],
            ]);
        } catch(\Stripe\Exception\CardException $e) {
          // Since it's a decline, \Stripe\Exception\CardException will be caught
          echo 'Status is:' . $e->getHttpStatus() . '\n';
          echo 'Type is:' . $e->getError()->type . '\n';
          echo 'Code is:' . $e->getError()->code . '\n';
          // param is '' in this case
          echo 'Param is:' . $e->getError()->param . '\n';
          echo 'Message is:' . $e->getError()->message . '\n';
          $this->out(['code' => 400, 'message' => 'Failed to update subscription. '.$e->getError()->message]);
          return;
        } catch (\Stripe\Exception\RateLimitException $e) {
          // Too many requests made to the API too quickly
          $this->out(['code' => 500, 'message' => 'Update failed due to API request overload.  Please try again.']);
          return;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
          // Invalid parameters were supplied to Stripe's API
          
          $this->out(['code' => 500, 'message' => 'Update failed due to a server error']);
          return;
        } catch (\Stripe\Exception\AuthenticationException $e) {
          // Authentication with Stripe's API failed
          // (maybe you changed API keys recently)
          $this->out(['code' => 500, 'message' => 'Update failed due to an API authentication error']);
          return;
        } catch (\Stripe\Exception\ApiConnectionException $e) {
          // Network communication with Stripe failed
          $this->out(['code' => 500, 'message' => 'Update failed due to a network error.  Please try again.']);
          return;
        } catch (\Stripe\Exception\ApiErrorException $e) {
          // Display a very generic error to the user, and maybe send
          // yourself an email
          $this->out(['code' => 500, 'message' => 'Update failed due to a server error.  We are looking into the problem.  Please try again.']);
          return;
        } catch (Exception $e) {
          // Something else happened, completely unrelated to Stripe
          $this->out(['code' => 500, 'message' => 'Update failed due to a server error.  We are looking into the problem.  Please try again.']);
          return;
          
        }
        
        
        $this->out(['code' => 200, 'message' => 'Subscription updated successfully']);
    }
    
    function out($data) {
        header('Content-type: application/json; charset="UTF-8"');
        echo json_encode($data);
    }
}
?>