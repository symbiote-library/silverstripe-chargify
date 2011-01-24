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
		$service   = ChargifyService::instance();
		$connector = $service->getConnector();

		foreach ($connector->getAllProducts() as $product) {
			$data = $service->getCastedProductDetails($product);

			$active = (bool) $selected->find('ProductID', $product->id);
			$data->setField('ActiveField', new CheckboxField(
				"{$this->name}[$product->id]", '', $active
			));

			$groups = DataObject::get('Group', sprintf(
				'"ChargifyProductID" = %d', $product->id
			));
			$data->setField('Groups', (
				$groups ? implode(', ', $groups->map()) : ''
			));

			$products->push($data);
		}

		return $products;
	}

	/**
	 * @return string
	 */
	public function ManageLink() {
		return Controller::join_links(ChargifyConfig::get_url(), 'products');
	}

}