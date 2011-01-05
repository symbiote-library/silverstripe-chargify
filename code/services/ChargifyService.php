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

}