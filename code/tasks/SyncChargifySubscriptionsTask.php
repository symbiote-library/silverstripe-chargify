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
		$synced = $deleted = 0;

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

				if ($result == 'synced') {
					$synced++;
				} elseif ($result == 'deleted') {
					$deleted++;
				}
			}
		}

		echo "Synced $synced and removed $deleted subscriptions.\n";
	}

	/**
	 * @param  ChargifySubscription $subscription
	 * @return string
	 */
	protected function processSubscription($subscription) {
		$memberLink = DataObject::get_one('ChargifyCustomerLink', sprintf(
			'"CustomerID" = %d', $subscription->customer->id
		));

		if ($memberLink) {
			$member = $memberLink->Member();
		} else {
			return;
		}

		if (in_array($subscription->state, array('canceled', 'unpaid', 'expired'))) {
			$member->chargifyUnsubscribe($subscription);
			return 'deleted';
		} else {
			// Create a subscription link if we don't have one.
			$subLink = DataObject::get_one('ChargifySubscriptionLink', sprintf(
				'"SubscriptionID" = %d', $subscription->id
			));

			if (!$subLink) {
				$reference = $subscription->customer->reference;
				$pageId    = substr($reference, strpos($reference, '-') + 1);

				$subLink = new ChargifySubscriptionLink();
				$subLink->SubscriptionID = $subscription->id;
				$subLink->MemberID       = $member->ID;
				$subLink->PageID         = $pageId;
				$subLink->write();
			}

			// First remove the user from all groups, then re-add them.
			$member->chargifyUnsubscribe($subscription);
			$member->chargifySubscribe($subscription);

			return 'synced';
		}
	}

}
