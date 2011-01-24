<?php
/**
 * Handles subscription state changes, and adds or removes users from the linked
 * groups.
 *
 * @package silverstripe-chargify
 */
class ChargifyWebhookController extends Controller {

	public static $allowed_actions = array(
		'index'
	);

	public function index($request) {
		$event     = $request->postVar('event');
		$payload   = $request->postVar('payload');

		$shared    = ChargifyConfig::get_shared_key();
		$hash      = md5($shared . $request->getBody());
		$signature = $_SERVER['HTTP_X_CHARGIFY_WEBHOOK_SIGNATURE'];

		if ($hash != $signature) {
			return $this->httpError(400, 'Invalid signature.');
		}

		// Handle a new subscription being created.
		if ($event == 'signup_success') {
			$subscription = $this->arrayToObject($payload['subscription']);
			list($memberId, $pageId) = explode('-', $subscription->customer->reference);

			if (!$member = DataObject::get_by_id('Member', $memberId)) {
				return $this->httpError(404, 'Member not found.');
			}

			if (!DataObject::get_by_id('ChargifySubscriptionPage', $pageId)) {
				return $this->httpError(404, 'Subscription page not found.');
			}

			// Create a customer link if one doesn't exist.
			$memberLink = DataObject::get_one('ChargifyCustomerLink', sprintf(
				'"CustomerID" = %d', $subscription->customer->id
			));

			if (!$memberLink) {
				$memberLink = new ChargifyCustomerLink();
				$memberLink->CustomerID = $subscription->customer->id;
				$memberLink->MemberID   = $memberId;
				$memberLink->write();
			}

			// And create a subscription link.
			$subLink = DataObject::get_one('ChargifySubscriptionLink', sprintf(
				'"SubscriptionID" = %d', $subscription->id
			));

			if (!$subLink) {
				$subLink = new ChargifySubscriptionLink();
				$subLink->SubscriptionID = $subscription->id;
				$subLink->MemberID       = $memberId;
				$subLink->PageID         = $pageId;
				$subLink->write();
			}

			// And subscribe the member.
			$member->chargifySubscribe($subscription);
		}

		// Handle subscription upgrades or downgrades.
		if ($event == 'subscription_product_change') {
			$subscription = $this->arrayToObject($payload['subscription']);

			$memberLink = DataObject::get_one('ChargifyCustomerLink', sprintf(
				'"CustomerID" = %d', $subscription->customer->id
			));

			if (!$memberLink) {
				return $this->httpError(404, 'Member not found.');
			}

			$member = $memberLink->Member();
			$member->chargifyUnsubscribe($subscription);
			$member->chargifySubscribe($subscription);
		}

		// Handle subscriptions ending.
		if ($event == 'subscription_state_change') {
			$subscription = $this->arrayToObject($payload['subscription']);

			if (in_array($subscription->state, array('canceled', 'expired', 'suspended'))) {
				$memberLink = DataObject::get_one('ChargifyCustomerLink', sprintf(
					'"CustomerID" = %d', $subscription->customer->id
				));

				if (!$memberLink) {
					return $this->httpError(404, 'Member not found.');
				}

				$member = $memberLink->Member();
				$member->chargifyUnsubscribe($subscription);
			}
		}

		return '';
	}

	/**
	 * Recursively converts an array to an object.
	 *
	 * @param  array $array
	 * @return object
	 */
	public function arrayToObject(array $array) {
		foreach ($array as &$value) {
			if (is_array($value)) $value = (object) $value;
		}

		return (object) $array;
	}

}