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

	public static $icon = 'ecommerce/images/icons/OrderConfirmationPage';

	public static $db = array(
		"YouDontHaveSavedOrders" => "HTMLText"
	);

	function canCreate($member = null) {
		return !DataObject :: get_one("OrderConfirmationPage", "\"ClassName\" = 'OrderConfirmationPage'");
	}

	public static $defaults = array(
		"YouDontHaveSavedOrders" => "<p>You dont have any saved orders yet.</p>",
		"ShowInMenus" => false,
		"ShowInSearch" => false
	);

	/**
	 *@return Fieldset
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Content.Messages', new HTMLEditorField("YouDontHaveSavedOrders", "Message to user: You dont have any saved orders (e.g. you have not placed any orders yet) ", 5, 5));
		return $fields;
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
			//Security::permissionFailure($this, $messages);
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
			Requirements::themedCSS("OrderReport"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("OrderReport_Print", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			return $this->renderWith("Invoice");
		}
		return array();
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
					$this->message = _t('OrderConfirmationPage.RECEIPTSENT', 'An order receipt has been sent to: ').$m->Email.'.';
				}
				else {
					$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOTSENDING', 'Email could NOT be sent.');
				}
			}
			else {
				$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No email could be found for sending this receipt.');
			}
		}
		else {
			$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
		}
		Director::redirectBack();
		return array();
	}


	protected function workOutMessagesAndActions(){
		if(!$this->workedOutMessagesAndActions) {
			$this->actionLinks = new DataObjectSet();
			if($this->currentOrder) {
				if ($this->currentOrder->IsSubmitted() || !$this->currentOrder->canEdit() ) {
					if($this->StartNewOrderLinkLabel && CartPage::new_order_link())
					//start a new order
					$this->actionLinks->push(new ArrayData(array (
						"Title" => $this->StartNewOrderLinkLabel,
						"Link" => CartPage::new_order_link()
					)));
				}
				elseif($this->currentOrder->canEdit() && $this->ProceedToCheckoutLabel && $this->CheckoutPageID && $this->currentOrder && $this->currentOrder->Items()) {
					//finalise order...
					$this->actionLinks->push(new ArrayData(array (
						"Title" => $this->ProceedToCheckoutLabel,
						"Link" => $this->CheckoutPage()->Link()
					)));
				}
			}
			$this->workedOutMessagesAndActions = true;
			//does nothing at present....
		}
	}


}



