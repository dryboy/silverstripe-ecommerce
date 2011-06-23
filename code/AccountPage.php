<?php
/**
 * @description:
 * Account page shows order history and a form to allow the member to edit his/her details.
 * You do not need to be logged in to the account page in order to view it... If you are not logged in
 * then the account page can be a page to create an account.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 * TODO: should this extend cart page?
 **/

class AccountPage extends CartPage {

	public static $icon = 'ecommerce/images/icons/account';

	/**
	 * standard SS method
	 *@return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'AccountPage'");
	}

	/**
	 * Returns the link or the Link to the AccountPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if(!$page = DataObject::get_one('AccountPage', "\"ClassName\" = 'AccountPage'")) {
			return CheckoutPage::find_link();
		}
		return $page->Link();
	}

	/**
	 *@return DataObjectSet or Null - DataObjectSet contains DataObjects. Each DataObject has two params: Heading and Orders
	 * we use this format so that we can easily iterate through all the orders in the template.
	 * TO DO: make this more standardised.
	 * TO DO: create Object called OrdersDataObject with standardised fields (Title, Orders, etc...)
	 **/
	public function AllMemberOrders() {
		$dos = new DataObjectSet();
		$doCurrentOrders = new DataObject();
		$dos->push("ShoppingCartOrders", _t("Account.CURRENTORDER", "Current Shopping Cart"));
		$dos->push("IncompleteOrders", _t("Account.INCOMPLETEORDERS", "Incomplete Orders"));
		$dos->push("InProcessOrders", _t("Account.INPROCESSORDERS", "In Process Orders"));
		$dos->push("CompleteOrders", _t("Account.COMPLETEORDERS", "Completed Orders"));
		if($dos->count()) {
			return $dos;
		}
		return null;
	}

	/**
	 *
	 *
	 *@return DataObject - returns a dataobject with two variables: Orders and Heading.... Orders contains a dataobjectset of orders, Heading is the name of the Orders.
	 **/
	protected function putTogetherOrderDataObjectSet($method, $title) {
		$dos = new DataObject();
		$dos->Orders = $this->$method();
		if($dos->Orders) {
			$dos->Heading = $title;
		}
		return $dos;
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are incomplete.
	 *
	 * @return DataObjectSet | null
	 */
	function ShoppingCartOrders() {
		$order = ShoppingCart::current_order();
		if($order) {
			$dos = new DataObjectSet();
			$dos->push($order);
			return $dos;
		}
		return null;
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are incomplete.
	 *
	 * @return DataObjectSet | null
	 */
	function IncompleteOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsUncompletedOrder\" = 1 ";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are completed.
	 *
	 * @return DataObjectSet | null
	 */
	function InProcessOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsInProcessOrder\" = 1";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 * Returns all {@link Order} records for this
	 * member that are completed.
	 *
	 * @return DataObjectSet | null
	 */
	function CompleteOrders() {
		$statusFilter = "\"OrderStep\".\"ShowAsCompletedOrder\" = 1";
		return $this->otherOrderSQL($statusFilter);
	}

	/**
	 *@return DataObjectSet  | null
	 **/
	protected function otherOrderSQL ($statusFilter) {
		$memberID = Member::currentUserID();
		if($memberID) {
			$orders = DataObject::get(
				$className = 'Order',
				$where = "\"Order\".\"MemberID\" = '$memberID' AND ".$statusFilter." AND \"CancelledByID\" = 0",
				$sort = "\"Created\" DESC",
				$join = "INNER JOIN \"OrderStep\" ON \"Order\".\"StatusID\" = \"OrderStep\".\"ID\""
			);
			if($orders) {
				foreach($orders as $order) {
					if(!$order->Items() || !$order->canView()) {
						$orders->remove($order);
					}
					elseif($order->IsSubmitted())  {
						$order->tryToFinaliseOrder();
					}
				}
				return $orders;
			}
		}
		return null;
	}


}

class AccountPage_Controller extends CartPage_Controller {

	static $allowed_actions = array(
		'loadorder',
		'startneworder',
		'showorder',
		'sendreceipt',
		'CancelForm',
		'PaymentForm',
		'MemberForm'
	);

	/**
	 * standard controller function
	 **/
	function init() {
		parent::init();
		//Requirements::themedCSS('AccountPage'); // in TEMPLATE
		if($m = Member::currentUser()) {
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
		if(!$this->Order()) {
			return new ShopAccountForm($this, 'MemberForm');
		}
	}

	/**
	 * Returns the form to cancel the current order,
	 * checking to see if they can cancel their order
	 * first of all.
	 *
	 * @return OrderForm_Cancel
	 */
	function CancelForm() {
		if($this->Order()) {
			if($this->currentOrder->canCancel()) {
				return new OrderForm_Cancel($this, 'CancelForm', $this->currentOrder);
			}
		}
		//once cancelled, you will be redirected to main page - hence we need this...
		if($this->orderID) {
			return array();
		}
	}


	/**
	 *@return Form (OrderForm_Payment) or Null
	 **/
	function PaymentForm(){
		if($this->Order()){
			if($this->currentOrder->canPay()) {
				Requirements::javascript("ecommerce/javascript/EcomPayment.js");
				return new OrderForm_Payment($this, 'PaymentForm', $this->currentOrder);
			}
		}
	}


}
