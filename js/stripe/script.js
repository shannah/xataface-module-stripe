//require-css <duDialog.min.css>
//require <duDialog.min.js>
//require <jquery.packed.js>
(function() {
    var $ = jQuery;
    //var DATAFACE_SITE_HREF='';
    // Create a Checkout Session with the selected plan ID
    var createCheckoutSession = function(priceId) {
      return fetch(DATAFACE_SITE_HREF+"?-action=stripe_create_checkout_session", {
        method: "POST",
		credentials: 'same-origin',
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          priceId: priceId
        })
      }).then(function(result) {
        return result.json();
      });
    };
    //foo
    
    var updateSubscription = function(subscriptionId, priceId) {
        return fetch(DATAFACE_SITE_HREF+"?-action=stripe_update_subscription", {
            method: "POST",
            credentials: 'same-origin',
            headers: { 'Content-type': 'application/x-www-form-urlencoded' },
            body: 'subscription_id='+encodeURIComponent(subscriptionId)+'&price_id='+encodeURIComponent(priceId)
        }).then(function(result) {
            return result.json();
        });
    }

    // Handle any errors returned from Checkout
    var handleResult = function(result) {
      if (result.error) {
        var displayError = document.getElementById("error-message");
        displayError.textContent = result.error.message;
      }
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        fetch(DATAFACE_SITE_HREF+'?-action=stripe_get_subscriptions')
            .then(function(result) {
                return result.json();
            })
            .then(function(json) {
                if (json.length > 0) {
                    var subscription = json[0];
                
                //json.forEach(function(subscription) {
                    
                    
                    
                    subscription.items.forEach(function(item) {
                        document.querySelector('body').classList.add('subscribed');
                        document.querySelectorAll('[data-stripe-product-id="'+item.product+'"]').forEach(function(div) {
                            div.classList.add('subscribed');
                        });
                        document.querySelectorAll('[data-stripe-price-id="'+item.price+'"]').forEach(function(div) {
                            div.classList.add('subscribed');
                            if (subscription.cancel_at_period_end) {
                                div.classList.add('canceled');
                            }
                            div.setAttribute('data-stripe-subscription-id', subscription.id);
                        });
                        document.querySelectorAll('[data-stripe-price-id]').forEach(function(div) {
                            div.setAttribute('data-stripe-subscription-id', subscription.id);
                        });
                        
                    
                    });
                    //});
                }
            });
    }, false);
    
    
    
    function duAlert(msg) {
        var prompt = new duDialog(null, msg, {
          buttons: duDialog.DEFAULT,
          okText: 'OK',
          callbacks: {
            okClick: function(){
              
              this.hide();  // hides the dialog
            }
          }
        });
    }

    /* Get your Stripe publishable key to initialize Stripe.js */
    fetch(DATAFACE_SITE_HREF+"?-action=stripe_config")
      .then(function(result) {
        return result.json();
      })
      .then(function(json) {
          console.log("json is ", json);
        var publishableKey = json.publishableKey;
        var basicPriceId = json.basicPrice;
        var proPriceId = json.proPrice;

        var stripe = Stripe(publishableKey);

        // Setup event handler to create a Checkout Session when button is clicked
        document.querySelectorAll('[data-stripe-price-id]').forEach(function(btn) {

            btn.addEventListener("click", function(evt) {
                
                if ($(btn).hasClass('subscribed') && !$(btn).hasClass('canceled')) {
                    duAlert("You are already subscribed to this plan");
                    return;
                }
                
                var subscriptionId = btn.getAttribute('data-stripe-subscription-id');
                if (subscriptionId) {
                    var planName = $(btn).parents('section').find('h1').text();
                    var msg = "Change your plan to '"+planName+"' for "+$('span.price', btn).text()+"?";
                    if ($(btn).hasClass('canceled')) {
                        msg = "You previously canceled this plan.  Would you like to renew it?";
                    }
                    var prompt = new duDialog(null, msg, {
                      buttons: duDialog.OK_CANCEL,
                      okText: 'Proceed',
                      callbacks: {
                        okClick: function(){
                          // do something
                            var spinner = $('<div class="spin fillscreen"></div>');
                            $('body').append(spinner);
                            updateSubscription(subscriptionId, btn.getAttribute('data-stripe-price-id')).then(function(data) {
                                $(spinner).remove();
                                duAlert("Your plan has been successfully changed");
                                
                            });
                            
                          this.hide();  // hides the dialog
                        }
                      }
                    });
                    
                    
                } else {
                    createCheckoutSession(btn.getAttribute('data-stripe-price-id')).then(function(data) {
                      // Call Stripe.js method to redirect to the new Checkout page
                      stripe
                        .redirectToCheckout({
                          sessionId: data.sessionId
                        })
                        .then(handleResult);
                    });
                }
              
            });
        });
        
      });
    
})();
