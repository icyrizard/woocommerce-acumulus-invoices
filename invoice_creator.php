<?php

/* Plugin Name: woocommerce-acumulus-invoices
* Plugin URI:
* Description: Sends invoice to acumulus.nl
* Version: 1.0
* Author: Richard Torenvliet
* Author URI: http://www.sponiza.nl
* License: GPLv2 or later
*/

/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    include_once('acumulus-functions.php');

    function invoice_creator_activation() {
        include_once("settings.php");
        include_once('invoice_creator-hooks.php');
        /* settings file */
        global $wpdb, $API_NAME, $TABLE_NAME, $TABLE_FIELDS;

        /* hook to wp_admin settings */
        add_action('admin_notices', 'invoice_creator_admin_notices');

        /* included to set activation admin notice */
        $table_name = $wpdb->prefix . $TABLE_NAME;
        $fields = "";

        /* get fields from settings */
        foreach($TABLE_FIELDS as $name => $additional){
            $fields .= $name ." ". $additional . ',';
        }

        /* create settings table */
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            $fields
            exclude_custom_fields VARCHAR(64) DEFAULT '',
            textinvoice VARCHAR(128) DEFAULT 'Thanks for purchasing, this is your invoice' NOT NULL,
            UNIQUE KEY id (id)
        );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        /* insert one row */
        if (!$wpdb->get_var( "SELECT COUNT(*) FROM $table_name"))
            $rows_affected = $wpdb->insert($table_name, array('exclude_custom_fields' => ''));
    }

    function invoice_creator_deactivation() {
        global $wpdb, $TABLE_NAME;

        $table = $wpdb->prefix. $TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    register_activation_hook(__FILE__, 'invoice_creator_activation');
    register_uninstall_hook(__FILE__, 'invoice_creator_deactivation');
    //register_deactivation_hook( __FILE__, 'invoice_creator_deactivation' );
}

?>
