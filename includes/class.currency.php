<?php
use SureCart\Support\Currency;
class ESHB_Surecart_Currency {

    public function __construct(){
        if(class_exists('SureCart\Support\Currency') && !class_exists('ESHB_CURRENCY_SERVER')){
           add_filter('eshb_price_html_price', array($this, 'apply_custom_currency_rate'), 20, 1);
           add_filter('eshb_service_price', array($this, 'apply_custom_currency_rate'), 20, 1);
           add_filter('eshb_currency_symbol', array($this, 'change_currency_symbol'), 10, 1);
           add_filter('eshb_apply_currency_converter_on_pricing_calculation', array($this, 'apply_currency_converter_on_pricing_calculation'), 10, 2);
        }
    }

    public function apply_custom_currency_rate($price) {

        $user_preferred_currency = ESHB_Surecart_Helper::get_user_preferred_surecart_currency();
        
        if ( empty( $user_preferred_currency ) ) {
            return $price;
        }
        
        $currency_rate = ESHB_Surecart_Helper::get_surecart_currency_rate();
        if ( $currency_rate <= 0 ) {
            return $price;
        }

        if ( is_array( $price ) ) {
            // Regular & Sale Price
            $regular_price = $price['regular_price'];
            $sale_price    = $price['sale_price'];
            
            if ( $regular_price > 0 ) {
                $price['regular_price'] = round( $regular_price * $currency_rate, 2 );
            }

            if ( $sale_price > 0 ) {
                $price['sale_price'] = round( $sale_price * $currency_rate, 2 );
            }

            return $price;
        }

        if ( $price > 0 ) {
            $price = round( $price * $currency_rate, 2);
        }

        return $price;
    }

    public function change_currency_symbol($currency_symbol){
        $user_preferred_currency = ESHB_Surecart_Helper::get_user_preferred_surecart_currency();
        if(!empty($user_preferred_currency)){
            $currency_symbol = Currency::getCurrencySymbol($user_preferred_currency);
        }
        return $currency_symbol;
    }

    public function apply_currency_converter_on_pricing_calculation($amount, $currency_converter) {
        if ($currency_converter) {
            return $this->apply_custom_currency_rate($amount);
        }
        return $amount;
    }
}

new ESHB_Surecart_Currency();