<?php

include_once('settings.php');
add_action('admin_menu', 'invoice_creator');
add_action('admin_post_invoice', 'process_invoice_options' );

function process_invoice_options(){
    global $wpdb, $TABLE_FIELDS, $API_NAME, $TABLE_NAME;

    if ( !current_user_can( 'manage_options' ) ){
        wp_die( 'You are not allowed to be on this page.' );
    }

    /* supported fields, default fields */
    $options = array(
        "textinvoice" => '',
        "exclude_custom_fields" => '',
    );

    /* load fields from settings table */
    foreach($TABLE_FIELDS as $name => $additional){
        $options[$name] = '';
    }

    elog("POST!");
    elog($_POST);
    /* gather information from the post data and sanatize */
    foreach($options as $k => $v){
        if (isset ($_POST[$k])){
            $options[$k] = sanitize_text_field($_POST[$k]);
        } else {
            unset($options[$k]);
        }
    }
    elog($options);

    /* update url */
    $wpdb->update($wpdb->prefix . $TABLE_NAME, $options, array("api_name" => $API_NAME));

    wp_redirect($_SERVER['HTTP_REFERER']);
}

function invoice_creator(){
    global $API_NAME;

    $hook_suffix = add_options_page("$API_NAME Options", "$API_NAME Settings",
        'manage_options', 'invoice_creator-plugin-page',
        'invoice_creator_options' );

    /* Use the hook suffix to compose the hook and register an action executed
     * when plugin's options page is loaded */
    add_action( 'load-' . $hook_suffix , 'invoice_creator_load_function' );
}

function invoice_creator_load_function() {
    /* Current admin page is the options page for our plugin, so do not display
    /* the notice. (remove the action responsible for this)*/
    remove_action( 'admin_notices', 'invoice_creator_admin_notices' );
}

function invoice_creator_admin_notices(){
    echo "<div id='notice' class='updated fade'><p>Acumulus plugin is not configured yet, please do this (add API KEY and personal settings)</p></div>\n";
}

/** Step 3. */
function invoice_creator_options() {
    global $wpdb, $TABLE_FIELDS, $API_NAME, $TABLE_NAME;

    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $fields = ['textinvoice', 'exclude_custom_fields'];

    /* get fields */
    foreach($TABLE_FIELDS as $name => $additional){
        array_push($fields, $name);
    }

    /* comma seperated string as prep for select query */
    $fields = implode(',', $fields);

    $options = $wpdb->get_row( $wpdb->prepare("
        SELECT {$fields}
        FROM {$wpdb->prefix}{$TABLE_NAME}",0)
    );
        echo '<div class="wrap">';
        echo "<h2>$API_NAME plugin settings</h2>";
        echo "<p>Place your credentials obtained from $API_NAME
        the api-version and api-url are already filled in. Change them if needed.</p>";

    echo render_settings($options);
    echo '</div>';
}

?>
