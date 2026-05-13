<?php
use SureCart\Models\Coupon;
use SureCart\Models\Promotion;
class ESHB_Surecart_Coupon {

    public function __construct() {
        // Initialize the save coupon action 
        
    }

    
	public static function create_surecart_coupon($title, $coupon_code, $amount, $discount_type = 'percent', $usage_limit = '', $usage_limit_per_user = '1', $expiry_date = '', $product_id = '', $sc_coupon_id = '') {
        
        // Check if SureCart is active
        if (!class_exists('SureCart')) {
            return 'SureCart is not active.';
        }

        $is_percent = ($discount_type === 'percent');

        $args = [
            'name'                           => $title,
            'duration'                       => 'forever',
            'amount_off'                     => $is_percent ? '' : $amount * 100,
            'percent_off'                    => $is_percent ? $amount : '',
            'max_redemptions'                => $usage_limit,
            'max_redemptions_per_customer'   => $usage_limit_per_user,
            'product_ids'                    => $product_id,
            'redeem_by'                      => $expiry_date,
            'promotions'                     => [
                [
                    'code'           => $coupon_code,
                    'max_redemptions'=> $usage_limit,
                ]
            ],
        ];

        // Check if the coupon already exists
        if (!empty($sc_coupon_id)) {
            // Add existing coupon ID to promotions
            foreach ($args['promotions'] as &$promotion) {
                $promotion['coupon'] = $sc_coupon_id;
            }
            // Delete the coupon using SureCart API
            try {
                $coupon = Coupon::find($sc_coupon_id);
                $coupon = json_decode(json_encode($coupon), true);
                Coupon::delete($sc_coupon_id);
            } catch (Exception $e) {
                // Handle exception if coupon not found
                return;
            }
           
           $coupon = Coupon::create($args);


           return $coupon->id;
        }

        // Create a new coupon
        $coupon = Coupon::create($args);
        return $coupon->id;
    }

}
