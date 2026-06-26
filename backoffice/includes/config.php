<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */


 $admin_menu = array(
    array(
        "menu_title"=>"Dashboard",
        "menu_section"=>"dashboard",
        "menu_url"=>"backoffice/dashboard.php"
    ),
     array(
         "menu_title"=>"Payments",
         "menu_section"=>"payments",
         "menu_url"=>"backoffice/payments"
     ),
     array(
         "menu_title"=>"Subscriptions",
         "menu_section"=>"subscriptions",
         "menu_url"=>"backoffice/subscriptions"
     ),
     array(
         "menu_title"=>"Invoices",
         "menu_section"=>"invoices",
         "menu_url"=>"backoffice/invoices"
     ),
     array(
         "menu_title"=>"Customers",
         "menu_section"=>"customers",
         "menu_url"=>"backoffice/customers"
     ),
     array(
         "menu_title"=>"Items",
         "menu_section"=>"items",
         "menu_url"=>"backoffice/items"
     ),
     array(
         "menu_title"=>"Settings",
         "menu_section"=>"settings",
         "menu_url"=>"backoffice/settings"
     )


 );

$AVAILABLE_PLUGINS = array(
    array(
        'plugin_name' =>  'Customer Portal',
        'plugin_description' =>  'This plugin is designed to work with subscriptions only. Adds functionality for customers to create accounts, view their subscriptions, and upgrade/downgrade/cancel them, as well as the ability to download an invoice for each subscription payment. Also adds a new section in admin where you can manage customer accounts.',
        'plugin_version' =>  '1.0.0',
        'name' => 'customer_portal',
        'buy_link' => 'https://tinyurl.com/spt-addon-1',
        'plugin_link' => 'https://www.criticalgears.io/product/stripe-payment-terminal-customer-portal/'
    ),
    array(
        'plugin_name' =>  'Multi User',
        'plugin_description' =>  'This plugin adds multi user functionality to the Stripe Payment Terminal. Allows to create admins with various permissions and adds admin activity log tracking.',
        'plugin_version' =>  '1.0.0',
        'name' => 'multi_user',
        'buy_link' => 'https://tinyurl.com/spt-addon-2',
        'plugin_link' => 'https://www.criticalgears.io/product/stripe-payment-terminal-multi-user/'
    ),
);
