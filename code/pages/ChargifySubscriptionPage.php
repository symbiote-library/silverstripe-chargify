<?php
/**
 * A page which allows a user to subscribe to a chargify product, or update
 * their subscription.
 *
 * @package silverstripe-chargify
 */
class ChargifySubscriptionPage extends Page {

	public static $has_many = array(
		'Products' => 'ChargifyProductLink'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Content', new Tab('Subscriptions'), 'Metadata');
		$fields->addFieldToTab('Root.Content.Subscriptions', new ChargifyProductSetField(
			'Products', 'Available Subscription Types'
		));

		return $fields;
	}

}

class ChargifySubscriptionPage_Controller extends Page_Controller {

	public function init() {
		parent::init();

		if (!Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'You must be logged in to manage your subscription.'
			));
		}
	}

}