<?php

/**
 * @description: The order class is a databound object for handling Orders within SilverStripe.
 * Note that it works closely with the ShoppingCart class, which accompanies the Order
 * until it has been paid for / confirmed by the user.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: model
 *
 * CONTENTS:
 * ----------------------------------------------
 * 1. CMS STUFF
 * 2. MAIN TRANSITION FUNCTIONS:
 * 3. STATUS RELATED FUNCTIONS / SHORTCUTS
 * 4. LINKING ORDER WITH MEMBER AND ADDRESS
 * 5. CUSTOMER COMMUNICATION
 * 6. ITEM MANAGEMENT
 * 7. CRUD METHODS (e.g. canView, canEdit, canDelete, etc...)
 * 8. GET METHODS (e.g. Total, SubTotal, Title, etc...)
 * 9. TEMPLATE RELATED STUFF
 * 10. STANDARD SS METHODS (requireDefaultRecords, onBeforeDelete, etc...)
 * 11. DEBUG
 *
 **/

class Order extends DataObject {

	public static $db = array(
		'SessionID' => "Varchar(32)", //so that in the future we can link sessions with Orders.... One session can have several orders, but an order can onnly have one session
		'UseShippingAddress' => 'Boolean',
		'CustomerOrderNote' => 'Text'
	);

	public static $has_one = array(
		'Member' => 'Member',
		'BillingAddress' => 'BillingAddress',
		'ShippingAddress' => 'ShippingAddress',
		'Status' => 'OrderStep',
		'CancelledBy' => 'Member'
	);

	public static $has_many = array(
		'Attributes' => 'OrderAttribute',
		'OrderStatusLogs' => 'OrderStatusLog',
		'Payments' => 'Payment',
		'Emails' => 'OrderEmailRecord'
	);

	public static $many_many = array();

	public static $belongs_many_many = array();

	public static $defaults = array();

	public static $indexes = array(
		"SessionID" => true
	);

	public static $default_sort = "\"Created\" DESC";

	public static $casting = array(
		'Title' => 'Text',
		'Total' => 'Currency',
		'SubTotal' => 'Currency',
		'TotalPaid' => 'Currency',
		'TotalOutstanding' => 'Currency',
		'TotalItems' => 'Int',
		'TotalItemsTimesQuantity' => 'Int',
		'IsCancelled' => 'Boolean',
		'Country' => "Varchar", //This is the applicable country for the order - for tax purposes, etc....
		'FullNameCountry' => "Varchar",
		'IsSubmitted' => "Boolean"
	);

	public static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);

	public static $singular_name = "Order";
		function i18n_singular_name() { return _t("Order.ORDER", "Order");}

	public static $plural_name = "Orders";
		function i18n_plural_name() { return _t("Order.ORDERS", "Orders");}

	/**
	 * Modifiers represent the additional charges or
	 * deductions associated to an order, such as
	 * shipping, taxes, vouchers etc.
	 *
	 * @var array
	 */
	protected static $modifiers = array();

	/**
	 * Total Items : total items in cart
	 *
	 * @var integer / null
	 */

	protected static $total_items = null;

	/**
	 * Set the modifiers that apply to this site.
	 *
	 * @param array $modifiers An array of {@link OrderModifier} subclass names
	 */
	public static function set_modifiers($modifiers, $replace = false) {
		if($replace) {
			self::$modifiers = $modifiers;
		}
		else {
			self::$modifiers =  array_merge(self::$modifiers,$modifiers);
		}
	}


	public static function get_modifier_forms($controller) {
		user_error("this method has been changed to getModifierForms, the current function has been depreciated", E_USER_ERROR);
	}

	/**
	 * Returns a set of modifier forms for use in the checkout order form,
	 * Controller is optional, because the orderForm has its own default controller.
	 *
	 *@return DataObjectSet of OrderModiferForms
	 **/
	public function getModifierForms($optionalController = null) {
		$dos = new DataObjectSet();
		if($modifiers = $this->Modifiers()) {
			foreach($modifiers as $modifier) {
				if($modifier->showForm()) {
					if($form = $modifier->getModifierForm($optionalController)) {
						$dos->push($form);
					}
				}
			}
		}
		if( $dos->count() ) {
			return $dos;
		}
		else {
			return null;
		}
	}


	/**
	 * The maximum difference between the total cost of the order and the total payment made.
	 * If this value is, for example, 10 cents and the total amount outstanding for an order is less than
	 * ten cents, than the order is considered "paid".
	 *@var Float
	 **/
	protected static $maximum_ignorable_sales_payments_difference = 0.01;
		static function set_maximum_ignorable_sales_payments_difference(float $f) {self::$maximum_ignorable_sales_payments_difference = $f;}
		static function get_maximum_ignorable_sales_payments_difference() {return(float)self::$maximum_ignorable_sales_payments_difference;}

	/**
	 * Each order has an order number.  Normally, the order numbers start at one,
	 * but in case you would like this number to be different you can set it here.
	 *
	 *@var Integer
	 **/
 	protected static $order_id_start_number = 0;
		static function set_order_id_start_number(integer $i) {self::$order_id_start_number = $i;}
		static function get_order_id_start_number() {return(integer)self::$order_id_start_number;}


	/**
	 * This function returns the OrderSteps
	 *
	 *@returns: DataObjectSet (OrderSteps)
	 **/
	public static function get_order_status_options() {
		return DataObject::get("OrderStep");
	}

	/**
	 * Like the standard get_by_id, but it checks whether we are allowed to view the order.
	 *
	 *@returns: DataObject (Order)
	 **/
	public static function get_by_id_if_can_view($id) {
		$order = DataObject::get_by_id("Order", $id);
		if($order && is_object($order) && $order->canView()){
			if(!$order->canEdit()) {
				// LITTLE HACK TO MAKE SURE WE SHOW THE LATEST INFORMATION!
				$this->currentOrder->tryToFinaliseOrder();
			}
			return $order;
		}
		return null;
	}









/*******************************************************
   * 1. CMS STUFF
*******************************************************/

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 **/
	public static $summary_fields = array(
		"\"Order\".\"ID\"" => "ID",
		'BillingAddress.Surname',
		'BillingAddress.Email',
		'TotalAsCurrencyObject.Nice' => 'Total',
		'Status.Name',
	);
		public static function get_summary_fields() {return self::$summary_fields;}

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 **/
	public static $searchable_fields = array(
		'ID' => array(
			'field' => 'NumericField',
			'title' => 'Order Number'
		),
		'Member.FirstName' => array(
			'title' => 'Customer First Name',
			'filter' => 'PartialMatchFilter'
		),
		'Member.Surname' => array(
			'title' => 'Customer Last Name',
			'filter' => 'PartialMatchFilter'
		),
		'BillingAddress.Email' => array(
			'title' => 'Customer Email',
			'filter' => 'PartialMatchFilter'
		),

		//TODO: this breaks the sales part of the CMS
		/*'Member.Phone' => array(
			'title' => 'Customer Phone',
			'filter' => 'PartialMatchFilter'
		),*/
		'Created' => array(
			'field' => 'TextField',
			'filter' => 'OrderFilters_AroundDateFilter',
			'title' => "Date (e.g. Today)"
		),
		'TotalPaid' => array(
			'filter' => 'OrderFilters_MustHaveAtLeastOnePayment'
		),
		'StatusID' => array(
			'filter' => 'OrderFilters_MultiOptionsetStatusIDFilter'
		),
		'CancelledByID' => array(
			'filter' => 'OrderFilters_HasBeenCancelled',
			'title' => "Cancelled"
		)
		/*,
		'To' => array(
			'field' => 'DateField',
			'filter' => 'OrderFilters_EqualOrSmallerDateFilter'
		)
		*/
	);

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 **/
	function scaffoldSearchFields(){
		$fieldSet = parent::scaffoldSearchFields();
		if($statusOptions = self::get_order_status_options()) {
			$fieldSet->push(new CheckboxSetField("StatusID", "Status", $statusOptions->toDropDownMap()));
		}
		$fieldSet->push(new DropdownField("TotalPaid", "Has Payment", array(-1 => "(Any)", 1 => "yes", 0 => "no")));
		$fieldSet->push(new DropdownField("CancelledByID", "Cancelled", array(-1 => "(Any)", 1 => "yes", 0 => "no")));
		return $fieldSet;
	}

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 **/
	function validate() {
		if($this->StatusID) {
			//do nothing
		}
		else {
			$firstStep = DataObject::get_one("OrderStep");
			if($firstStep) {
				$this->StatusID = $firstStep->ID;
				if($this->StatusID) {
					//rerun with valid StatusID in place
					return $this->validate();
				}
			}
			return new ValidationResult(false, _t("Order.MUSTSETSTATUS", "You must set a status"));
		}
		return parent::validate();
	}

	/**
	 * STANDARD SILVERSTRIPE STUFF
	 * broken up into submitted and not (yet) submitted
	 **/
	function getCMSFields(){
		$this->tryToFinaliseOrder();
		$fields = parent::getCMSFields();


		$submitted = (bool)$this->IsSubmitted();
		$fieldsAndTabsToBeRemoved = array('MemberID', 'Attributes', 'SessionID', 'BillingAddressID', 'ShippingAddressID', 'UseShippingAddress', 'OrderStatusLogs', 'Payments');
		if(!$submitted) {
			$fieldsAndTabsToBeRemoved[] = "Emails";
		}
		else {
			$fieldsAndTabsToBeRemoved[] = "CustomerOrderNote";
		}
		foreach($fieldsAndTabsToBeRemoved as $field) {
			$fields->removeByName($field);
		}
		$fields->insertBefore(new LiteralField('Title',"<h2>".$this->Title()."</h2>"),'Root');
		if($submitted) {
			$htmlSummary = $this->renderWith("Order");
			$printlabel = _t("Order.PRINTINVOICE", "Print Invoice");
			$fields->addFieldsToTab('Root.Main', array(
				new LiteralField("PrintInvoice",'<p class="print"><a href="OrderReport_Popup/index/'.$this->ID.'?print=1" onclick="javascript: window.open(this.href, \'print_order\', \'toolbar=0,scrollbars=1,location=1,statusbar=0,menubar=0,resizable=1,width=800,height=600,left = 50,top = 50\'); return false;">'.$printlabel.'</a></p>')
			));
			$fields->addFieldToTab('Root.Main', new LiteralField('MainDetails', $htmlSummary));
			$paymentsTable = new HasManyComplexTableField(
				$this,
				"Payments", //$name
				"Payment", //$sourceClass =
				null, //$fieldList =
				null, //$detailedFormFields =
				"\"OrderID\" = ".$this->ID."", //$sourceFilter =
				"\"Created\" ASC", //$sourceSort =
				null //$sourceJoin =
			);
			$paymentsTable->setPageSize(100);
			if($this->IsPaid()){
				$paymentsTable->setPermissions(array('export', 'show'));
			}
			else {
				$paymentsTable->setPermissions(array('edit', 'delete', 'export', 'add', 'show'));
			}
			$paymentsTable->setShowPagination(false);
			$paymentsTable->setRelationAutoSetting(true);
			$fields->addFieldToTab('Root.Payments',$paymentsTable);
			if($member = $this->Member()) {
				$fields->addFieldToTab('Root.Customer', $member->getEcommerceFieldsForCMS());
			}
			/*
			$fields->addFieldsToTab(
				"Root.Delivery",
				array(
					new CheckboxField("UseShippingAddress", "Shipping Address is not the same as Billing Address"),
					new HeaderField("DispatchLog", _t("Order.DISPATCHLOG", "Dispatch Log")),
					new ComplexTableField($controller = "OrderStatusLog_Dispatch", "OrderStatusLog_Dispatch", "OrderStatusLog_Dispatch", $fieldList = null, $detailFormFields = null, $sourceFilter = "\"OrderID\" = ".$this->ID, $sourceSort = "", $sourceJoin = "")
				)
			);
			*/
		}
		else {
			$fields->addFieldToTab('Root.Main', new LiteralField('MainDetails', _t("Order.NODETAILSSHOWN", '<p>No details are shown here as this order has not been submitted yet. Once you change the status of the order more options will be available.</p>')));
			$orderItemsTable = new HasManyComplexTableField(
				$this, //$controller
				"Attributes", //$name =
				"OrderItem", //$sourceClass =
				null, //$fieldList =
				null, //$detailedFormFields =
				"\"OrderID\" = ".$this->ID."", //$sourceFilter =
				"\"Created\" ASC", //$sourceSort =
				null //$sourceJoin =
			);
			$orderItemsTable->setPermissions(array('edit', 'delete', 'export', 'add', 'inlineadd', "show"));
			$orderItemsTable->setShowPagination(false);
			$orderItemsTable->setRelationAutoSetting(true);
			$orderItemsTable->addSummary(
				"Total",
				array("Total" => array("sum","Currency->Nice"))
			);
			$fields->addFieldToTab('Root.Items',$orderItemsTable);
			$modifierTable = new TableListField(
				"OrderModifiers", //$name
				"OrderModifier", //$sourceClass =
				OrderModifier::$summary_fields, //$fieldList =
				"\"OrderID\" = ".$this->ID."" //$sourceFilter =
			);
			$modifierTable->setPermissions(array('edit', 'delete', 'export', 'add', 'show'));
			$modifierTable->setPageSize(100);
			$fields->addFieldToTab('Root.Extras',$modifierTable);
		}
		if($this->MyStep()) {
			$this->MyStep()->addOrderStepFields($fields, $this);
		}
		$this->extend('updateCMSFields',$fields);
		$fields->addFieldToTab('Root.Debug',new LiteralField('sessionid',"order session id: ".$this->SessionID." your session id: ".session_id()));//debug
		return $fields;
	}

	/**
	 *
	 *@return HasManyComplexTableField
	 **/
	function OrderStatusLogsTable($sourceClass) {
		$orderStatusLogsTable = new HasManyComplexTableField(
			$this,
			"OrderStatusLogs", //$name
			$sourceClass, //$sourceClass =
			null, //$fieldList =
			null, //$detailedFormFields =
			"\"OrderID\" = ".$this->ID.""
		);
		$orderStatusLogsTable->setPageSize(100);
		$orderStatusLogsTable->setShowPagination(false);
		$orderStatusLogsTable->setRelationAutoSetting(true);
		return $orderStatusLogsTable;
	}









/*******************************************************
   * 2. MAIN TRANSITION FUNCTIONS
*******************************************************/

	/**
	 *init runs on start of a new Order (@see onAfterWrite)
	 * it adds all the modifiers to the orders and the starting OrderStep
	 *
	 * @return DataObject (Order)
	 **/
	public function init() {
		//to do: check if shop is open....
		if(!$this->StatusID) {
			if($newStatus = DataObject::get_one("OrderStep")) {
				$this->StatusID = $newStatus->ID;
			}
			else {
				//user_error("There are no OrderSteps ... please Run Dev/Build", E_USER_WARNING);
			}
		}
		$createdModifiersClassNames = array();
		$this->modifiers = $this->modifiersFromDatabase($includingRemoved = true);
		if($this->modifiers) {
			foreach($this->modifiers as $modifier) {
				$createdModifiersClassNames[$modifier->ID] = $modifier->ClassName;
			}
		}
		else {
			$this->modifiers = new DataObjectSet();
		}
		if(is_array(self::$modifiers) && count(self::$modifiers) > 0) {
			foreach(self::$modifiers as $numericKey => $className) {
				if(!in_array($className, $createdModifiersClassNames)) {
					if(class_exists($className)) {
						$modifier = new $className();
						//only add the ones that should be added automatically
						if(!$modifier->DoNotAddAutomatically()) {
							if($modifier instanceof OrderModifier) {
								$modifier->OrderID = $this->ID;
								$modifier->Sort = $numericKey;
								//init method includes a WRITE
								$modifier->init();
								//IMPORTANT - add as has_many relationship  (Attributes can be a modifier OR an OrderItem)
								$this->Attributes()->add($modifier);
								$this->modifiers->push($modifier);
							}
						}
					}
					else{
						user_error("reference to a non-existing class: ".$className." in modifiers", E_USER_NOTICE);
					}
				}
			}
		}
		$this->extend('onInit', $this);
		$this->write();
		return $this;
	}

	/**
	 * Goes through the order steps and tries to "apply" the next status to the order
	 *
	 **/
	public function tryToFinaliseOrder() {
		do {
			//status of order is being progressed
			$nextStatusID = $this->doNextStatus();
		}
		while ($nextStatusID);
	}

	/**
	 * Goes through the order steps and tries to "apply" the next
	 *@return Integer (StatusID or false if the next status can not be "applied")
	 **/
	public function doNextStatus() {
		if($this->MyStep()->initStep($this)) {
			if($this->MyStep()->doStep($this)) {
				if($nextOrderStepObject = $this->MyStep()->nextStep($this)) {
					$this->StatusID = $nextOrderStepObject->ID;
					$this->write();
					return $this->StatusID;
				}
			}
		}
		return false;
	}










/*******************************************************
   * 3. STATUS RELATED FUNCTIONS / SHORTCUTS
*******************************************************/

	/**
	 * @return DataObject (current OrderStep)
	 */
	public function MyStep() {
		$obj = null;
		if($this->StatusID) {
			$obj = DataObject::get_by_id("OrderStep", $this->StatusID);
		}
		if(!$obj) {
			$obj = DataObject::get_one("OrderStep"); //TODO: this could produce strange results
		}
		$this->StatusID = $obj->ID;
		return $obj;
	}


	/**
	 * @return DataObject (current OrderStep that can be seen by customer)
	 */
	public function CurrentStepVisibleToCustomer() {
		$obj = $this->MyStep();
		if($obj->HideStepFromCustomer) {
			$obj = DataObject::get_one("OrderStep", "\"Sort\" < ".$obj->Sort." AND \"HideStepFromCustomer\" = 0");
			if(!$obj) {
				$obj = DataObject::get_one("OrderStep");
			}
		}
		return $obj;
	}

	/**
	 * works out if the order is still at the first OrderStep.
	 * @return boolean
	 */
	public function IsFirstStep() {
		$firstStep = DataObject::get_one("OrderStep");
		$currentStep = $this->MyStep();
		if($firstStep && $currentStep) {
			if($firstStep->ID == $currentStep->ID) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is the order still being "edited" by the customer?
	 * @return boolean
	 */
	function IsInCart(){
		return (bool)$this->IsSubmitted();
	}

	/**
	 * The order has "passed" the IsInCart phase
	 * @return boolean
	 */
	function IsPastCart(){
		return (bool) !$this->IsInCart();
	}

	/**
	* Are there still steps the order needs to go through?
	 * @return boolean
	 */
	function IsUncomplete() {
		return (bool)$this->MyStep()->ShowAsUncompletedOrder;
	}

	/**
	* Is the order in the :"processing" phaase.?
	 * @return boolean
	 */
	function IsProcessing() {
		return (bool)$this->MyStep()->ShowAsInProcessOrder;
	}

	/**
	* Is the order completed?
	 * @return boolean
	 */
	function IsCompleted() {
		return (bool)$this->MyStep()->ShowAsCompletedOrder;
	}

	/**
	 * Has the order been paid?
	 * @return boolean
	 */
	function IsPaid() {
		return (bool)($this->Total() > 0 && $this->TotalOutstanding() <= 0);
	}

	/**
	 * Has the order been cancelled?
	 * @return boolean
	 */
	public function IsCancelled() {
		return (bool)$this->CancelledByID;
	}

	/**
	 * Has the order been cancelled by the customer?
	 * @return boolean
	 */
	function IsCustomerCancelled() {
		if($this->MemberID == $this->IsCancelledID && $this->MemberID > 0) {
			return true;
		}
		return false;
	}


	/**
	 * Has the order been cancelled by the  administrator?
	 * @return boolean
	 */
	function IsAdminCancelled() {
		if($this->IsCancelled()) {
			if(!$this->IsCustomerCancelled()) {
				$admin = DataObject::get_by_id("Member", $this->CancelledByID);
				if($admin) {
					if($admin->IsShopAdmin()) {
						return true;
					}
				}
			}
		}
		return false;
	}


	/**
	* Is the Shop Closed for business?
	 * @return boolean
	 */
	function ShopClosed() {
		$siteConfig = DataObject::get_one("SiteConfig");
		return $siteConfig->ShopClosed;
	}









/*******************************************************
   * 4. LINKING ORDER WITH MEMBER AND ADDRESS
*******************************************************/


	/**
	 * returns a member linked to the order -
	 * this member is NOT written, if a member is already linked, it will return the existing member.
	 * also note that if a new member is created, it is not automatically written
	 *@return: DataObject (Member)
	 **/
	public function CreateOrReturnExistingMember() {
		if(!$this->MemberID) {
			if($member = Member::currentMember()) {
				$this->write();
			}
		}
		else {
			$member = DataObject::get_by_id("Member", $this->MemberID);
		}
		if(!$member) {
			$member = new Member();
		}
		if($member) {
			if($member->ID) {
				$this->MemberID = $member->ID;
			}
			return $member;
		}
		return null;
	}

	/**
	 * DOES NOT WRITE OrderAddress for the sake of it.
	 * returns either the existing one or a new one...
	 * Method used to retrieve object e.g. for $order->BillingAddress(); "BillingAddress" is the method name you can use.
	 * If the method name is the same as the class name then dont worry about providing one.
	 *
	 *@param String $className   - ClassName of the Address (e.g. BillingAddress or ShippingAddress)
	 *@param String $alternativeMethodName  -
	 *
	 * @return DataObject (OrderAddress)
	 **/

	public function CreateOrReturnExistingAddress(string $className, $alternativeMethodName = '') {
		if($this->ID) {
			$variableName = $className."ID";
			$methodName = $className;
			if($alternativeMethodName) {
				$methodName = $alternativeMethodName;
			}
			$address = null;
			if($this->$variableName) {
				$address = $this->$methodName();
			}
			if(!$address) {
				$address = new $className();
				if($member = $this->CreateOrReturnExistingMember()) {
					$address->FillWithLastAddressFromMember($member, $write = false);
				}
			}
			if($address) {
				//save address
				$address->OrderID = $this->ID;
				$address->write();
				//save order
				$this->$variableName = $address->ID;
				$this->write();	
				return $address;
			}
		}
		return null;
	}

	/**
	 * Sets the country in the billing and shipping address
	 *
	 **/
	public function SetCountry($countryCode) {
		if($billingAddress = $this->CreateOrReturnExistingAddress("BillingAddress")) {
			$billingAddress->SetCountryFields($countryCode);
		}
		if(OrderAddress::get_use_separate_shipping_address()) {
			if($shippingAddress = $this->CreateOrReturnExistingAddress("ShippingAddress")) {
				$shippingAddress->SetCountryFields($countryCode);
			}
		}
	}

	/**
	 * Sets the country in the billing and shipping address
	 *
	 **/
	public function SetRegion($regionID) {
		if($billingAddress = $this->CreateOrReturnExistingAddress("BillingAddress")) {
			$billingAddress->SetRegionFields($regionID);
		}
		if(OrderAddress::get_use_separate_shipping_address()) {
			if($shippingAddress = $this->CreateOrReturnExistingAddress("ShippingAddress")) {
				$shippingAddress->SetRegionFields($regionID);
			}
		}
	}










/*******************************************************
   * 5. CUSTOMER COMMUNICATION
*******************************************************/

	/**
	 * Send the invoice of the order by email.
	 */
	function sendInvoice($message = "", $resend = false) {
		$subject = str_replace("{OrderNumber}", $this->ID,Order_Email::get_subject());
		$replacementArray = array("Message" => $message);
		return $this->sendEmail('Order_ReceiptEmail', $subject, $replacementArray, $resend);
	}

	/**
	 * Send the receipt of the order by email.
	 * Precondition: The order payment has been successful
	 */
	function sendReceipt($message = "", $resend = false) {
		$subject = str_replace("{OrderNumber}", $this->ID,Order_Email::get_subject());
		$replacementArray = array(
			'Message' => $message
		);
		return $this->sendEmail('Order_ReceiptEmail', $subject, $replacementArray, $resend);
	}

	/**
	 * Send a message to the client containing the latest
	 * note of {@link OrderStatusLog} and the current status.
	 *
	 * Used in {@link OrderReport}.
	 *
	 * @param string $note Optional note-content (instead of using the OrderStatusLog)
	 */
	function sendStatusChange($subject, $message = '', $resend = false) {
		if(!$message) {
			$emailableLogs = DataObject::get('OrderStatusLog', "\"OrderID\" = {$this->ID} AND \"EmailCustomer\" = 1 AND \"EmailSent\" = 0 ", "\"Created\" DESC", null, 1);
			if($logs) {
				$latestEmailableLog = $lemailableLogs->First();
				$message = $latestEmailableLog->Note;
			}
		}
		if(!$subject) {
			$subject = str_replace("{OrderNumber}", $this->ID,Order_Email::get_subject());
		}
		$replacementArray = array("Message" => $message);
		return $this->sendEmail('Order_StatusEmail', $subject, $replacementArray, $resend);
	}


	/**
	 * Send a mail of the order to the client (and another to the admin).
	 *
	 * @param String $emailClass - the class name of the email you wish to send
	 * @param String $subject - email subject
	 * @param Array $replacementArray - array of fields to replace with data...
	 * @param Boolean $copyToAdmin - true by default, whether it should send a copy to the admin
	 *
	 * @return Boolean TRUE for success, FALSE for failure (not tested)
	 */
	protected function sendEmail($emailClass, $subject, $replacementArray = array(), $resend = false) {
		$replacementArray["Order"] = $this;
		$replacementArray["EmailLogo"] = SiteConfig::current_site_config()->EmailLogo();
 		$from = Order_Email::get_from_email();
 		$to = $this->BillingAddress()->Email;
		//TO DO: should be a payment specific message as well???
		$email = new $emailClass();
		if(!($email instanceOf Email)) {
			user_error("No correct email class provided.", E_USER_ERROR);
		}
 		$email->setFrom($from);
 		$email->setTo($to);
 		$email->setSubject($subject);
		$email->populateTemplate($replacementArray);
		return $email->send(null, $this, $resend);
	}










/*******************************************************
   * 6. ITEM MANAGEMENT
*******************************************************/

	/**
	 * Returns the items of the order.
	 * Items are the order items (products) and NOT the modifiers (discount, tax, etc...)
	 *
	 *@param String filter - where statement to exclude certain items.
	 *
	 *@return DataObjectSet (OrderItems)
	 */
	function Items($filter = "") {
 		if(!$this->ID){
 			$this->write();
		}
		return $this->itemsFromDatabase($filter);
	}

	/**
	 * Return all the {@link OrderItem} instances that are
	 * available as records in the database.
	 *
	 *@param String filter - where statement to exclude certain items.
	 *
	 * @return DataObjectSet
	 */
	protected function itemsFromDatabase($filter = null) {
		$extrafilter = ($filter) ? " AND $filter" : "";
		$items = DataObject::get("OrderItem", "\"OrderID\" = '$this->ID' AND \"Quantity\" > 0 $extrafilter");
		return $items;
	}


	/**
	 * Returns the modifiers of the order, if it hasn't been saved yet
	 * it returns the modifiers from session, if it has, it returns them
	 * from the DB entry. ONLY USE OUTSIDE ORDER
	 *
	 *@param String filter - where statement to exclude certain items.
	 *
	 *@return DataObjectSet(OrderModifiers)
	 */
 	function Modifiers($filter = '') {
		return $this->modifiersFromDatabase();
	}

	/**
	 * Get all {@link OrderModifier} instances that are
	 * available as records in the database.
	 * NOTE: includes REMOVED Modifiers, so that they do not get added again...
	 *
	 *@param String filter - where statement to exclude certain items.
	 *
	 * @return DataObjectSet
	 */
	protected function modifiersFromDatabase($filter = '') {
		$extrafilter = ($filter) ? " AND $filter" : "";
		return DataObject::get('OrderModifier', "\"OrderAttribute\".\"OrderID\" = ".$this->ID." $extrafilter");
	}

	/**
	 * Calculates and updates all the modifiers.
	 **/

	public function calculateModifiers($force = false) {
		//check if order has modifiers already
		//check /re-add all non-removable ones
		//$start = microtime();
		$createdModifiers = $this->modifiersFromDatabase();
		if($createdModifiers) {
			foreach($createdModifiers as $modifier){
				if($modifier) {
					$modifier->runUpdate();
				}
			}
		}
		$this->extend("onCalculate");
	}

	/**
	 * @param String $className: class name for the modifier
	 * @return DataObject (OrderModifier)
	 **/
	function RetrieveModifier(String $className) {
		if($modifiers = $this->Modifiers()) {
			foreach($modifiers as $modifier) {
				if($modifier instanceof $className) {
					return $modifier;
				}
			}
		}
	}

	/**
	 * Returns a TaxModifier object that provides
	 * information about tax on this order.
	 * @return DataObject (TaxModifier)
	 */
	function TaxInfo() {
		return $this->RetrieveModifier("TaxModifier");
	}










/*******************************************************
   * 7. CRUD METHODS (e.g. canView, canEdit, canDelete, etc...)
*******************************************************/

	/**
	 *
	 * @return DataObject (Member)
	 **/
	 //TODO: please comment why we make use of this function
	protected function getMemberForCanFunctions($member = null) {
		if(!$member) {$member = Member::currentMember();}
		if(!$member) {
			$member = new Member();
			$member->ID = 0;
		}
		return $member;
	}


	/**
	 *
	 *@return Boolean
	 **/
	public function canCreate($member = null) {
		$member = $this->getMemberForCanFunctions($member);
		$extended = $this->extendedCan('canCreate', $member->ID);
		if($extended !== null) {return $extended;}
		//TO DO: setup a special group of shop admins (probably can copy some code from Blog)
		if($member->ID) {
			return $member->IsShopAdmin();
		}
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function canView($member = null) {
		$member = $this->getMemberForCanFunctions($member);
		//check if this has been "altered" in a DataObjectDecorator
		$extended = $this->extendedCan('canView', $member->ID);
		if($extended !== null) {return $extended;}
		//no member present: ONLY if the member can edit the order it can be viewed...
		if($member->IsShopAdmin()) {
			return true;
		}
		elseif($this->SessionID == session_id()){

			return true;
		}
		elseif(!$this->MemberID) {
			return false;
		}
		elseif($member && $this->MemberID == $member->ID && !$this->IsCancelled()) {
			return true;
		}
		return false;
	}


	/**
	 *
	 *@return Boolean
	 **/
	function canEdit($member = null) {
		$member = $this->getMemberForCanFunctions($member);
		$extended = $this->extendedCan('canEdit', $member->ID);
		if($extended !== null) {return $extended;}
		if(!$this->canView($member) || $this->IsCancelled()) {
			return false;
		}
		if($member->ID) {
			if($member->IsShopAdmin()) {
				return true;
			}
		}
		return $this->MyStep()->CustomerCanEdit;
	}

	/**
	 *
	 *@return Boolean
	 **/
	function canPay($member = null) {
		$member = $this->getMemberForCanFunctions($member);
		$extended = $this->extendedCan('canPay', $member->ID);
		if($extended !== null) {return $extended;}
		if($this->IsPaid() || $this->IsCancelled()) {
			return false;
		}
		return $this->MyStep()->CustomerCanPay;
	}

	/**
	 *
	 *@return Boolean
	 **/
	function canCancel($member = null) {
		//if it is already cancelled it can be cancelled again
		if($this->CancelledByID) {
			return true;
		}
		$member = $this->getMemberForCanFunctions($member);
		$extended = $this->extendedCan('canCancel', $member->ID);
		if($extended !== null) {return $extended;}
		if($member->ID) {
			if($member->IsShopAdmin()) {
				return true;
			}
		}
		return $this->MyStep()->CustomerCanCancel;
	}


	/**
	 *
	 *@return Boolean
	 **/
	public function canDelete($member = null) {
		$member = $this->getMemberForCanFunctions($member);
		$extended = $this->extendedCan('canDelete', $member->ID);
		if($extended !== null) {return $extended;}
		return false;
	}


	/**
	 * Returns all the order logs that the current member can view
	 * i.e. some order logs can only be viewed by the admin (e.g. suspected fraud orderlog).
	 *
	 * @return DataObjectSet|Null (set of OrderLogs)
	 **/

	function CanViewOrderStatusLogs() {
		$canViewOrderStatusLogs = new DataObjectSet();
		$logs = $this->OrderStatusLogs();
		foreach($logs as $log) {
			if($log->canView()) {
				$canViewOrderStatusLogs->push($log);
			}
		}
		if($canViewOrderStatusLogs->count()) {
			return $canViewOrderStatusLogs;
		}
		return null;
	}








/*******************************************************
   * 8. GET METHODS (e.g. Total, SubTotal, Title, etc...)
*******************************************************/

	/**
	 * see Order::Title()
	 *@return String
	 **/
	function getTitle() {
		return $this->Title();
	}

	/**
	 * A "Title" for the order, which summarises the main details (date, and customer) in a string.
	 *@return String
	 **/
	function Title() {
		if($this->ID) {
			$v = $this->i18n_singular_name(). " #$this->ID - ".$this->dbObject('Created')->Nice();
			if($this->MemberID && $this->Member()->exists() ) {
				if($this->MemberID != Member::currentUserID()) {
					$v .= " - ".$this->Member()->getName();
				}
			}
		}
		else {
			$v = _t("Order.NEW", "New")." ".$this->i18n_singular_name();
		}
		return $v;
	}

	/**
	 * Returns the subtotal of the modifiers for this order.
	 * If a modifier appears in the excludedModifiers array, it is not counted.
	 *
	 * @param string|array $excluded - Class(es) of modifier(s) to ignore in the calculation.
	 * @param Boolean $stopAtExcludedModifier  - when this flag is TRUE, we stop adding the modifiers when we reach an excluded modifier.
	 *
	 * @return Float
	 */
	function ModifiersSubTotal($excluded = null, $stopAtExcludedModifier = false) {
		$total = 0;
		if($modifiers = $this->Modifiers()) {
			foreach($modifiers as $modifier) {
				if(!$modifier->IsRemoved()) { //we just double-check this...
					if(is_array($excluded) && in_array($modifier->ClassName, $excluded)) {
						if($stopAtExcludedModifier) {
							break;
						}
						continue;
					}
					elseif($excluded && ($modifier->ClassName == $excluded)) {
						if($stopAtExcludedModifier) {
							break;
						}
						continue;
					}
					$total += $modifier->CalculationTotal();
				}
			}
		}
		return $total;
	}

	/**
	 *
	 * @param string|array $excluded - Class(es) of modifier(s) to ignore in the calculation.
	 * @param Boolean $stopAtExcludedModifier  - when this flag is TRUE, we stop adding the modifiers when we reach an excluded modifier.
	 *
	 *@return Currency (DB Object)
	 **/
	function ModifiersSubTotalAsCurrencyObject($excluded = null, $stopAtExcludedModifier = false) {
		return DBField::create('Currency',$this->ModifiersSubTotal($excluded, $stopAtExcludedModifier));
	}

	/**
	 * Returns the subtotal of the items for this order.
	 *@return float
	 */
	function SubTotal() {
		$result = 0;
		if($items = $this->Items()) {
			foreach($items as $item) {
				if($item instanceOf OrderAttribute) {
					$result += $item->Total();
				}
			}
		}
		return $result;
	}

	/**
	 *
	 *@return Currency (DB Object)
	 **/
	function SubTotalAsCurrencyObject() {
		return DBField::create('Currency',$this->SubTotal());
	}

	/**
  	 * Returns the total cost of an order including the additional charges or deductions of its modifiers.
	 *@return float
  	 */
	function Total() {
		return $this->SubTotal() + $this->ModifiersSubTotal();
	}

	/**
	 *
	 *@return Currency (DB Object)
	 **/
	function TotalAsCurrencyObject() {
		return DBField::create('Currency',$this->Total());
	}

	/**
	 * Checks to see if any payments have been made on this order
	 * and if so, subracts the payment amount from the order
	 *
	 *@return float
	 **/
	function TotalOutstanding(){
		$total = $this->Total();
		$paid = $this->TotalPaid();
		$outstanding = $total - $paid;
		if(abs($outstanding) < self::get_maximum_ignorable_sales_payments_difference()) {
			$outstanding = 0;
		}
		return floatval($outstanding);
	}

	/**
	 *
	 *@return Currency (DB Object)
	 **/
	function TotalOutstandingAsCurrencyObject(){
		return DBField::create('Currency',$this->TotalOutstanding());
	}

	/**
	 *
	 *@return Money
	 **/
	function TotalOutstandingAsMoneyObject(){
		$money = DBField::create('Money', array("Amount" => $this->TotalOutstanding(), "Currency" => $this->Currency()));
		return $money;
	}

	/**
	 *@return float
	 */
	function TotalPaid() {
		$paid = 0;
		if($payments = $this->Payments()) {
			foreach($payments as $payment) {
				if($payment->Status == 'Success') {
					$paid += $payment->Amount->getAmount();
				}
			}
		}
		return $paid;
	}

	/**
	 *
	 *@return Currency (DB Object)
	 **/
	function TotalPaidAsCurrencyObject(){
		return DBField::create('Currency',$this->TotalPaid());
	}

	/**
	 * returns the total number of OrderItems (not modifiers).
	 *@return Integer
	 **/
	public function TotalItems() {
		if(self::$total_items === null) {
			//to do, why do we check if you can edit ????
			self::$total_items = DB::query("
				SELECT COUNT(\"OrderItem\".\"ID\")
				FROM \"OrderItem\"
					INNER JOIN \"OrderAttribute\" ON \"OrderAttribute\".\"ID\" = \"OrderItem\".\"ID\"
					INNER JOIN \"Order\" ON \"OrderAttribute\".\"OrderID\" = \"Order\".\"ID\"
					INNER JOIN \"OrderStep\" ON \"OrderStep\".\"ID\" = \"Order\".\"StatusID\"
					WHERE
						\"OrderAttribute\".\"OrderID\" = ".$this->ID."
						AND \"OrderItem\".\"Quantity\" > 0
						AND \"OrderStep\".\"CustomerCanEdit\" = 1"
			)->value();
		}
		return self::$total_items;
	}

	/**
	 * returns the total number of OrderItems (not modifiers) times their respectective quantities.
	 *@return Integer
	 **/
	function TotalItemsTimesQuantity() {
		$qty = 0;
		if($orderItems = $this->Items()) {
			foreach($orderItems as $item) {
				$qty += $item->Quantity;
			}
		}
		return $qty;
	}

	/**
	 * Returns the country code for the country that applies to the order.
	 *@return String (country code)
	 **/
	public function Country() {
		$countryCodes = array();
		if($this->BillingAddressID) {
			if($billingAddress = DataObject::get_by_id("BillingAddress", $this->BillingAddressID)) {
				if($billingAddress->Country) {
					$countryCodes[] = $billingAddress->Country;
				}
			}
		}
		if($this->ShippingAddressID) {
			if($shippingAddress = DataObject::get_by_id("ShippingAddress", $this->ShippingAddressID)) {
				if($shippingAddress->ShippingCountry) {
					$countryCodes[] = $shippingAddress->ShippingCountry;
				}
			}
		}
		if(count($countryCodes)) {
			if(EcommerceCountry::get_use_shipping_address_for_main_region_and_country()) {
				$countryCodes = array_reverse($countryCodes);
			}
			return array_shift($countryCodes);
		}
	}

	/**
	 * returns name of coutry
	 *@return String - country name
	 **/
	public function FullNameCountry() {
		return EcommerceCountry::find_title($this->Country());
	}

	/**
	 * Returns the region that applies to the order.
	 * we check both billing and shipping, in case one of them is empty.
	 *@return DataObject | Null (EcommerceRegion)
	 **/
	public function Region() {
		$regionIDs = array();
		if($this->BillingAddressID) {
			if($billingAddress = DataObject::get_by_id("BillingAddress", $this->BillingAddressID)) {
				if($billingAddress->RegionID) {
					$regionIDs[] = $billingAddress->RegionID;
				}
			}
		}
		if($this->ShippingAddressID) {
			if($shippingAddress = DataObject::get_by_id("ShippingAddress", $this->ShippingAddressID)) {
				if($shippingAddress->ShippingRegionID) {
					$regionIDs[] = $shippingAddress->ShippingRegionID;
				}
			}
		}
		if(count($countryCodes)) {
			if(EcommerceCountry::get_use_shipping_address_for_main_region_and_country()) {
				$regionIDs = array_reverse($regionIDs);
			}
			$id = array_shift($regionIDs);
			if($id) {
				return DataObject::get_by_id("EcommerceRegion", $id);
			}
		}
	}


	/**
	 * Casted variable - has the order been submitted?
	 *
	 *@return Boolean
	 **/
	function IsSubmitted() {
		$className = OrderStatusLog::get_order_status_log_class_used_for_submitting_order();
		return DataObject::get_one($className, "\"OrderID\" = ".$this->ID)? true : false;
	}

	/**
	 * returns the link to view the Order
	 * TO DO: explain why not "CheckoutPage" ???
	 *@return String(URLSegment)
	 */
	function Link() {
		if(!$this->IsSubmitted()) {
			//make sure to get a Cart page and not some other extension of Cart Page
			$page = DataObject::get_one("CartPage", "\"ClassName\" = 'CartPage'");
		}
		else {
			$page = DataObject::get_one("OrderConfirmationPage");
		}
		//backup....
		if(!$page) {
			$page = DataObject::get_one("CartPage");
		}
		if($page) {
			return $page->getOrderLink($this->ID);
		}
	}

	/**
	 * Return a link to the {@link CheckoutPage} instance
	 * that exists in the database.
	 *
	 * @return string
	 */
	function CheckoutLink() {
		return CheckoutPage::find_link();
	}


	/**
	 * Return the currency of this order.
	 * Note: this is a fixed value across the entire site.
	 *
	 * @return string
	 */
	function Currency() {
		if(class_exists('Payment')) {
			return Payment::site_currency();
		}
	}

	/**
	 * Converts the Order into HTML, based on the Order Template.
	 *@return String - HTML1
	 **/
	public function ConvertToHTML() {
		return $this->renderWith("Order");
	}

	/**
	 * Converts the Order into a serialized string
	 * TO DO: check if this works and check if we need to use special sapphire serialization code
	 *@return String - serialized object
	 **/
	public function ConvertToString() {
		return serialize($this->addHasOneAndHasManyAsVariables());
	}

	/**
	 * Converts the Order into a JSON object
	 * TO DO: check if this works and check if we need to use special sapphire JSON code
	 *@return String -  JSON
	 **/
	public function ConvertToJSON() {
		return json_encode($this->addHasOneAndHasManyAsVariables());
	}


	/**
	 * returns itself wtih more data added as variables.
	 *@return DataObject - Order - with most important has one and has many items included as variables.
	 **/
	protected function addHasOneAndHasManyAsVariables() {
		$this->Member = $this->Member();
		$this->BillingAddress = $this->BillingAddress();
		$this->ShippingAddress = $this->ShippingAddress();
		$this->Attributes = $this->OrderAttributes();
		$this->OrderStatusLogs = $this->OrderStatusLogs();
		$this->Payments = $this->Payments();
		$this->Emails = $this->Emails();
		$this->Title = $this->Title();
		$this->Total = $this->Total();
		$this->SubTotal = $this->SubTotal();
		$this->TotalPaid = $this->TotalPaid();
		return $this;
	}




/*******************************************************
   * 9. TEMPLATE RELATED STUFF
*******************************************************/

	/**
	 * $template_id_prefix is a prefix to all HTML IDs referred to in the shopping cart
	 * e.g. CartCellID can become MyCartCellID by setting the template_id_prefix to "My"
	 * The IDs are used for setting values in the HTML using the AJAX method with
	 * the CartResponse providing the DATA (JSON).
	 *
	 *@var String
	 **/
	protected static $template_id_prefix = "";
		public static function set_template_id_prefix(string $s) {self::$template_id_prefix = $s;}
		public static function get_template_id_prefix() {return self::$template_id_prefix;}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function TableMessageID() {return self::$template_id_prefix.'Table_Order_Message';}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function TableSubTotalID() {return self::$template_id_prefix.'Table_Order_SubTotal';}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function TableTotalID() {return self::$template_id_prefix.'Table_Order_Total';}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function CartSubTotalID() {return self::$template_id_prefix.'Cart_Order_SubTotal';}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function CartTotalID() {return self::$template_id_prefix.'Cart_Order_Total';}

	/**
	 * id that is used in templates and in the JSON return @see CartResponse
	 *@return String
	 **/
	function OrderForm_OrderForm_AmountID() {return self::$template_id_prefix.'OrderForm_OrderForm_Amount';}

	/**
	 *
	 *@return Array (for use in AJAX for JSON)
	 **/
	function updateForAjax(array &$js) {
		$subTotal = $this->SubTotalAsCurrencyObject()->Nice();
		$total = $this->TotalAsCurrencyObject()->Nice();
		$js[] = array('id' => $this->TableSubTotalID(), 'parameter' => 'innerHTML', 'value' => $subTotal);
		$js[] = array('id' => $this->TableTotalID(), 'parameter' => 'innerHTML', 'value' => $total);
		$js[] = array('id' => $this->OrderForm_OrderForm_AmountID(), 'parameter' => 'innerHTML', 'value' => $total);
		$js[] = array('id' => $this->CartSubTotalID(), 'parameter' => 'innerHTML', 'value' => $subTotal);
		$js[] = array('id' => $this->CartTotalID(), 'parameter' => 'innerHTML', 'value' => $total);
	}






/*******************************************************
   * 10. STANDARD SS METHODS (requireDefaultRecords, onBeforeDelete, etc...)
*******************************************************/

	/**
	 *standard SS method
	 *
	 **/
	function populateDefaults() {
		parent::populateDefaults();
		//@Session::start();
		//$this->SessionID = Session_id();
	}

	/**
 	 * Marks if a records has been "init"-ed....
 	 * @var Boolean
 	 **/
	protected $newRecord = true;

	/**
	 *standard SS method
	 *
	 **/
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if((isset($this->record['ID']) && $this->record['ID'])) {
			$this->newRecord = false;
		}
	}

	/**
	 *standard SS method
	 *
	 **/
	function onAfterWrite() {
		parent::onAfterWrite();
		if($this->newRecord) {
			$this->init();
		}
	}

	/**
	 *standard SS method
	 *
	 * delete attributes, statuslogs, and payments
	 */
	function onBeforeDelete(){
		if($attributes = $this->Attributes()){
			foreach($attributes as $attribute){
				$attribute->delete();
				$attribute->destroy();
			}
		}
		if($statuslogs = $this->OrderStatusLogs()){
			foreach($statuslogs as $log){
				$log->delete();
				//$log->destroy();
			}
		}
		if($payments = $this->Payments()){
			foreach($payments as $payment){
				$payment->delete();
				//$payment->destroy();
			}
		}
		if($billingAddress = $this->BillingAddress()) {
			if($billingAddress->exists()) {			
				$billingAddress->delete();
			}
			//$billingAddress->destroy();
		}
		if($shippingAddress = $this->ShippingAddress()) {
			if($shippingAddress->exists()) {
				$shippingAddress->delete();
			}
			//$shippingAddress->destroy();
		}
		if($emails = $this->Emails()) {
			foreach($emails as $email){
				$email->delete();
				//$email->destroy();
			}
		}
		parent::onBeforeDelete();
	}






/*******************************************************
   * 11. DEBUG
*******************************************************/

	function debug(){
		$val = "<h3>Database record: $this->class</h3>\n<ul>\n";
		if($this->record) foreach($this->record as $fieldName => $fieldVal) {
			$val .= "\t<li>$fieldName: " . Debug::text($fieldVal) . "</li>\n";
		}
		$val .= "</ul>\n";
		$val .= "<h4>Items</h4>";
		if($this->Items()) {
			$val .= $this->Items()->debug();
		}
		$val .= "<h4>Modifiers</h4>";
		if($this->Modifiers()) {
			$val .= $this->Modifiers()->debug();
		}
		return $val;
	}

}


