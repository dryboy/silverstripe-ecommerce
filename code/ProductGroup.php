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

	public static $db = array(
		"LevelOfProductsToShow" => "Int",
		"NumberOfProductsPerPage" => "Int",
		"DefaultSortOrder" => "Varchar(50)",
		"ProductsAlsoInOthersGroups" => "Boolean"
	);

	public static $belongs_many_many = array(
		'Products' => 'Product'
	);

	public static $defaults = array(
		"DefaultSortOrder" => "default",
		"LevelOfProductsToShow" => 99,
		"ProductsAlsoInOthersGroups" => 0
	);

	public static $default_child = 'Product';

	public static $icon = 'ecommerce/images/icons/productgroup';

	protected static $sort_options = array(
			'default' => array("Title" => 'Default Order', "SQL" => "\"Sort\" ASC, \"Title\" ASC"),
			'title' => array("Title" => 'Alphabetical', "SQL" => "\"Title\" ASC"),
			'price' => array("Title" => 'Lowest Price', "SQL" => "\"Price\" ASC, \"Title\" ASC"),
		);
		static function add_sort_option($key, $title, $sql){self::$sort_options[$key] = array("Title" => $title, "SQL" => $sql);}
		static function remove_sort_option($key){unset(self::$sort_options[$key]);}
		static function set_sort_options(array $a){self::$sort_options = $a;}
		static function get_sort_options(){return self::$sort_options;}
		//NON-STATIC
		protected function getSortOptionsForDropdown(){
			$array = array();
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

	protected $standardFilter = " AND \"ShowInSearch\" = 1";
	public function getStandardFilter(){return $this->standardFilter;}

	protected $showProductLevels = array(
		0 => "None",
		1 => "Direct Child Products",
		2 => "Direct Child Products + Grand Child Products",
		3 => "Direct Child Products + Grand Child Products + Great Grand Child Products",
		4 => "Direct Child Products + Grand Child Products + Great Grand Child Products + Great Great Grand Child Products",
		99 => "All Child Products"
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab(
			'Root.Content',
			new Tab(
				'Products',
				new DropdownField("LevelOfProductsToShow", _t("ProductGroup.PRODUCTSTOSHOW", "Products to show ..."), $this->showProductLevels),
				new HeaderField("whatproductsshown", _t("ProductGroup.WHATPRODUCTSSHOWN", _t("ProductGroup.OPTIONSSELECTEDBELOWAPPLYTOCHILDGROUPS", "Options selected below apply to child product group pages as well as this product group page."))),
				new NumericField("NumberOfProductsPerPage", _t("ProductGroup.PRODUCTSPERPAGE", "Number of products per page")),
				new DropdownField("DefaultSortOrder", _t("ProductGroup.DEFAULTSORTORDER", "Default Sort Order"), $this->getSortOptionsForDropdown()),
				new CheckboxField("ProductsAlsoInOthersGroups", _t("ProductGroup.PRODUCTSALSOINOTHERSGROUPS", "Also allow the products for this product group to show in other groups (see product pages for actual selection)."))
			)
		);
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters. Checks get query for sorting and pagination.
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 */
	function ProductsShowable($extraFilter = '', $recursive = true){
				//GROUPS FILTER
		if($this->LevelOfProductsToShow == 0) {
			return null;
		}

		// STANDARD FILTER
		$filter = $this->getStandardFilter(); //
		$join = "";

		// EXTRA FILTER
		if($extraFilter) {
			$filter.= " AND $extraFilter";
		}

		//PARENT ID
		$groupIDs = array();
		$groupIDs[$this->ID] = $this->ID;
		if($this->LevelOfProductsToShow > 1) {
			$childGroups = $this->ChildGroups($this->LevelOfProductsToShow);
			if($childGroups) {
				$groupIDs = array_merge($groupIDs,$childGroups->map('ID','ID'));
			}
		}

		//OTHER GROUPS MANY MANY
		$join = $this->getManyManyJoin('Products','Product');
		$multiCategoryFilter = $this->getManyManyFilter('Products','Product');

		// GET PRODUCTS
		$where = "(\"ParentID\" IN (".implode(",", $groupIDs).") OR $multiCategoryFilter) $filter";
		$allProducts = DataObject::get('Product',$where,null,$join);

		//REMOVE DUPLICATES AND NOT canPurcahse
		if($allProducts && $allProducts instanceOf DataObjectSet) {
			$allProducts->removeDuplicates();
			$siteConfig = SiteConfig::current_site_config();
			if($siteConfig->OnlyShowProductsThatCanBePurchased) {
				foreach($allProducts as $product) {
					if(!$product->canPurchase()) {
						$allProducts->remove($product);
					}
				}
			}
		}


		//LIMIT
		if($allProducts) {
			if($allProducts->Count() > 0) {
				//SORT BY
				if(!isset($_GET['sortby'])) {
					$sortKey = $this->MyDefaultSortOrder();
				}
				else {
					$sortKey = Convert::raw2sqL($_GET['sortby']);
				}
				$sort = $this->getSortOptionSQL($sortKey);

				$limit = (isset($_GET['start']) && (int)$_GET['start'] > 0) ? (int)$_GET['start'] : "0";
				$limit .= ", ".$this->MyNumberOfProductsPerPage();
				$stage = '';
				if(Versioned::current_stage() == "Live") {
					$stage = "_Live";
				}
				$whereForPageOnly = "\"Product$stage\".\"ID\" IN (".implode(",", $allProducts->map("ID", "ID")).")";
				$products = DataObject::get('Product',$whereForPageOnly,$sort, null,$limit);
				if($products) {
					$this->totalCount = $products->count();
					return $products;
				}
			}
		}
		return null;
	}

	function TotalCount() {
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
	 *@return String
	 **/
	function MyDefaultSortOrder() {
		$defaultSortOrder = "";
		if($this->DefaultSortOrder) {
			$defaultSortOrder = $this->DefaultSortOrder;
		}
		else {
			if($parent = $this->ParentGroup()) {
				$defaultSortOrder = $parent->MyDefaultSortOrder();
			}
		}
		return $defaultSortOrder;
	}

	/**
	 *@return Boolean
	 **/
	function MyProductsAlsoInOthersGroups() {
		$alsoInOtherGroups = self::$defaults["ProductsAlsoInOthersGroups"];
		if($this->ProductsAlsoInOthersGroups) {
			$alsoInOtherGroups = $this->ProductsAlsoInOthersGroups;
		}
		else {
			if($parent = $this->ParentGroup()) {
				$alsoInOtherGroups = $parent->MyProductsAlsoInOthersGroups();
			}
		}
		return $alsoInOtherGroups;
	}

	/**
	 * Return children ProductGroup pages of this group.
	 * @return DataObjectSet
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
	 *@return DataObject (ProductGroup)
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

	function requireDefaultRecords(){
		parent::requireDefaultRecords();
		if(!DataObject::get("ProductGroup", "\"LevelOfProductsToShow\" > 0") && isset($_GET[["resetproductshowlevels"])) {
			DB::query("UPDATE ProductGroup SET \"LevelOfProductsToShow\" = ".self::$defaults["LevelOfProductsToShow"]);
			DB::query("UPDATE ProductGroup_Live SET \"LevelOfProductsToShow\" = ".self::$defaults["LevelOfProductsToShow"]);
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
				'Current' => $current,
				'LinkingMode' => $current ? "current" : "link"
			)));
		}
		return $dos;
	}

}
