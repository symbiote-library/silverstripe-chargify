<?php
/**
 * Links chargify customers with their corresponding member.
 *
 * @package silverstripe-chargify
 */
class SyncChargifyMembersTask extends BuildTask {

	public $title = 'Sync Chargify Members';
	public $description = 'Syncs the SilverStripe and Chargify Customer/Member
		databases, so each Member is linked to a Chargify customer.';

	public function run($request) {
		$synced = $invalid = 0;

		// Ensure we have an uncached connector.
		$connector = ChargifyService::instance()->getConnector();
		$connector->setCacheExpiry(-1);

		// Now loop through each page of API data until we reach the end.
		for ($page = 1; ; $page++) {
			if (!$customers = $connector->getAllCustomers($page)) {
				break;
			}

			foreach ($customers as $customer) {
				$result = $this->processCustomer($customer, $connector);

				if ($result == 'updated') {
					$synced++;
				} elseif ($result == 'invalid') {
					$invalid++;
				}
			}
		}

		echo "Synced $synced members, and skipped $invalid invalid customers.\n";
	}

	/**
	 * Processes a customer, and ensures we have a corresponding member record.
	 *
	 * @param  ChargifyCustomer $customer
	 * @param  ChargifyConnector $connector
	 * @return int
	 */
	protected function processCustomer($customer, $connector) {
		$member  = null;
		$updated = false;

		// Attempt to find the member by ID. The reference is in the format
		// {Member ID}-{Page ID}-{Token}
		if ($ref = $customer->reference) {
			list($memberId, $pageId, $token) = explode('-', $ref);
			$member = DataObject::get_by_id('Member', $memberId);
		} else {
			return;
		}

		if ($token != ChargifyService::instance()->generateToken($memberId, $pageId)) {
			return 'invalid';
		}

		if (!$member) return;

		// Check we have a product linking
		$link = DataObject::get_one('ChargifyCustomerLink', sprintf(
			'"CustomerID" = %d AND "MemberID" = %d', $customer->id, $member->ID
		));

		if (!$link) {
			$link = new ChargifyCustomerLink();
			$link->CustomerID = $customer->id;
			$link->MemberID   = $member->ID;
			$link->write();

			$updated = true;
		}

		// If we have a Member, check if the details are the same. If they're
		// different, and the silverstripe record is newer then push the changes.
		$fields = array(
			'Email'     => 'email',
			'FirstName' => 'first_name',
			'Surname'   => 'last_name'
		);

		$identical = true;

		foreach ($fields as $mField => $cField) {
			if ($member->$mField != $customer->$cField) $identical = false;
		}

		// Update the most recently edited record.
		if (!$identical) {
			$mLast = strtotime($member->LastEdited);
			$cLast = strtotime($customer->updated_at);

			if ($mLast > $cLast) {
				$customer->email      = $member->Email;
				$customer->first_name = $member->FirstName;
				$customer->last_name  = $member->Surname;

				try {
					$connector->updateCustomer($customer);
				} catch(ChargifyValidationException $e) {
					return;
				}

				$updated = true;
			}
		}

		return $updated ? 'updated' : false;
	}

}
