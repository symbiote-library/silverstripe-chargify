<?php
/**
 * Manages the API details for connecting to Chargify.
 *
 * @package silverstripe-chargify
 */
class ChargifyConfig extends DataObjectDecorator {

	/**
	 * @return string
	 */
	public function get_url() {
		return sprintf('https://%s.chargify.com/', self::get_domain());
	}

	/**
	 * @return string
	 */
	public static function get_domain() {
		if (defined('CHARGIFY_DOMAIN')) {
			return CHARGIFY_DOMAIN;
		} else {
			return SiteConfig::current_site_config()->ChargifyDomain;
		}
	}

	/**
	 * @return string
	 */
	public function get_api_key() {
		if (defined('CHARGIFY_API_KEY')) {
			return CHARGIFY_API_KEY;
		} else {
			return SiteConfig::current_site_config()->ChargifyApiKey;
		}
	}

	/**
	 * @return string
	 */
	public function get_shared_key() {
		if (defined('CHARGIFY_SHARED_KEY')) {
			return CHARGIFY_SHARED_KEY;
		} else {
			return SiteConfig::current_site_config()->ChargifySharedKey;
		}
	}

	/**
	 * @return string
	 */
	public function get_currency() {
		if (defined('CHARGIFY_CURRENCY')) {
			return CHARGIFY_CURRENCY;
		} else {
			return SiteConfig::current_site_config()->ChargifyCurrency;
		}
	}

	public function extraStatics() {
		return array('db' => array(
			'ChargifyDomain'    => 'Varchar(100)',
			'ChargifyApiKey'    => 'Varchar(20)',
			'ChargifySharedKey' => 'Varchar(20)',
			'ChargifyCurrency'  => 'Varchar(3)'
		));
	}

	public function updateCMSFields($fields) {
		$hasDomain = defined('CHARGIFY_DOMAIN');
		$hasKey    = defined('CHARGIFY_API_KEY');
		$hasShared = defined('CHARGIFY_SHARED_KEY');
		$hasCurr   = defined('CHARGIFY_CURRENCY');

		if (!$hasDomain) $fields->addFieldToTab('Root.Chargify', new TextField(
			'ChargifyDomain', 'Chargify Domain'
		));

		if (!$hasKey) $fields->addFieldToTab('Root.Chargify', new TextField(
			'ChargifyApiKey', 'Chargify API Key'
		));

		if (!$hasShared) $fields->addFieldToTab('Root.Chargify', new TextField(
			'ChargifySharedKey', 'Chargify Site Shared Key'
		));

		if (!$hasCurr) $fields->addFieldToTab('Root.Chargify', new TextField(
			'ChargifyCurrency', 'Chargify Currency'
		));

		$webhook = Director::absoluteURL('chargify/webhook');

		$fields->addFieldToTab('Root.Chargify', new HeaderField(
			'ChargifyWebhooksHeader', 'Chargify Webhooks'
		));
		$fields->addFieldToTab('Root.Chargify', new LiteralField(
			'ChargifyWebhookNote', sprintf('<p>Please enable webhooks on your ' .
			'chargify account for the following url: "%s".</p><p>At least '      .
			'the following events must be enabled: "Signup Success", '          .
			'"Subscription State Change", and "Subscription Product Change"'    .
			'.</p>', $webhook)
		));
	}

}