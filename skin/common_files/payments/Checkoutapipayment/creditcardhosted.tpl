<div class="widget-container"></div>
<div class="content" id="payment">
    <input type="hidden" name="cko-payment-token" id="cko-payment-token">
{checkoutapijs}
<script>
    function checkCheckoutForm()
    {ldelim}
            // Check if profile filled in: registerform should not exist on the page
    if ($('form[name=registerform]').length > 0) {ldelim}
        xAlert(txt_opc_incomplete_profile, '', 'E');
        return false;
    {rdelim}

    if (need_shipping && ($('input[name=shippingid]').val() <= 0 || (undefined === shippingid || shippingid <= 0))) {ldelim}
        xAlert(txt_opc_shipping_not_selected, '', 'E');
        return false;
    {rdelim}

    if (!paymentid && (undefined === paymentid || paymentid <= 0)) {ldelim}
        xAlert(txt_opc_shipping_not_selected, '', 'E');
        return false;
    {rdelim}

            // Check terms accepting
    var termsObj = $('#accept_terms')[0];
    if (termsObj && !termsObj.checked) {ldelim}
        xAlert(txt_accept_terms_err, '', 'W');
        return false;
    {rdelim}
    var checkoutId = jQuery('#cko-payment-token').parents('.payment-details').attr('id').split('_')[1];
    if (checkoutId && $('#pm' + checkoutId + ':checked').length && $('#cko-payment-token').val() == '') {ldelim}
        if (CheckoutIntegration){ldelim}
            var transactionValue = CheckoutIntegration.getTransactionValue();
            document.getElementById('cko-payment-token').value = transactionValue.paymentToken;
            $('button.place-order-button').trigger('submit');
                            
    {rdelim}

                $('.being-placed, .blockOverlay, .blockPage').hide();
                return false;
    {rdelim}

            return true;
    {rdelim}
</script>
<script type="text/javascript"> 

        var reload = false;
        window.CKOConfig = {ldelim}
                debugMode: false,
                renderMode: 2,
                namespace: 'CheckoutIntegration',
                publicKey: '{$checkoutapiData.publicKey}',
                paymentToken: '{$checkoutapiData.paymentToken}',
                value: '{$checkoutapiData.amount}',
                currency: '{$checkoutapiData.currency}',
                customerEmail: '{$checkoutapiData.email}',
                customerName: '{$checkoutapiData.name}',
                forceMobileRedirect: true,
                paymentMode: '{$checkoutapiData.paymentMode}',
                cardFormMode: 'cardTokenisation',
                title: '',
                subtitle: 'Please enter your credit card details',
                widgetContainerSelector: '.widget-container',
                paymentTokenExpired: function (event) {ldelim}
                  reload = true;
                {rdelim},
                widgetRendered: function(){ldelim}

                    setTimeout(function(){
                        jQuery('.payment-methods').attr('style','opacity: inherit;');
                    },1000);
                    
                {rdelim}
    {rdelim};
</script>
{if $checkoutapiData.mode eq 'live'}
    {literal}
        <script src="https://cdn.checkout.com/js/checkout.js" async ></script>
    {/literal}
{else}
    {literal}
        <script src="https://cdn.checkout.com/sandbox/js/checkout.js" async ></script>
    {/literal}
{/if}