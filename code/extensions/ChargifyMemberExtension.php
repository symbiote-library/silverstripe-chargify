<?php
/**
 * Links each {@link Member} to a Chargify customer ID.
 *
 * @package silverstripe-chargify
 */
class ChargifyMemberExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array('db' => array(
			'ChargifyID' => 'Int'
		));
	}

	public function onAfterWrite() {
		if (!$this->owner->ChargifyID) {
			$valid = (
				$this->owner->FirstName
				&& $this->owner->Surname
				&& $this->owner->Email
			);

			if (!$valid) return;

			$connection = ChargifyService::instance()->getConnector();
			$customer   = new ChargifyCustomer();

			$customer->email      = $this->owner->Email;
			$customer->first_name = $this->owner->FirstName;
			$customer->last_name  = $this->owner->Surname;
			$customer->reference  = $this->owner->ID;

			try {
				$customer = $connection->createCustomer($customer);
			} catch(ChargifyValidationException $e) {
				return;
			}

			$this->owner->ChargifyID = $customer->id;
			$this->owner->write();
		}
	}

	public function updateCMSFields($fields) {
		$fields->removeByName('ChargifyID');
	}

	public function updateMemberFormFields($fields) {
		$fields->removeByName('ChargifyID');
	}

	public function updateFrontEndFields($fields) {
		$fields->removeByName('ChargifyID');
	}

}