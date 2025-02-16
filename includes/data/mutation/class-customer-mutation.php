<?php
/**
 * Defines functions for processing customer mutations.
 *
 * @package WPGraphQL\Extensions\WooCommerce\Data\Mutation
 * @since 0.1.0
 */

namespace WPGraphQL\Extensions\WooCommerce\Data\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Types;

/**
 * Class Customer_Mutation
 */
class Customer_Mutation {
	/**
	 * Maps the GraphQL input to a format that the used by WooCommerce's WC_Customer class
	 *
	 * @param array  $input    Data coming from the GraphQL mutation query input.
	 * @param string $mutation Mutation being performed.
	 *
	 * @access public
	 * @return array
	 */
	public static function prepare_customer_props( $input, $mutation ) {
		$customer_args = array();

		if ( ! empty( $input['billing'] ) ) {
			$customer_args['billing'] = self::address_input_mapping( 'billing', $input['billing'] );
		}

		if ( ! empty( $input['shipping'] ) ) {
			$customer_args['shipping'] = self::address_input_mapping( 'shipping', $input['shipping'] );
		}

		$customer_args['role'] = 'customer';

		/**
		 * Filters the mappings for input to arguments
		 *
		 * @var array  $customer_args The arguments to ultimately be passed to the WC_Customer::set_props function
		 * @var array  $input         Input data from the GraphQL mutation
		 * @var string $mutation      What customer mutation is being performed for context
		 */
		$customer_args = apply_filters( 'woocommerce_new_customer_data', $customer_args, $input, $mutation );

		return $customer_args;
	}

	/**
	 * Formats CustomerAddressInput into a address object to be used by WC_Customer object
	 *
	 * @param string $type  Address type.
	 * @param array  $input Customer address input.
	 *
	 * @return array;
	 */
	public static function address_input_mapping( $type = 'billing', $input ) {
		// Map GQL input to address props array.
		$key_mapping = array(
			'firstName' => 'first_name',
			'lastName'  => 'last_name',
			'address1'  => 'address_1',
			'address2'  => 'address_2',
		);

		$skip = apply_filters( 'customer_address_input_mapping_skipped', array( 'overwrite' ) );

		$type    = 'empty_' . $type;
		$address = ! empty( $input['overwrite'] ) && true === $input['overwrite']
			? self::{$type}()
			: array();
		foreach ( $input as $input_field => $value ) {
			if ( in_array( $input_field, array_keys( $key_mapping ), true ) ) {
				$address[ $key_mapping[ $input_field ] ] = $value;
			} elseif ( in_array( $input_field, $skip, true ) ) {
				continue;
			} else {
				$address[ $input_field ] = $value;
			}
		}

		return $address;
	}

	/**
	 * Returns default customer shipping address data
	 *
	 * @return array
	 */
	public static function empty_shipping() {
		return array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);
	}

	/**
	 * Returns default customer billing address data
	 *
	 * @return array
	 */
	public static function empty_billing() {
		return array_merge(
			self::empty_shipping(),
			array(
				'email' => '',
				'phone' => '',
			)
		);
	}
}
