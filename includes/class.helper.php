<?php
use SureCart\Support\Currency;
use SureCart\Models\Product;
use SureCart\Models\ProductMedia;
class ESHB_Surecart_Helper {

    public function __construct(){

    }

    public static function get_or_create_surecart_product($accomodation_id, $thumbnail_id) {
        $product_id = get_post_meta($accomodation_id, '_surecart_product_id', true);
        $product_existing = false;

        if (!empty($product_id)) {
            $product = Product::find($product_id);
            $product = json_decode(json_encode($product), true);
            $product_existing = !empty($product['post']);
        }

        if (!$product_existing) {
            $image_url = wp_get_attachment_url($thumbnail_id);

            $product = Product::create([
                'name' => get_the_title($accomodation_id),
                'description' => get_the_title($accomodation_id),
                'imageUrl' => $image_url,
                'status' => 'published',
                'shipping_enabled' => false,
                'auto_fulfill_enabled' => true
            ]);

            $product_id = $product->id;

            $productMedia = new ProductMedia([
                'url' => $image_url,
                'product' => $product_id,
            ]);
            $productMedia->save();

            update_post_meta($accomodation_id, '_surecart_product_id', $product_id);
        }

        return $product_id;
    }


    public static function get_user_preferred_surecart_currency(){
        if(!class_exists('SureCart\Support\Currency')) return 'usd';
        $currency = Currency::getCurrentCurrency();
        if(isset($_COOKIE['sc_current_currency']) && !empty($_COOKIE['sc_current_currency'])){
            $currency = $_COOKIE['sc_current_currency'];
        }
        if(isset($_REQUEST['currency']) && !empty($_REQUEST['currency'])){
            $currency = $_REQUEST['currency'];
        }
        return $currency;
    }

    public static function get_surecart_currency_rate(){
        if(!class_exists('SureCart\Support\Currency')) return 1;
        $currency = self::get_user_preferred_surecart_currency();
        $currency_rate = Currency::getExchangeRate($currency);
        return $currency_rate;
    }
}