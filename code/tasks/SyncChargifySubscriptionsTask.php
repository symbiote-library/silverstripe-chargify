<?php
/**
 * Syncs the subscription to group linking. Note: this should be run after
 * {@link SyncChargifyMembersTask}.
 *
 * @package silverstripe-chargify
 */
class SyncChargifySubscriptionsTask extends BuildTask {

	protected $title = 'Sync Chargify Subscriptions';
	protected $description = 'Syncs the Chargify subscription to group linking.';

	public function run($request) {
		$subscribed = $deleted = 0;

		// Ensure we have an uncached connector.
		$connector = ChargifyService::instance()->getConnector();
		$connector->setCacheExpiry(-1);

		// Now loop through each page of API data until we reach the end.
		for ($page = 1; ; $page++) {
			if (!$subs = $connector->getSubscriptions($page)) {
				break;
			}

			foreach ($subs as $subscription) {
				$result = $this->processSubscription($subscription);

				if ($result == 'subscribed') {
					$subscribed++;
				} elseif ($result == 'deleted') {
					$deleted++;
				}
			}
		}

		echo "Subscribed $subscribed members and removed $deleted subscriptions.\n";
	}

	/**
	 * @param  ChargifySubscription $subscription
	 * @return string
	 */
	protected function processSubscription($subscription) {
		$id      = $subscription->id;
		$state   = $subscription->state;
		$custref = $subscription->customer->reference;

		if (in_array($state, array('canceled', 'unpaid', 'expired'))) {
			DB::query(sprintf(
				'DELETE FROM "Group_Members" WHERE "Chargify" = 1 AND "SubscriptionID" = %d',
				$id
			));

			return 'deleted';
		} else {
			$link = DataObject::get_one('ChargifyCustomerLink', sprintf(
				'"CustomerID" = %d', $subscription->customer->id
			));

			if (!$link) return;
			$member = $link->Member();

			$result = 'subscribed';

			$member->chargifySubscribe($subscription);

			$link = DataObject::get_one('ChargifySubscriptionLink', sprintf(
				'"SubscriptionID" = %d', $id
			));

			if (!$link) {
				$link = new ChargifySubscriptionLink();
				$link->SubscriptionID = $id;
				$link->MemberID = $member->ID;
				$link->PageID = substr(
					$custref, strpos($custref, '-') + 1
				);
				$link->write();

				$result = true;
			}

			return $result;
		}
	}

}
