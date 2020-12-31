//require <jquery.packed.js>
(function() {
    var $ = jQuery;
    window.xf = window.xf || {};
    window.xf.stripe = window.xf.stripe || {};
    window.xf.stripe.refreshProducts = function() {
        var spinner = $('<div class="spin fillscreen"/>');
        $('body').append(spinner);
        $.post(DATAFACE_SITE_HREF , {'-action' : 'stripe_refresh_products'})
            .done(function(data, statusText, xhr) {
                if (xhr.status === 201) {
                    alert("Stripe products have been refreshed");
                } else if (xhr.status === 202) {
                    alert("Failed to refresh products.  Check error log.");
                } else {
                    alert("Unknown response to refresh request.  Check error log");
                }
            }).always(function() {
                spinner.remove(); 
            });
    };
})();