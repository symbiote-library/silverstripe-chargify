<?php
/**
 * Does a two way sync between the Chargify customer database and the Member
 * list, ensuring that each Member is linked to a customer.
 *
 * @package silverstripe-chargify
 */
class SyncChargifyMembersTask extends BuildTask {

	public $title = 'Sync Chargify Members';
	public $description = 'Syncs the SilverStripe and Chargify Customer/Member
		databases, so each Member is linked to a Chargify customer.';

	public function run($request) {
		$synced = $created = 0;

		// Ensure we have an uncached connector.
		$connector = ChargifyService::instance()->getConnector();
		$connector->setCacheExpiry(-1);

		// Now loop through each page of API data until we reach the end.
		for ($page = 1; ; $page++) {
			if (!$customers = $connector->getAllCustomers($page)) {
				break;
			}

			foreach ($customers as $customer) {
				if ($result = $this->processCustomer($customer, $connector)) {
					if ($result['updated']) $synced++;
				}
			}
		}

		echo "Created $created customers and synced $synced members.\n";
	}

	/**
	 * Processes a customer, and ensures we have a corresponding member record.
	 *
	 * @param  ChargifyCustomer $customer
	 * @param  ChargifyConnector $connector
	 * @return int
	 */
	protected function processCustomer($customer, $connector) {
		$member = null;

		// Attempt to find the member directly by reference ID.
		if ($customer->reference) {
			$member = DataObject::get_by_id('Member', $customer->reference);
		}

		// Then try to look up the member by email.
		if (!$member) {
			$member = DataObject::get_one('Member', sprintf(
				'Email = \'%s\'', Convert::raw2sql($customer->email)
			));

			$member->ChargifyID = $customer->id;
			$member->write();

			$customer->reference = $member->ID;
			$connector->updateCustomer($customer);
		}

		// If we have a Member, ensure the details are the same across both
		// systems. If we can't find a corresponding Member record then just
		// ignore it.
		if ($member) {
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
						$customer = $connection->createCustomer($customer);
					} catch(ChargifyValidationException $e) {
						return;
					}
				} else {
					$member->Email     = $customer->email;
					$member->FirstName = $customer->first_name;
					$member->Surname   = $customer->last_name;
					$member->write();
				}

				return array('id' => $member->ID, 'updated' => true);
			}

			return array('id' => $member->ID, 'updated' => false);
		}
	}

}
