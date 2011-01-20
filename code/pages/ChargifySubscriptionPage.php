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

	public static $allowed_actions = array(
		'creditcard',
		'transactions',
		'upgrade',
		'subscribe'
	);

	public function init() {
		parent::init();

		if (!Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'You must be logged in to manage your subscription.'
			));
		}
	}

	public function creditcard() {
		if (!$subscription = $this->getChargifySubscription()) {
			return $this->httpError(404);
		}

		$card = $subscription->credit_card;

		return array(
			'FirstName' => $card->first_name,
			'LastName'  => $card->last_name,
			'Number'    => $card->masked_card_number,
			'ExpMonth'  => $card->expiration_month,
			'ExpYear'   => $card->expiration_year
		);
	}

	/**
	 * Lists all transactions for the subscription.
	 *
	 * @return array
	 */
	public function transactions() {
		if (!$subscription = $this->getChargifySubscription()) {
			return $this->httpError(404);
		}

		$set   = new DataObjectSet();
		$conn  = ChargifyService::instance()->getConnector();
		$curr  = ChargifyConfig::get_currency();
		$trans = $conn->getTransactionsBySubscriptionID($subscription->id);

		foreach ($trans as $transaction) {
			$amount = DBField::create('Money', array(
				'Amount'   => ($transaction->amount_in_cents / 100),
				'Currency' => $curr
			));

			$balance = DBField::create('Money', array(
				'Amount'   => ($transaction->ending_balance_in_cents / 100),
				'Currency' => $curr
			));

			$set->push(new ArrayData(array(
				'ID'      => $transaction->id,
				'Type'    => $transaction->type,
				'Date'    => DBField::create('Date', $transaction->created_at),
				'Amount'  => $amount,
				'Balance' => $balance,
				'Success' => (bool) $transaction->success
			)));
		}

		return array(
			'Transactions' => $set
		);
	}

	/**
	 * @todo
	 */
	public function upgrade() {
		return '';
	}

	/**
	 * @todo
	 */
	public function subscribe() {
		return '';
	}

	/**
	 * If the current member has a subscription to one of the products attached
	 * to this page, return it.
	 *
	 * @return ChargifySubscription
	 */
	public function getChargifySubscription() {
		if (!$member = Member::currentUser()) return;

		$prods  = implode(', ', $this->data()->Products()->map('ID', 'ProductID'));
		$filter = sprintf(
			'"ChargifyProductID" IN (%s) AND "Group_Members"."Chargify" = 1',
			$prods
		);

		$group = $member->getManyManyComponents('Groups', $filter, null, null, 1);
		if (!$group = $group->First()) return;

		$conn = ChargifyService::instance()->getConnector();
		return $conn->getSubscriptionsByID($group->SubscriptionID);
	}

	/**
	 * @return DataObjectSet
	 */
	public function Products() {
		$products = $this->data()->Products();
		$service  = ChargifyService::instance();
		$conn     = $service->getConnector();
		$sub      = $this->getChargifySubscription();
		$result   = new DataObjectSet();

		if (!count($products)) return;

		foreach ($products as $link) {
			if (!$product = $conn->getProductByID($link->ProductID)) {
				continue;
			}

			$data = $service->getCastedProductDetails($product);

			if ($sub) {
				if ($sub->product->id == $product->id) {
					$data->setField('Active', true);
				} else {
					$link = Controller::join_links(
						$this->Link(), 'upgrade', $product->id
					);
					$link = SecurityToken::inst()->addToUrl($link);

					$data->setField('ActionTitle', 'Change subscription');
					$data->setField('ActionLink', $link);
				}
			} else {
				$link = Controller::join_links(
					$this->Link(), 'subscribe', $product->id
				);
				$link = SecurityToken::inst()->addToUrl($link);

				$data->setField('ActionTitle', 'Subscribe');
				$data->setField('ActionLink', $link);
			}

			$result->push($data);
		}

		return $result;
	}

	/**
	 * @return Date
	 */
	public function NextBillingDate() {
		if ($sub = $this->getChargifySubscription()) {
			return DBField::create('Date', $sub->next_assessment_at);
		}
	}

	/**
	 * @return string
	 */
	public function UpdateBillingLink() {
		if (!$sub = $this->getChargifySubscription()) return;

		$base  = ChargifyConfig::get_url();
		$key   = ChargifyConfig::get_shared_key();
		$token = substr(sha1("update_payment--$sub->id--$key"), 0, 10);

		return Controller::join_links($base, 'update_payment', $sub->id, $token);
	}

}