<?php

/**
 * CheckoutPage is a CMS page-type that shows the order
 * details to the customer for their current shopping
 * cart on the site. It also lets the customer review
 * the items in their cart, and manipulate them (add more,
 * deduct or remove items completely). The most important
 * thing is that the {@link CheckoutPage_Controller} handles
 * the {@link OrderForm} form instance, allowing the customer
 * to fill out their shipping details, confirming their order
 * and making a payment.
 *
 * @see CheckoutPage_Controller->Order()
 * @see OrderForm
 * @see CheckoutPage_Controller->OrderForm()
 *
 * The CheckoutPage_Controller is also responsible for setting
 * up the modifier forms for each of the OrderModifiers that are
 * enabled on the site (if applicable - some don't require a form
 * for user input). A usual implementation of a modifier form would
 * be something like allowing the customer to enter a discount code
 * so they can receive a discount on their order.
 *
 * @see OrderModifier
 * @see CheckoutPage_Controller->ModifierForms()
 *
 * TO DO: get rid of all the messages...
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class CheckoutPage extends CartPage {

	public static $icon = 'ecommerce/images/icons/CheckoutPage';

	public static $db = array (
		'HasOrderSteps' => 'Boolean',
		'InvitationToCompleteOrder' => 'HTMLText',
	);

	public static $has_one = array (
		'TermsPage' => 'Page'
	);

	public static $defaults = array (
		'InvitationToCompleteOrder' => '<p>Please finalise your order below.</p>',
	);

	/**
	 * Returns the Terms and Conditions Page (if there is one).
	 * @return DataObject (Page)
	 */
	public static function find_terms_and_conditions_page() {
		$checkoutPage = DataObject::get_one('CheckoutPage', "\"ClassName\" = 'CheckoutPage'");
		if($checkoutPage) {
			return DataObject::get_by_id('Page', $checkoutPage->TermsPageID);
		}
	}

	/**
	 * Returns the link or the Link to the account page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if ($page = DataObject::get_one("CheckoutPage", "\"ClassName\" = 'CheckoutPage'")) {
			return $page->Link();
		}
		return "";
	}

	/**
	 * Returns the link to the checkout page on this site, using
	 * a specific Order ID that already exists in the database.
	 *
	 * @param int $orderID ID of the {@link Order}
	 * @param boolean $urlSegment If set to TRUE, only returns the URLSegment field
	 * @return string Link to checkout page
	 */
	public static function get_checkout_order_link($orderID) {
		if($page = self::find_link()) {
			return $page->Link("showorder") . "/" . $orderID . "/";
		}
		return "";
	}

	/**
	 * Standard SS function, we only allow for one checkout page to exist
	 *@return Boolean
	 **/
	function canCreate($member = null) {
		return !DataObject :: get_one("CheckoutPage", "\"ClassName\" = 'CheckoutPage'");
	}

	/**
	 * Standard SS function
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent :: getCMSFields();
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ProceedToCheckoutLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ContinueShoppingLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"ContinuePageID");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"LoadOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"CurrentOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"SaveOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Messages.Messages.Actions',"DeleteOrderLinkLabel");
		$fields->addFieldToTab('Root.Content.Process', new TreeDropdownField('TermsPageID', 'Terms and Conditions Page', 'SiteTree'));
		$fields->addFieldToTab('Root.Content.Process', new CheckboxField('HasOrderSteps', 'Checkout Process in Steps'));
		$fields->addFieldToTab('Root.Content.Main', new HtmlEditorField('InvitationToCompleteOrder', 'Invitation to complete order ... shown when the customer can do a regular checkout', $row = 4));
		//The Content field has a slightly different meaning for the Checkout Page.
		$fields->removeFieldFromTab('Root.Content.Main', "Content");
		$fields->addFieldToTab('Root.Content.Messages.Messages.AlwaysVisible', new HtmlEditorField('Content', 'General note - always visible on the checkout page', 7, 7));
		return $fields;
	}

}
class CheckoutPage_Controller extends CartPage_Controller {

	/**
	 * FOR STEP STUFF SEE BELOW
	 **/


	/**
	 * Standard SS function
	 * if set to false, user can edit order, if set to true, user can only review order
	 **/
	public function init() {
		parent::init();
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
		if($this->HasOrderSteps) {
			if(!$this->orderstep) {
				$this->currentStep = $this->checkoutSteps[0];
			}
		}
	}

	function processmodifierform($request) {
		$formName = $request->param("ID");
		if ($forms = $this->ModifierForms()) {
			foreach ($forms as $form) {
				$fullName = explode("/", $form->Name());
				$shortName = $fullName[1];
				if ($shortName == $formName) {
					return $form->submit($request->requestVars(), $form);
				}
			}
		}
	}

	/**
	 * Returns a DataObjectSet of {@link OrderModifierForm} objects. These
	 * forms are used in the OrderInformation HTML table for the user to fill
	 * in as needed for each modifier applied on the site.
	 *
	 * @return DataObjectSet
	 */
	function ModifierForms() {
		if ($this->currentOrder) {
			return $this->currentOrder->getModifierForms();
		}
	}
	/**
	 * Adjusts the page title if steps are being used
	 *
	 * @return String
	 */
	function Title() {
		$v = $this->Title;
		if($this->HasOrderSteps) {
			if($this->checkoutSteps) {
				$position = 0;
				$currentPosition = 1;
				foreach($this->checkoutSteps as $pos => $step) {
					$position++;
					if($step == $this->currentStep) {
						$currentPosition = $position;
					}
				}
				$v .= " step $currentPosition of $position";
			}
		}
		return $v;
	}


	/**
	 * Returns a form allowing a user to enter their
	 * details to checkout their order.
	 *
	 * @return OrderForm object
	 */
	function OrderForm() {
		$form = new OrderForm($this, 'OrderForm');
		$this->data()->extend('updateOrderForm', $form);
		//load session data
		if ($data = Session :: get("FormInfo.{$form->FormName()}.data")) {
			$form->loadDataFrom($data);
		}
		return $form;
	}

	/**
	 * Can the user proceed? It must be an editable order (see @link CartPage)
	 * and is must also contain items.
	 *
	 * @return boolean
	 */
	function CanCheckout() {
		return $this->currentOrder->Items()  && !$this->currentOrder->IsSubmitted();
	}


	function ModifierForm($request) {
		user_error("Make sure that you set the controller for your ModifierForm to a controller directly associated with the Modifier", E_USER_WARNING);
		return array ();
	}

	/**
	 * STEP STUFF
	 *

	/**
	 *@var $currentStep Integer
	 * if set to zero (0), all steps will be included
	 **/
	protected $checkoutSteps = array(
		"orderitems",
		"ordermodifiers",
		"orderconfirmation",
		"orderformandpayment"
	);


	/**
	 *@var $currentStep Integer
	 **/
	protected $currentStep = "";

	/**
	 * Show only one step in the order process (e.g. only show OrderItems)
	 */
	function orderstep($request) {
		$this->HasOrderSteps = true;
		$step = $request->Param("ID");
		if($step) {
			if (in_array($step, $this->checkoutSteps)) {
				$this->currentStep = $step;
			}
		}
		return array ();
	}


	/**
	 *@param $part Strong (OrderItems, OrderModifiers, OrderForm, OrderPayment)
	 *@return Boolean
	 **/
	function CanShowStep($step) {
		if (!$this->currentStep) {
			return in_array($step, $this->checkoutSteps);
		}
		else {
			return $step == $this->currentStep;
		}
	}


}
