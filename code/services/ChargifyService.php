<?php
/**
 * Encapsulates commonly used Chargify functionality.
 *
 * @package silverstripe-chargify
 */
class ChargifyService {

	protected static $instance;

	/**
	 * @return ChargifyService
	 */
	public static function instance() {
		return self::$instance ? self::$instance : self::$instance = new self();
	}

	/**
	 * @return ChargifyConnector
	 */
	public function getConnector() {
		return new CachedChargifyConnector(
			false, ChargifyConfig::get_domain(), ChargifyConfig::get_api_key()
		);
	}

	/**
	 * Returns a casted array of product details from a {@link ChargifyProduct}
	 * object.
	 *
	 * @param  ChargifyProduct $product
	 * @return ArrayData
	 */
	public function getCastedProductDetails(ChargifyProduct $product) {
		$link = Controller::join_links(
			ChargifyConfig::get_url(), 'products', $product->id
		);

		$currency = ChargifyConfig::get_currency();
		$price = DBField::create('Money', array(
			'Amount'   => ((int) $product->price_in_cents) / 100,
			'Currency' => $currency
		));
		$initial = DBField::create('Money', array(
			'Amount'   => ((int) $product->initial_charge_in_cents) / 100,
			'Currency' => $currency
		));
		$trial = DBField::create('Money', array(
			'Amount'   => ((int) $product->trial_price_in_cents) / 100,
			'Currency' => $currency
		));

		return new ArrayData(array(
			'Name'              => $product->name,
			'Family'            => $product->product_family->name,
			'ChargifyLink'      => $link,
			'Price'             => $price,
			'Interval'          => $product->interval,
			'IntervalUnit'      => $product->interval_unit,
			'InitialCharge'     => $initial,
			'TrialPrice'        => $trial,
			'TrialInterval'     => $product->trial_interval,
			'TrialIntervalUnit' => $product->trial_interval_unit
		));
	}

}