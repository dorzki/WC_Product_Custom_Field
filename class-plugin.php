<?php
/**
 * Main plugin file.
 *
 * @package    dorzki\WooCommerce
 * @subpackage Plugin
 * @author     Dor Zuberi <webmaster@dorzki.co.il>
 * @link       https://www.dorzki.co.il
 * @version    1.0.0
 */

namespace dorzki\WooCommerce;

// Block if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Plugin
 *
 * @package dorzki\WooCommerce
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var null|Plugin
	 */
	private static $instance = null;


	/* ------------------------------------------ */


	/**
	 * Custom field id.
	 *
	 * @var string
	 */
	public static $field_id = '_wc_custom_field';


	/* ------------------------------------------ */


	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'inject_field' ] );
		add_action( 'woocommerce_add_order_item_meta', [ $this, 'save_order_item_data' ], 10, 2 );

		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_field' ], 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'save_field' ] );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'store_data' ], 10, 2 );
		add_filter( 'woocommerce_get_item_data', [ $this, 'show_on_cart' ], 10, 2 );
		add_filter( 'woocommerce_order_item_display_meta_key', [ $this, 'show_on_overview' ] );
		add_filter( 'woocommerce_email_order_meta_fields', [ $this, 'show_on_email' ] );

	}


	/* ------------------------------------------ */


	/**
	 * Inject the field just above the add to cart button.
	 */
	public function inject_field() {

		$field_value = ( isset( $_POST[ self::$field_id ] ) ) ? sanitize_text_field( $_POST[ self::$field_id ] ) : null;

		printf( '<label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%1$s" value="%3$s">', self::$field_id, esc_html__( 'Your Name:', 'dorzki-wc-product-custom-field' ), $field_value );

	}


	/**
	 * Saves the order data to database.
	 *
	 * @param int   $item_id order item id.
	 * @param array $values  order item data.
	 */
	public function save_order_item_data( $item_id, $values ) {

		if ( ! empty( $values[ self::$field_id ] ) ) {

			wc_add_order_item_meta( $item_id, self::$field_id, $values[ self::$field_id ] );

		}

	}


	/* ------------------------------------------ */


	/**
	 * Check if user have entered data into the field.
	 *
	 * @param bool $passed     if product passed validation.
	 * @param int  $product_id product id.
	 *
	 * @return bool
	 */
	public function validate_field( $passed, $product_id ) {

		$field_value = ( isset( $_POST[ self::$field_id ] ) ) ? sanitize_text_field( $_POST[ self::$field_id ] ) : null;

		if ( empty( $field_value ) ) {

			$product = wc_get_product( $product_id );

			wc_add_notice( sprintf(
			/* translators: 1: Product Name */
				esc_html__( '"%1$s" can\'t be added to cart until you fill all data.', 'dorzki-wc-product-custom-field' ),
				"<strong>{$product->get_title()}</strong>"
			) );

			return false;

		}

		return $passed;

	}


	/**
	 * Save the field value to current cart session.
	 *
	 * @param array $cart_item cart item data.
	 *
	 * @return array
	 */
	public function save_field( $cart_item ) {

		$field_value = ( isset( $_POST[ self::$field_id ] ) ) ? sanitize_text_field( $_POST[ self::$field_id ] ) : null;

		if ( ! empty( $field_value ) ) {

			$cart_item[ self::$field_id ] = $field_value;

		}

		return $cart_item;

	}


	/**
	 * Stores cart item field data to current cart session.
	 *
	 * @param array $cart_item_data cart item data.
	 * @param array $values         product item data.
	 *
	 * @return array
	 */
	public function store_data( $cart_item_data, $values ) {

		if ( isset( $values[ self::$field_id ] ) ) {

			$cart_item_data[ self::$field_id ] = $values[ self::$field_id ];

		}

		return $cart_item_data;

	}


	/**
	 * Display custom field data on cart page.
	 *
	 * @param array $item_data item registered data.
	 * @param array $cart_item item saved data.
	 *
	 * @return array
	 */
	public function show_on_cart( $item_data, $cart_item ) {

		if ( ! empty( $cart_item[ self::$field_id ] ) ) {

			$item_data[] = [
				'name'  => esc_html__( 'Your Name', 'dorzki-wc-product-custom-field' ),
				'value' => sanitize_text_field( $cart_item[ self::$field_id ] ),
			];

		}

		return $item_data;

	}


	/**
	 * Change the admin meta display key.
	 *
	 * @param string $display_key meta display key.
	 *
	 * @return string
	 */
	public function show_on_admin_order( $display_key ) {

		return ( self::$field_id === $display_key ) ? __( 'Your Name', 'dorzki-wc-product-custom-field' ) : $display_key;

	}


	/**
	 * Display custom field data on email.
	 *
	 * @param array $fields array of fields.
	 *
	 * @return array
	 */
	public function show_on_email( $fields ) {

		$fields[ self::$field_id ] = __( 'Your Name', 'dorzki-wc-product-custom-field' );

		return $fields;

	}


	/* ------------------------------------------ */


	/**
	 * Retrieve plugin instance.
	 *
	 * @return Plugin|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

}

// initiate plugin.
Plugin::get_instance();
