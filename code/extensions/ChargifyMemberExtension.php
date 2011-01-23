<?php
/**
 * Links each {@link Member} to a Chargify customer ID.
 *
 * @package silverstripe-chargify
 */
class ChargifyMemberExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array('has_many' => array(
			'ChargifyCustomers'     => 'ChargifyCustomerLink',
			'ChargifySubscriptions' => 'ChargifySubscriptionLink'
		));
	}

	public function onBeforeWrite() {
		if (!count($this->owner->ChargifyCustomers())) return;

		$changed = array_keys($this->owner->getChangedFields());
		$push    = array('Email', 'FirstName', 'Surname');

		if (array_intersect($push, $changed)) {
			$connector = ChargifyService::instance()->getConnector();

			foreach ($this->owner->ChargifyCustomers() as $link) {
				try {
					$customer = $connector->getCustomerByID($link->CustomerID);
				} catch(ChargifyNotFoundException $e) {
					$link->delete();
					continue;
				}

				$customer->email      = $this->owner->Email;
				$customer->first_name = $this->owner->FirstName;
				$customer->last_name  = $this->owner->Surname;

				try {
					$connection->updateCustomer($customer);
				} catch(ChargifyValidationException $e) {  }
			}
		}
	}

	public function updateCMSFields($fields) {
		$fields->removeByName('ChargifyCustomers');
		$fields->removeByName('ChargifySubscriptions');
	}

}