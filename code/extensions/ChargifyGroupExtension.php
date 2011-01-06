<?php
/**
 * Allows a {@link Group} to be linked to a chargify product, so any members who
 * purchase that product are added to the group.
 *
 * @package silverstripe-chargify
 */
class ChargifyGroupExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array(
			'db' => array(
				'ChargifyProductID' => 'Int'
			),
			'many_many_extraFields' => array(
				'Members' => array('Chargify' => 'Boolean')
			)
		);
	}

	public function updateCMSFields($fields) {
		$connector = ChargifyService::instance()->getConnector();
		$products  = array();

		foreach ($connector->getAllProducts() as $product) {
			$id     = $product->id;
			$family = $product->product_family->name;
			$name   = $product->name;

			if (!array_key_exists($family, $products)) {
				$products[$family] = array();
			}

			$products[$family][$id] = "($family) $name";
		}

		$fields->addFieldsToTab('Root.Chargify', array(
			new HeaderField('ChargifyHeader', 'Chargify Product Link'),
			new LiteralField('ChargifyNote', '<p>If you select a Chargify '  .
				'product below, then any members who purchase that product ' .
				'will be added to this group.</p>'),
			new GroupedDropdownField('ChargifyProductID',
				'', $products, null, null, true)
		));
	}

}