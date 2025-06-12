<?=
/**MIT License

Copyright (c) 2025 Feryx

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software to use, copy, modify, merge, publish, and distribute it,
but not to sell it, in whole or in part, without explicit permission.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.*/


////////****************
//Add to wp-config.php!!!!4!4!!!
//define('PEPITA_API_KEY', 'Long code like 32476ZGKUG87gth8zugIGBIUGH7g8gGU98632476ZGKUG87gth8zugIGBIUGH7g8gGU986');
//
//That what you need tro add pepita team after add the code for function.php:  https://YOURSITE.hu/wp-json/pepita/v1/order/store?apikey=YOURAPIKEY
//apikey: YOURAPIKEY
////////*******************////////
//add the code for wp-content/themes/yourthemename_child/function.php file end or make the child template.

//Add GINT number for the Admin/product/editor.  GTIN mező hozzáadása a termék admin szerkesztőfelülethez
add_action('woocommerce_product_options_general_product_data', 'add_gtin_custom_field');
function add_gtin_custom_field()
{
    woocommerce_wp_text_input(array(
        'id' => '_wpm_gtin_code',
        'label' => __('GTIN (EAN)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Globális termékazonosító (pl. EAN, UPC). KÉRI A PEPITA FEED', 'woocommerce'),
        'type' => 'text',
    ));
}

//Save GTIN, mező mentése
add_action('woocommerce_process_product_meta', 'save_gtin_custom_field');
function save_gtin_custom_field($post_id)
{
    if (isset($_POST['_wpm_gtin_code'])) {
        update_post_meta($post_id, '_wpm_gtin_code', sanitize_text_field($_POST['_wpm_gtin_code']));
    }
}

/***Pepita Rest API endpoint****************************************/
// 1. REST API registration, regisztrálása
add_action('rest_api_init', function () {
    register_rest_route('pepita/v1', '/order/store', [
        'methods' => 'POST',
        'callback' => 'handle_pepita_order',
        'permission_callback' => '__return_true'
    ]);
});

// 2. API endpoint logic, végpont logika
function handle_pepita_order(WP_REST_Request $request)
{
    $apikey = $request->get_param('apikey');
    if ($apikey !== PEPITA_API_KEY) {
        return new WP_REST_Response([
            'isError' => true,
            'responseCode' => 403,
            'message' => 'Érvénytelen API kulcs'
        ], 403);
    }

    $data = $request->get_json_params();

    // Log to file, Logolás fájlba  //after test and it's work comment out! Tesztelés után ha minden ok kommenteld ki!
	/*
    $log_path = __DIR__ . '/pepita_log_all.json';
    $log_entry = date('Y-m-d H:i:s') . " - " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($log_path, $log_entry, FILE_APPEND);

    if (!$data) {
        return new WP_REST_Response([
            'isError' => true,
            'responseCode' => 400,
            'message' => 'Hiányzó vagy érvénytelen JSON'
        ], 400);
    }

    try {
        $order_id = pepita_create_wc_order($data);
        return new WP_REST_Response([
            'isError' => false,
            'responseCode' => 200,
            'messages' => []
        ], 200);
    } catch (Exception $e) {
        return new WP_REST_Response([
            'isError' => true,
            'responseCode' => 500,
            'message' => 'Hiba: ' . $e->getMessage()
        ], 500);
    }*/
}

// 3. Create order, Rendelés létrehozása
function pepita_create_wc_order($data)
{
    $order = wc_create_order();

    foreach ($data['products'] as $item) {
        $product_id = wc_get_product_id_by_sku($item['sku']);
        if (!$product_id)
            continue;

        $order->add_product(wc_get_product($product_id), $item['quantity']);
    }

    $order->set_address([
        'first_name' => $data['customer']['first_name'],
        'last_name' => $data['customer']['last_name'],
        'email' => $data['customer']['email'],
        'phone' => $data['customer']['phone'],
        'address_1' => $data['customer']['shipping_street_address'] ?? '',
        'address_2' => $data['customer']['shipping_house_number'] ?? '',
        'postcode' => $data['customer']['shipping_postal_code'] ?? '',
        'city' => $data['customer']['shipping_city'] ?? '',
        'country' => $data['customer']['shipping_country'] ?? ''
    ], 'billing');

    $order->set_payment_method($data['payment_mode'] ?? 'cod');
    $order->calculate_totals();
    $order->update_status('okosugyvitel', 'Pepita rendelés');
    $order->update_meta_data('_order_attribution_origin', 'pepita');
    $order->update_meta_data('_order_attribution_source_type', 'api');
    $order->update_meta_data('_order_attribution_utm_source', 'pepita');
    $order->update_meta_data('_order_attribution_utm_medium', 'integration');
    $order->update_meta_data('_order_attribution_utm_campaign', 'pepita_marketplace');
    $order->update_meta_data('_order_attribution_device_type', 'unknown');
    $order->update_meta_data('_pepita_order', '1');
    $order->save();

    return $order->get_id();
}

//When ordering, please specify the new source. rendelésnél írjuk az uj forrást
add_action('add_meta_boxes', function () {
    add_meta_box(
        'pepita_source_box',
        'Rendelés forrás (külső)',
        'render_pepita_source_box',
        'shop_order',
        'side',
        'core'
    );
});

function render_pepita_source_box($post)
{
    $is_pepita = get_post_meta($post->ID, '_pepita_order', true);
    if ($is_pepita) {
        echo '<p><strong>Ez egy Pepita rendelés</strong></p>';
    } else {
        echo '<p>Nem Pepita rendelés.</p>';
    }
}
//also indicated on the orders page.
// Add a new column to the order list.
//rendelések oldalon is jelezzük.
// Új oszlop hozzáadása a rendeléslistához
add_filter('manage_edit-shop_order_columns', 'add_pepita_order_column');
function add_pepita_order_column($columns)
{
    $new_columns = [];

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        // after the 'order_status'
        if ($key === 'order_status') {
            $new_columns['pepita_source'] = 'Forrás';
        }
    }

    return $new_columns;
}

//Print column content, Oszlop tartalom kiírása
add_action('manage_shop_order_posts_custom_column', 'render_pepita_order_column', 10, 2);
function render_pepita_order_column($column, $post_id)
{
    if ($column === 'pepita_source') {
        $is_pepita = get_post_meta($post_id, '_pepita_order', true);
        if ($is_pepita) {
            echo '<span class="badge" style="color: white; background: #ce161f; padding: 3px 7px; border-radius: 3px;">Pepita</span>';
        } else {
            echo '<span style="color:white; background:#4282a4; padding: 3px 7px; border-radius: 3px;">Lifee</span>';
        }
    }
}
/***Pepita Rest API endpoint END****************************************/