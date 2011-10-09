<?php

/**
 *@description: adds a few functions to SiteTree to give each page some e-commerce related functionality.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package integration
 *
 **/


class EcommerceSiteTreeExtension extends DataObjectDecorator {

	function extraStatics(){
		return array(
			'casting' => array(
				"EcommerceMenuTitle" => "Varchar"
			)
		);
	}

	/**
	 *@return Boolean
	 **/
	function ShopClosed() {
		$siteConfig = DataObject::get_one("SiteConfig");
		return $siteConfig->ShopClosed;
	}

	/**
	 *@return Order
	 **/
	function Cart() {
		return ShoppingCart::current_order();
	}

	/**
	 *@return Integer
	 **/
	public function NumItemsInCart() {
		$order = ShoppingCart::current_order();
		if($order) {
			return $order->TotalItems();
		}
		return 0;
	}

	/**
	 *@return String (HTML Snippet)
	 **/
	function getEcommerceMenuTitle() {
		return $this->owner->getMenuTitle();
	}
	function EcommerceMenuTitle(){return $this->getEcommerceMenuTitle();}

}

class EcommerceSiteTreeExtension_Controller extends Extension {

	/*
	 *TO DO: this even seemed to be called then the CMS is opened
	 **/

	function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		$checkoutPages = DataObject::get("CartPage");
		$jsArray = Array();
		if($checkoutPages) {
			foreach($checkoutPages as $page) {
				$jsArray[] = '
				jQuery("a[href=\''.str_replace('/', '\/', Convert::raw2js($page->Link())).'\']").each(
					function(i, el) {
						var oldText = jQuery(el).text();
						var newText = \''.Convert::raw2js($page->getEcommerceMenuTitle()).'\'
						if(oldText == \''.$page->MenuTitle.'\' && newText) {
							jQuery(el).html(newText)
						}
					}
				);';
			}
		}
		if(count($jsArray)) {
			Requirements::customScript(
				'
				jQuery(document).ready(
					function() {
						'.implode("", $jsArray).'
					}
				);'
				,"getEcommerceMenuTitle"
			);
		}
	}


	/**
	 *@return string
	 **/
	function SimpleCartLinkAjax() {
		return ShoppingCart_Controller::get_url_segment()."/showcart/";
	}

	/**
	 *@return Boolean
	 **/
	public function MoreThanOneItemInCart() {
		return $this->owner->NumItemsInCart() > 1;
	}

	/**
	 *@return Float
	 **/
	public function SubTotalCartValue() {
		$order = ShoppingCart::current_order();
		return $order->SubTotal;
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function AccountPageLink() {
		return AccountPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function CheckoutLink() {
		return CheckoutPage::find_link();
	}
	/**
	 *@return String (URLSegment)
	 **/
	public function CartPage() {
		return CartPage::find_link();
	}

	/**
	 *@return String (URLSegment)
	 **/
	public function OrderConfirmationPage() {
		return OrderConfirmationPage::find_link();
	}

}
