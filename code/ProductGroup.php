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
		"DefaultSortOrder" => "Varchar(50)",
		"DefaultFilter" => "Varchar(50)",
		"LevelOfProductsToShow" => "Int"
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
		protected function getSortOptionsForDropdown(){
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
		protected function getFilterOptionsForDropdown(){
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
	 * List of options to show products.
	 *
	 * With it, we provide a bunch of methods to access and edit the options.
	 *
	 */
	protected $showProductLevels = array(
	 -1 => "All products on site",
		0 => "None",
		1 => "Direct Child Products",
		2 => "Direct Child Products + Grand Child Products",
		3 => "Direct Child Products + Grand Child Products + Great Grand Child Products",
		4 => "Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products",
		99 => "All Child Products (default)"
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$numberOfProductsPerPageExplanation = $this->MyNumberOfProductsPerPage() != $this->NumberOfProductsPerPage ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$this->MyNumberOfProductsPerPage()." "._t("ProductGroup.INHERITED", " (inherited from parent page)") : "";
		$sortOrderKey = $this->MyDefaultSortOrder();
		$defaultSortOrderName = $this->getSortOptionTitle($sortOrderKey);
		$defaultSortOrderExplanation = $sortOrderKey != $this->DefaultSortOrder ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$defaultSortOrderName." "._t("ProductGroup.INHERITED", " (inherited from parent page)") : "";
		$filterKey = $this->MyDefaultFilter();
		$defaultFilterName = $this->getFilterOptionTitle($filterKey);
		$defaultFilterNameExplanation = $filterKey != $this->DefaultFilter ? _t("ProductGroup.CURRENTLVALUE", " - current value: ").$defaultFilterName." "._t("ProductGroup.INHERITED", " (inherited from parent page)") : "";
		$fields->addFieldToTab(
			'Root.Content',
			new Tab(
				'Products',
				new DropdownField("LevelOfProductsToShow", _t("ProductGroup.PRODUCTSTOSHOW", "Products to show ..."), $this->showProductLevels),
				new HeaderField("whatproductsshown", _t("ProductGroup.WHATPRODUCTSSHOWN", _t("ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS", "Inherited options"))),
				new NumericField("NumberOfProductsPerPage", _t("ProductGroup.PRODUCTSPERPAGE", "Number of products per page").$numberOfProductsPerPageExplanation),
				new DropdownField("DefaultSortOrder", _t("ProductGroup.DEFAULTSORTORDER", "Default Sort Order").$defaultSortOrderExplanation, $this->getSortOptionsForDropdown()),
				new DropdownField("DefaultFilter", _t("ProductGroup.DEFAULTFILTER", "Default Filter").$defaultFilterNameExplanation, $this->getFilterOptionsForDropdown())
			)
		);
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
	public function ProductsShowable($extraFilter = '', $recursive = true){
		$allProducts = $this->currentInitialProducts($extraFilter, $recursive);
		return $this->currentFinalProducts($allProducts);
	}

	/**
	 * returns the inital (all) products, based on the all the eligile products
	 * for the page.
	 *
	 * This is THE pivotal method that probably changes for classes that
	 * extend ProductGroup as here you can determine what products or other buyables are shown.
	 *
	 * The return from this method will then be sorted and filtered to product the final product list
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 **/
	protected function currentInitialProducts($extraFilter = '', $recursive = true){
		// STANDARD FILTER
		$filter = $this->getStandardFilter();
		// EXTRA FILTER
		if($extraFilter) {
			$filter.= " AND $extraFilter";
		}		//PARENT ID
		$join = "";
		$groupFilter = "";
		if($this->LevelOfProductsToShow < 0) {
			//all products
			$groupFilter = " (1 = 1) ";
		}
		elseif($this->LevelOfProductsToShow == 0) {
			//no produts
			$groupFilter = " (1 = 2) " ;
		}
		elseif($this->LevelOfProductsToShow > 1) {
			$groupIDs = array();
			$groupIDs[$this->ID] = $this->ID;
			$childGroups = $this->ChildGroups($this->LevelOfProductsToShow);
			if($childGroups) {
				$groupIDs = array_merge($groupIDs,$childGroups->map('ID','ID'));
			}
			//OTHER GROUPS MANY-MANY
			$join = $this->getManyManyJoin('Products','Product');
			$multiCategoryFilter = $this->getManyManyFilter('Products','Product');
			$groupFilter = " ( \"ParentID\" IN (".implode(",", $groupIDs).")  OR $multiCategoryFilter )";
		}
		// GET PRODUCTS
		$where = "($groupFilter) AND ($filter)";
		$allProducts = DataObject::get('Product',$where,null,$join);
		return $allProducts;
	}

	/**
	 * returns the final products, based on the all the eligile products
	 * for the page.
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
	 * returns the WHERE part of the final selection of products.
	 * @param Object $allProducts - list of ALL products showable (without the applied LIMIT)
	 * @return String
	 */
	protected function currentWhereSQL($buyables) {
		$className = $this->currentClassNameSQL();
		$stage = '';
		if(Versioned::current_stage() == "Live") {
			$stage = "_Live";
		}
		$where = "\"{$className}{$stage}\".\"ID\" IN (".implode(",", $buyables->map("ID", "ID")).")";
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
	 * @param $field "", "Title", "SQL"
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
	 * Returns children ProductGroup pages of this group.
	 * @return DataObjectSet | null
	 */
	function ChildGroups($maxRecursiveLevel = 99, $filter = "", $numberOfRecursions = 1) {
		$numberOfRecursions++;
		$filterWithAND = '';
		if($filter) {
			$filterWithAND = " AND $filter";
		}
		if($numberOfRecursions < $maxRecursiveLevel){
			if($children = DataObject::get('ProductGroup', "\"ParentID\" = '$this->ID' $filterWithAND")){
				//what is this serialize stuff for?????
				$output = unserialize(serialize($children));
				foreach($children as $group){
					$output->merge($group->ChildGroups($maxRecursiveLevel, $filter, $numberOfRecursions));
				}
				return $output;
			}
			return null;
		}
		else{
			return DataObject::get('ProductGroup', "\"ParentID\" = '$this->ID' $filterWithAND");
		}
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

	function init() {
		parent::init();
		Requirements::themedCSS('Products');
		Requirements::themedCSS('ProductGroup');
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
