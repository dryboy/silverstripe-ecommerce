<?php
 /**
  * Product Group is a 'holder' for Products within the CMS
  * It contains functions for versioning child products
  *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: Products
 *
 **/

class ProductGroup extends Page {

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $db = array(
		"NumberOfProductsPerPage" => "Int",
		"LevelOfProductsToShow" => "Int",
		"DefaultSortOrder" => "Varchar(50)",
		"DefaultFilter" => "Varchar(50)",
		"DisplayStyle" => "Varchar(50)"
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $belongs_many_many = array(
		'Products' => 'Product'
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $defaults = array(
		"DefaultSortOrder" => "default",
		"DefaultFilter" => "default",
		"LevelOfProductsToShow" => 99
	);

	/**
	 * standard SS variable
	 * @static String
	 */
	public static $default_child = 'Product';

	/**
	 * standard SS variable
	 * @static String | Array
	 *
	 */
	public static $icon = 'ecommerce/images/icons/productgroup';

	/**
	 * @static Array
	 *
	 * List of sort options.  Each option has a key an array of Title and SQL.
	 *
	 * With it, we provide a bunch of methods to access and edit the options.
	 *
	 */
	protected static $sort_options = array(
			'default' =>  array(  "Title" => 'Default Order',  "SQL" => "\"Sort\" ASC, \"Title\" ASC"),
			'title' =>    array(  "Title" => 'Alphabetical',   "SQL" => "\"Title\" ASC"),
			'price' =>    array(  "Title" => 'Lowest Price',   "SQL" => "\"Price\" ASC, \"Title\" ASC"),
		);
		static function add_sort_option($key, $title, $sql){self::$sort_options[$key] = array("Title" => $title, "SQL" => $sql);}
		static function remove_sort_option($key){unset(self::$sort_options[$key]);}
		static function set_sort_options($a){self::$sort_options = $a;}
		static function get_sort_options(){return self::$sort_options;}
		protected function getDefaultSortKey(){
			if(isset(self::$sort_options["default"])) {
				return "default";
			}
			$keys = array_keys(self::$sort_options);
			return $keys[0];
		}
		//NON-STATIC
		public function getSortOptionsForDropdown(){
			$inheritTitle = _t("ProductGroup.INHERIT", "Inherit");
			$array = array("inherit" => $inheritTitle);
			if(is_array(self::$sort_options) && count(self::$sort_options)) {
				foreach(self::$sort_options as $key => $sort_option) {
					$array[$key] = $sort_option["Title"];
				}
			}
			return $array;
		}
		protected function getSortOptionSQL($key = ""){ // NOT STATIC
			if($key && isset(self::$sort_options[$key])) {
				return self::$sort_options[$key]["SQL"];
			}
			elseif(is_array(self::$sort_options) && count(self::$sort_options)) {
				$firstItem = array_shift(self::$sort_options);
				return $firstItem["SQL"];
			}
			else {
				return "\"Sort\" ASC";
			}
		}
		protected function getSortOptionTitle($key = ""){ // NOT STATIC
			if($key && isset(self::$sort_options[$key])) {
				return self::$sort_options[$key]["Title"];
			}
			elseif(is_array(self::$sort_options) && count(self::$sort_options)) {
				$firstItem = array_shift(self::$sort_options);
				return $firstItem["Title"];
			}
			else {
				return _t("ProductGroup.UNKNOWN", "UNKNOWN");
			}
		}

	/**
	 * @static Array
	 *
	 * List of filter options.  Each option has a key an array of Title and SQL.
	 *
	 * With it, we provide a bunch of methods to access and edit the options.
	 *
	 */
	protected static $filter_options = array(
			'default' => array(
				"Title" => 'All Searchable Products (default)',
				"SQL" => "\"ShowInSearch\"  = 1"
			),
			'featuredonly' => array(
				"Title" => 'Featured Only',
				"SQL" => "\"ShowInSearch\"  = 1 AND \"FeaturedProduct\" = 1"
			),
			'nonfeaturedonly' => array(
				"Title" => 'Non Featured Only',
				"SQL" => "\"ShowInSearch\"  = 1 AND \"FeaturedProduct\" = 0"
			)
		);
		static function add_filter_option($key, $title, $sql){self::$filter_options[$key] = array("Title" => $title, "SQL" => $sql);}
		static function remove_filter_option($key){unset(self::$filter_options[$key]);}
		static function set_filter_options($a){self::$filter_options = $a;}
		static function get_filter_options(){return self::$filter_options;}
		protected function getDefaultFilterKey(){
			if(isset(self::$filter_options["default"])) {
				return "default";
			}
			$keys = array_keys(self::$filter_options);
			return $keys[0];
		}
		//NON-STATIC
		public function getFilterOptionsForDropdown(){
			$inheritTitle = _t("ProductGroup.INHERIT", "Inherit");
			$array = array("inherit" => $inheritTitle);
			if(is_array(self::$filter_options) && count(self::$filter_options)) {
				foreach(self::$filter_options as $key => $filter_option) {
					$array[$key] = $filter_option["Title"];
				}
			}
			return $array;
		}
		protected function getFilterOptionSQL($key = ""){ // NOT STATIC
			if($key && isset(self::$filter_options[$key])) {
				return self::$filter_options[$key]["SQL"];
			}
			elseif(is_array(self::$filter_options) && count(self::$filter_options)) {
				$firstItem = array_shift(self::$filter_options);
				return $firstItem["SQL"];
			}
			else {
				return " \"ShowInSearch\" = 1";
			}
		}
		protected function getFilterOptionTitle($key = ""){ // NOT STATIC
			if($key && isset(self::$filter_options[$key])) {
				return self::$filter_options[$key]["Title"];
			}
			elseif(is_array(self::$filter_options) && count(self::$filter_options)) {
				$firstItem = array_shift(self::$filter_options);
				return $firstItem["Title"];
			}
			else {
				return _t("ProductGroup.UNKNOWN", "UNKNOWN");
			}
		}


	/**
	 * @static Array
	 *
	 * Get list of available views
	 *
	 * These can be set in the site config.
	 *
	 */

	/**
	 * @static Boolean
	 *
	 * Allow the shop administrator to show the products as a SHORT list in product group pages.
	 *
	 */
	protected static $allow_short_display_style = false;
		static function set_allow_short_display_style($b){self::$allow_short_display_style = $b;}
		static function get_allow_short_display_style(){return self::$allow_short_display_style;}

	/**
	 * @static Boolean
	 *
	 * Allow the shop administrator to show the products as a MORE DETAIL list in product group pages.
	 *
	 */
	protected static $allow_more_detail_display_style = false;
		static function set_allow_more_detail_display_style($b){self::$allow_more_detail_display_style = $b;}
		static function get_allow_more_detail_display_style(){return self::$allow_more_detail_display_style;}

	/**
	 * @static Array
	 *
	 * Get list of available views
	 *
	 * These can be set in the site config.
	 *
	 */
	public function getDisplayStyleForDropdown(){
		//inherit
		$array = array(
			"inherit" => _t("ProductGroup.INHERIT", "Inherit"),
		);
		//short
		if(self::get_allow_short_display_style()) {
			$array["Short"] = _t("ProductGroup.SHORT", "Short");
		}
		//standard / default
		$array["Default"] = _t("ProductGroup.DEFAULT", "Standard");
		//more details
		if(self::get_allow_more_detail_display_style()) {
			$array["MoreDetail"] = _t("ProductGroup.MOREDETAIL", "More Detail");
		}
		return $array;
	}
	protected function getDefaultDisplayStyle(){
		return "Default";
	}


	/**
	 * @static Array
	 *
	 * List of options to show products.
	 *
	 * With it, we provide a bunch of methods to access and edit the options.
	 *
	 */
	protected $showProductLevels = array(
	 -2 => "None",
	 -1 => "All products",
		1 => "Direct Child Products",
		2 => "Direct Child Products + Grand Child Products",
		3 => "Direct Child Products + Grand Child Products + Great Grand Child Products",
		4 => "Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products",
		99 => "All Child Products (default)"
	);
		public function SetShowProductLevels($a) {$this->showProductLevels = $a;}
		public function RemoveShowProductLevel($i) {unset($this->showProductLevels[$i]);}
		public function AddShowProductLevel($key, $value) {$this->showProductLevels[$key] = $value; ksort($this->showProductLevels);}


	function getCMSFields() {
		$fields = parent::getCMSFields();
		//number of products
		$numberOfProductsPerPageExplanation = $this->MyNumberOfProductsPerPage() != $this->NumberOfProductsPerPage ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$this->MyNumberOfProductsPerPage()." "._t("ProductGroup.INHERITEDFROMPARENTSPAGE", " (inherited from parent page because it is set to zero)") : "";
		$fields->addFieldToTab(
			'Root.Content',
			new Tab(
				'ProductDisplay',
				new DropdownField("LevelOfProductsToShow", _t("ProductGroup.PRODUCTSTOSHOW", "Products to show ..."), $this->showProductLevels),
				new HeaderField("WhatProductsAreShown", _t("ProductGroup.WHATPRODUCTSSHOWN", _t("ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS", "Inherited options"))),
				new NumericField("NumberOfProductsPerPage", _t("ProductGroup.PRODUCTSPERPAGE", "Number of products per page").$numberOfProductsPerPageExplanation)
			)
		);
		//sort
		$sortDropdownList = $this->getSortOptionsForDropdown();
		if(count($sortDropdownList) > 1) {
			$sortOrderKey = $this->MyDefaultSortOrder();
			if($this->DefaultSortOrder == "inherit") {
				$actualValue = " (".(isset($sortDropdownList[$sortOrderKey]) ? $sortDropdownList[$sortOrderKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$sortDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.Content.ProductDisplay",
				new DropdownField("DefaultSortOrder", _t("ProductGroup.DEFAULTSORTORDER", "Default Sort Order"), $sortDropdownList)
			);
		}
		//filter
		$filterDropdownList = $this->getFilterOptionsForDropdown();
		if(count($filterDropdownList) > 1) {
			$filterKey = $this->MyDefaultFilter();
			if($this->DefaultFilter == "inherit") {
				$actualValue = " (".(isset($filterDropdownList[$filterKey]) ? $filterDropdownList[$filterKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$filterDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.Content.ProductDisplay",
				new DropdownField("DefaultFilter", _t("ProductGroup.DEFAULTFILTER", "Default Filter"), $filterDropdownList)
			);
		}
		//displa style
		$displayStyleDropdownList = $this->getDisplayStyleForDropdown();
		if(count($displayStyleDropdownList) > 2) {
			$displayStyleKey = $this->MyDefaultDisplayStyle();
			if($this->DisplayStyle == "inherit") {
				$actualValue = " (".(isset($displayStyleDropdownList[$displayStyleKey]) ? $displayStyleDropdownList[$displayStyleKey] : _t("ProductGroup.ERROR", "ERROR")).")";
				$displayStyleDropdownList["inherit"] = _t("ProductGroup.INHERIT", "Inherit").$actualValue;
			}
			$fields->addFieldToTab(
				"Root.Content.ProductDisplay",
				new DropdownField("DisplayStyle", _t("ProductGroup.DEFAULTDISPLAYSTYLE", "Default Display Style"), $displayStyleDropdownList)
			);
		}
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters.
	 * This method is usually called by the various controller methods.
	 * The extraFilter and recursive help you to select different products,
	 * depending on the method used in the controller.
	 *
	 * We do not use the recursive here.
	 * Furthermore, extrafilter can take onl all sorts of variables.
	 * This is basically setup like this so that in ProductGroup extensions you
	 * can setup all sorts of filters, while still using the ProductsShowable method.
	 *
	 * @param mixed $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 */
	public function ProductsShowable($extraFilter = ''){
		$allProducts = $this->currentInitialProducts($extraFilter);
		return $this->currentFinalProducts($allProducts);
	}

	/**
	 * returns the inital (all) products, based on the all the eligile products
	 * for the page.
	 *
	 * This is THE pivotal method that probably changes for classes that
	 * extend ProductGroup as here you can determine what products or other buyables are shown.
	 *
	 * The return from this method will then be sorted and limited to produce the final product list.
	 *
	 * NOTE: there is no sort and limit for the initial retrieval
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @return DataObjectSet | Null
	 **/
	protected function currentInitialProducts($extraFilter = ''){
		// STANDARD FILTER
		$filter = $this->getStandardFilter();
		// EXTRA FILTER
		if($extraFilter) {
			$filter.= " AND $extraFilter";
		}
		//PARENT ID
		$groupFilter = $this->getGroupFilter();
		// GET PRODUCTS
		$class = $this->getClassNameSQL();
		$where = "($groupFilter) AND ($filter)";
		$sort = null;
		$join = $this->getGroupJoin();
		$limit = null;
		$allProducts = DataObject::get($class,$where, $sort, $join, $limit);
		return $allProducts;
	}



	/**
	 * Returns the class we are working with
	 * @return String
	 */
	protected function getClassNameSQL(){
		return "Product";
	}


	/**
	 * Do products occur in more than one group
	 * @return Boolean
	 */
	protected function getProductsAlsoInOtherGroups(){
		$siteConfig = SiteConfig::current_site_config();
		return $siteConfig->ProductsAlsoInOtherGroups;
	}




	/**
	 * returns the filter SQL, based on the $_GET or default entry.
	 * The standard filter excludes the product group filter.
	 * The default would be something like "ShowInSearch = 1"
	 * @return String
	 */
	protected function getStandardFilter(){
		if(isset($_GET['filterfor'])) {
			$filterKey = Convert::raw2sqL($_GET['filterfor']);
		}
		else {
			$filterKey = $this->MyDefaultFilter();
		}
		$filter = $this->getFilterOptionSQL($filterKey);
		return $filter;
	}

	/**
	 * works out the group filter baswed on the LevelOfProductsToShow value
	 * it also considers the other group many-many relationship
	 * this filter ALWAYS returns something: 1 = 1 if nothing else.
	 * @return String
	 */
	protected function getGroupFilter(){
		$groupFilter = "";
		if($this->LevelOfProductsToShow == -2) {
			//no produts
			$groupFilter = " (1 = 2) " ;
		}
		elseif($this->LevelOfProductsToShow == -1) {
			//all products
			$groupFilter = " (1 = 1) ";
		}
		elseif($this->LevelOfProductsToShow > 0) {
			$groupIDs = array();
			$groupIDs[$this->ID] = $this->ID;
			$childGroups = $this->ChildGroups($this->LevelOfProductsToShow);
			if($childGroups) {
				$groupIDs = array_merge($groupIDs,$childGroups->map('ID','ID'));
			}
			//OTHER GROUPS MANY-MANY
			$groupFilter = " ( \"ParentID\" IN (".implode(",", $groupIDs).") ";
			if($this->getProductsAlsoInOtherGroups()) {
				$multiCategoryFilter = $this->getManyManyFilter('Products','Product');
				$groupFilter .= "  OR ( $multiCategoryFilter  ) ";
			}
			$groupFilter .= " ) ";
		}
		return $groupFilter;
	}

	/**
	 * Join statement for the product groups.
	 * @return Null | String
	 */
	protected function getGroupJoin() {
		if($this->getProductsAlsoInOtherGroups()) {
			return $this->getManyManyJoin('Products','Product');
		}
		return null;
	}


	/**
	 * returns the final products, based on the all the eligile products
	 * for the page.
	 *
	 * All of the 'current' methods are to support the currentFinalProducts Method.
	 *
	 * @param Object $allProducts DataObjectSet of all eligile products before sorting and limiting
	 * @returns Object DataObjectSet of products
	 **/
	protected function currentFinalProducts($buyables){
		if($buyables && $buyables instanceOf DataObjectSet) {
			$buyables->removeDuplicates();
			$siteConfig = SiteConfig::current_site_config();
			if($siteConfig->OnlyShowProductsThatCanBePurchased) {
				foreach($buyables as $buyable) {
					if(!$buyables->canPurchase()) {
						$buyables->remove($buyable);
					}
				}
			}
		}
		if($buyables) {
			$this->totalCount = $buyables->Count();
			if($this->totalCount) {
				return DataObject::get(
					$this->currentClassNameSQL(),
					$this->currentWhereSQL($buyables),
					$this->currentSortSQL(),
					$this->currentJoinSQL(),
					$this->currentLimitSQL()
				);
			}
		}

	}


	/**
	 * returns the CLASSNAME part of the final selection of products.
	 * @return String
	 */
	protected function currentClassNameSQL() {
		return "Product";
	}

	/**
	 * returns the WHERE part of the final selection of products.
	 * @param Object | Array $buyables - list of ALL products showable (without the applied LIMIT)
	 * @return String
	 */
	protected function currentWhereSQL($buyables) {
		if($buyables instanceOf DataObjectSet) {
			$buyables = $buyables->map("ID", "ID");
		}
		$className = $this->currentClassNameSQL();
		$stage = '';
		//@to do - make sure products are versioned!
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		$where = "\"{$className}{$stage}\".\"ID\" IN (".implode(",", $buyables).")";
		return $where;
	}

	/**
	 * returns the SORT part of the final selection of products.
	 * @return String
	 */
	protected function currentSortSQL() {
		if(isset($_GET['sortby'])) {
			$sortKey = Convert::raw2sqL($_GET['sortby']);
		}
		else {
			$sortKey = $this->MyDefaultSortOrder();
		}
		$sort = $this->getSortOptionSQL($sortKey);
		return $sort;
	}

	/**
	 * returns the JOIN part of the final selection of products.
	 * @return String
	 */
	protected function currentJoinSQL() {
		return null;
	}

	/**
	 * returns the LIMIT part of the final selection of products.
	 * @return String
	 */
	protected function currentLimitSQL() {
		$limit = (isset($_GET['start']) && (int)$_GET['start'] > 0) ? (int)$_GET['start'] : "0";
		$limit .= ", ".$this->MyNumberOfProductsPerPage();
		return $limit;
	}

	/**
	 * returns the total numer of products (before pagination)
	 * @return Integer
	 **/
	public function TotalCount() {
		return $this->totalCount ? $this->totalCount : 0;
	}

	/**
	 *@return Integer
	 **/
	function ProductsPerPage() {return $this->MyNumberOfProductsPerPage();}
	function MyNumberOfProductsPerPage() {
		$productsPagePage = 0;
		if($this->NumberOfProductsPerPage) {
			$productsPagePage = $this->NumberOfProductsPerPage;
		}
		else {
			if($parent = $this->ParentGroup()) {
				$productsPagePage = $parent->MyNumberOfProductsPerPage();
			}
			else {
				$siteConfig = SiteConfig::current_site_config();
				if($siteConfig) {
					$productsPagePage = $siteConfig->NumberOfProductsPerPage;
				}
			}
		}
		return $productsPagePage;
	}

	/**
	 * returns the code of the default sort order.
	 * @param $field "", "Title", "SQL"
	 * @return String
	 **/
	function MyDefaultSortOrder() {
		$defaultSortOrder = "";
		if($this->DefaultSortOrder && array_key_exists($this->DefaultSortOrder, self::get_sort_options())) {
			$defaultSortOrder = $this->DefaultSortOrder;
		}
		if(!$defaultSortOrder && $parent = $this->ParentGroup()) {
			$defaultSortOrder = $parent->MyDefaultSortOrder();
		}
		elseif(!$defaultSortOrder) {
			$defaultSortOrder = $this->getDefaultSortKey();
		}
		return $defaultSortOrder;
	}

	/**
	 * returns the code of the default sort order.
	 * @return String
	 **/
	function MyDefaultFilter() {
		$defaultFilter = "";
		if($this->DefaultFilter && array_key_exists($this->DefaultFilter, self::get_filter_options())) {
			$defaultFilter = $this->DefaultFilter;
		}
		if(!$defaultFilter && $parent = $this->ParentGroup()) {
			$defaultFilter = $parent->MyDefaultFilter();
		}
		elseif(!$defaultFilter) {
			$defaultFilter = $this->getDefaultFilterKey();
		}
		return $defaultFilter;
	}

	/**
	 * returns the code of the default style for template.
	 * @return String
	 **/
	function MyDefaultDisplayStyle() {
		$displayStyle = "";
		if($this->DisplayStyle != "inherit") {
			$displayStyle = $this->DisplayStyle;
		}
		if($displayStyle == "inherit" && $parent = $this->ParentGroup()) {
			$displayStyle = $parent->MyDefaultDisplayStyle();
		}
		if(!$displayStyle) {
			$displayStyle = $this->getDefaultDisplayStyle();
		}
		return $displayStyle;
	}


	/**
	 * Returns children ProductGroup pages of this group.
	 * @return DataObjectSet | null
	 */
	function ChildGroups($maxRecursiveLevel = 99, $filter = "", $numberOfRecursions = 0, $output = null) {
		$numberOfRecursions++;
		$filterWithAND = '';
		if($filter) {
			$filterWithAND = " AND $filter";
		}
		if($numberOfRecursions < $maxRecursiveLevel){
			if($children = DataObject::get('ProductGroup', "\"ParentID\" = '$this->ID' $filterWithAND")){
				if($output == null) {
					$output = $children;
				}
				foreach($children as $group){
					$output->merge($group->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions, $output));
				}
			}
		}
		return $output;
	}

	/**
	 * returns the parent page, but only if it is an instance of Product Group.
	 * @return DataObject | Null (ProductGroup)
	 **/
	function ParentGroup() {
		return DataObject::get_by_id("ProductGroup", $this->ParentID);
	}


	/**
	 * Recursively generate a product menu.
	 * @return DataObjectSet
	 */
	function GroupsMenu($filter = "ShowInMenus = 1") {
		if($parent = $this->Parent()) {
			return $parent instanceof ProductGroup ? $parent->GroupsMenu() : $this->ChildGroups($filter);
		}
		else {
			return $this->ChildGroups($filter);
		}
	}


}
class ProductGroup_Controller extends Page_Controller {

	/**
	 * standard SS method
	 */
	function init() {
		parent::init();
		Requirements::themedCSS('Products');
		Requirements::themedCSS('ProductGroup');
		Requirements::themedCSS('ProductGroupPopUp');
		Requirements::javascript('ecommerce/javascript/EcomProducts.js');
	}

	/**
	 * tells us if the current page is part of e-commerce.
	 * @return Boolean
	 */
	function IsEcommercePage() {
		return true;
	}

	/**
	 * Return the products for this group.
	 *
	 *@return DataObjectSet(Products)
	 **/
	public function Products($recursive = true){
	//	return $this->ProductsShowable("\"FeaturedProduct\" = 1",$recursive);
		return $this->ProductsShowable('',$recursive);
	}

	/**
	 * Return products that are featured, that is products that have "FeaturedProduct = 1"
	 *
	 *@return DataObjectSet(Products)
	 */
	function FeaturedProducts($recursive = true) {
		return $this->ProductsShowable("\"FeaturedProduct\" = 1",$recursive);
	}

	/**
	 * Return products that are not featured, that is products that have "FeaturedProduct = 0"
	 *
	 *@return DataObjectSet(Products)
	 */
	function NonFeaturedProducts($recursive = true) {
		return $this->ProductsShowable("\"FeaturedProduct\" = 0",$recursive);
	}

	/**
	 * Provides a dataset of links for sorting products.
	 *
	 *@return DataObjectSet(Name, Link, Current (boolean), LinkingMode)
	 */
	function SortLinks(){
		if(count(ProductGroup::get_sort_options()) <= 0) return null;
		if($this->totalCount < 3) return null;
		$sort = (isset($_GET['sortby'])) ? Convert::raw2sql($_GET['sortby']) : $this->MyDefaultSortOrder();
		$dos = new DataObjectSet();
		foreach(ProductGroup::get_sort_options() as $key => $array){
			$current = ($key == $sort) ? 'current' : false;
			$dos->push(new ArrayData(array(
				'Name' => _t('ProductGroup.SORTBY'.strtoupper(str_replace(' ','',$array['Title'])),$array['Title']),
				'Link' => $this->Link()."?sortby=$key",
				'SelectKey' => $key,
				'Current' => $current,
				'LinkingMode' => $current ? "current" : "link"
			)));
		}
		return $dos;
	}


	/**
	 *
	 * This method can be extended to show products in the side bar.
	 *
	 * @return Object DataObjectSet
	 */
	function SidebarProducts(){
		return null;
	}

}
