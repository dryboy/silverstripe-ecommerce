<?php


/**
 * @description: each order has an address: a Shipping and a Billing address
 * This is a base-class for both.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: address
 *
 **/

class OrderAddress extends DataObject {


	/**
	 *Do the goods need to he shipped and if so,
	 * do we allow these goods to be shipped to a different address than the billing address?
	 *
	 *@var Boolean
	 **/
	protected static $use_separate_shipping_address = false;
		static function set_use_separate_shipping_address($b){self::$use_separate_shipping_address = $b;}
		static function get_use_separate_shipping_address(){return self::$use_separate_shipping_address;}

	/**
	 * In case you have some conflicts in the class / IDs for formfields then you can use this variable
	 * to add a few characters in front of the classes / IDs
	 *@var String $s
	 **/
	protected static $field_class_and_id_prefix = "";
		static function set_field_class_and_id_prefix($s){self::$field_class_and_id_prefix = $s;}
		static function get_field_class_and_id_prefix(){return self::$field_class_and_id_prefix;}

	//e.g. http://www.nzpost.co.nz/Cultures/en-NZ/OnlineTools/PostCodeFinder
	static function get_postal_code_url() {$sc = DataObject::get_one('SiteConfig'); if($sc) {return $sc->PostalCodeURL;}  }

	static function get_postal_code_label() {$sc = DataObject::get_one('SiteConfig'); if($sc) {return $sc->PostalCodeLabel;}  }

	public static $singular_name = "Order Address";
		function i18n_singular_name() { return _t("OrderAddress.ORDERADDRESS", "Order Address");}

	public static $plural_name = "Order Addresses";
		function i18n_plural_name() { return _t("OrderAddress.ORDERADDRESSES", "Order Addresses");}

	/**
	 *
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", $fields->dataFieldByName("OrderID")->performReadonlyTransformation());
		return $fields;
	}

	/**
	 *
	 *@return FieldSet
	 **/
	function scaffoldSearchFields(){
		$fields = parent::scaffoldSearchFields();
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order Number"));
		return $fields;
	}

	/**
	 *@return Fieldset
	 **/
	function getEcommerceFields() {
		$fields = new FieldSet();
		return $fields;
	}

	/**
	 *put together a textfield for a postal code field
	 *@param String $name - name of the field
	 *@return TextField
	 **/
	protected function getPostalCodeField($name) {
		$field = new TextField($name, _t('OrderAddress.POSTALCODE','Postal Code'));
		if(self::get_postal_code_url()){
			$field->setRightTitle('<a href="'.self::get_postal_code_url().'" id="'.self::get_field_class_and_id_prefix().$name.'Link" class="'.self::get_field_class_and_id_prefix().'postalCodeLink">'.self::get_postal_code_label().'</a>');
		}
		return $field;
	}

	/**
	 *put together a dropdown for the region field
	 *@param String $name - name of the field
	 *@return DropdownField
	 **/
	protected function getRegionField($name) {
		if(EcommerceRegion::show()) {
			$regionsForDropdown = EcommerceRegion::list_of_allowed_entries_for_dropdown();
			$regionField = new DropdownField($name,EcommerceRegion::$singular_name, $regionsForDropdown);
		}
		else {
			//adding region field here as hidden field to make the code easier below...

		}
		if(count($regionsForDropdown) < 2) {
			$regionField = $regionField->performReadonlyTransformation();
			if(count($regionsForDropdown) < 1) {
				$regionField = new HiddenField($name, '', 0);
			}
		}
		$regionField->addExtraClass(self::get_field_class_and_id_prefix().'ajaxRegionField');
		return $regionField;
	}

	/**
	 *put together a dropdown for the region field
	 *@param String $name - name of the field
	 *@return DropdownField
	 **/
	protected function getCountryField($name) {
		$countriesForDropdown = EcommerceCountry::list_of_allowed_entries_for_dropdown();
		$countryField = new DropdownField($name, EcommerceCountry::$singular_name, $countriesForDropdown, EcommerceCountry::get_country());
		if(count($countriesForDropdown) < 2) {
			$countryField = $countryField->performReadonlyTransformation();
			if(count($countriesForDropdown) < 1) {
				$countryField = new HiddenField($name, '', "not available");
			}
		}
		$countryField->addExtraClass(self::get_field_class_and_id_prefix().'ajaxCountryField');
		return $countryField;
	}

	/**
  	 * Saves region - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
  	 * @param Integer -  RegionID
  	 **/
	public function SetRegion($regionID) {
		$this->RegionID = $regionID;
		$this->ShippingRegionID = $regionID;
		$this->write();
	}

	/**
  	 * Saves country - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
  	 * @param String - CountryCode - e.g. NZ
  	 **/
	public function SetCountry($countryCode) {
		$this->Country = $countryCode;
		$this->ShippingCountry = $countryCode;
		$this->write();
	}

	/**
	 * Copies the last address used by the member.
	 *@return DataObject (OrderAddress / ShippingAddress / BillingAddfress)
	 **/
	public function FillWithLastAddressFromMember($member = null) {
		if(!$member) {
			//cant use "Current Member" here, because the order might be created by the Shop Admin...
			$member = $this->getMemberFromOrder();
		}
		if($member) {
			$oldAddress = $this->previousAddressFromMember($member);
			if($oldAddress) {
				if($this instanceOf BillingAddress) {
					$prefix = "";
				}
				elseif($this instanceOf ShippingAddress) {
					$prefix = "Shipping";
				}
				$fieldNameArray = $this->getFieldNameArray($prefix);
				foreach($fieldNameArray as $field) {
					if(!$this->$field && isset($oldAddress->$field)) {
						$this->$field = $oldAddress->$field;
					}
				}
			}
			//copy data from  member
			if(!$prefix) {
				$this->Email = $member->Email;
			}
			$fieldNameArray = array("FirstName" => $prefix."FirstName", "Surname" => $prefix."Surname");
			foreach($fieldNameArray as $memberField => $fieldName) {
				//NOTE, we always override the Billing Address (which does not have a prefix)
				if(!$this->$fieldName || !$prefix) {$this->$fieldName = $member->$memberField();}
			}
		}
		$this->write();
		return $this;
	}

	/**
	 * Finds the last address used by this member
	 *@return DataObject (OrderAddress / ShippingAddress / BillingAddress)
	 **/
	protected function previousAddressFromMember($member = null) {
		if($member) {
			$fieldName = $this->ClassName."ID";
			$orders = DataObject::get(
				"Order",
				"\"MemberID\" = '".$member->ID."' AND \"$fieldName\" <> ".$this->ID,
				"\"Created\" DESC ",
				$join = null,
				$limit = "1"
			);
			if($orders) {
				$order = $orders->First();
				if($order  && $order->ID) {
					return DataObject::get_one($this->ClassName, "\"OrderID\" = '".$order->ID."'");
				}
			}
		}
	}

	/**
	 * find the member associated with the current Order.
	 *@return DataObject (Member)
	 **/
	protected function getMemberFromOrder() {
		if($this->OrderID) {
			if($order = $this->Order()) {
				if($order->MemberID) {
					return DataObject::get_by_id("Member", $order->MemberID);
				}
			}
		}
	}


	/**
	 *@param String - $prefix = either "" or "Shipping"
	 *@return array of fields for an Order DataObject
	 **/
	protected function getFieldNameArray($prefix = '') {
		$fieldNameArray = array(
			"Email",
			"FirstName",
			"Surname",
			"Address",
			"Address2",
			"City",
			"PostalCode",
			"RegionID",
			"Country",
			"Phone"
		);
		if($prefix) {
			foreach($fieldNameArray as $key => $value) {
				$fieldNameArray[$key] = $prefix.$value;
			}
		}
		return $fieldNameArray;
	}

}

