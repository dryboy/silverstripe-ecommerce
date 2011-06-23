<?php

/**
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class OrderConfirmationPage extends CartPage{

	public static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'OrderConfirmationPage'");
	}

	/**
	 * Returns the link or the Link to the OrderConfirmationPage page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('OrderConfirmationPage', "\"ClassName\" = 'OrderConfirmationPage'")) {
			return $page->Link();
		}
		return CartPage::find_link();
	}

}

class OrderConfirmationPage_Controller extends CartPage_Controller{

	function init() {
		parent::init();
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
	}

}



