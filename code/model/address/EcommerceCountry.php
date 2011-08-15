<?php

/**
 * @description: This class helps you to manage countries within the context of e-commerce.
 * For example: To what countries can be sold.
 * /dev/build/?resetecommercecountries=1 will reset the list of countries...
 *
 * @author nicolaas [at] sunnysideup.co.nz
 *
 * @package: ecommerce
 * @sub-package: address
 *
 **/

class EcommerceCountry extends EcommerceRegion {

	static $has_many = array(
		"Regions" => "EcommerceRegion"
	);

	public static $singular_name = "Country";
		function i18n_singular_name() { return _t("EcommerceCountry.COUNTRY", "Country");}

	public static $plural_name = "Countries";
		function i18n_plural_name() { return _t("EcommerceCountry.COUNTRIES", "Countries");}

	/**
	 * Should we automatically add all countries to EcommerceCountry dataObjects.
	 * The default value is YES, but in some cases you may want to do this yourself.
	 * In this case, use set_save_countries_in_database and set it to NO.
	 *@var Boolean
	 **/
	protected static $save_countries_in_database = true;
		static function set_save_countries_in_database($b) {self::$save_countries_in_database = $b;}
		static function get_save_countries_in_database() {return self::$save_countries_in_database;}

	/**
	 * Set $allowed_country_codes to allow sales to a select number of countries
	 *@param $a : array("NZ" => "NZ", "UK => "UK", etc...)
	 *@param $s : string - country code, e.g. NZ
	 **/
	protected static $allowed_country_codes = array();
		static function set_allowed_country_codes(array $a) {self::$allowed_country_codes = $a;}
		static function get_allowed_country_codes() {return self::$allowed_country_codes;}
		static function add_allowed_country_code(string $s) {self::$allowed_country_codes[$s] = $s;}
		static function remove_allowed_country_code(string $s) {unset(self::$allowed_country_codes[$s]);}


	/**
	 *This function exists as a shortcut.  If there is only ONE allowed country code then a lot of checking of countries
	 * can be avoided.
	 *@return String - countrycode
	 **/
	static function get_fixed_country_code() {
		$a = self::get_allowed_country_codes();
		if(is_array($a) && count($a) == 1) {
			return array_shift($a);
		}
		return "";
	}


	/**
	 *@param $code String (Code)
	 *@return String ( name)
	 **/
	public static function find_title($code) {
		$options = Geoip::getCountryDropDown();
		// check if code was provided, and is found in the country array
		if($options && isset($options[$code])) {
			return $options[$code];
		}
		else {
			return "";
		}
	}

	/**
	 * This function works out the most likely country for the current order
	 *@return String - Country Code - e.g. NZ
	 **/
	public static function get_country() {
		$countryCode = '';
		//1. fixed country is first
		$countryCode = self::get_fixed_country_code();
		if(!$countryCode) {
			//2. check shipping address
			if($o = ShoppingCart::current_order()) {
				$countryCode = $o->Country();
			}
			//3. check GEOIP information
			if(!$countryCode) {
				$countryCode = @Geoip::visitor_country();
				//4 check default country set in GEO IP....
				if(!$countryCode) {
					$countryCode = Geoip::$default_country_code;
					//5. take the FIRST country from the get_allowed_country_codes
					if(!$countryCode) {
						$a = self::get_default_array();
						if(count($a)) {
							$countryCode = array_shift($a);
						}
					}
				}
			}
		}
		return $countryCode;
	}


	/**
	 *
	 *@return Array - array of CountryCode => Country
	 **/
	protected static function get_default_array() {
		$defaultArray = array();
		$countries = null;
		if($code = self::get_fixed_country_code()) {
			$defaultArray[$code] = self::find_title($code);
			return $defaultArray;
		}
		if(self::get_save_countries_in_database()) {
			$countries = DataObject::get("EcommerceCountry", "DoNotAllowSales <> 1");
			if($countries) {
				foreach($countries as $country) {
					$defaultArray[$country->Code] = $country->Name;
				}
			}
		}
		else {
			$defaultArray = Geoip::getCountryDropDown();
		}
		$allowed = self::get_allowed_country_codes();
		if(is_array($allowed) && count($allowed) && count($defaultArray)) {
			$newDefaultArray = array();
			foreach($allowed as $code) {
				if(!isset($defaultArray[$code])) {
					$newDefaultArray[$code] = $defaultArray[$code];
				}
			}
			$defaultArray = $newDefaultArray;
		}
		return $defaultArray;
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		if(self::get_save_countries_in_database()) {
			if(!DataObject::get("EcommerceCountry") || isset($_REQUEST["resetecommercecountries"])) {
				$array = Geoip::getCountryDropDown();
				foreach($array as $key => $value) {
					if(!DataObject::get_one("EcommerceCountry", "\"Code\" = '".$key."'")) {
						$obj = new EcommerceCountry();
						$obj->Code = $key;
						$obj->Name = $value;
						$obj->write();
					}
				}
			}
		}
	}

	//DYNAMIC LIMITATIONS

	/**
	 *these variables and methods allow to to "dynamically limit the countries available, based on, for example: ordermodifiers, item selection, etc....
	 * for example, if a person chooses delivery within Australasia (with modifier) - then you can limit the countries available to "Australasian" countries
	 *NOTE: these methods / variables below are IMPORTANT, because they allow the dropdown for the country to be limited for just that order
	 * @param $a = array should be country codes.e.g array("NZ", "NP", "AU");
	**/
	protected static $for_current_order_only_show_countries = array();
		static function set_for_current_order_only_show_countries(array $a) {
			if(count(self::$for_current_order_only_show_countries)) {
				//we INTERSECT here so that only countries allowed by all forces (modifiers) are added.
				self::$for_current_order_only_show_countries = array_intersect($a, self::$for_current_order_only_show_countries);
			}
			else {
				self::$for_current_order_only_show_countries = $a;
			}
		}
		//NOTE: this method below is more generic (does not have _countries part) so that it can be used by a method that is shared between EcommerceCountry and EcommerceRegion
		static function get_for_current_order_only_show_countries() {return self::$for_current_order_only_show_countries;}

	protected static $for_current_order_do_not_show_countries = array();
		static function set_for_current_order_do_not_show_countries(array $a) {
			//We MERGE here because several modifiers may limit the countries
			self::$for_current_order_do_not_show_countries = array_merge($a, self::$for_current_order_do_not_show_countries);
		}
		//NOTE: this method below is more generic (does not have _countries part) so that it can be used by a method that is shared between EcommerceCountry and EcommerceRegion
		static function get_for_current_order_do_not_show() {return self::$for_current_order_do_not_show_countries;}




}

