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
			$cust   = $payload['subscription']['customer']['id'];
			$sub    = $payload['subscription']['id'];
			$email  = $payload['subscription']['customer']['email'];
			$prod   = $payload['subscription']['product']['id'];

			$member = $this->getMember($cust);

			if (!$member) {
				return $this->httpError(404, 'Member could not be found.');
			}

			$this->subscribeMember($prod, $sub, $member);
		}

		// Handle subscription upgrades or downgrades.
		if ($event == 'subscription_product_change') {
			$cust  = $payload['subscription']['customer']['id'];
			$sub   = $payload['subscription']['id'];
			$email = $payload['subscription']['customer']['email'];
			$prev  = $payload['previous_product']['id'];
			$curr  = $payload['subscription']['product']['id'];

			$member = $this->getMember($cust);

			if (!$member) {
				return $this->httpError(404, 'Member could not be found.');
			}

			$this->unsubscribeMember($prev, $sub, $member);
			$this->subscribeMember($curr, $sub, $member);
		}

		// Handle subscriptions ending.
		if ($event == 'subscription_state_change') {
			$cust   = $payload['subscription']['customer']['id'];
			$sub    = $payload['subscription']['id'];
			$email  = $payload['subscription']['customer']['email'];
			$prod   = $payload['subscription']['product']['id'];
			$state  = $payload['subscription']['state'];

			if (in_array($state, array('canceled', 'expired', 'suspended'))) {
				$member = $this->getMember($cust);

				if (!$member) {
					return $this->httpError(404, 'Member could not be found.');
				}

				$this->unsubscribeMember($prod, $sub, $member);
			}
		}

		return '';
	}

	/**
	 * Adds a member to the groups linked to a Chargify product.
	 *
	 * @param int $product
	 * @param int $subscription
	 * @param Member $member
	 */
	protected function subscribeMember($product, $subscription, Member $member) {
		$groups = DataObject::get('Group', sprintf(
			'"ChargifyProductID" = %d', $product
		));

		if ($groups) foreach ($groups as $group) {
			$member->Groups()->add($group, array(
				'Chargify'       => true,
				'SubscriptionID' => $subscription
			));
		}
	}

	/**
	 * Removes a member from the groups linked to a Chargify product.
	 *
	 * @param int $product
	 * @param int $subscription
	 * @param Member $member
	 */
	protected function unsubscribeMember($product, $subscription, Member $member) {
		$groups = $member->getManyManyComponents('Groups', sprintf(
			'"ChargifyProductID" = %d ' .
			'AND "Group_Members"."Chargify" = 1 ' .
			'AND "Group_Members"."SubscriptionID" = %d',
			$product, $subscription
		));

		if (count($groups)) {
			$member->Groups()->removeMany($groups->map('ID', 'ID'));
		}
	}

	/**
	 * Returns the {@link Member} object for a chargify customer ID.
	 *
	 * @param  int $id
	 * @return Member
	 */
	protected function getMember($id) {
		$link = DataObject::get_one('ChargifyCustomerLink', sprintf(
			'"CustomerID" = %d', $id
		));

		if ($link) {
			return $link->Member();
		}
	}

}