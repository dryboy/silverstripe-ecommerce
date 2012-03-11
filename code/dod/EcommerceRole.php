<?php
/**
 * @description EcommerceRole provides customisations to the {@link Member}
 * class specifically for this ecommerce module.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package member
 *
 **/

class EcommerceRole extends DataObjectDecorator {

	/**
	 * allow customers to setup an account - when set to false customer can NEVER set up an account at all.
	 * @var Boolean
	 **/
	protected static $allow_customers_to_setup_accounts = true;
		static function set_allow_customers_to_setup_accounts($b){self::$allow_customers_to_setup_accounts = $b;}
		static function get_allow_customers_to_setup_accounts(){return self::$allow_customers_to_setup_accounts;}

	/**
	 * automatically add new customer as a member, when set to false members that do not enter a password
	 * are not added as members.
	 * @var Boolean
	 **/
	protected static $automatic_membership = true;
		static function set_automatic_membership($b){self::$automatic_membership = $b;}
		static function get_automatic_membership(){return self::$automatic_membership;}

	/**
	 * automatically update member details for a logged-in user
	 * when set to TRUE, the member details are updated based on the values in the Billing Address.
	 * @var Boolean
	 **/
	protected static $automatically_update_member_details = true;
		static function set_automatically_update_member_details($b){self::$automatically_update_member_details = $b;}
		static function get_automatically_update_member_details(){return self::$automatically_update_member_details;}

	/**
	 * standard SS method
	 * defines additional statistics
	 */
	function extraStatics() {
		return array(
			'db' => array(
				'Notes' => 'HTMLText'
			),
			'has_many' => array(
				'Orders' => 'Order'
			),
			'api_access' => array(
				'view' =>
					array('ID', 'Orders')
				)
		);
	}


	/**
	 * code used for the customer Member group
	 * @var String
	 **/
	protected static $customer_group_code = 'shopcustomers';
		static function set_customer_group_code($s) {self::$customer_group_code = $s;}
		static function get_customer_group_code() {return ereg_replace("[^A-Za-z0-9]", "", self::$customer_group_code);}

	/**
	 * name used for the customer Member group
	 * @var String
	 **/
	protected static $customer_group_name = "shop customers";
		static function set_customer_group_name($s) {self::$customer_group_name = $s;}
		static function get_customer_group_name() {return self::$customer_group_name;}

	/**
	 * permission code used for the customer Member group
	 * @var String
	 **/
	protected static $customer_permission_code = "SHOPCUSTOMER";
		static function set_customer_permission_code($s) {self::$customer_permission_code = $s;}
		static function get_customer_permission_code() {return ereg_replace("[^A-Za-z0-9]", "", self::$customer_permission_code);}


	/**
	 *@return DataObject (Group)
	 **/
	public static function get_customer_group() {
		return DataObject::get_one("Group", "\"Code\" = '".self::get_customer_group_code()."' OR \"Title\" = '".self::get_customer_group_name()."'");
	}


/*******************************************************
   * SHOP ADMIN
*******************************************************/

	public static function current_member_is_shop_admin($member = null) {
		if(!$member) {
			$member = Member::currentMember();
		}
		if($member) {
			return $member->IsShopAdmin();
		}
		return false;
	}

	/**
	 * code used for the shop admin Member group
	 * @var String
	 **/
	protected static $admin_group_code = "shopadministrators";
		static function set_admin_group_code($s) {self::$admin_group_code = $s;}
		static function get_admin_group_code() {return ereg_replace("[^A-Za-z0-9]", "", self::$admin_group_code);}

	/**
	 * name used for the shop admin Member group
	 * @var String
	 **/
	protected static $admin_group_name = "shop administrators";
		static function set_admin_group_name($s) {self::$admin_group_name = $s;}
		static function get_admin_group_name() {return self::$admin_group_name;}

	/**
	 * permission code used for the shop admin Member group
	 * @var String
	 **/
	protected static $admin_permission_code = "SHOPADMIN";
		static function set_admin_permission_code($s) {self::$admin_permission_code = $s;}
		static function get_admin_permission_code() {return ereg_replace("[^A-Za-z0-9]", "", self::$admin_permission_code);}

	/**
	 * title for the admin role
	 * @var String
	 **/
	protected static $admin_role_title = "managing store";
		static function set_admin_role_title($s){self::$admin_role_title = $s;}
		static function get_admin_role_title(){return self::$admin_role_title;}


	/**
	 * the permission codes that get granted to the shop administrator
	 * @var Array
	 **/
	protected static $admin_role_permission_codes = array(
		"CMS_ACCESS_ProductsAndGroupsModelAdmin",
		"CMS_ACCESS_SalesAdmin",
		"CMS_ACCESS_StoreAdmin"
	);
		static function set_admin_role_permission_codes($a){self::$admin_role_permission_codes = $a;}
		static function get_admin_role_permission_codes(){return self::$admin_role_permission_codes;}

	/**
	 *@return DataObject (Group)
	 **/
	public static function get_admin_group() {
		return DataObject::get_one("Group", "\"Code\" = '".self::get_admin_group_code()."' OR \"Title\" = '".self::get_admin_group_name()."'");
	}



	/**
	 * get CMS fields describing the member in the CMS when viewing the order.
	 *
	 * @return Field / ComponentSet
	 **/

	public function getEcommerceFieldsForCMS() {
		$fields = new CompositeField();
		$memberTitle = new TextField("MemberTitle", "Name", $this->owner->getTitle());
		$fields->push($memberTitle->performReadonlyTransformation());
		$memberEmail = new TextField("MemberEmail","Email", $this->owner->Email);
		$fields->push($memberEmail->performReadonlyTransformation());
		$lastLogin = new TextField("MemberLastLogin","Last login",$this->owner->dbObject('LastVisited')->Nice());
		$fields->push($lastLogin->performReadonlyTransformation());
		return $fields;
	}

	/**
	 * returns content for a literal field for the CMS that links through to the member.
	 * @return String
	 **/

	function getEcommerceFieldsForCMSAsString() {
		$v = "<address>";
		$v = "Name: ". $this->owner->getTitle();
		$v .= "<br />Email: <a href=\"".$this->owner->Email."\" target=\"_blank\">".$this->owner->Email."</a>";
		$v .= "<br />Last Login: ".$this->owner->dbObject('LastVisited')->Nice();
		$v .= "</address>";
		if($group = EcommerceRole::get_customer_group()) {
			$v .= '<p><a href="/admin/security/show/'.$group->ID.'/" target="_blank">view (and edit) all customers</a></p>';
		}
		return $v;
	}


	/**
	 * @param Boolean $additionalFields: extra fields to be added.
	 * @return FieldSet
	 */
	function getEcommerceFields($additionalFields = false) {
		if($additionalFields) {
			$fields = new FieldSet(
				new HeaderField('PersonalInformation', _t('EcommerceRole.PERSONALINFORMATION','Personal Information'), 3),
				new TextField('FirstName', _t('EcommerceRole.FIRSTNAME','First Name')),
				new TextField('Surname', _t('EcommerceRole.SURNAME','Surname')),
				new EmailField('Email', _t('EcommerceRole.EMAIL','Email'))
			);
		}
		else {
			$fields = new FieldSet();
		}
		$this->owner->extend('augmentEcommerceFields', $fields);
		return $fields;
	}

	/**
	 * Return which member fields should be required on {@link OrderForm}
	 * and {@link ShopAccountForm}.
	 *
	 * @return array
	 */
	function getEcommerceRequiredFields() {
		$fields = array(
			//'FirstName',
			//'Surname',
			//'Email'
		);
		$this->owner->extend('augmentEcommerceRequiredFields', $fields);
		return $fields;
	}


	/**
	 * standard SS method
	 * Make sure the member is added as a customer
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();
		//...
		$customerGroup = EcommerceRole::get_customer_group();
		if($customerGroup){
			$existingMembers = $customerGroup->Members();
			if($existingMembers){
				$existingMembers->add($this->owner);
			}
		}
	}

	/**
	 * Is the member a member of the ShopAdmin Group
	 *@return Boolean
	 **/
	function IsShopAdmin() {
		if($this->owner->IsAdmin()) {
			return true;
		}
		else{
			return Permission::checkMember($this->owner, self::get_admin_permission_code());
		}
	}


}



