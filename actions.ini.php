[stripe_manage_billing]
    category=personal_tools
    materialIcon=account_balance
    url="?-action=stripe_customer_portal"
    condition="df_is_logged_in() && xf_stripe()->getCustomerId()"
    label="Manage Billing"
    target="top"
    
[stripe_refresh_products]
    category=personal_tools
    materialIcon=refresh
    onclick="xf.stripe.refreshProducts()"
    permission="manage"
    label="Refresh Stripe Products"
    url="javascript:void(0)"
    