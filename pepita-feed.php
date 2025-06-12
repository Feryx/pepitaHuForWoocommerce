<?php
/*MIT License

Copyright (c) 2025 Feryx

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software to use, copy, modify, merge, publish, and distribute it,
but not to sell it, in whole or in part, without explicit permission.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.*/
//the one who makes you tear your hair out: Feryx
//;) curl -s https://valami.hu/pepita-feed.php -o /path/to/feed.xml
require_once('wp-load.php');
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
echo '<?xml version="1.0" encoding="UTF-8"?>';
$t = time();
?>
<Catalog xmlns="https://pepita.hu/feed/1.0" created_at="<?= date('Y_m_d__H_i_s', $t); ?>">
    <?php
    /*all products to xml*/
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
	/*selection like only 'AAAma'*/
	/*$args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => 'AAAma',
            ),
        ),
    );*/
    $loop = new WP_Query($args);

    while ($loop->have_posts()):
        $loop->the_post();
        global $product;
        $product = wc_get_product(get_the_ID());
        $id = get_the_ID();
        $title = get_the_title();
        $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $description = strip_tags($product->get_short_description() . "\n\n" . $product->get_description());
        $description = str_replace('&nbsp;', ' ', $description);
        $description = htmlspecialchars($description, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $shortDescription = strip_tags($product->get_description());
        $shortDescription = str_replace('&nbsp;', ' ', $shortDescription);
        $shortDescription = htmlspecialchars($shortDescription, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $link = get_permalink();
        $image = wp_get_attachment_url($product->get_image_id());
        $price = floor($product->get_price());//$product->get_price();
        $currency = 'HUF';//$product->get_currency();//if you use multiple currency
        $availability = $product->is_in_stock() ? 'true' : 'false';//pepita.hu use true/false not yep/nope etc..
        $stock_quantity = $product->get_stock_quantity();
        $sku = $product->get_sku();
        $main_image = wp_get_attachment_url($product->get_image_id());
        $gallery_images = $product->get_gallery_image_ids();
        $gtin = get_post_meta($id, '_wpm_gtin_code', true); 
        $mfg_part = get_post_meta($id, '_manufacturer_part_number', true);
        $youtube = get_post_meta($id, '_youtube_url', true);
        $attachments = get_post_meta($id, '_attachments_url', true); // PDF
        $brand = wp_get_post_terms($id, 'product_brand', array('fields' => 'names'));
        $brand_name = !empty($brand) ? $brand[0] : '';
        $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
        $first_category = !empty($categories) ? $categories[0] : '';
		?>
        <Product>
            <id><?= $id ?></id>
            <Descriptions>
                <Name>
                    <![CDATA[ <?= $title ?> ]]>
                </Name>
                <Description>
                    <![CDATA[ <?= $description ?> ]]>
                </Description>
            </Descriptions>
            <Prices>
                <Currency><?= $currency ?></Currency>
                <Price><?= $price ?></Price>
            </Prices>
            <Categories>
                <Category><?= $first_category ?></Category>
                <Name><?= $first_category ?></Name>
            </Categories>
            <Categories>
                <Brand><?= $brand_name ?></Brand>
            </Categories>
            <ProductUrl>
                <![CDATA[ <?= $link ?> ]]>
            </ProductUrl>
            <Availability>
                <Available><?= $availability ?></Available>
                <Quantity><?= $stock_quantity !== null ? $stock_quantity : 0 ?></Quantity>
            </Availability>
            <Attributes>
                <?php foreach ($product->get_attributes() as $attribute):
                    $attr_name = wc_attribute_label($attribute->get_name());
                    $attr_value = implode(', ', wc_get_product_terms($id, $attribute->get_name(), array('fields' => 'names')));
                    ?>
                    <attribute>
                        <AttributeName>
                            <![CDATA[ <?= $attr_name ?> ]]>
                        </AttributeName>
                        <AttributeValue>
                            <![CDATA[ <?= $attr_value ?> ]]>
                        </AttributeValue>
                    </attribute>
                <?php endforeach; ?>
                <attribute>
                    <AttributeName>Rövid leírás</AttributeName>
                    <AttributeValue>
                        <![CDATA[ <?= $shortDescription ?> ]]>
                    </AttributeValue>
                </attribute>
            </Attributes>
            <Photos>
                <?php if ($main_image): ?>
                    <Photo>
                        <Url><?= esc_url($main_image) ?></Url>
                        <IsPrimary>true</IsPrimary>
                    </Photo>
                <?php endif; ?>

                <?php foreach ($gallery_images as $img_id):
                    $img_url = wp_get_attachment_url($img_id);
                    if (!$img_url)
                        continue;
                    ?>
                    <Photo>
                        <Url><?= esc_url($img_url) ?></Url>
                        <IsPrimary>false</IsPrimary>
                    </Photo>
                <?php endforeach; ?>
            </Photos>
            <Sku><?= $sku ?></Sku>
            <Gtin><?= $gtin ?: '' ?></Gtin>
            <?php if ($mfg_part): ?>
                <Manufacturer_part_number><?= $mfg_part ?></Manufacturer_part_number><?php endif; ?>
            <?php if ($youtube): ?>
                <Youtube><?= $youtube ?></Youtube><?php endif; ?>
            <?php if ($attachments): ?>
                <Attachments><?= $attachments ?></Attachments><?php endif; ?>
        </Product>
        <?php
    endwhile;
    wp_reset_postdata();
    ?>
</Catalog>