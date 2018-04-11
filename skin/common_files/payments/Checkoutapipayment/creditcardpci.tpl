<link rel="stylesheet" type="text/css" href="{$SkinDir}/css/checkoutapi.css" />

<ul>
    <li>
        {capture name=regfield}
            <input type="text" id="card_name" name="card_name" size="32" maxlength="128" value="{$module_data.param02|escape}" />
        {/capture}
        {include file="modules/One_Page_Checkout/opc_form_field.tpl" content=$smarty.capture.regfield required="Y" name="Name on card" field="card_name"}
    </li>

    <li >
        {capture name=regfield}
            <input type="text" id="card_no" name="card_no" size="32" maxlength="32" value="" />
        {/capture}
        {include file="modules/One_Page_Checkout/opc_form_field.tpl" content=$smarty.capture.regfield required="Y" name="Card Number" field="card_no"}
    </li>
    <li>
        {capture name=regfield}
            {html_select_date
            time='--'
            prefix='card_expdate_'
            start_year='-0'
            end_year='+15'
            display_days=false
            month_format='%m'}
        {/capture}
        {include file="modules/One_Page_Checkout/opc_form_field.tpl" content=$smarty.capture.regfield required="Y" name="Expiry Date" field="card_expdate_Month"}
    </li>
    <li>
        {capture name=regfield}
            <input type="text" id="card_cvv" name="card_cvv" size="4" maxlength="4" value="" style="width:15%" />
        {/capture}
        {include file="modules/One_Page_Checkout/opc_form_field.tpl" content=$smarty.capture.regfield required="Y" name="CVV" field="card_cvv"}
    </li>


</ul>