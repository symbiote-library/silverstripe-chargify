<?php
/**
 * @package silverstripe-chargify
 */

Object::add_extension('SiteConfig', 'ChargifyConfig');
Object::add_extension('Group', 'ChargifyGroupExtension');
Object::add_extension('Member', 'ChargifyMemberExtension');