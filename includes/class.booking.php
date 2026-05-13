<?php
use SureCart\Models\Price;

class ESHB_Surecart_Booking {

    public function __construct() {
       add_action( 'surecart/checkout_confirmed', [$this, 'create_booking_on_checkout'], 10, 2 );
    }

    public function create_booking_on_checkout($checkout, $request){
		
		$eshb_settings = get_option('eshb_settings', []);
		$booking_status = isset($eshb_settings['booking-auto-approval']) && $eshb_settings['booking-auto-approval'] == true ? 'completed' : 'processing';

		$checkout_id = $checkout->id;
		$order_id = $checkout->order;
        $line_items = $checkout->line_items->data;
		$line_items = json_decode(json_encode($line_items), true);
		
		$eshb_cart = [];

		$session = new ESHB_Session_Manager();
		// Get cart data
		$eshb_cart_session = $session->get('cart');

		if(!empty($eshb_cart_session)){
			$eshb_cart = $eshb_cart_session;
		}

		foreach ($line_items as $key => $line_item) {
			
			$sc_product_id = $line_item['price']['product_id'];

			//error_log('sc product id: ' . $sc_product_id);

			$item = array_filter($eshb_cart, function($item) use ($sc_product_id) {
            	return $item['product_id'] == $sc_product_id;
        	});

			$item = reset($item);

			//error_log('filtered item' . print_r($item, true));

			$accomodation_id = $item['accomodation_id'];

			// Retrieve meta data saved in the order item
			$product_id = !empty($item['product_id']) ? $item['product_id'] : '';
			$accomodation_id = !empty($item['accomodation_id']) ? $item['accomodation_id'] : '';
			$accomodation_title = !empty($accomodation_id) ? get_the_title($accomodation_id) : '';
			$room_quantity = !empty($item['room_quantity']) ? $item['room_quantity'] : 0;
			$extra_bed_quantity = !empty($item['extra_bed_quantity']) ? $item['extra_bed_quantity'] : 0;
			$adult_quantity = !empty($item['adult_quantity']) ? $item['adult_quantity'] : 0;
			$children_quantity = !empty($item['children_quantity']) ? $item['children_quantity'] : 0;
			$start_date = !empty($item['start_date']) ? $item['start_date'] : '';
			$end_date = !empty($item['end_date']) ? $item['end_date'] : '';
			$dates = !empty($item['dates']) ? $item['dates'] : array();
			$details_html = !empty($item['details_html']) ? $item['details_html'] : '';
			$extra_services = !empty($item['extra_services']) ? $item['extra_services'] : array();
			$extra_services_html = !empty($item['extra_services_html']) ? $item['extra_services_html'] : '';
			$base_price = !empty($item['base_price']) ? $item['base_price'] : 0;
			$subtotal_price = !empty($item['subtotal_price']) ? $item['subtotal_price'] : 0;
			$total_price = !empty($item['total_price']) ? $item['total_price'] : 0;
			$extra_services_charge = !empty($item['extra_services_charge']) ? $item['extra_services_charge'] : 0;
			$extra_bed_price = !empty($item['extra_bed_price']) ? $item['extra_bed_price'] : 0;
			$start_time = !empty($item['start_time']) ? $item['start_time'] : '';
			$end_time = !empty($item['end_time']) ? $item['end_time'] : '';
			$times = !empty($item['times']) ? $item['times'] : '';

			// Prepare the data array to store in post meta
			$meta_data = [
				'booking_status' => $booking_status,
				'order_id' => $order_id,
				'booking_accomodation_id' => $accomodation_id,
				'subtotal_price' => $subtotal_price,
				'total_price' => $total_price,
				'total_paid' => $total_price,
				'base_price' => $base_price,
				'extra_service_price' => $extra_services_charge,
				'extra_bed_price' => $extra_bed_price,
				'booking_start_date' => $start_date,
				'booking_end_date' => $end_date,
				'booking_start_time' => !empty($start_time) ? $start_time : '10:00',
				'booking_end_time' => !empty($end_time) ? $end_time : '22:00',
				'dates' => $dates,
				'room_quantity' => $room_quantity,
				'extra_bed_quantity' => $extra_bed_quantity,
				'adult_quantity' => $adult_quantity,
				'children_quantity' => $children_quantity,
				'extra_services' => $extra_services,
				'start_time' => !empty($start_time) ? $start_time : '10:00',
				'end_time' => !empty($end_time) ? $end_time : '22:00',
				'times' => $times,
				'details_html' => $details_html,
				'extra_services_html' => $extra_services_html,
				'sc_checkout_id' => $checkout_id
			];

			// Insert the post
			$post_id = wp_insert_post(array(
				'post_type'   => 'eshb_booking',
				'post_title'  => 'Booking for ' . $start_date . ' - ' . $end_date,
				'post_status' => 'publish',
				'meta_input'  => array(
					'eshb_booking_metaboxes' => $meta_data
				)
			));
	
			// Check if the post was inserted successfully
			
			if ($post_id) {
				// Update the post title to include the post ID
				wp_update_post(array(
					'ID'         => $post_id,
					'post_title' => 'Booking #' . $post_id . ' for: ' . $accomodation_title,
					'post_status' => 'publish' // Update status to 'publish'
				));
				
				// Update Available Rooms Count For This Accomodation
				$accomodation_metaboxes = get_post_meta( $accomodation_id, 'eshb_accomodation_metaboxes', true );
				$total_rooms = floatval($accomodation_metaboxes['total_rooms']);
				$current_available_rooms = floatval($accomodation_metaboxes['available_rooms']);

				if(!empty($current_available_rooms)){
					$available_rooms = $current_available_rooms - floatval($room_quantity);
				}else{
					$available_rooms = $total_rooms - floatval($room_quantity);
				}
				
                // Update Available rooms
				$accomodation_metaboxes['available_rooms'] = $available_rooms;
				update_post_meta($accomodation_id, 'eshb_accomodation_metaboxes', $accomodation_metaboxes);
				

				// Update Sure Cart Order Status
				$new_status = $booking_status;
				update_post_meta($order_id, '_booking_post_created', $post_id);
				do_action('after_created_surecart_booking', $order_id, $post_id, $post);
			}
		}

		// delete surecart price
		foreach ($line_items as $key => $line_item) {
			$sc_price_id = $line_item['price']['id'];
			error_log('Deleting SureCart price ID: ' . $sc_price_id);
			Price::delete($sc_price_id);
		}

		// clear cart session
		$session->remove('cart');

	}
}

new ESHB_Surecart_Booking();



