<?php
/**
 * A page which allows a user to subscribe to a chargify product, or update
 * their subscription.
 *
 * @package silverstripe-chargify
 */
class ChargifySubscriptionPage extends Page {

	public static $db = array(
		'UpgradeType'          => 'Enum("Prorated, Simple", "Prorated")',
		'UpgradeIncludeTrial'  => 'Boolean',
		'UpgradeInitialCharge' => 'Boolean'
	);

	public static $has_many = array(
		'Products' => 'ChargifyProductLink'
	);

	public static $defaults = array(
		'UpgradeType' => 'Prorated'
	);

	protected $message;

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Content', new Tab('Subscriptions'), 'Metadata');
		$fields->addFieldToTab('Root.Content.Subscriptions', new ChargifyProductSetField(
			'Products', 'Available Subscription Types'
		));

		$fields->addFieldsToTab('Root.Content.Advanced', array(
			new HeaderField('UpgradeHeader', 'Product Upgrades/Downgrades'),
			new OptionSetField('UpgradeType', '', array(
				'Prorated' => 'Do a prorated upgrade',
				'Simple'   => 'Just change the product'
			)),
			new CheckboxField('UpgradeIncludeTrial',
				'Include initial trial in prorated upgrade?'),
			new CheckboxField('UpgradeInitialCharge',
				'Include initial charges in prorated upgrade?')
		));

		return $fields;
	}

}

class ChargifySubscriptionPage_Controller extends Page_Controller {

	public static $allowed_actions = array(
		'creditcard',
		'transactions',
		'aftersignup',
		'upgrade',
		'cancel',
		'reactivate'
	);

	protected $subscription;

	public function init() {
		parent::init();

		if (!Member::currentUserID()) {
			return Security::permissionFailure($this, array(
				'default' => 'You must be logged in to manage your subscription.'
			));
		}
	}

	public function creditcard() {
		if (!$subscription = $this->getChargifySubscription(300)) {
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
		if (!$subscription = $this->getChargifySubscription(300)) {
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
	 * Handles the user returning from a chargify signup.
	 */
	public function aftersignup($request) {
		$id = $request->getVar('subscription_id');

		if (!ctype_digit($id)) {
			return $this->httpError(400, 'No subscription ID.');
		}

		$conn = ChargifyService::instance()->getConnector();
		$conn->setCacheExpiry(-1);

		if (!$subscription = $conn->getSubscriptionsByID($id)) {
			return $this->httpError(404, 'Invalid subscription ID.');
		}

		// Check if the subscription is already linked.
		$link = DataObject::get_one('ChargifySubscriptionLink', sprintf(
			'"SubscriptionID" = %d', $id
		));

		if ($link) {
			return $this->httpError(404, 'The subscription has already been activated.');
		}

		// Ensure the page ID and member ID are the current ones.
		list($memberId, $pageId) = explode('-', $subscription->customer->reference);

		if ($memberId != Member::currentUserID()) {
			return $this->httpError(403, 'Incorrect member ID');
		}

		if ($pageId != $this->ID) {
			return $this->httpError(403, 'Incorrect page ID.');
		}

		$memberLink = DataObject::get_one('ChargifyCustomerLink', sprintf(
			'"CustomerID" = %d', $subscription->customer->id
		));

		if (!$memberLink) {
			$memberLink = new ChargifyCustomerLink();
			$memberLink->CustomerID = $subscription->customer->id;
			$memberLink->MemberID   = Member::currentUserID();
			$memberLink->write();
		}

		// Create the subscription link.
		$subscriptionLink = new ChargifySubscriptionLink();
		$subscriptionLink->SubscriptionID = $subscription->id;
		$subscriptionLink->MemberID       = Member::currentUserID();
		$subscriptionLink->PageID         = $this->ID;
		$subscriptionLink->write();

		// And add the member to the groups.
		Member::currentUser()->chargifySubscribe($subscription);

		Session::set("ChargifySubscriptionPage.{$this->ID}", array(
			'flush'   => true,
			'message' => 'Your subscription has been created.'
		));
		return $this->redirect($this->Link());
	}

	/**
	 * Changes the product the subscription is attached to.
	 *
	 * @return array
	 */
	public function upgrade($request) {
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		if (!$this->HasActiveSubscription()) {
			return $this->httpError(404);
		}

		$subscription = $this->getChargifySubscription();
		$connector    = ChargifyService::instance()->getConnector();
		$product      = $request->param('ID');
		$products     = $this->data()->Products()->map('ID', 'ProductID');

		if (!in_array($product, $products)) {
			return $this->httpError(404, 'Invalid product ID.');
		}

		$product = $connector->getProductByID($product);

		if ($this->UpgradeType == 'Simple') {
			$connector->updateSubscriptionProduct($subscription->id, $product);
		} else {
			$connector->updateSubscriptionProductProrated(
				$subscription->id,
				$product,
				$this->UpgradeIncludeTrial,
				$this->UpgradeInitialCharge
			);
		}

		Session::set("ChargifySubscriptionPage.{$this->ID}", array(
			'flush'   => true,
			'message' => 'Your subscription has been updated.'
		));
		return $this->redirectBack();
	}

	/**
	 * Cancels the users current subscription.
	 *
	 * @return string
	 */
	public function cancel($request) {
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		if (!$this->HasActiveSubscription()) {
			return $this->httpError(404);
		}

		$subscription = $this->getChargifySubscription();

		$conn = ChargifyService::instance()->getConnector();
		$conn->cancelSubscription($subscription->id, null);

		// Remove all group relationships.
		Member::currentUser()->chargifyUnsubscribe($subscription);

		Session::set("ChargifySubscriptionPage.{$this->ID}", array(
			'flush'   => true,
			'message' => 'Your subscription has been canceled.'
		));
		return $this->redirectBack();
	}

	/**
	 * Reactivates a dead subscription and optionally points it to a new product.
	 */
	public function reactivate($request) {
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		if ($this->HasActiveSubscription()) {
			return $this->httpError(404);
		}

		if (!$subscription = $this->getChargifySubscription()) {
			return $this->httpError(404);
		}

		$connector    = ChargifyService::instance()->getConnector();
		$subscription = $connector->reactivateSubscription($subscription->id);

		Member::currentUser()->chargifySubscribe($subscription);

		Session::set("ChargifySubscriptionPage.{$this->ID}", array(
			'flush'   => true,
			'message' => 'Your subscription has been re-activated.'
		));
		return $this->redirectBack();
	}

	/**
	 * If the current member has a subscription to one of the products attached
	 * to this page, return it.
	 *
	 * @param  int $expiry
	 * @return ChargifySubscription
	 */
	public function getChargifySubscription($expiry = null) {
		if ($this->subscription !== null) {
			return $this->subscription;
		}

		if (!$member = Member::currentUser()) return $this->subscription = false;

		$link = DataObject::get_one('ChargifySubscriptionLink', sprintf(
			'"MemberID" = %d AND "PageID" = %d',
			Member::currentUserID(), $this->ID
		));

		if (!$link) return $this->subscription = false;

		$conn  = ChargifyService::instance()->getConnector();

		if (Session::get("ChargifySubscriptionPage.{$this->ID}.flush")) {
			Session::clear("ChargifySubscriptionPage.{$this->ID}.flush");
			$conn->setCacheExpiry(-1);
		} elseif ($expiry) {
			$conn->setCacheExpiry($expiry);
		}

		$sub = $conn->getSubscriptionsByID($link->SubscriptionID);

		return $this->subscription = $sub;
	}

	/**
	 * @return DataObjectSet
	 */
	public function Products() {
		$products = $this->data()->Products();
		$member   = Member::currentUser();
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

			if ($this->HasActiveSubscription()) {
				if ($sub->product->id == $product->id) {
					$data->setField('Active', true);
				} else {
					$link = Controller::join_links(
						$this->Link(), 'upgrade', $product->id
					);
					$link = SecurityToken::inst()->addToUrl($link);

					$data->setField('ActionTitle', 'Change subscription');
					$data->setField('ActionLink', $link);
					$data->setField('ActionConfirm', true);
				}
			} else {
				if ($sub) {
					if ($sub->product->id == $product->id) {
						$link = SecurityToken::inst()->addToUrl($this->Link('reactivate'));

						$data->setField('ActionTitle', 'Re-activate');
						$data->setField('ActionLink', $link);
						$data->setField('ActionConfirm', true);
					}
				} else {
					$link = Controller::join_links(
						ChargifyConfig::get_url(),
						'h', $product->id,
						'subscriptions/new',
						'?first_name=' . urlencode($member->FirstName),
						'?last_name='  . urlencode($member->Surname),
						'?email='      . urlencode($member->Email),
						'?reference='  . urlencode("{$member->ID}-{$this->ID}")
					);

					$data->setField('ActionTitle', 'Subscribe');
					$data->setField('ActionLink', $link);
				}
			}

			$result->push($data);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function HasActiveSubscription() {
		if (!$subscription = $this->getChargifySubscription()) {
			return false;
		}

		return !in_array(
			$subscription->state, array('canceled', 'expired', 'suspended')
		);
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

	/**
	 * @return string
	 */
	public function CancelLink() {
		return SecurityToken::inst()->addToUrl($this->Link('cancel'));
	}

	/**
	 * Loads a message from the session if one is present.
	 *
	 * @return string
	 */
	public function Message() {
		if (!$this->message) {
			$this->message = Session::get("ChargifySubscriptionPage.{$this->ID}.message");
			Session::clear("ChargifySubscriptionPage.{$this->ID}.message");
		}

		return $this->message;
	}

}