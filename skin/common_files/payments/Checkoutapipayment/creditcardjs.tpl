<h4>Please select a Credit / Debit card</h4>
<div class="widget-container"></div>
<div class="content" id="payment">
    <input type="hidden" name="cko-cc-paymenToken" id="cko-cc-paymenToken" value="">
    <input type="hidden" name="cko-cc-redirectUrl" id="cko-cc-redirectUrl" value="">
    <input type="hidden" name="cko-card-token" id="cko-card-token" value="">
</div>
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
            var checkoutId = jQuery('#cko-card-token').parents('.payment-details').attr('id').split('_')[1];
            if (checkoutId && $('#pm' + checkoutId + ':checked').length
                    && $('#cko-card-token').val() == '') {ldelim}
                            if (CheckoutIntegration){ldelim}
                                if(!CheckoutIntegration.isMobile()){ldelim}
                                    CheckoutIntegration.open();
                                {rdelim} else {ldelim}
                                    document.getElementById('cko-cc-redirectUrl').value = CheckoutIntegration.getRedirectionUrl();
                                    var transactionValue = CheckoutIntegration.getTransactionValue();
                                    document.getElementById('cko-cc-paymenToken').value = transactionValue.paymentToken;
                                    $('button.place-order-button').trigger('submit');
                                {rdelim}
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
                paymentMode: 'mixed',
                cardFormMode: 'cardTokenisation',
                title: '',
                subtitle: 'Please enter your credit card details',
                widgetContainerSelector: '.widget-container',
                // cardCharged: function (event) {ldelim}
                //             document.getElementById('cko-cc-paymenToken').value = event.data.paymentToken;
                //             $('button.place-order-button').trigger('submit');

                // {rdelim},
                cardTokenised: function(event){ldelim}

                            if (document.getElementById('cko-card-token').value.length === 0 || document.getElementById('cko-card-token').value != event.data.cardToken){
document.getElementById('cko-card-token').value = event.data.cardToken;
                                $('button.place-order-button').trigger('submit');

                            }
                                
                                

                {rdelim},
                lightboxDeactivated: function (event) {ldelim}

                    if (reload) {ldelim}
                        window.location.reload();
                    {rdelim}
                {rdelim},
                paymentTokenExpired: function (event) {ldelim}
                  reload = true;
                {rdelim},
                invalidLightboxConfig: function (event) {ldelim}
                  reload = true;
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