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
		$id    = $subscription->id;
		$state = $subscription->state;

		if (in_array($state, array('canceled', 'unpaid', 'expired'))) {
			DB::query(sprintf(
				'DELETE FROM "Group_Members" WHERE "Chargify" = 1 AND "SubscriptionID" = %d',
				$id
			));

			return 'deleted';
		} else {
			$member = DataObject::get_one('Member', sprintf(
				'"ChargifyID" = %d', $subscription->customer->id
			));

			if (!$member) return;

			$groups = DataObject::get('Group', sprintf(
				'"ChargifyProductID" = %d', $subscription->product->id
			));

			$result = null;

			if ($groups) foreach ($groups as $group) {
				if (!$member->inGroup($group)) {
					$member->Groups()->add($group, array(
						'Chargify'       => true,
						'SubscriptionID' => $id
					));
					$result = 'subscribed';
				}
			}

			return $result;
		}
	}

}
