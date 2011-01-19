<?php
/**
 * Links a {@link ChargifySubscriptionPage} to a product.
 *
 * @package silverstripe-chargify
 */
class ChargifyProductLink extends DataObject {

	public static $db = array(
		'ProductID' => 'Int'
	);

	public static $has_one = array(
		'Parent' => 'ChargifySubscriptionPage'
	);

}