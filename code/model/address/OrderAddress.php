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
	 * standard SS static definition
	 */
	public static $singular_name = "Order Address";
		function i18n_singular_name() { return _t("OrderAddress.ORDERADDRESS", "Order Address");}


	/**
	 * standard SS static definition
	 */
	public static $plural_name = "Order Addresses";
		function i18n_plural_name() { return _t("OrderAddress.ORDERADDRESSES", "Order Addresses");}


	/**
	 * standard SS static definition
	 */
	public static $casting = array(
		"FullName" => "Text"
	);


	/**
	 * Do the goods need to he shipped and if so,
	 * do we allow these goods to be shipped to a
	 * different address than the billing address?
	 *
	 * @var Boolean
	 **/
	protected static $use_separate_shipping_address = false;
		public static function set_use_separate_shipping_address($b){self::$use_separate_shipping_address = $b;}
		public static function get_use_separate_shipping_address(){return self::$use_separate_shipping_address;}

	/**
	 * In determing the country/region from which the order originated.
	 * For, for example, tax purposes - we use the Billing Address (@see Order::Country).
	 * However, we can also choose the Shipping Address by setting this variable to TRUE
	 * @var Boolean
	 **/
	protected static $use_shipping_address_for_main_region_and_country = false;
		public static function set_use_shipping_address_for_main_region_and_country($b) {self::$use_shipping_address_for_main_region_and_country = $b;}
		public static function get_use_shipping_address_for_main_region_and_country() {return self::$use_shipping_address_for_main_region_and_country;}


	/**
	 * In case you have some conflicts in the class / IDs for formfields then you can use this variable
	 * to add a few characters in front of the classes / IDs
	 * @var String $s
	 **/
	protected static $field_class_and_id_prefix = "";
		public static function set_field_class_and_id_prefix($s){self::$field_class_and_id_prefix = $s;}
		public static function get_field_class_and_id_prefix(){return self::$field_class_and_id_prefix;}

	/**
	 * e.g. http://www.nzpost.co.nz/Cultures/en-NZ/OnlineTools/PostCodeFinder
	 * @return String
	 */
	public static function get_postal_code_url() {$sc = SiteConfig::current_site_config(); if($sc) {return $sc->PostalCodeURL;}  }

	/**
	 * e.g. "click here to check post code"
	 * @return String
	 */
	public static function get_postal_code_label() {$sc = SiteConfig::current_site_config(); if($sc) {return $sc->PostalCodeLabel;}  }

	/**
	 * returns the id of the MAIN country field for template manipulation.
	 * Main means the one that is used as the primary one (e.g. for tax purposes).
	 * @see OrderAddress::use_shipping_address_for_main_region_and_country
	 * @return String
	 */
	public static function get_country_field_ID() {
		if(self::get_use_shipping_address_for_main_region_and_country()) {
			return "ShippingCountry";
		}
		else {
			return "Country";
		}
	}

	/**
	 * returns the id of the MAIN region field for template manipulation.
	 * Main means the one that is used as the primary one (e.g. for tax purposes).
	 * @return String
	 */
	public static function get_region_field_ID() {
		if(self::get_use_shipping_address_for_main_region_and_country()) {
			return "ShippingRegion";
		}
		else {
			return "Region";
		}
	}


	/**
	 * There might be times when a modifier needs to make an address field read-only.
	 * In that case, this is done here.
	 *
	 * @var Array
	 */
	protected $readOnlyFields = array();

	/**
	 * sets a field to readonly state
	 * we use this when modifiers have been set that require a field to be a certain value
	 * for example - a PostalCode field maybe set in the modifier.
	 * @param String $fieldName
	 */
	function addReadOnlyField($fieldName) {
		$this->readOnlyFields[$fieldName] = $fieldName;
	}

	/**
	 * removes a field from the readonly state
	 * @param String $fieldName
	 */
	function removeReadOnlyField($fieldName) {
		unset($this->readOnlyFields[$fieldName]);
	}

	/**
	 * save edit status for speed's sake
	 * @var Boolean
	 */
	protected $_canEdit = null;

	/**
	 * save view status for speed's sake
	 * @var Boolean
	 */
	protected $_canView = null;


	/**
	 * standard SS method
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return true;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canView($member = null) {
		if($this->_canView === null) {
			$this->_canView = false;
			if($this->Order()) {
				if($this->Order()->exists()) {
					if($this->Order()->canView($member)) {
						$this->_canView = true;
					}
				}
			}
		}
		return $this->_canView;
	}

	/**
	 * Standard SS method
	 * This is an important method.
	 *
	 * @return Boolean
	 **/
	function canEdit($member = null) {
		if($this->_canEdit === null) {
			$this->_canEdit = false;
			if($this->Order()) {
				if($this->Order()->exists()) {
					if($this->Order()->canEdit($member)) {
						$this->_canEdit = true;
					}
				}
			}
		}
		return $this->_canEdit;
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
	 *
	 *
	 * @return FieldSet
	 */

	protected function getEcommerceFields() {
		return new FieldSet();
	}
	/**
	 * put together a textfield for a postal code field
	 * @param String $name - name of the field
	 * @return TextField
	 **/
	protected function getPostalCodeField($name) {
		$field = new TextField($name, _t('OrderAddress.POSTALCODE','Postal Code'));
		if(self::get_postal_code_url()){
			$field->setRightTitle('<a href="'.self::get_postal_code_url().'" id="'.self::get_field_class_and_id_prefix().$name.'Link" class="'.self::get_field_class_and_id_prefix().'postalCodeLink">'.self::get_postal_code_label().'</a>');
		}
		return $field;
	}

	/**
	 * put together a dropdown for the region field
	 * @param String $name - name of the field
	 * @return DropdownField
	 **/
	protected function getRegionField($name) {
		if(EcommerceRegion::show()) {
			$regionsForDropdown = EcommerceRegion::list_of_allowed_entries_for_dropdown();
			$regionField = new DropdownField($name,EcommerceRegion::$singular_name, $regionsForDropdown);
			if(count($regionsForDropdown) < 2) {
				$regionField = $regionField->performReadonlyTransformation();
				if(count($regionsForDropdown) < 1) {
					$regionField = new HiddenField($name, '', 0);
				}
			}

		}
		else {
			//adding region field here as hidden field to make the code easier below...
			$regionField = new HiddenField($name, '', 0);
		}
		$regionField->addExtraClass(self::get_field_class_and_id_prefix().'ajaxRegionField');
		return $regionField;
	}

	/**
	 * put together a dropdown for the country field
	 * @param String $name - name of the field
	 * @return DropdownField
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
	 * makes selected fields into read only using the $this->readOnlyFields array
	 *
	 * @param Object(FieldSet)
	 */
	protected function makeSelectedFieldsReadOnly(&$fields) {
		if(is_array($this->readOnlyFields) && count($this->readOnlyFields) ) {
			foreach($this->readOnlyFields as $readOnlyField) {
				if($oldField = $fields->fieldByName($readOnlyField)) {
					$fields->replaceField($readOnlyField, $oldField->performReadonlyTransformation());
				}
			}
		}
	}

	/**
	 * Saves region - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
	 * NOTE: do not call this method SetCountry as this has a special meaning! *
	 * @param Integer -  RegionID
	 **/
	public function SetRegionFields($regionID) {
		$this->RegionID = $regionID;
		$this->ShippingRegionID = $regionID;
		$this->write();
	}

	/**
	 * Saves country - both shipping and billing fields are saved here for convenience sake (only one actually gets saved)
	 * NOTE: do not call this method SetCountry as this has a special meaning!
	 * @param String - CountryCode - e.g. NZ
	 */
	public function SetCountryFields($countryCode) {
		$this->Country = $countryCode;
		$this->ShippingCountry = $countryCode;
		$this->write();
	}

	/**
	 * returns the full name of the person, e.g. "John Smith"
	 *
	 * @return String
	 */
	public function getFullName() {
		$fieldNameField = $this->prefix()."FirstName";
		$fieldFirst = $this->$fieldNameField;
		$lastNameField =  $this->prefix()."Surname";
		$fieldLast = $this->$lastNameField;
		return $fieldFirst.' '.$fieldLast;
	}
		public function FullName(){ return $this->getFullName();}



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

	/**
	 * returns the field prefix string for shipping addresses
	 * @return String
	 **/
	protected function prefix() {
		if($this instanceOf BillingAddress) {
			$prefix = "";
		}
		elseif($this instanceOf ShippingAddress) {
			$prefix = "Shipping";
		}
		return $prefix;
	}


	/**
	 *
	 * returns a DataObjectSet of all other Order Addresses related to the current Address
	 * @param Object (Member)
	 * @param Object (Order)
	 * @return null | DataObjectSet
	 */
	public function AllOtherMemberAddresses($member = null) {
		if(!$member) {
			$member = $this->getMemberFromOrder();
		}
		if($member) {
			$fieldName = $this->ClassName."ID";
			$orders = DataObject::get(
				"Order",
				"\"MemberID\" = '".$member->ID."' AND \"$fieldName\" <> ".intval($this->ID)-0,
				"\"Order\".\"ID\" DESC ",
				$join = null,
				$limit = "1"
			);
			if($orders) {
				$array = $orders->map($fieldName, $fieldName);
				if($array && count($array)) {
					return DataObject::get($this->ClassName, "\"OrderAddress\".\"ID\" IN (".implode(", ", $array).")");
				}
			}
		}
	}


	/**
	 * Copies the last address used by the member.
	 * @return DataObject (OrderAddress / ShippingAddress / BillingAddfress)
	 * @param Object (Member) $member
	 * @param Boolean $write - should the address be written
	 */
	public function FillWithLastAddressFromMember($member, $write = false) {
		$excludedFields = array("ID", "OrderID");
		$prefix = $this->prefix();
		if($member && $member->exists()) {
			$oldAddress = $this->previousAddressFromMember($member);
			if($oldAddress) {
				$fieldNameArray = $this->getFieldNameArray($prefix);
				foreach($fieldNameArray as $field) {
					if(!$this->$field && isset($oldAddress->$field) && !in_array($field, $excludedFields)) {
						$this->$field = $oldAddress->$field;
					}
				}
			}
			//copy data from  member
			if($this instanceOf BillingAddress) {
				$this->Email = $member->Email;
			}
			$fieldNameArray = array("FirstName" => $prefix."FirstName", "Surname" => $prefix."Surname");
			foreach($fieldNameArray as $memberField => $fieldName) {
				//NOTE, we always override the Billing Address (which does not have a prefix)
				if(!$this->$fieldName || $this instanceOf BillingAddress) {$this->$fieldName = $member->$memberField;}
			}
		}
		if($write) {
			$this->write();
		}
		return $this;
	}

	/**
	 * Finds the last address used by this member
	 * @param Object (Member)
	 * @return Null | DataObject (OrderAddress / ShippingAddress / BillingAddress)
	 **/
	protected function previousAddressFromMember($member = null) {
		if(!$member) {
			$member = $this->getMemberFromOrder();
		}
		if($member) {
			$fieldName = $this->ClassName."ID";
			$orders = DataObject::get(
				"Order",
				"\"MemberID\" = '".$member->ID."' AND \"$fieldName\" <> ".intval($this->ID)-0,
				"\"Order\".\"ID\" DESC ",
				$join = null,
				$limit = "1"
			);
			if($orders) {
				$order = $orders->First();
				if($order  && $order->exists()) {
					return DataObject::get_by_id($this->ClassName, $order->$fieldName);
				}
			}
		}
	}

	/**
	 * find the member associated with the current Order.
	 * @return DataObject (Member)
	 **/
	protected function getMemberFromOrder() {
		if($order = $this->Order() && $order->exists()) {
			if($order->MemberID) {
				return DataObject::get_by_id("Member", $order->MemberID);
			}
		}
	}

}

