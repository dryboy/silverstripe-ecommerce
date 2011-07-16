<?php

/**
 * @description: each order has a billing address.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: address
 *
 **/

class BillingAddress extends OrderAddress {

	static $db = array(
		'Prefix' => 'Varchar(10)',
		'FirstName' => 'Varchar(100)',
		'Surname' => 'Varchar(100)',
		'Address' => 'Varchar(200)',
		'Address2' => 'Varchar(200)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'Country' => 'Varchar(4)',
		'Phone' => 'Varchar(200)',
		'MobilePhone' => 'Varchar(200)',
		'Email' => 'Varchar'
	);

	/**
	 * HAS_ONE =array(ORDER => ORDER);
	 * we place this relationship here
	 * (rather than in the parent class: OrderAddress)
	 * because that makes for a cleaner relationship
	 * (otherwise we ended up with a "has two" relationship in Order)
	 **/
	static $has_one = array(
		"Order" => "Order",
		"Region" => "EcommerceRegion"
	);

	static $indexes = array(
		// "SearchFields" => "fulltext (FirstName, Surname, Address, Address2, City, PostalCode, Email)"
		array(
			'name' => 'SearchFields',
			'type' => 'fulltext',
			'value' => 'FirstName, Surname, Address, Address2, City, PostalCode, Email'
		)
	);

	public static $casting = array(
		"FullCountryName" => "Varchar(200)"
	);

	public static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		"Email" => "PartialMatchFilter",
		"FirstName" => "PartialMatchFilter",
		"Surname" => "PartialMatchFilter",
		"Address" => "PartialMatchFilter",
		"City" => "PartialMatchFilter",
		"Country" => "PartialMatchFilter"
	);

	public static $summary_fields = array(
		"Order.Title",
		"Surname",
		"City"
	);

	public static $singular_name = "Billing Address";
		function i18n_singular_name() { return _t("OrderAddress.BILLINGADDRESS", "Billing Address");}

	public static $plural_name = "Billing Addresses";
		function i18n_plural_name() { return _t("OrderAddress.BILLINGADDRESSES", "Billing Addresses");}

	/**
	 *
	 *@return String
	 **/
	function FullCountryName() {
		return EcommerceRegion::get_title($this->Country);
	}

	/**
	 *@return Fieldset
	 **/
	public function getFields() {
		$fields = parent::getEcommerceFields();
		$billingFields = new CompositeField(
			new HeaderField(_t('OrderAddress.BILLINGDETAILS','Billing Details'), 3),
			new EmailField('Email', _t('OrderAddress.EMAIL','Email')),
			new TextField('FirstName', _t('OrderAddress.FIRSTNAME','First Name')),
			new TextField('Surname', _t('OrderAddress.SURNAME','Surname')),
			new TextField('Address', _t('OrderAddress.ADDRESS','Address')),
			new TextField('Address2', _t('OrderAddress.ADDRESS2','&nbsp;')),
			new TextField('City', _t('OrderAddress.CITY','City')),
			$this->getPostalCodeField("PostalCode"),
			$this->getRegionField("RegionID"),
			$this->getCountryField("Country"),
			new TextField('Phone', _t('OrderAddress.PHONE','Phone'))
		);
		$billingFields->SetID('BillingFields');
		$fields->push($billingFields);
		$this->extend('augmentEcommerceBillingAddressFields', $fields);
		return $fields;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getRequiredFields() {
		$requiredFieldsArray = array(
			'Email',
			'FirstName',
			'Surname',
			'Address',
			'City'
		);
		$this->extend('augmentEcommerceBillingAddressRequiredFields', $requiredFieldsArray);
		return $requiredFieldsArray;
	}

	function populateDefaults() {
		parent::populateDefaults();
		$this->Country = EcommerceCountry::get_country();
	}

}
