<?php
/**
 * Links a {@link Member} to several chargify customer records.
 *
 * @package silverstripe-chargify
 */
class ChargifyCustomerLink extends DataObject {

	public static $db = array(
		'CustomerID' => 'Int'
	);

	public static $has_one = array(
		'Member' => 'Member'
	);

}