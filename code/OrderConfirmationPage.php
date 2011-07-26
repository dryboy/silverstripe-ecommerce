<?php

/**
 * @description:
 * The Order Confirmation page shows order history.
 * It also serves as the end point for the current order...
 * once submitted, the Order Confirmation page shows the
 * finalised detail of the order.
 * 
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class OrderConfirmationPage extends CartPage{

	public static $db = array(
		"YouDontHaveSavedOrders" => "HTMLText"
	);

	public static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'OrderConfirmationPage'");
	}

	/**
	 *@return Fieldset
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main.Messages", new HTMLEditorField("YouDontHaveSavedOrders", "Message to user: You dont have any saved orders (e.g. you have not placed any orders yet) ", 5, 5));
		return $fields;
	}

	function canView($member = null) {
		if(!$member) {
			$member = Member::CurrentMember();
		}
		if(EcommerceRole::current_member_is_shop_admin($member)) {
			return true;
		}
		elseif($member) {
			$orders = DataObject::get("Order", "\"MemberID\" = ".$member->ID);
			if($orders) {
				foreach($orders as $order) {
					if($order->IsSubmitted()) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Returns the link or the Link to the OrderConfirmationPage page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('OrderConfirmationPage', "\"ClassName\" = 'OrderConfirmationPage'")) {
			return $page->Link();
		}
		elseif($page = DataObject::get_one('OrderConfirmationPage')) {
			return $page->Link();
		}
		return CartPage::find_link();
	}


	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_email_link($orderID) {
		return self::find_link(). 'sendreceipt/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public function getOrderLink($orderID) {
		return self::get_order_link($orderID);
	}

	/**
	 *@return DataObjectSet or Null - DataObjectSet contains DataObjects. Each DataObject has two params: Heading and Orders
	 * we use this format so that we can easily iterate through all the orders in the template.
	 * TO DO: make this more standardised.
	 * TO DO: create Object called OrdersDataObject with standardised fields (Title, Orders, etc...)
	 **/
	public function AllMemberOrders() {
		$dos = new DataObjectSet();
		$doCurrentOrders = $this->putTogetherOrderDataObjectSet("ShoppingCartOrders", _t("Account.CURRENTORDER", "Current Shopping Cart"));
		if($doCurrentOrders){
			$dos->push($doCurrentOrders);
		}
		$incompleteOrders = $this->putTogetherOrderDataObjectSet("IncompleteOrders", _t("Account.INCOMPLETEORDERS", "Incomplete Orders"));
		if($incompleteOrders){
			$dos->push($incompleteOrders);
		}
		$inProcessOrders = $this->putTogetherOrderDataObjectSet("InProcessOrders", _t("Account.INPROCESSORDERS", "In Process Orders"));
		if($inProcessOrders){
			$dos->push($inProcessOrders);
		}
		$completeOrders = $this->putTogetherOrderDataObjectSet("CompleteOrders", _t("Account.COMPLETEORDERS", "Completed Orders"));
		if($completeOrders){
			$dos->push($completeOrders);
		}
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
			$dos->Heading = DBField::create($className = "TextField", $title);
		}
		return null;
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

class OrderConfirmationPage_Controller extends CartPage_Controller{

	static $allowed_actions = array(
		'retrieveorder',
		'loadorder',
		'startneworder',
		'showorder',
		'sendreceipt',
		'CancelForm',
		'PaymentForm',
	);


	/**
	 * standard controller function
	 **/
	function init() {
		parent::init();
		$retrievedOrder = null;
		if($this->request && $this->request->param("Action") == "retrieveorder") {
			$sessionID = Convert::raw2sql($this->request->param("ID"));
			$id = intval(Convert::raw2sql($this->request->param("OtherID")));
			$retrievedOrder = DataObject::get_one("Order", "\"Order\".\"SessionID\" = '".$sessionID."' AND \"Order\".\"ID\" = $id");
			$this->currentOrder = $retrievedOrder;
		}
		if(!Member::CurrentMember() && !$retrievedOrder) {
			$messages = array(
				'default' => '<p class="message good">' . _t('OrderConfirmationPage.LOGINFIRST', 'You will need to login before you can access the submitted order page. ') . '</p>',
				'logInAgain' => _t('OrderConfirmationPage.LOGINAGAIN', 'You have been logged out. If you would like to log in again, please do so below.')
			);
			Security::permissionFailure($this, $messages);
			return false;
		}
		Requirements::themedCSS('Order'); 
		Requirements::themedCSS('Order_Print', "print"); 
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
	}

	/**
	 *@return array just so that template shows -  sets CurrentOrder variable
	 **/
	function showorder($request) {
		if(!$this->currentOrder) {
			$this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
		}
		if(isset($_REQUEST["print"])) {
			return $this->renderWith("Invoice")''
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

	function retrieveorder(){
		return array();
	}	


	/**
	 *@return Array - just so the template is still displayed
	 **/
	function sendreceipt($request) {
		if($o = $this->currentOrder) {
			if($m = $o->Member()) {
				if($m->Email) {
					$o->sendReceipt(_t("Account.COPYONLY", "--- COPY ONLY ---"), true);
					$this->message = _t('Account.RECEIPTSENT', 'An order receipt has been sent to: ').$m->Email.'.';
				}
				else {
					$this->message = _t('Account.RECEIPTNOTSENTNOEMAIL', 'No email could be found for sending this receipt.');
				}
			}
			else {
				$this->message = _t('Account.RECEIPTNOTSENTNOEMAIL', 'No email could be found for sending this receipt.');
			}
		}
		else {
			$this->message = _t('Account.RECEIPTNOTSENTNOORDER', 'Order could not be found...');
		}
		Director::redirectBack();
		return array();
	}


	protected function workOutMessagesAndActions(){
		if(!$this->workedOutMessagesAndActions) {
			$this->actionLinks = new DataObjectSet();
			if ($this->currentOrder && $this->currentOrder->IsSubmitted()) {
				//start a new order
				$this->actionLinks->push(new ArrayData(array (
					"Title" => $this->StartNewOrderLinkLabel,
					"Link" => CartPage::new_order_link()
				)));
			}			
			$this->workedOutMessagesAndActions = true;
			//does nothing at present....
		}
	}


}



