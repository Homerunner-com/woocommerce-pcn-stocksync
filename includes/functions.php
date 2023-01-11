<?php

// Action to update quantity when a new customer hits "Thank you" page
add_action('woocommerce_thankyou', 'pcn_stock_sync_on_order', 11, 1);
function pcn_stock_sync_on_order( $order_id ) {
    if(get_option('pcn_settings_updateonorder') == 'yes') {
        // Get order and order items
        $order = wc_get_order($order_id);
        $order_items = $order->get_items();

        // Get stocklist by performing cURL to PCN
        $curl = new PCNStockSync_Curl();
        $stockList = $curl->getStockList();

        foreach ($order_items as $order_item) {
            $product = wc_get_product($order_item->get_data()['product_id']);

            foreach ($stockList->results as $stockItem) {
                // Check if product with same SKU exists at PCN
                if ($stockItem->articleno == $product->get_sku()) {
                    // Check if stock quantity isn't the same in WooCommerce as stock at PCN
                    if ($stockItem->instock != $product->get_stock_quantity()) {
                        // Set quantity and save product if amount isn't same as PCN
                        $product->set_stock_quantity($stockItem->instock);
                        $product->save();
                    }
                }
            }
        }
    }
}



add_action( 'wp_ajax_pcn_stock_sync_updateajax', 'pcn_stock_sync_updateajax' );
function pcn_stock_sync_updateajax() {
    $product_id = $_POST['id'];

    // Get stocklist by performing cURL to PCN
    $curl = new PCNStockSync_Curl();
    $stockList = $curl->getStockList();

    $product = wc_get_product( $product_id );
    $productFound = 0;
    $toPrint = "noChange";

    foreach ($stockList->results as $stockItem) {
        // Check if product with same SKU exists at PCN
        if($stockItem->articleno == $product->get_sku()) {
            // Check if stock quantity isn't the same in WooCommerce as stock at PCN
            if($stockItem->instock != $product->get_stock_quantity()) {
                // Set quantity and save product if amount isn't same as PCN
                $product->set_stock_quantity($stockItem->instock);
                $product->save();

                $toPrint = $stockItem->instock;
            }
            $productFound = 1;
        }
    }

    if($productFound == 0) {
        $toPrint = "notFound";
    }

    error_log('toPrint: ' . $toPrint);

    echo $toPrint;
    wp_die();
}

// Add button to sync stock from PCN
add_action('admin_footer', 'pcn_stock_sync_updatebutton');
function pcn_stock_sync_updatebutton() {
    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            var newButton = $("<div style='width: 100%; border-top: 1px solid #eeeeee; padding: 10px;'><a data-id='<?php echo get_the_ID(); ?>' id='pcn-stocksync-button' class='button button-primary button-large'><?php echo __('Get stock quantity from PakkecenterNord', 'coolrunner-pcn-stocksync'); ?></a></div>");
            jQuery('.stock_fields').append(newButton)
        });

        jQuery(document).ready(function () {
            jQuery("#pcn-stocksync-button").click(function () {
                jQuery(this).removeClass('button-primary').addClass('button-disabled');
                var id = jQuery(this).data('id');
                var data = {
                    'action': 'pcn_stock_sync_updateajax',
                    'id': id
                }

                jQuery.post(ajaxurl, data, function (response) {
                    if(response !== 'noChange' && response !== 'notFound') {
                        jQuery('#_stock').val(response);
                    } else {
                        if(response === 'notFound') {
                            alert('<?php echo __('SKU does not exist at PakkecenterNord', 'coolrunner-pcn-stocksync'); ?>>');
                        } else {
                            alert('<?php echo __('Quantity is already correct', 'pcn-stocksync') ?>');
                        }
                    }

                    jQuery("#pcn-stocksync-button").removeClass('button-disabled').addClass('button-primary');
                })
            });
        });

    </script>
    <?php
}

// Add bulk action to update stock
add_filter( 'bulk_actions-edit-product', 'pcn_stock_sync_addbulkaction', 20, 1 );
function pcn_stock_sync_addbulkaction( $actions ) {
    $actions['update_all_stockquantity'] = __( __('PCN - Update stock quantity', 'coolrunner-pcn-stocksync') );
    return $actions;
}

// Handle bulk action
add_filter( 'handle_bulk_actions-edit-product', 'pcn_stock_sync_updatebulkaction', 10, 3 );
function pcn_stock_sync_updatebulkaction( $redirect_to, $doaction, $post_ids ) {

    // Get stocklist by performing cURL to PCN
    $curl = new PCNStockSync_Curl();
    $stockList = $curl->getStockList();
    $countChanged = 0;

    foreach ($post_ids as $post_id) {
        $product = wc_get_product( $post_id );
        $productFound = 0;
        $toPrint = "noChange";

        foreach ($stockList->results as $stockItem) {
            // Check if product with same SKU exists at PCN
            if($stockItem->articleno == $product->get_sku()) {
                // Check if stock quantity isn't the same in WooCommerce as stock at PCN
                if($stockItem->instock != $product->get_stock_quantity()) {
                    // Set quantity and save product if amount isn't same as PCN
                    $product->set_stock_quantity($stockItem->instock);
                    $product->save();

                    $toPrint = $stockItem->instock;
                }
                $productFound = 1;
            }
        }

        if($productFound == 0) {
            $toPrint = "notFound";
        }

        if($toPrint != 'notFound' AND $toPrint != 'noChange') {
            $countChanged++;
        }
    }

    return admin_url() . 'edit.php?post_type=product';
}

// Create cron function
add_action('wp_scheduled_pcn_stocksync', 'pcn_stocksync_cron');
function pcn_stocksync_cron() {
    if(get_option('pcn_settings_updatecron') == 'yes') {
        // Get stocklist by performing cURL to PCN
        $curl = new PCNStockSync_Curl();
        $stockList = $curl->getStockList();

        // Pagination
        $args = array(
            'post_type' => array('product', 'product_variation'),
            'posts_per_page' => 100
        );

        $loop = new WP_Query($args);

        while ($loop->have_posts()): $loop->the_post();
            global $product;
            foreach ($stockList->results as $pcnProduct) {
                if ($pcnProduct->articleno == $product->get_sku()) {
                    $newStock = ($pcnProduct->instock - $pcnProduct->onorder);
                    if ($product->get_stock_quantity() != $newStock) {
                        $product->set_stock_quantity($newStock);
                        $product->save();
                    }
                }
            }
        endwhile;

        // empty the query
        wp_reset_query();
    }
}

// Register cron intervals
add_filter('cron_schedules', 'pcn_stocksync_cron_intervals');
function pcn_stocksync_cron_intervals($schedules) {
    $schedules['every3hours'] = array(
        'interval' => 10800, // 10800 seconds = 3 hours
        'display' => __('Every 3 hours', 'coolrunner-pcn-stocksync')
    );

    return $schedules;
}

// Handle activation of plugin and create event
register_activation_hook(PLUGIN_FILE_URL, 'pcn_stocksync_activate');
function pcn_stocksync_activate() {
    wp_schedule_event(time(), 'every3hours', 'wp_scheduled_pcn_stocksync');
}

// Handle deactivation of plugin and remove event
register_deactivation_hook(PLUGIN_FILE_URL, 'pcn_stocksync_deactivate');
function pcn_stocksync_deactivate() {
    wp_clear_scheduled_hook('wp_scheduled_pcn_stocksync');
}