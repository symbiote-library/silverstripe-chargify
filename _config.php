<?php
/**
 * @package silverstripe-chargify
 */

Director::addRules(20, array(
	'POST chargify/webhook' => 'ChargifyWebhookController'
));

Object::add_extension('SiteConfig', 'ChargifyConfig');
Object::add_extension('Group', 'ChargifyGroupExtension');
Object::add_extension('Member', 'ChargifyMemberExtension');