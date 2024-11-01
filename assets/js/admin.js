jQuery(document).ready(function () {
    jQuery(document).on('click', '#pbx-tabs a', function (e) {
        jQuery('#pbx-plugin-configuration .tab-active').removeClass('tab-active');
        jQuery(jQuery(this).attr('href')).addClass('tab-active');
        jQuery('#pbx-tabs a.nav-tab-active').removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active');
        jQuery('#mainform').attr('action', jQuery(this).attr('href'));
        e.preventDefault();
    });
    // Auto-select nav tab
    if (typeof (window.location.hash) == 'string') {
        jQuery('.nav-tab[href="' + window.location.hash + '"]').trigger('click');
        jQuery('#mainform').attr('action', window.location.hash);
    }

    // Ask for confirmation
    jQuery(document).on('change', '#woocommerce_' + pbxGatewayId + '_environment', function () {
        if (confirm(pbxConfigModeMessage)) {
            jQuery('.woocommerce-save-button').trigger('click');
        }
    });

    // Ask for confirmation
    jQuery(document).on('change', '[name="typeOfConfiguration"]', function () {
        window.onbeforeunload = null;
        window.location = pbxUrl + '&config_mode=' + this.value;
    });
});
