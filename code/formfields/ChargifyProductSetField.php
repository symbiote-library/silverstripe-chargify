<?php
/**
 * @package silverstripe-chargify
 */
class ChargifyProductSetField extends FormField {

	public function FieldHolder() {
		return $this->renderWith('ChargifyProductSetField');
	}

	public function saveInto(DataObject $record) {
		$set = $record->{$this->name}();

		foreach ($set as $item) {
			$item->delete();
		}

		if ($this->value) foreach ($this->value as $id => $bool) {
			if (!$bool) continue;

			$product = new ChargifyProductLink();
			$product->update(array(
				'ParentID'  => $record->ID,
				'ProductID' => $id
			));
			$product->write();
		}
	}

	/**
	 * @return DataObjectSet
	 */
	public function Products() {
		$products  = new DataObjectSet();
		$selected  = $this->form->getRecord()->{$this->name}();
		$connector = ChargifyService::instance()->getConnector();
		$currency  = ChargifyConfig::get_currency();

		foreach ($connector->getAllProducts() as $product) {
			$active = (bool) $selected->find('ProductID', $product->id);
			$groups = DataObject::get('Group', sprintf(
				'"ChargifyProductID" = %d', $product->id
			));

			$link = Controller::join_links(
				ChargifyConfig::get_url(), 'products', $product->id
			);

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

			$products->push(new ArrayData(array(
				'Title' => $product->name,
				'ActiveField' => new CheckboxField("{$this->name}[$product->id]", '', $active),
				'Family' => $product->product_family->name,
				'Groups' => $groups ? implode(', ', $groups->map()) : '',
				'ChargifyLink' => $link,
				'Price' => $price,
				'Interval' => $product->interval,
				'IntervalUnit' => $product->interval_unit,
				'InitialCharge' => $initial,
				'TrialPrice' => $trial,
				'TrialInterval' => $product->trial_interval,
				'TrialIntervalUnit' => $product->trial_interval_unit
			)));
		}

		return $products;
	}

	/**
	 * @return string
	 */
	public function ManageLink() {
		return Controller::join_links(ChargifyConfig::get_url(), 'dashboard');
	}

}