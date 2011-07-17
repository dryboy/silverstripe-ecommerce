<?php

/**
 * @description:
 * Defines the Order Status Options.  Basically OrderSteps guide the Order from inception to archiving.
 * Each project can have its own unique order steps - to match the requirements of the shop at hand.
 * The Order Step has a number of functions:
 * a. move the order along
 * b. describe what can be done to the order (edit, view, delete, etc...) by whom
 * c. describe the status of the order
 * d. email the customer about the progress
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: model
 *
 **/

class OrderStep extends DataObject {
	//database
	public static $db = array(
		"Name" => "Varchar(50)",
		"Code" => "Varchar(50)",
		"Description" => "Text",
		"CustomerMessage" => "HTMLText",
		//customer privileges
		"CustomerCanEdit" => "Boolean",
		"CustomerCanCancel" => "Boolean",
		"CustomerCanPay" => "Boolean",
		//What to show the customer...
		"ShowAsUncompletedOrder" => "Boolean",
		"ShowAsInProcessOrder" => "Boolean",
		"ShowAsCompletedOrder" => "Boolean",
		"HideStepFromCustomer" => "Boolean",
		//sorting index
		"Sort" => "Int"
		//by-pass
	);

	public static $indexes = array(
		"Code" => true,
		"Sort" => true
	);

	public static $has_many = array(
		"Orders" => "Order"
	);

	public static $field_labels = array(
		"Sort" => "Sorting Index",
		"CustomerCanEdit" => "Customer can edit",
		"CustomerCanPay" => "Customer can pay",
		"CustomerCanCancel" => "Customer can cancel"
	);

	public static $summary_fields = array(
		"Name" => "Name",
		"CustomerCanEditNice" => "CustomerCanEdit",
		"CustomerCanPayNice" => "CustomerCanPay",
		"CustomerCanCancelNice" => "CustomerCanCancel",
		"ShowAsUncompletedOrderNice" => "ShowAsUncompletedOrder",
		"ShowAsInProcessOrderNice" => "ShowAsInProcessOrder",
		"ShowAsCompletedOrderNice" => "ShowAsCompletedOrder"
	);

	public static $casting = array(
		"CustomerCanEditNice" => "Varchar",
		"CustomerCanPayNice" => "Varchar",
		"CustomerCanCancelNice" => "Varchar",
		"ShowAsUncompletedOrderNice" => "Varchar",
		"ShowAsInProcessOrderNice" => "Varchar",
		"ShowAsCompletedOrderNice" => "Varchar",
		"HideStepFromCustomer" => "Varchar"
	);

	public static $searchable_fields = array(
		'Name' => array(
			'title' => 'Name',
			'filter' => 'PartialMatchFilter'
		),
		'Code' => array(
			'title' => 'Code',
			'filter' => 'PartialMatchFilter'
		)
	);

	function CustomerCanEditNice() {if($this->CustomerCanEdit) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function CustomerCanPayNice() {if($this->CustomerCanPay) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function CustomerCanCancelNice() {if($this->CustomerCanCancel) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function ShowAsUncompletedOrderNice() {if($this->ShowAsUncompletedOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function ShowAsInProcessOrderNice() {if($this->ShowAsInProcessOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function ShowAsCompletedOrderNice() {if($this->ShowAsCompletedOrder) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}
	function HideStepFromCustomer() {if($this->HideStepFromCustomer) {return _t("OrderStep.YES", "Yes");}return _t("OrderStep.NO", "No");}

	public static $singular_name = "Order Step";
		function i18n_singular_name() { return _t("OrderStep.ORDERSTEP", "Order Step");}

	public static $plural_name = "Order Steps";
		function i18n_plural_name() { return _t("OrderStep.ORDERSTEPS", "Order Steps");}

	// SUPER IMPORTANT TO KEEP ORDER!
	public static $default_sort = "\"Sort\" ASC";

	public static function get_status_id_from_code($code) {
		if($otherStatus = DataObject::get_one("OrderStep", "\"Code\" = '".$code."'")) {
			return $otherStatus->ID;
		}
		return 0;
	}

	// MOST IMPORTANT DEFINITION!
	protected static $order_steps_to_include = array(
		"OrderStep_Created",
		"OrderStep_Submitted",
		"OrderStep_SentInvoice",
		"OrderStep_Paid",
		"OrderStep_Confirmed",
		"OrderStep_SentReceipt",
		"OrderStep_Sent",
		"OrderStep_Archived"
	);
		static function set_order_steps_to_include(array $a) {self::$order_steps_to_include = $a;}
		static function get_order_steps_to_include() {return(array)self::$order_steps_to_include;}
		static function add_order_step_to_include($s, $placeAfter) {
			array_splice(self::$order_steps_to_include, array_search($placeAfter, self::$order_steps_to_include) + 1, 0, $s);
		}
		/**
		 *
		 *@return Array
		 **/
		static function get_codes_for_order_steps_to_include() {
			$newArray = array();
			$array = self::get_order_steps_to_include();
			if(is_array($array) && count($array)) {
				foreach($array as $className) {
					$code = singleton($className)->getMyCode();
					$newArray[$className] = strtoupper($code);
				}
			}
			return $newArray;
		}
		function getMyCode() {
			$array = Object::uninherited_static($this->ClassName, 'defaults');
			if(!isset($array["Code"])) {user_error($this->class." does not have a default code specified");}
			return $array["Code"];
		}

	//IMPORTANT:: MUST HAVE Code must be defined!!!
	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 1,
		"ShowAsUncompletedOrder" => 0,
		"ShowAsInProcessOrder" => 0,
		"ShowAsCompletedOrder" => 0,
		"Code" => "ORDERSTEP"
	);

	function populateDefaults() {
		parent::populateDefaults();
		$array = Object::uninherited_static($this->ClassName, 'defaults');
		if($array && count($array)) {
			foreach($array as $field => $value) {
				$this->$field = $value;
			}
		}
	}

	/**
	 *
	 *@return Fieldset
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		//replacing
		$fields->addFieldToTab("Root.InternalDescription", new TextareaField("Description", _t("OrderStep.DESCRIPTION", "Explanation for internal use only"), 5));
		$fields->addFieldToTab("Root.CustomerMessage", new HTMLEditorField("CustomerMessage", _t("OrderStep.CUSTOMERMESSAGE", "Customer Message"), 5));
		//adding
		if(!$this->ID || !$this->isDefaultStatusOption()) {
			$fields->removeFieldFromTab("Root.Main", "Code");
			$fields->addFieldToTab("Root.Main", new DropdownField("ClassName", _t("OrderStep.TYPE", "Type"), self::get_codes_for_order_steps_to_include()), "Name");
		}
		if($this->isDefaultStatusOption()) {
			$fields->replaceField("Code", $fields->dataFieldByName("Code")->performReadonlyTransformation());
		}
		//headers
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING1", _t("OrderStep.CAREFUL", "CAREFUL! please edit with care"), 1), "Name");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING2", _t("OrderStep.CUSTOMERCANCHANGE", "What can be changed during this step?"), 3), "CustomerCanEdit");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING5", _t("OrderStep.ORDERGROUPS", "Order groups for customer?"), 3), "ShowAsUncompletedOrder");
		$fields->addFieldToTab("Root.Main", new HeaderField("WARNING7", _t("OrderStep.SORTINGINDEXHEADER", "Index Number (lower number come first)"), 3), "Sort");
		return $fields;
	}

	/**
	 * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields
	 *@param FieldSet $fields
	 *@param Order $order
	 *@return FieldSet
	 **/
	function addOrderStepFields(&$fields, $order) {
		return $fields;
	}

	/**
	 *
	 *@return ValidationResult
	 **/
	function validate() {
		$result = DataObject::get_one(
			"OrderStep",
			" (\"Name\" = '".$this->Name."' OR \"Code\" = '".strtoupper($this->Code)."') AND \"OrderStep\".\"ID\" <> ".intval($this->ID));
		if($result) {
			return new ValidationResult((bool) ! $result, _t("OrderStep.ORDERSTEPALREADYEXISTS", "An order status with this name already exists. Please change the name and try again."));
		}
		$result = (bool)($this->ClassName == "OrderStep");
		if($result) {
			return new ValidationResult((bool) ! $result, _t("OrderStep.ORDERSTEPCLASSNOTSELECTED", "You need to select the right order status class."));
		}
		return parent::validate();
	}


/**************************************************
* moving between statusses...
**************************************************/
	/**
  	*initStep:
  	* makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
	* should be able to run this function many times to check if the step is ready
	*@see Order::doNextStatus
  	*@param Order object
  	*@return Boolean - true if the current step is ready to be run...
  	**/
	public function initStep($order) {
		user_error("Please implement this in a subclass of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
  	*doStep:
	* should only be able to run this function once (init stops you from running it twice - in theory....)
  	*runs the actual step
	*@see Order::doNextStatus
  	*@param Order object
  	*@return Boolean - true if run correctly
  	**/
	public function doStep($order) {
		user_error("Please implement this in a subclass of OrderStep", E_USER_WARNING);
		return true;
	}

	/**
  	*nextStep:
  	*returns the next step (checks if everything is in place for the next step to run...)
	*@see Order::doNextStatus
  	*@param Order object
  	*@return DataObject | Null (next step OrderStep object)
  	**/
	public function nextStep($order) {
		$nextOrderStepObject = DataObject::get_one("OrderStep", "\"Sort\" > ".$this->Sort);
		if($nextOrderStepObject) {
			return $nextOrderStepObject;
		}
		return null;
	}



/**************************************************
* Boolean checks
**************************************************/

	/**
	 *
	 *@return Boolean
	 **/
	public function hasPassed($code, $orIsEqualTo = false) {
		$otherStatus = DataObject::get_one("OrderStep", "\"Code\" = '".$code."'");
		if($otherStatus) {
			if($otherStatus->Sort < $this->Sort) {
				return true;
			}
			if($orIsEqualTo && $otherStatus->Code == $this->Code) {
				return true;
			}
		}
		else {
			user_error("could not find $code in OrderStep", E_USER_NOTICE);
		}
		return false;
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function hasPassedOrIsEqualTo($code) {
		return $this->hasPassed($code, true);
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function hasNotPassed($code) {
		return (bool)!$this->hasPassed($code, true);
	}

	/**
	 *
	 *@return Boolean
	 **/
	public function isBefore($code) {
		return (bool)!$this->hasPassed($code, false);
	}

	/**
	 *
	 *@return Boolean
	 **/
	protected function isDefaultStatusOption() {
		return in_array($this->Code, self::get_codes_for_order_steps_to_include());
	}

	//EMAIL

	/**
	 *
	 *@return Boolean
	 **/
	protected function hasBeenSent($order) {
		return DataObject::get_one("OrderEmailRecord", "\"OrderEmailRecord\".\"OrderID\" = ".$order->ID." AND \"OrderEmailRecord\".\"OrderStepID\" = ".$this->ID." AND  \"OrderEmailRecord\".\"Result\" = 1");
	}

/**************************************************
* Silverstripe Standard Data Object Methods
**************************************************/

	/**
	 *
	 *@return Boolean
	 **/
	public function canDelete($member = null) {
		if($order = DataObject::get_one("Order", "\"StatusID\" = ".$this->ID)) {
			return false;
		}
		if($this->isDefaultStatusOption()) {
			return false;
		}
		return true;
	}


	function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Code = strtoupper($this->Code);
	}

	function onAfterDelete() {
		parent::onAfterDelete();
		$this->requireDefaultRecords();
	}


	//USED TO BE: Unpaid,Query,Paid,Processing,Sent,Complete,AdminCancelled,MemberCancelled,Cart
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$orderStepsToInclude = self::get_order_steps_to_include();
		$codesToInclude = self::get_codes_for_order_steps_to_include();
		if($orderStepsToInclude && count($orderStepsToInclude) && count($codesToInclude)) {
			foreach($codesToInclude as $className => $code) {
				if(!DataObject::get_one($className)) {
					if(!DataObject::get_one("OrderStep", "\"Code\" = '".strtoupper($code)."'")) {
						$obj = new $className();
						$obj->Code = strtoupper($obj->Code);
						$obj->write();
						DB::alteration_message("Created \"$code\" as $className.", "created");
					}
				}
			}
		}
	}
}

/**
 * This is the first Order Step.
 *
 *
 *
 *
 **/


class OrderStep_Created extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 1,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 1,
		"Name" => "Create",
		"Code" => "CREATED",
		"Sort" => 10,
		"ShowAsUncompletedOrder" => 1
	);

	/**
	 * Can always run step.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function initStep($order) {
		return true;
	}

	/**
	 * Add the member to the order, in case the member is not an admin.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if(!$order->MemberID) {
			$m = Member::currentUser();
			if($m) {
				if(!$m->IsShopAdmin()) {
					$order->MemberID = $m->ID();
					$order->write();
				}
			}
		}
		return true;
	}

	/**
	 * We can run the next step, once any items have been added.
	 *@param DataObject - $order Order
	 *@return DataObject | Null (nextStep DataObject)
	 **/
	public function nextStep($order) {
		if($order->TotalItems()) {
			return parent::nextStep($order);
		}
		return null;
	}


	function addOrderStepFields(&$fields, $order) {
		return $fields;
	}


}

class OrderStep_Submitted extends OrderStep {

	static $db = array(
		"SaveOrderAsHTML" => "Boolean",
		"SaveOrderAsSerializedObject" => "Boolean",
		"SaveOrderAsJSON" => "Boolean"
	);

	static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanPay" => 1,
		"CustomerCanCancel" => 0,
		"Name" => "Submit",
		"Code" => "SUBMITTED",
		"Sort" => 20,
		"ShowAsInProcessOrder" => 1,
		"SaveOrderAsHTML" => 1,
		"SaveOrderAsSerializedObject" => 0,
		"SaveOrderAsJSON" => 0
	);


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("HOWTOSAVESUBMITTEDORDER", _t("OrderStep.HOWTOSAVESUBMITTEDORDER", "How would you like to make a backup of your order at the moment it is submitted?"), 1), "SaveOrderAsHTML");
		return $fields;
	}

	/**
	 * Can run this step once any items have been submitted.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function initStep($order) {
		return (bool) $order->TotalItems();
	}

	/**
	 * Add a member to the order - in case he / she is not a shop admin.
	 *@param DataObject - $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if(!$order->IsSubmitted()) {
			$className = OrderStatusLog::get_order_status_log_class_used_for_submitting_order();
			if(class_exists($className)) {
				$obj = new $className();
				if($obj instanceOf OrderStatusLog) {
					$obj->OrderID = $order->ID;
					$obj->Title = $this->Name;
					if($this->SaveOrderAsHTML)             {$obj->OrderAsHTML = $order->ConvertToHTML();}
					if($this->SaveOrderAsSerializedObject) {$obj->OrderAsString = $order->ConvertToString();}
					if($this->SaveOrderAsJSON)             {$obj->OrderAsJSON = $order->ConvertToJSON();}
					$obj->write();
				}
				else {
					user_error('OrderStatusLog::$order_status_log_class_used_for_submitting_order refers to a class that is NOT an instance of OrderStatusLog');
				}

			}
			else {
				user_error('OrderStatusLog::$order_status_log_class_used_for_submitting_order refers to a non-existing class');
			}
		}
		return true;		
	}

	/**
	 * go to next step if order has been submitted.
	 *@param DataObject - $order Order
	 *@return DataObject | Null  (next step OrderStep)
	 **/
	public function nextStep($order) {
		if($order->IsSubmitted()) {
			return parent::nextStep($order);
		}
		return null;
	}


	function addOrderStepFields(&$fields, $order) {
		if(!$order->IsSubmitted()) {
			//LINE BELOW IS NOT REQUIRED
			//OrderStatusLog::add_available_log_classes_array($className);
			$header = _t("OrderStep.SUBMITORDER", "Submit Order");
			$msg = _t("OrderStep.MUSTDOSUBMITRECORD", "Tick the box below to submit this order.");
			$problems = array();
			if(!$order->Items()) {
				$problems[] = "There are no items associated with this order.";
			}
			$problems = array();
			if(!$order->MemberID) {
				$problems[] = "There is no customer associated with this order.";
			}
			if(count($problems)) {
				$msg = "You can not submit this order because <ul><li>".implode("</li><li>", $problems)."</li></ul>";
			}
			$fields->addFieldToTab("Root.Main", new HeaderField("CreateSubmitRecordHeader", $header), "CustomerOrderNote");
			$fields->addFieldToTab("Root.Main", new LiteralField("CreateSubmitRecordMessage", '<p>'.$msg.'</p>'), "CustomerOrderNote");
			if(!$problems) {
				$fields->addFieldToTab("Root.Main", new CheckboxField("SubmitNow", "Submit Now"), "CustomerOrderNote");
			}
		}
		return $fields;
	}


}



class OrderStep_SentInvoice extends OrderStep {

	static $db = array(
		"SendInvoiceToCustomer" => "Boolean"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 1,
		"Name" => "Send invoice",
		"Code" => "INVOICED",
		"Sort" => 25,
		"ShowAsInProcessOrder" => 1,
		"SendInvoiceToCustomer" => 1
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("ACTUALLYSENDINVOICE", _t("OrderStep.ACTUALLYSENDINVOICE", "Actually send the invoice?"), 1), "SendInvoiceToCustomer");
		return $fields;
	}

	/**
	 * can run step once order has been submitted.
	 *@param DataObject $order Order
	 *@return Boolean
	 **/
	public function initStep($order) {
		return $order->IsSubmitted();
	}

	/**
	 * send invoice to customer
	 *@param DataObject $order Order
	 *@return Boolean
	 **/
	public function doStep($order) {
		if($this->SendInvoiceToCustomer){
			if(!$this->hasBeenSent($order)) {
				return $order->sendInvoice($this->CustomerMessage);
			}
		}
		return true;
	}

	/**
	 * can do next step once the invoice has been sent or in case the invoice does not need to be sent.
	 *@param DataObject $order Order
	 *@return DataObject | Null  (next step OrderStep object)
	 **/
	public function nextStep($order) {
		if(!$this->SendInvoiceToCustomer || $this->hasBeenSent($order)) {
			return  parent::nextStep($order);
		}
		return null;
	}

}

class OrderStep_Paid extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Pay",
		"Code" => "PAID",
		"Sort" => 30,
		"ShowAsInProcessOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 * can go to next step if order has been paid
	 *@param DataObject $order Order
	 *@return DataObject | Null  (next step OrderStep object)
	 **/
	public function nextStep($order) {
		if($order->IsPaid()) {
			return parent::nextStep($order);
		}
		return null;
	}


	function addOrderStepFields(&$fields, $order) {
		if(!$order->IsPaid()) {
			//LINE BELOW IS NOT REQUIRED
			//OrderStatusLog::add_available_log_classes_array($className);
			$header = _t("OrderStep.SUBMITORDER", "Order NOT Paid");
			$msg = _t("OrderStep.ORDERNOTPAID", "This order can not be completed, because it has not been paid.");
			$fields->addFieldToTab("Root.Main", new HeaderField("NotPaidHeader", $header), "StatusID");
			$fields->addFieldToTab("Root.Main", new LiteralField("NotPaidMessage", '<p>'.$msg.'</p>'), "StatusID");
		}
		return $fields;
	}
}


class OrderStep_Confirmed extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Confirm",
		"Code" => "CONFIRMED",
		"Sort" => 35,
		"ShowAsInProcessOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 * can go to next step if order payment has been confirmed...
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		if(DataObject::get_one("OrderStatusLog_PaymentCheck", "\"OrderID\" = ".$order->ID." AND \"PaymentConfirmed\" = 1")) {
			return parent::nextStep($order);
		}
		return null;
	}


	function addOrderStepFields(&$fields, $order) {
		OrderStatusLog::add_available_log_classes_array("OrderStatusLog_PaymentCheck");
		$msg = _t("OrderStep.MUSTDOPAYMENTCHECK", " ... To move this order to the next step you must carry out a payment check (is the money in the bank?) and record it below");
		$fields->addFieldToTab("Root.Main", $order->OrderStatusLogsTable("OrderStatusLog_PaymentCheck", $msg),"StatusID");
		return $fields;
	}


}



class OrderStep_SentReceipt extends OrderStep {

	static $db = array(
		"SendReceiptToCustomer" => "Boolean"
	);

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Send receipt",
		"Code" => "RECEIPTED",
		"Sort" => 40,
		"ShowAsInProcessOrder" => 1,
		"SendReceiptToCustomer" => 1
	);


	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Main", new HeaderField("ACTUALLYSENDRECEIPT", _t("OrderStep.ACTUALLYSENDRECEIPT", "Actually send the receipt?"), 1), "SendReceiptToCustomer");
		return $fields;
	}

	public function initStep($order) {
		return $order->IsPaid();
	}

	public function doStep($order) {
		if($this->SendReceiptToCustomer){
			if(!$this->hasBeenSent($order)) {
				return $order->sendReceipt($this->CustomerMessage);
			}
		}
		return true;
	}

	/**
	 * can continue if receipt has been sent or if there is no need to send a receipt.
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = next OrderStep
	 **/
	public function nextStep($order) {
		if(!$this->SendReceiptToCustomer || $this->hasBeenSent($order)) {
			return parent::nextStep($order);
		}
		return null;
	}


}


class OrderStep_Sent extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Send order",
		"Code" => "SENT",
		"Sort" => 50,
		"ShowAsCompletedOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 *
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		if(DataObject::get_one("OrderStatusLog_DispatchPhysicalOrder", "\"OrderID\" = ".$order->ID)) {
			return parent::nextStep($order);
		}
		return null;
	}

	function addOrderStepFields(&$fields, $order) {
		OrderStatusLog::add_available_log_classes_array("OrderStatusLog_DispatchPhysicalOrder");
		$msg = _t("OrderStep.MUSTENTERDISPATCHRECORD", " ... To move this order to the next step you enter the dispatch details in the logs.");
		$fields->addFieldToTab("Root.Main", $order->OrderStatusLogsTable("OrderStatusLog_DispatchPhysicalOrder", $msg),"StatusID");
		return $fields;
	}


}


class OrderStep_Archived extends OrderStep {

	public static $defaults = array(
		"CustomerCanEdit" => 0,
		"CustomerCanCancel" => 0,
		"CustomerCanPay" => 0,
		"Name" => "Archived order",
		"Code" => "ARCHIVED",
		"Sort" => 55,
		"ShowAsCompletedOrder" => 1
	);

	public function initStep($order) {
		return true;
	}

	public function doStep($order) {
		return true;
	}

	/**
	 *
	 *@param DataObject $order Order
	 *@return DataObject | Null - DataObject = OrderStep
	 **/
	public function nextStep($order) {
		//IMPORTANT
		return null;
	}

}


