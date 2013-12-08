<?php
include_once("invoice_creator-common.php");

function elog($v){
    error_log(var_export($v, true), 0);
}

function build_url($api_section){
    $c = get_credentials();

    $url_list = array(
        "invoices" => "invoices/invoice_add.php",
    );

    return $c->api_url . $url_list[$api_section];
}

function add_header($xml_body){
    $cred = get_credentials();

    return "<?xml version='1.0' encoding='UTF-8'?>
    <myxml>
        <contract>
            <contractcode>$cred->contract_code</contractcode>
            <username>$cred->username</username>
            <password>$cred->password</password>
            <emailonerror></emailonerror>
            <emailonwarning></emailonwarning>
        </contract>
        $xml_body
        <format></format>
    </myxml>";

    return $xml_body;
}

function get_fullname($order){
    return $order->billing_first_name . " " . $order->billing_last_name;
}

/* generate product lines xml list from array */
function product_lines_xml($product_lines){
    $p_xml = "";
    foreach($product_lines as $p){
        $p_xml .= "<line>";
        $p_xml .= "<itemnumber>". $p['product_id'] ."</itemnumber>";
        $p_xml .= "<product>". $p['description']. "</product>";
        $p_xml .= "<unitprice>". $p['price'] . "</unitprice>";
        $p_xml .= "<quantity>". $p["amount"] ."</quantity>";
        $p_xml .= "<costprice></costprice>"; # not used, see API doc.
        $p_xml .= "</line>";
    }

    return $p_xml;
}

function create_invoice_body($order){
    $cred = get_credentials();

    /* see api documentation */
    $product_lines = generate_productlines($order);

    $product_list_xml = product_lines_xml($product_lines);

    $xml_body = "<customer>
        <type>1</type>
        <fullname>" . get_fullname($order) ." </fullname>
        <address1>$order->billing_address_1</address1>
        <address2>$order->billing_address_2</address2>
        <postalcode>$order->billing_postcode</postalcode>
        <city>$order->city</city>
        <countrycode>". $order->billing_country ."</countrycode>
        <telephone>$order->billing_phone</telephone>
        <email>$order->billing_email</email>
        <invoice>
            <concept>0</concept>
            <issuedate>".date('Y-m-d') ."</issuedate>
            <paymentstatus>2</paymentstatus>
            <paymentdate>" .date('Y-m-d') ."</paymentdate>
            <description>$cred->textinvoice</description>
            $product_list_xml
            <emailaspdf>
                <emailto>$order->billing_email</emailto>
                <emailfrom>" . get_bloginfo('admin_email') ." </emailfrom>
                <subject>Bevestiging aankoop ". $order->get_order_number() ."</subject>
                <message>Bevestiging aankoop ". $order->get_order_number() ."</message>
            </emailaspdf>
        </invoice>
    </customer>";

    return $xml_body;
}

function send_msg($xml_string, $url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "xmlstring=$xml_string");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function send_invoice($order_id){
    if(!$order_id){
        return;
    }

    $order = new WC_Order($order_id);

    try {
        /* get client nmr */
        $invoice = create_invoice_body($order);
        $msg = add_header($invoice);

        // file_put_contents(dirname(__FILE__). "/xmlfile.xml", formatXmlString($msg));

        /* create request object */
        $url = build_url('invoices');
        send_msg($msg, $url);
        #$request = set_connection("invoices", "POST");
    } catch(Exception $e) {
        error_log('Caught Exception: ' . $e->getMessage(), 0);
    }
}

#add_action('woocommerce_order_status_completed', 'send_invoice');
add_action('wp_head', 'send_invoice');
?>
