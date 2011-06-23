<?php

/**
 * @description: each order has a shipping address.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: address
 *
 **/

class ShippingAddress extends OrderAddress {


	static $db = array(
		'ShippingFirstName' => 'Text',
		'ShippingSurname' => 'Text',
		'ShippingAddress' => 'Text',
		'ShippingAddress2' => 'Text',
		'ShippingCity' => 'Text',
		'ShippingPostalCode' => 'Varchar(30)',
		'ShippingCountry' => 'Varchar(4)',
		'ShippingPhone' => 'Varchar(200)'
	);


	/**
	 * HAS_ONE =array(ORDER => ORDER);
	 * we place this relationship here
	 * (rather than in the parent class: OrderAddress)
	 * because that makes for a cleaner relationship
	 * (otherwise we ended up with a "has two" relationship in Order)
	 **/
	public static $has_one = array(
		"Order" => "Order",
		"ShippingRegion" => "EcommerceRegion"
	);

	static $indexes = array(
		// "SearchFields" => "fulltext (Address, Address2, City, PostalCode, Phone)"
		array(
			'name' => 'SearchFields',
			'type' => 'fulltext',
			'value' => 'ShippingAddress, ShippingAddress2, ShippingCity, ShippingPostalCode, ShippingPhone'
		)
	);

	public static $casting = array(
		"ShippingFullCountryName" => "Varchar(200)"
	);

	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"ShippingSurname" => "PartialMatchFilter",
		"ShippingAddress" => "PartialMatchFilter",
		"ShippingCity" => "PartialMatchFilter",
		"ShippingCountry" => "PartialMatchFilter"
	);

	public static $summary_fields = array(
		"Order.Title",
		"ShippingSurname",
		"ShippingCity"
	);

	public static $singular_name = "Shipping Address";
		function i18n_singular_name() { return _t("OrderAddress.SHIPPINGADDRESS", "Shipping Address");}

	public static $plural_name = "Shipping Addresses";
		function i18n_plural_name() { return _t("OrderAddress.SHIPPINGADDRESSES", "Shipping Addresses");}


	/**
	 * returns the full name for the shipping country code saved.
	 *@return String
	 **/
	function ShippingFullCountryName() {
		return EcommerceRegion::get_title($this->ShippingCountry);
	}

	/**
	 * Puts together the fields for the Order Form (and other front-end purposes).
	 *@return Fieldset
	 **/
	public function getFields() {
		$fields = parent::getEcommerceFields();
		if(OrderAddress::get_use_separate_shipping_address()) {
			$fields = parent::getEcommerceFields();
			$shippingFields = new CompositeField(
				new HeaderField(_t('OrderAddress.SENDGOODSTODIFFERENTADDRESS','Send goods to different address'), 3),
				new LiteralField('ShippingNote', '<p class="message warning">'._t('OrderAddress.SHIPPINGNOTE','Your goods will be sent to the address below.').'</p>'),
				new LiteralField('Help', '<p>'._t('OrderAddress.SHIPPINGHELP','You can use this for gift giving. No billing information will be disclosed to this address.').'</p>'),
				new TextField('ShippingName', _t('OrderAddress.NAME','Name')),
				new TextField('ShippingAddress', _t('OrderAddress.ADDRESS','Address')),
				new TextField('ShippingAddress2', _t('OrderAddress.ADDRESS2','')),
				new TextField('ShippingCity', _t('OrderAddress.CITY','City')),
				$this->getPostalCodeField("ShippingPostalCode"),
				$this->getRegionField("ShippingRegionID"),
				$this->getCountryField("ShippingCountry")
			);
			$shippingFields->SetID('ShippingFields');
			$fields->push($shippingFields);
		}
		else {
			$fields = new FieldSet();
		}
		$this->owner->extend('augmentEcommerceShippingAddressFields', $fields);
		return $fields;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getEcommerceRequiredFields() {
		$requiredFieldsArray = array();
		$this->owner->extend('augmentEcommerceShippingAddressRequiredFields', $requiredFieldsArray);
		return $requiredFieldsArray;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getRequiredFields() {
		$requiredFieldsArray = array(
			'ShippingAddress',
			'ShippingCity',
			'ShippingCountry'
		);
		return $requiredFieldsArray;
	}

	function populateDefaults() {
		parent::populateDefaults();
		$this->ShippingCountry = EcommerceCountry::get_country();
	}

}
