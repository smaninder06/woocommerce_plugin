<?php
   /*
   Plugin Name: Woocommerce Order Again
   Plugin URI: https://www.instagram.com/smaninder_kamboj05/
   description: This plugin is used to reorder the order by changing the quantity and remove the extra products.
   Version: 1.0.0
   Author: Maninder Singh
   Author URI: https://www.instagram.com/smaninder_kamboj05/
   */

class Woocommerce_order_again {
    public function __construct(){
        define('WP_ORDER_PATH', plugins_url('', __FILE__ ));
        wp_register_style('wc_order_style', WP_ORDER_PATH.'/assets/css/style.css', false, time(), 'all');
        wp_enqueue_style('wc_order_style');
        add_action('wp_head', array($this, 'add_ajax_url_for_frontend'));
        add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'mani_add_btn_order_page'), 10, 2 );
        add_action("wp_ajax_get_product_by_order_id_reorder", array($this, "get_product_by_order_id_reorder"));
        add_action("wp_ajax_nopriv_get_product_by_order_id_reorder", array($this, "get_product_by_order_id_reorder"));
        add_action("wp_ajax_add_to_cart_custom", array($this, "add_to_cart_custom"));
        add_action("wp_ajax_nopriv_add_to_cart_custom", array($this, "add_to_cart_custom"));
        add_action('wp_footer', array($this, 'add_ajax_order_btn'));
    }

    function add_ajax_url_for_frontend(){
        ?>
        <script type="text/javascript">
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        </script>
        <?php
    }

    function mani_add_btn_order_page( $actions, $order ) {
        $actions['repeat-order-again'] = array(
            'url' => '#'.$order->get_id(),
            'name' => 'Repeat Order',
        );
        return $actions;
    }

    function get_product_by_order_id_reorder(){
        extract($_POST);
        $order_id = str_replace("#","",$order_id);
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        $data = '<form name="reorder-products" id="reorder-form" method="post">
                    <table id="reorder-table" class="reorder-main-block">
                        <tr class="reorder-main-header">
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>';
        foreach ( $items as $item ) {
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            $data .= '<tr class="reorder-inner-product">
                        <td class="reorder-img"><img src="'.wp_get_attachment_url( $product->get_image_id() ).'" /></td>
                        <td>'.$product->get_title().'</td>
                        <td>'.$product->get_price_html().'</td>
                        <td> <input type="hidden" name="product_id[]" value="'.$product->get_id().'">
                        <input type="number" min="1" name="quantity[]" id="'.$product->get_image_id().'" class="qty-tab" price="'.$product->get_price().'" value="'.$quantity.'"></td>
                        <td id="total-price-'.$product->get_image_id().'">'.$quantity*$product->get_price().'</td>
                        <td class="remove-product"> X </td>
                    </tr>';
        }
        $data .='</table>
        <input type="hidden" name="action" value="add_to_cart_custom">
        <input id="reorder-proceed" type="submit" name="submit_reorder" value="Proceed"> </form>';
        echo $data;
        die;
    }

    function add_to_cart_custom(){
        extract($_POST);
        if( ! WC()->cart->is_empty() ){
            WC()->cart->empty_cart();
        }
        foreach ($product_id as $id => $prod_id) {
            WC()->cart->add_to_cart( $prod_id, $quantity[$id]);
        }
        echo wc_get_cart_url();
        die;
    }

    function add_ajax_order_btn(){
        ?>
        <div id="re-order-block" class="modal">
            <div class="modal-content">
                <span id="close-order-modal" class="close">&times;</span>
                <div class="re-order-inner" id="reorder-inner"></div>
            </div>
        </div>
        <script>
            jQuery(document).on('click','.repeat-order-again', function(e){
                e.preventDefault();
                var order_id = jQuery(this).attr('href');
                jQuery.ajax({
                    type : "post",
                    url : ajaxurl,
                    data : {action: "get_product_by_order_id_reorder", order_id : order_id},
                    success: function(response) {
                        jQuery("#reorder-inner").html(response);
                        jQuery("#re-order-block").show();
                    }
                });
            });
            jQuery(document).on('click',"#close-order-modal", function(e){
                jQuery("#re-order-block").hide();
                jQuery("#reorder-inner").html('');
            });
            
            jQuery(document).on("submit", "#reorder-form", function(e){
                e.preventDefault();
                var formdata= jQuery(this).serialize();
                jQuery.ajax({
                    type : "post",
                    url : ajaxurl,
                    data : formdata,
                    success: function(response) {
                        window.location.replace(response);
                        //console.log(response);
                    }
                });
            });

            jQuery(document).on('click', '.remove-product', function(e){
                jQuery(this).parent().remove();
                if (jQuery('#reorder-table tr').length < 2) {
                    jQuery("#reorder-proceed").prop('disabled', true);
                }
            });

            jQuery(document).on('change', '.qty-tab', function(e){
                var qty_id = jQuery(this).attr('id');
                var price = jQuery(this).attr('price');
                var quantity = jQuery(this).val();
                jQuery("#total-price-"+qty_id).html(price*quantity);
            });

        </script>
        <?php
    }
}

$wc_again = new Woocommerce_order_again();


?>