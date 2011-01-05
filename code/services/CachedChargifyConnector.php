<?php
/**
 * An extension to {@link ChargifyConnector} to cache the request results.
 *
 * @package silverstripe-chargify
 */
class CachedChargifyConnector extends ChargifyConnector {

	protected $cacheExpiry = 1800;

	/**
	 * @return int
	 */
	public function getCacheExpiry() {
		return $this->cacheExpiry;
	}

	/**
	 * @param int $expiry
	 */
	public function setCacheExpiry($expiry) {
		$this->cacheExpiry = $expiry;
	}

	protected function sendRequest($uri, $format = 'XML', $method = 'GET', $data = '') {
		$link  = Controller::join_links(ChargifyConfig::get_url(), $uri);
		$hash  = md5($link);
		$cache = Controller::join_links(TEMP_FOLDER, "chargify_$hash");

		$useCache = (
			!isset($_REQUEST['flush'])
			&& $method == 'GET'
			&& file_exists($cache)
			&& filemtime($cache) + $this->getCacheExpiry() > time()
		);

		if ($useCache) {
			return unserialize(file_get_contents($cache));
		}

		$result = parent::sendRequest($uri, $format, $method, $data);
		file_put_contents($cache, serialize($result));

		return $result;
	}

}