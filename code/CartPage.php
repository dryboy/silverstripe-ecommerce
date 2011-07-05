<?php

/**
 * @description: This is a page that shows the cart content,
 * without "leading to" checking out. That is, there is no "next step" functionality
 * or a way to submit the order.
 * NOTE: both the Account and the Checkout Page extend from this class as they
 * share some functionality.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class CartPage extends Page{

	public static $db = array();

	public static $has_one = array(
		'CheckoutPage' => 'CheckoutPage',
		'ContinuePage' => 'SiteTree'
	);

	public static $icon = 'ecommerce/images/icons/cart';

	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'CartPage'");
	}
	/**
	 *@return Fieldset
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		if($this->ClassName == "CartPage") {
			if($checkouts = DataObject::get('CheckoutPage')) {
				$fields->addFieldToTab('Root.Content.Links',new DropdownField('CheckoutPageID','Checkout Page',$checkouts->toDropdownMap()));
			}
			$fields->addFieldToTab('Root.Content.Links',new TreeDropdownField('ContinuePageID','Continue Page',"SiteTree"));
		}
		return $fields;
	}

	/**
	 *@return String (HTML Snippet)
	 **/
	function EcommerceMenuTitle() {
		$count = 0;
		$order = ShoppingCart::current_order();
		if($order) {
			$count = $order->TotalItems();
		}
		$v = $this->MenuTitle;
		if($count) {
			$v .= " <span class=\"numberOfItemsInCart\">(".$count.")</span>";
		}
		return $v;
	}

	/**
	 * Returns the link or the Link to the CartPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if(!$page = DataObject::get_one('CartPage', "\"ClassName\" = 'CartPage'")) {
			return CheckoutPage::find_link();
		}
		return $page->Link();
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	public function getOrderLink($orderID) {
		return $this->Link('showorder').'/'.$orderID.'/';
	}

}

class CartPage_Controller extends Page_Controller{

	protected $currentOrder = null;

	protected $memberID = 0;

	protected $message = "";

	protected static $session_code = "CartPageMessage";
		static function set_session_code($s) {self::$session_code = $s;}
		static function get_session_code() {return self::$session_code;}

	public static function set_message($s) {Session::set(self::get_session_code(), $s);}

	public function init() {
		parent::init();
		//Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js"); VIA EcommerceSiteTreeExtension::initcontentcontroller()
		Requirements::javascript('ecommerce/javascript/EcomCart.js'); 
		$orderID = 0;
		//WE HAVE THIS FOR SUBMITTING FORMS!
		if(isset($_REQUEST['OrderID'])) {
			$orderID = intval($_REQUEST['OrderID']);
		}
		elseif(Director::urlParam('ID') && Director::urlParam('Action') == "showorder"){
			$orderID = intval(Director::urlParam('ID'));
		}
		if($orderID) {
			$this->currentOrder = Order::get_by_id_if_can_view($orderID);
		}
		else {
			$this->currentOrder = ShoppingCart::current_order();
			if(!$this->currentOrder) {
				$this->currentOrder = DataObject::get_one("Order", "\"SessionID\" = '".session_id()."'");
			}
		}
	}

	/**
	 *@return String
	 **/
	function Message() {
		if($sessionMessage = Session::get(self::get_session_code())) {
			$this->message .= $sessionMessage;
			Session::set(self::get_session_code(), "");
			Session::clear(self::get_session_code());
		}
		return $this->message;
	}

	/**
	 *
	 *@return DataObject | Null - Order
	 **/
	public function Order() {
		return $this->currentOrder;
	}

	/**
	 *
	 *@return Boolean
	 **/
	function CanEditOrder() {
		if($this->currentOrder) {
			return $this->currentOrder->canEdit();
		}
		return false;
	}

	/**
	 *@return array just so that template shows -  sets CurrentOrder variable
	 **/
	function showorder($request) {
		//Requirements::themedCSS('Order'); // VIA Order.ss
		//Requirements::themedCSS('Order_print', 'print'); // VIA Order.ss - hopefully that works!!!
		if(!$this->currentOrder) {
			$this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
		}
		return array();
	}

	/**
	 * Loads either the current order from the shopping cart or
	 * by the specified Order ID in the URL.
	 *
	 */
	function loadorder($request) {
		if ($orderID = intval($request->param('ID'))) {
			$this->currentOrder = ShoppingCart::singleton()->loadOrder($orderID);
			Director :: redirect($this->Link());
			exit();
		}
		return array ();
	}

	/**
	 * Start a new order
	 */
	function startneworder($request) {
		ShoppingCart :: singleton()->clear();
		Director :: redirect($this->Link());
	}


	/**
	 *@return Array - just so the template is still displayed
	 **/
	function sendreceipt($request) {
		if($this->orderID) {
			if($o = $this->CurrentOrder()) {
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
				Director::redirect($o->Link());
			}
			else {
				$this->message = _t('Account.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
			}
		}
		return array();
	}

}



