<?php
use SureCart\Support\Currency;
use SureCart\Models\Product;
use SureCart\Models\Price;
use SureCart\Models\LineItem;
use SureCart\Models\ProductMedia;
use SureCart\Models\Order;
use SureCart\Models\Checkout;
use SureCart\Models\Customer;
use SureCart\Models\Coupon;
use SureCart\Models\PaymentMethod;
use SureCart\Models\ManualPaymentMethod;
use SureCart\Models\ShippingMethod;

class EHB_SureCart_Filters {
    public function __construct() {
        add_filter('eshb_product_id_for_cart', [$this, 'add_surecart_product_id'], 10, 3);
        add_action('eshb_after_add_to_cart', [$this, 'add_booking_to_cart'], 10, 3);
        add_action('eshb_booking_status_changed', [$this, 'handle_booking_status_change'], 10, 4);
        add_filter('eshb_coupon_product_ids', [$this, 'add_coupon_product_ids'], 10, 2);
        add_action('eshb_before_create_coupon', [$this, 'create_coupon'], 10, 3);
        add_action('eshb_before_delete_coupon', [$this, 'delete_coupon'], 10, 2);
        add_filter('eshb_get_product_id_for_cart', [$this, 'replace_product_id_for_adding_to_cart'], 10, 2 );
        add_filter('eshb_billing_data_booking_view', [$this, 'add_billing_data_to_booking_info_table'], 10, 3 );
        add_filter('eshb_customer_data_in_calendar', [$this, 'add_customer_data_in_calendar'], 10, 3);
    }

    public function add_surecart_product_id($product_id, $accomodation_id, $thumbnail_id) {
        $eshb_settings = get_option('eshb_settings', []);
        $booked_type = !empty($eshb_settings['booking-type']) ? $eshb_settings['booking-type'] : 'woocommerce';

        if($booked_type == 'surecart') {
            return ESHB_Surecart_Helper::get_or_create_surecart_product($accomodation_id, $thumbnail_id);
        }
        return $product_id;
    }

    public function add_booking_to_cart($booking_type, $cart_item_data, $string_booking_success_msg) {
        if ($booking_type == 'surecart') {
            if (class_exists('\SureCart\Models\Product')) {

                $product_id = $cart_item_data['product_id'];
                $total_price = $cart_item_data['total_price'];
                $dates = !empty($cart_item_data['dates']) ? $cart_item_data['dates'] : '';
                $times = !empty($cart_item_data['times']) ? $cart_item_data['times'] : '';
                
                $sc_details_html = esc_html__( 'Date : ', 'easy-hotel' ) . $dates;

                if(!empty($times)) {
                    $sc_details_html .= esc_html__( 'Time : ', 'easy-hotel' ) . $times;
                }
                
                $sc_currency = Currency::getCurrentCurrency();
                if(class_exists('ESHB_CURRENCY_SERVER')){
                    $currency_server = new ESHB_CURRENCY_SERVER();
                    $sc_currency = $currency_server->eshb_currency_get_user_preferred_currency();
                }
                
                $new_price = Price::create([
                            'name' => $sc_details_html,
                            'amount' => $total_price * 100, // $50.00
                            'product' => $product_id,  // UUID format
                            'currency' => $sc_currency
                        ]);
                $price_id = $new_price->id;

      
    
                $line_items = array(
                    array(
                        'price_id' => $price_id,
                        'quantity' => 1,
                    )
                );

                $query_args = array();

                // Manually build line_items query format
                foreach ( $line_items as $index => $item ) {
                    foreach ( $item as $key => $value ) {
                        $query_args["line_items[{$index}][{$key}]"] = $value;
                    }
                }

                $surecart_checkout_url = add_query_arg(
                    $query_args,
                    \SureCart::pages()->url('checkout')
                );

                // Validate inputs
                if (!empty($product_id)) {

                    // save to session
                    $eshb_cart = [];

                    $session = new ESHB_Session_Manager();

                    // Get cart data
                    $eshb_cart_session = $session->get('cart');

                    if(!empty($eshb_cart_session)){
                        $eshb_cart = $eshb_cart_session;
                    }

                    // Check if the product already exists in the cart
                    $eshb_cart_found = false;

                    foreach ($eshb_cart as $key => $item) {
                        if ($item['product_id'] == $product_id) {
                            // Replace existing product
                            $eshb_cart[$key] = $cart_item_data;
                            $eshb_cart_found = true;
                            break;
                        }
                    }

                    // If not found, add new product
                    if (!$eshb_cart_found) {
                        $eshb_cart[] = $cart_item_data;
                    }

                    // Set Session
                    $session->set('cart', $eshb_cart);

                    wp_send_json_success([
                    'checkout_url' => $surecart_checkout_url,
                    'cart_item_data' => $cart_item_data,
                    'booking-type' => $booking_type,
                    'message' => esc_html__($string_booking_success_msg, 'easy-hotel'),
                    'success' => true,
                    ]);

                }

            }
        }
    }

    public function handle_booking_status_change($post_id, $booking_status, $booking_type, $order_id) {
        if($booking_type == 'surecart' && class_exists('\SureCart\Models\Order')) {

			$order = Order::find($order_id);
			$order = json_decode(json_encode($order), true);

			$checkout_id = $order['checkout_id'];
			$checkout = Checkout::find($checkout_id);
			$checkout = json_decode(json_encode($checkout), true);

			$customer_id = $checkout['customer_id'];
			$customer = Customer::with( [ 'shipping_address', 'billing_address', 'tax_identifier' ] )->find( $customer_id );
			$customer = json_decode(json_encode($customer), true);
			$first_name = $checkout['first_name'];
			$last_name = $checkout['last_name'];
			$customer_name = $checkout['name'];
			$customer_name = !empty( $customer_name ) ? trim($first_name . ' ' . $last_name) : $customer_name; // Concatenate first and last name
			$customer_email = !empty($checkout['email']) ? $checkout['email'] : '';

		
			$hotel_core = new ESHB_Core();

			$admin_email = get_option('admin_email');
			$recipent_email = $eshb_settings['recipent_email'];
			$recipent_email = empty($recipent_email) ? $admin_email : $recipent_email;
			$from_name = parse_url(get_site_url(), PHP_URL_HOST);
				

			switch ($booking_status) {
				case 'completed':
					// Send manual customer email when order is cancelled
					$subject = sprintf( __( 'Your Booking #%s has been Completed', 'easy-hotel' ), $post_id );
					$message = '<h3>'. __( 'Booking Completed', 'easy-hotel' ) . '</h3>';

					$message .= '<p>' . sprintf( __( 'Hi %s,', 'easy-hotel' ), esc_html( $customer_name ) ) . '</p>';
					$message .= '<p>' . sprintf( __( 'We regret to inform you that your <strong>%s</strong> has been <strong>completed</strong>.', 'easy-hotel' ), esc_html( get_the_title( $post_id ) ) ) . '</p>';
					$message .= '<p>' . __( 'If you have any questions, please feel free to reply to this email.', 'easy-hotel' ) . '</p>';
					$message .= '<p>' . __( 'Thank you for shopping with us.', 'easy-hotel' ) . '</p>';

					
					break;
	
				case 'processing':
					// Send manual customer email when order is cancelled
					$subject = sprintf( __( 'Your Booking #%s is on processing', 'easy-hotel' ), $post_id );
					$message = '<h3>'. __( 'Booking On Processing', 'easy-hotel' ) . '</h3>';

					$message .= '<p>' . sprintf( __( 'Hi %s,', 'easy-hotel' ), esc_html( $customer_name ) ) . '</p>';
					$message .= '<p>' . sprintf( __( 'We regret to inform you that your <strong>%s</strong> is <strong>on processing</strong>.', 'easy-hotel' ), esc_html( get_the_title( $post_id ) ) ) . '</p>';
					$message .= '<p>' . __( 'If you have any questions, please feel free to reply to this email.', 'easy-hotel' ) . '</p>';
					$message .= '<p>' . __( 'Thank you for shopping with us.', 'easy-hotel' ) . '</p>';

					break;

				case 'on-hold':
					// Send manual customer email when order is cancelled
					$subject = sprintf( __( 'Your Booking #%s is now on hold', 'easy-hotel' ), $post_id );
					$message = '<h3>'. __( 'Booking On Hold', 'easy-hotel' ) . '</h3>';

					$message .= '<p>' . sprintf( __( 'Hi %s,', 'easy-hotel' ), esc_html( $customer_name ) ) . '</p>';
					$message .= '<p>' . sprintf( __( 'We regret to inform you that your <strong>%s</strong> is now <strong>on hold</strong>.', 'easy-hotel' ), esc_html( get_the_title( $post_id ) ) ) . '</p>';
					$message .= '<p>' . __( 'If you have any questions, please feel free to reply to this email.', 'easy-hotel' ) . '</p>';
					$message .= '<p>' . __( 'Thank you for shopping with us.', 'easy-hotel' ) . '</p>';

					break;

				case 'cancelled':
					// Send manual customer email when order is cancelled
					$subject = sprintf( __( 'Your Booking #%s has been Cancelled', 'easy-hotel' ), $post_id );
					$message = '<h3>'. __( 'Booking Cancelled', 'easy-hotel' ) . '</h3>';

					$message .= '<p>' . sprintf( __( 'Hi %s,', 'easy-hotel' ), esc_html( $customer_name ) ) . '</p>';
					$message .= '<p>' . sprintf( __( 'We regret to inform you that your <strong>%s</strong> has been <strong>cancelled</strong>.', 'easy-hotel' ), esc_html( get_the_title( $post_id ) ) ) . '</p>';
					$message .= '<p>' . __( 'If you have any questions, please feel free to reply to this email.', 'easy-hotel' ) . '</p>';
					$message .= '<p>' . __( 'Thank you for shopping with us.', 'easy-hotel' ) . '</p>';

					
					break;
				// You can add more cases depending on which emails you want to send.
			}

			$hotel_core->eshb_send_html_email($customer_email, $subject, $message, $from_name, $recipent_email);
		}
    }

    public function add_coupon_product_ids($product_ids, $accomodation_ids) {
        $settings       = get_option('eshb_settings', []);
        $booking_type   = $settings['booking-type'] ?? 'woocommerce';

        if ($booking_type !== 'surecart' || !class_exists('SureCart')) {
            return $product_ids; // Return original product IDs if not SureCart
        }

        $surecart_product_ids = [];
        if (is_array($accomodation_ids) && !empty($accomodation_ids)) {
            foreach ($accomodation_ids as $accomodation_id) {
                $accomodation_id = (int) $accomodation_id;
                $thumbnail_id = get_post_thumbnail_id($accomodation_id);
                $product_id = ESHB_Surecart_Helper::get_or_create_surecart_product($accomodation_id, $thumbnail_id);
                if (!empty($product_id)) {
                    $surecart_product_ids[] = $product_id;
                }
            }
        }

        // Merge existing product IDs with SureCart product IDs and remove duplicates
        $all_product_ids = array_unique(array_merge($product_ids, $surecart_product_ids));

        return $all_product_ids;
    }

    public function create_coupon($post_id, $coupon, $product_ids) {
        $settings       = get_option('eshb_settings', []);
        $booking_type   = $settings['booking-type'] ?? 'woocommerce';

        if ($booking_type !== 'surecart' || !class_exists('SureCart') || !class_exists('ESHB_Surecart_Coupon')) {
            return; // Exit if not SureCart or required classes don't exist
        }

        if($booking_type === 'surecart' && class_exists('SureCart') && class_exists('ESHB_Surecart_Coupon')) {

            $title                 = get_the_title($post_id);
            $coupon_code           = $coupon['coupon-code'] ?? '';
            $coupon_amount         = $coupon['coupon-amount'] ?? '';
            $discount_type         = $coupon['discount-type'] ?? 'percent';
            $expiry_date           = $coupon['expiry-date'] ?? '';
            $usage_limit           = $coupon['usage-limit'] ?? '';
            $usage_limit_per_user  = $coupon['usage-limit-per-user'] ?? '1';


            $sc_coupon_id   = get_post_meta($post_id, 'eshb_coupon_sc_id', true);
            $product_ids_string    = implode(',', $product_ids);

            // Create or update the SureCart coupon
            $coupon_id = ESHB_Surecart_Coupon::create_surecart_coupon(
                $title, $coupon_code, $coupon_amount, $discount_type,
                $usage_limit, $usage_limit_per_user, $expiry_date,
                $product_ids, $sc_coupon_id
            );

            if(!empty($coupon_id)){
                update_post_meta($post_id, 'eshb_coupon_sc_id', $coupon_id);
            }

            if(!empty($product_ids_string)){
                update_post_meta($post_id, 'eshb_coupon_sc_product_ids', $product_ids_string);
            }
        }
    }

    public function delete_coupon($post_id, $booking_type) {
        if ($booking_type !== 'surecart' || !class_exists('SureCart') || !class_exists('ESHB_Surecart_Coupon')) {
            return; // Exit if not SureCart or required classes don't exist
        }

        if($booking_type === 'surecart' && class_exists('SureCart') && class_exists('ESHB_Surecart_Coupon')) {
            $sc_coupon_id = get_post_meta($post_id, 'eshb_coupon_sc_id', true);
            if(!empty($sc_coupon_id)){
                // Delete the coupon using SureCart API
                Coupon::delete($sc_coupon_id);

                // delete surecart coupon id
                delete_post_meta($post_id, 'eshb_coupon_sc_id');
            }
        }
    }

    public function replace_product_id_for_adding_to_cart($product_id, $accomodation_id) {
        $settings       = get_option('eshb_settings', []);
        $booking_type   = $settings['booking-type'] ?? 'woocommerce';

        if (!class_exists('\SureCart\Models\Product') || $booking_type !== 'surecart') {
            return $product_id; // Return original product ID if not SureCart
        }

        $product_id = get_post_meta($accomodation_id, '_surecart_product_id', true);
        if (empty($product_id)) {
            $thumbnail_id = get_post_thumbnail_id($accomodation_id);
            $product_id = ESHB_Surecart_Helper::get_or_create_surecart_product($accomodation_id, $thumbnail_id);
        }

        return $product_id;
    }

    public function add_billing_data_to_booking_info_table($billing_data, $order_id, $booking_type) {
       
        if(! class_exists('SureCart')) return $billing_data;
        
        $order = Order::find($order_id);
        if(!$order) return $customer;
        $order = json_decode(json_encode($order), true);
        if(isset($order['errors'])) return $billing_data;

        $checkout_id = $order['checkout_id'];
        $checkout = Checkout::find($checkout_id);
        $checkout = json_decode(json_encode($checkout), true);
        $customer_id = $checkout['customer_id'];
        $customer = Customer::with( [ 'shipping_address', 'billing_address', 'tax_identifier' ] )->find( $customer_id );
        $customer = json_decode(json_encode($customer), true);
        
        if($customer['billing_matches_shipping'] == true){
            $billing_address = $customer['shipping_address'];
        }else{
            $billing_address = $customer['billing_address'];
        }

        $order_status = $order['status'];
        $first_name = $checkout['first_name'];
        $last_name = $checkout['last_name'];
        $customer_name = $checkout['name'];
        $customer_name = !empty( $customer_name ) ? trim($first_name . ' ' . $last_name) : $customer_name; // Concatenate first and last name
        $payment_method_name = '';

        
        if(!empty($checkout['manual_payment_method'])){
            $payment_method_id = $checkout['manual_payment_method'];
            $payment_method = ManualPaymentMethod::find($payment_method_id);
            $payment_method_name = $payment_method->name;
        }else if(isset($checkout['payment_method'])){
            $payment_method_id = $checkout['payment_method'];
            $payment_method = PaymentMethod::find($payment_method_id);
            $payment_method_name = $payment_method->processor_type;
        }

        
        $billing_email      = !empty($checkout['email']) ? $checkout['email'] : '';
        $billing_phone      = !empty($checkout['phone']) ? $checkout['phone'] : '';
        $billing_company    = !empty($billing_address['company']) ? $billing_address['company'] : '';
        $billing_city       = !empty($billing_address['city']) ? $billing_address['city'] : '';
        $billing_state      = '';
        $billing_address_1  = !empty($billing_address['line_1']) ? $billing_address['line_1'] : '';
        $billing_address_2  = !empty($billing_address['line_2']) ? $billing_address['line_2'] : '';
        $billing_postcode   = !empty($billing_address['postal_code']) ? $billing_address['postal_code'] : '';
        $billing_country    = !empty($billing_address['country']) ? $billing_address['country'] : '';

        $billing_data['order_status'] = $order_status;
        $billing_data['customer_name'] = $customer_name; // Concatenate first and last name
        $billing_data['payment_method_name'] = $payment_method_name;
        $billing_data['billing_email'] = $billing_email;
        $billing_data['billing_phone'] = $billing_phone;
        $billing_data['billing_company'] = $billing_company;
        $billing_data['billing_city'] = $billing_city;
        $billing_data['billing_state'] = $billing_state;
        $billing_data['billing_postcode'] = $billing_postcode;
        $billing_data['billing_country'] = $billing_country;
        $billing_data['billing_address_1'] = $billing_address_1;
        $billing_data['billing_address_2'] = $billing_address_2;

        
        return $billing_data;
    }

    public function add_customer_data_in_calendar($customer, $order_id, $booked_type){

        if(! class_exists('SureCart')) return $customer;
        
        $order = Order::find($order_id);
        if(!$order) return $customer;
        $order = json_decode(json_encode($order), true);
        if(isset($order['errors'])) return $customer;
        $checkout_id = $order['checkout_id'];
        $checkout = Checkout::find($checkout_id);
        $checkout = json_decode(json_encode($checkout), true);
        $customer_id = $checkout['customer_id'];
        $customer = Customer::with( [ 'shipping_address', 'billing_address', 'tax_identifier' ] )->find( $customer_id );
        $customer = json_decode(json_encode($customer), true);
    
        //$order_status = $order['status'];
        $first_name = $checkout['first_name'] ?? '';
        $last_name = $checkout['last_name'] ?? '';
        $customer = $checkout['name'] ?? '';
        $customer = !empty( $customer ) ? trim($first_name . ' ' . $last_name) : $customer; // Concatenate first and last name
        
        return $customer;
    }
}

$EHB_SureCart_Filters = new EHB_SureCart_Filters();
