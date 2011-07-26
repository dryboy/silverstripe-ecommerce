<?php
/**
 * @description:
 * The Account Page allows the user to update their details. 
 * You do not need to be logged in to the account page in order to view it... If you are not logged in
 * then the account page can be a page to create an account.

 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class AccountPage extends Page {

	public static $icon = 'ecommerce/images/icons/AccountPage';

	/**
	 * standard SS method
	 *@return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'AccountPage'");
	}

	/**
	 * Returns the link to the AccountPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('AccountPage', "\"ClassName\" = 'AccountPage'")) {
			return $page->Link();
		}
	}

}

class AccountPage_Controller extends Page_Controller {

	static $allowed_actions = array(
		'MemberForm'
	);

	/**
	 * standard controller function
	 **/
	function init() {
		parent::init();
		if(!Member::CurrentMember()) {
			$messages = array(
				'default' => '<p class="message good">' . _t('Account.LOGINFIRST', 'You will need to login before you can access the account page. If you are not registered, you will not be able to access it until you place your first order, otherwise please enter your details below.') . '</p>',
				'logInAgain' => _t('Account.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
			);
			Security::permissionFailure($this, $messages);
			return false;
		}
	}

	/**
	 * Return a form allowing the user to edit
	 * their details with the shop.
	 *
	 * @return ShopAccountForm
	 */
	function MemberForm() {
		return new ShopAccountForm($this, 'MemberForm');
	}



}
