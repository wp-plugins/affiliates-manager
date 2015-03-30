<?php

function wpam_display_addons_menu()
{
    echo '<div class="wrap">';
    echo '<h2>' .__('Affiliates Manager Add-ons', 'wpam') . '</h2>';
    echo '<link type="text/css" rel="stylesheet" href="' . WPAM_URL . '/style/wpam-addons-listing.css" />' . "\n";
    
    $addons_data = array();

    $addon_1 = array(
        'name' => 'MailChimp Integration',
        'thumbnail' => WPAM_URL . '/images/addons/mailchimp-integration.png',
        'description' => 'Allows you to signup the affiliates to your MailChimp list after registration',
        'page_url' => 'https://wpaffiliatemanager.com/signup-affiliates-mailchimp-list/',
    );
    array_push($addons_data, $addon_1);

    $addon_2 = array(
        'name' => 'Google reCAPTCHA',
        'thumbnail' => WPAM_URL . '/images/addons/google-recaptcha.png',
        'description' => 'Allows you to add Google recaptcha to your affiliate signup page. Helps prevent spam signup.',
        'page_url' => 'https://wpaffiliatemanager.com/affiliates-manager-google-recaptcha-integration/',
    );
    array_push($addons_data, $addon_2);

    $addon_3 = array(
        'name' => 'Mailpoet Newsletter',
        'thumbnail' => WPAM_URL . '/images/addons/mailpoet-integration.jpg',
        'description' => 'You can automatically sign up your affiliates to a specific MailPoet newsletter list.',
        'page_url' => 'https://wpaffiliatemanager.com/sign-affiliates-to-mailpoet-list/',
    );
    array_push($addons_data, $addon_3);

    $addon_4 = array(
        'name' => 'Infusionsoft Integration',
        'thumbnail' => WPAM_URL . '/images/addons/infusionsoft-integration.png',
        'description' => 'Automatically signup your affiliates to a specific tag in Your Infusionsoft account.',
        'page_url' => 'https://wpaffiliatemanager.com/infusionsoft-affiliates-manager-plugin-integration/',
    );
    array_push($addons_data, $addon_4);

    $addon_5 = array(
        'name' => 'PMPRO Integration',
        'thumbnail' => WPAM_URL . '/images/addons/pmpro-integration.png',
        'description' => 'Integrates with paid membership pro plugin so you can reward affiliates for sending members.',
        'page_url' => 'https://wpaffiliatemanager.com/affiliates-manager-paid-memberships-pro-integration/',
    );
    array_push($addons_data, $addon_5);
    
    $addon_6 = array(
        'name' => 'Sell Digital Downloads',
        'thumbnail' => WPAM_URL . '/images/addons/sdd-plugin-integraton.png',
        'description' => 'Integrates with sell digital downloads plugin so you can reward affiliates for sending customers.',
        'page_url' => 'https://wpaffiliatemanager.com/affiliates-manager-sell-digital-downloads-integration/',
    );
    array_push($addons_data, $addon_6);
    
    $addon_7 = array(
        'name' => 'S2member Integration',
        'thumbnail' => WPAM_URL . '/images/addons/s2member-integration.png',
        'description' => 'Integrates with s2member plugin so you can reward affiliates for sending members.',
        'page_url' => 'https://wpaffiliatemanager.com/affiliates-manager-s2member-integration/',
    );
    array_push($addons_data, $addon_7);
    
    $addon_8 = array(
        'name' => 'WooCommerce Affiliates',
        'thumbnail' => WPAM_URL . '/images/addons/woo-affiliates.png',
        'description' => 'Automatically create affiliate accounts for your WooCommerce customers.',
        'page_url' => 'https://wpaffiliatemanager.com/automatically-create-affiliate-account-woocommerce-customers/',
    );
    array_push($addons_data, $addon_8);
    
    $addon_9 = array(
        'name' => 'Woo Subscription',
        'thumbnail' => WPAM_URL . '/images/addons/woo-subscriptions.png',
        'description' => 'Integrate with the subscription addon so you can award affiliate commissions for recurring payments.',
        'page_url' => 'https://wpaffiliatemanager.com/affiliates-manager-woocommerce-subscription-integration/',
    );
    array_push($addons_data, $addon_9);
    
    $addon_10 = array(
        'name' => 'WooCommerce Coupons',
        'thumbnail' => WPAM_URL . '/images/addons/woo-coupons.png',
        'description' => 'Track affiliate commission via coupons/discount codes configured in your WooCommerce plugin',
        'page_url' => 'https://wpaffiliatemanager.com/tracking-affiliate-commission-using-woocommerce-coupons-or-discount-codes/',
    );
    array_push($addons_data, $addon_10);
    
    
    //Display the list
    foreach ($addons_data as $addon) {
        $output .= '<div class="wpam_addon_item_canvas">';

        $output .= '<div class="wpam_addon_item_thumb">';
        $img_src = $addon['thumbnail'];
        $output .= '<img src="' . $img_src . '" alt="' . $addon['name'] . '">';
        $output .= '</div>'; //end thumbnail

        $output .='<div class="wpam_addon_item_body">';
        $output .='<div class="wpam_addon_item_name">';
        $output .= '<a href="' . $addon['page_url'] . '" target="_blank">' . $addon['name'] . '</a>';
        $output .='</div>'; //end name

        $output .='<div class="wpam_addon_item_description">';
        $output .= $addon['description'];
        $output .='</div>'; //end description

        $output .='<div class="wpam_addon_item_details_link">';
        $output .='<a href="'.$addon['page_url'].'" class="wpam_addon_view_details" target="_blank">View Details</a>';
        $output .='</div>'; //end detils link      
        $output .='</div>'; //end body

        $output .= '</div>'; //end canvas
    }
    echo $output;
    
    echo '</div>';//end of wrap
}