<?php

/**
 * @Description: form to submit order.
 * @see CheckoutPage
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: forms
 *
 **/

class OrderForm extends Form {

	function __construct($controller, $name) {
		$order = ShoppingCart::current_order();
		Requirements::javascript('ecommerce/javascript/EcomOrderForm.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		$requiredFields = array();


		//  ________________ 1) Member + Address fields


		//member fields
		$addressFields = new FieldSet();
		$member = $order->CreateOrReturnExistingMember();
		//strange security situation...
		if($member->exists()) {
			if($member->ID != Member::currentUserID()) {
				$member->logOut();
			}
		}
		$memberFields = $member->getEcommerceFields();
		$requiredFields = array_merge($requiredFields, $member->getEcommerceRequiredFields());
		$addressFields->merge($memberFields);
		//billing address field
		$billingAddress = $order->CreateOrReturnExistingAddress("BillingAddress");
		$billingAddressFields = $billingAddress->getFields();
		$requiredFields = array_merge($requiredFields, $billingAddress->getRequiredFields());
		$addressFields->merge($billingAddressFields);
		//shipping address field
		if(OrderAddress::get_use_separate_shipping_address()) {
			//add the important CHECKBOX
			$useShippingAddressField = new FieldSet(new CheckboxField("UseShippingAddress", _t("OrderForm.USESHIPPINGADDRESS", "Use an alternative shipping address")));
			$addressFields->merge($useShippingAddressField);
			//now we can add the shipping fields
			$shippingAddress = $order->CreateOrReturnExistingAddress("ShippingAddress");
			$shippingAddressFields = $shippingAddress->getFields();
			//we have left this out for now as it was giving a lot of grief...
			//$requiredFields = array_merge($requiredFields, $shippingAddress->getRequiredFields());
			//finalise left fields
			$addressFields->merge($shippingAddressFields);
			Requirements::javascript('ecommerce/javascript/EcomOrderFormShipping.js'); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
		}
		$leftFields = new CompositeField($addressFields);
		$leftFields->setID('LeftOrder');


		//  ________________  2) Log in / vs Create Account fields - RIGHT-HAND-SIDE fields


		$rightFields = new CompositeField();
		$rightFields->setID('RightOrder');
		if(!$member || ($member && (!$member->exists() || !$member->Password)) && !$member->IsAdmin()) {
			//general header
			if(!$member->exists()) {
				$rightFields->push(
					new LiteralField('MemberInfo', '<p class="message good">'._t('OrderForm.MEMBERINFO','If you are already have an account then please')." <a href=\"Security/login?BackURL=" . $controller->Link() . "/\">"._t('OrderForm.LOGIN','log in').'</a>.</p>')
				);
			}
			$passwordField = new ConfirmedPasswordField('Password', _t('OrderForm.PASSWORD','Password'));
			//login invite right on the top
			if(EcommerceRole::get_automatic_membership()) {
				$rightFields->push(new HeaderField(_t('OrderForm.MEMBERSHIPDETAILS','Setup your account'), 3));
				//dont allow people to purchase without creating a password
				$passwordField->setCanBeEmpty(false);

			}
			else {
				$rightFields->push(new HeaderField(_t('OrderForm.MEMBERSHIPDETAILSOPTIONAL','Create an account (optional)'), 3));
				//allow people to purchase without creating a password
				$passwordField->setCanBeEmpty(true);
			}
			$requiredFields[] = 'Password[_Password]';
			$requiredFields[] = 'Password[_ConfirmPassword]';
			Requirements::customScript('jQuery("#ChoosePassword").click();', "EommerceChoosePassword"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			$rightFields->push(
				new LiteralField('AccountInfo','<p>'._t('OrderForm.ACCOUNTINFO','Please <a href="#Password" class="choosePassword">choose a password</a>, so you can log in and check your order history in the future.').'</p>')
			);
			$rightFields->push(new FieldGroup($passwordField));
		}


		//  ________________  3) Payment fields - BOTTOM FIELDS


		$bottomFields = new CompositeField();
		$bottomFields->setID('BottomOrder');
		$totalAsCurrencyObject = $order->TotalAsCurrencyObject(); //should instead be $totalobj = $order->dbObject('Total');
		$paymentFields = Payment::combined_form_fields($totalAsCurrencyObject->Nice());
		foreach($paymentFields as $paymentField) {
			if($paymentField->class == "HeaderField") {
				$paymentField->setTitle(_t("OrderForm.MAKEPAYMENT", "Make Payment"));
			}
			$bottomFields->push($paymentField);
		}
		if($paymentRequiredFields = Payment::combined_form_requirements()) {
			$requiredFields = array_merge($requiredFields, $paymentRequiredFields);
		}


		//  ________________  4) FINAL FIELDS


		$finalFields = new CompositeField();
		$finalFields->setID('FinalFields');
		$finalFields->push(new HeaderField(_t('OrderForm.COMPLETEORDER','Complete Order'), 3));
		// If a terms and conditions page exists, we need to create a field to confirm the user has read it
		if($termsAndConditionsPage = CheckoutPage::find_terms_and_conditions_page()) {
			$finalFields->push(new CheckboxField('ReadTermsAndConditions', _t('OrderForm.AGREEWITHTERMS1','I have read the ').' <a href="'.$termsAndConditionsPage->Link().'">'.Convert::raw2xml($termsAndConditionsPage->Title).'</a> '._t('OrderForm.AGREEWITHTERMS2','page.')));
			$requiredFields[] = 'ReadTermsAndConditions';
		}
		$finalFields->push(new TextareaField('CustomerOrderNote', _t('OrderForm.CUSTOMERNOTE','Note / Question'), 7, 30));


		//  ________________  5) Put all the fields in one FieldSet


		$fields = new FieldSet($rightFields, $leftFields, $bottomFields, $finalFields);



		//  ________________  6) Actions and required fields creation + Final Form construction


		$actions = new FieldSet(new FormAction('processOrder', _t('OrderForm.PROCESSORDER','Place order and make payment')));
		$requiredFields = new OrderForm_Validator($requiredFields);
		$this->extend('updateValidator',$requiredFields);
		$this->extend('updateFields',$fields);
		parent::__construct($controller, $name, $fields, $actions, $requiredFields);


		//  ________________  7)  Load saved data

		if($order) {
			$this->loadDataFrom($order);
			if($billingAddress) {
				$this->loadDataFrom($billingAddress);
			}
			if(OrderAddress::get_use_separate_shipping_address()) {
				if ($shippingAddress) {
					$this->loadDataFrom($shippingAddress);
				}
			}
		}
		if ($member) {
			//MEMBER ALSO HAS COUNTRY, SO WE FIX THIS HERE...
			$member->Country = $billingAddress->Country;
			$this->loadDataFrom($member);
		}


		//allow updating via decoration
		$this->extend('updateOrderForm',$this);


	}


	function addValidAction($action){
		$this->validactions[] = $action;
	}


	/**
	 *@return array
	 **/
	function getValidActions($format = true){
		$vas = $this->validactions;
		if($format){
			$actions = array();
			foreach($vas as $action){
				$actions[] = 'action_'.$action;
			}
		}
		return $actions;
	}




	/**
	 * Process the items in the shopping cart from session,
	 * creating a new {@link Order} record, and updating the
	 * customer's details {@link Member} record.
	 *
	 * {@link Payment} instance is created, linked to the order,
	 * and payment is processed {@link Payment::processPayment()}
	 *
	 * @param array $data Form request data submitted from OrderForm
	 * @param Form $form Form object for this action
	 * @param HTTPRequest $request Request object for this action
	 */
	function processOrder($data, $form, $request) {
		$this->saveDataToSession($data); //save for later if necessary
		$order = ShoppingCart::current_order();
		//check for cart items
		if(!$order) {
			$form->sessionMessage(_t('OrderForm.ORDERNOTFOUND','Your order could not be found.'), 'bad');
			Director::redirectBack();
			return false;
		}
		if($order && $order->TotalItems() < 1) {
			// WE DO NOT NEED THE THING BELOW BECAUSE IT IS ALREADY IN THE TEMPLATE AND IT CAN LEAD TO SHOWING ORDER WITH ITEMS AND MESSAGE
			$form->sessionMessage(_t('OrderForm.NOITEMSINCART','Please add some items to your cart.'), 'bad');
			Director::redirectBack();
			return false;
		}

		//RUN UPDATES TO CHECK NOTHING HAS CHANGED
		$order = ShoppingCart::current_order();
		$oldtotal = $order->Total();
		$order->calculateOrderAttributes($force = true);
		if($order->Total() != $oldtotal) {
			$form->sessionMessage(_t('OrderForm.PRICEUPDATED','The order price has been updated.'), 'warning');
			Director::redirectBack();
			return false;
		}

		//PASSWORD HACK ... TO DO: test that you can actually update a password as the method below
		//does NOT change the FORM only DATA, but we save to the new details using $form->saveInto($member)
		//and NOT $data->saveInto($member)
		if(isset($data['Password']) && is_array($data['Password'])) {
			$data['Password'] = $data['Password']['_Password'];
		}
		$member = $this->createOrFindMember($data);
		if(is_object($member)) {
			if($this->memberShouldBeSaved($data)) {
				$form->saveInto($member);
				$member->write();
			}
			if($this->memberShouldBeLoggedIn($data)) {
				$member->logIn();
			}
			$order->MemberID = $member->ID;
		}
		//BILLING ADDRESS
		if($billingAddress = $order->CreateOrReturnExistingAddress("BillingAddress")) {
			$form->saveInto($billingAddress);
			$order->BillingAddressID = $billingAddress->write();
		}

		// SHIPPING ADDRESS
		if(isset($data['UseShippingAddress'])){
			if($data['UseShippingAddress']) {
				if($shippingAddress = $order->CreateOrReturnExistingAddress("ShippingAddress")) {
					$form->saveInto($shippingAddress);
					// NOTE: write should return the new ID of the object
					$order->ShippingAddressID = $shippingAddress->write();
				}
			}
			else {
				$order->ShippingAddressID = 0;
			}
		}


		//ORDER
		//saving customer note, UseShippingAddress, country...
		$form->saveInto($order);

		// IMPORTANT - SAVE ORDER....!
		$order->write();

		$order->tryToFinaliseOrder();


		//----------------- CLEAR OLD DATA ------------------------------
		$this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed
		//----------------- PAYMENT ------------------------------
		$nextStep = EcommercePayment::process_payment_form_and_return_next_step($order, $form, $data, $member);
		ShoppingCart::singleton()->clear();
		return $nextStep;
	}

	function saveDataToSession($data){
		Session::set("FormInfo.{$this->FormName()}.data", $data);
	}

	function loadDataFromSession(){
		if($data = Session::get("FormInfo.{$this->FormName()}.data")){
			$this->loadDataFrom($data);
		}
	}

	function clearSessionData(){
		$this->clearMessage();
		Session::set("FormInfo.{$this->FormName()}.data", null);
	}


	/**
	*
	 *@param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 * @return DataObject| Null
	 *
	 **/
	protected function createOrFindMember($data) {
		$currentUserID = Member::currentUserID();
		$member = null;
		//simplest case...
		if($this->uniqueMemberFieldCanBeUsed($data)) {
			$member = Member::currentUser();
		}
		if(!$member) {
			//is there already someone with that unique field (return member) but CAN NOT BE LOGGED IN!!!!
			$member = $this->existingMemberWithUniqueField($data);
			//in case we still dont have a member AND we should create a member for every customer, then we do this below...
			if(!$member && EcommerceRole::get_automatic_membership()) {
				$member = new Member();
			}
		}
		return $member;
	}

	/**
	 *returns TRUE if member should be logged-in OR
	 * member is logged and the unique field matches
	 *@param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 *@return Boolean
	 **/
	protected function memberShouldBeSaved($data) {
		if(
			$this->memberShouldBeLoggedIn($data) ||
			(Member::currentUserID() && !$this->existingMemberWithUniqueField($data) && EcommerceRole::get_automatically_update_member_details())
		) {
			return true;
		}
		return false;
	}

	/**
	 *returns TRUE IF
	 * unique field does not exist already (there is no-one else with the same email)
	 * AND member is not logged in already
	 * AND non-members are automatically created (and logged in).
	 * i.e. the logged in member tries to take on another identity.
	 *@param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 *@return Boolean
	 **/
	protected function memberShouldBeLoggedIn($data) {
		if(!Member::currentUserID()) {
			if(EcommerceRole::get_automatic_membership()) {
				if($this->uniqueMemberFieldCanBeUsed($data)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 *returns FALSE IF
	 * 1. the unique field already exists in another member
	 * AND 2. the member being "tested" is already logged in...
	 *
	 *
	 * i.e. the logged in member tries to take on another identity.
	 * returns TRUE if there is no existing member with the unique field OR the member is not logged in.
	 * If you are not logged BUT the the unique field is used by an existing member then we can still
	 * use the field - we just CAN NOT log in the member.
	 * This method needs to be public because it is used by the OrderForm_Validator (see below).
	 *@param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 *@return Boolean
	 **/
	public function uniqueMemberFieldCanBeUsed($data) {
		return !$this->existingMemberWithUniqueField($data) || !Member::currentUserID() ? true : false;
	}

	/**
	 *returns existing member if it already exists and it is not the logged-in one.
	 * and null if this member does not exist (based on the unique field (email))
	 *@param Array - form data - should include $data[uniqueField....] - e.g. $data["Email"]
	 *@return DataObject | Null (dataobject = member)
	 **/
	protected function existingMemberWithUniqueField($data) {
		$uniqueField = Member::get_unique_identifier_field();
		$currentUserID = Member::currentUserID();
		//The check below covers both Scenario 3 and 4....
		if(isset($data[$uniqueField])) {
			$uniqueFieldValue = Convert::raw2xml($data[$uniqueField]);
			return DataObject::get_one('Member', "\"$uniqueField\" = '{$uniqueFieldValue}' AND \"Member\".\"ID\" <> ".$currentUserID);
		}
		return null;
	}



}


/**
 * @Description: allows customer to make additional payments for their order
 *
 * @package: ecommerce
 * @authors: Silverstripe, Jeremy, Nicolaas
 **/

class OrderForm_Validator extends ShopAccountForm_Validator{

	/**
	 * Ensures member unique id stays unique and other basic stuff...
	 * @param array $data = Form Data
	 * @return Boolean
	 */
	function php($data){
		$valid = parent::php($data);
		if(isset($data["ReadTermsAndConditions"])) {
			if(!$data["ReadTermsAndConditions"]) {
				$this->validationError(
					"ReadTermsAndConditions",
					_t("OrderForm.READTERMSANDCONDITIONS", "Have you read the terms and conditions?"),
					"required"
				);
				$valid = false;
			}
		}
		//Note the exclamation Mark - only applies if it return FALSE.
		if(!$this->form->uniqueMemberFieldCanBeUsed($data)) {
			$uniqueField = Member::get_unique_identifier_field();
			$this->validationError(
				$uniqueField,
				_t("OrderForm.EMAILFROMOTHERUSER", 'Sorry, an account with that email is already in use by another customer. If this is your email address then please log in first before placing your order.'),
				"required"
			);
			$valid = false;
		}
		if(!$valid) {
			$this->form->sessionMessage(_t("OrderForm.ERRORINFORM", "We could not proceed with your order, please check your errors below."), "bad");
			$this->form->messageForForm("OrderForm", _t("OrderForm.ERRORINFORM", "We could not proceed with your order, please check your errors below."), "bad");
		}
		return $valid;
	}

}



class OrderForm_Payment extends Form {

	function __construct($controller, $name, $order, $returnToLink = '') {
		$fields = new FieldSet(
			new HiddenField('OrderID', '', $order->ID)
		);
		if($returnToLink) {
			$fields->push(new HiddenField("returntolink", "", convert::raw2att($returnToLink)));
		}
		$totalAsCurrencyObject = $order->TotalAsCurrencyObject();
		$paymentFields = Payment::combined_form_fields($totalAsCurrencyObject->Nice());
		foreach($paymentFields as $paymentField) {
			if($paymentField->class == "HeaderField") {
				$paymentField->setTitle(_t("OrderForm.MAKEPAYMENT", "Make Payment"));
			}
			$fields->push($paymentField);
		}
		$requiredFields = array();
		if($paymentRequiredFields = Payment::combined_form_requirements()) {
			$requiredFields = array_merge($requiredFields, $paymentRequiredFields);
		}
		$actions = new FieldSet(
			new FormAction('dopayment', _t('OrderForm.PAYORDER','Pay outstanding balance'))
		);
		$form = parent::__construct($controller, $name, $fields, $actions, $requiredFields);
		$this->setFormAction($controller->Link().$name);
	}

	function dopayment($data, $form) {
		$SQLData = Convert::raw2sql($data);
		if(isset($SQLData['OrderID'])) {
			if($orderID = intval($SQLData['OrderID'])) {
				$order = Order::get_by_id_if_can_view($orderID);
				if($order && $order->canPay()) {
					return EcommercePayment::process_payment_form_and_return_next_step($order, $form, $data);
				}
			}
		}
		$form->sessionMessage(_t('OrderForm.COULDNOTPROCESSPAYMENT','Sorry, we could not process your payment.'),'bad');
		Director::redirectBack();
		return false;
	}

}


/**
 * @Description: allows customer to cancel order.
 *
 * @package: ecommerce
 * @authors: Silverstripe, Jeremy, Nicolaas
 **/


class OrderForm_Cancel extends Form {

	function __construct($controller, $name, $order) {
		$fields = new FieldSet(
			new HiddenField('OrderID', '', $order->ID)
		);
		$actions = new FieldSet(
			new FormAction('docancel', _t('OrderForm.CANCELORDER','Cancel this order'))
		);
		parent::__construct($controller, $name, $fields, $actions);
	}

	/**
	 * Form action handler for OrderForm_Cancel.
	 *
	 * Take the order that this was to be change on,
	 * and set the status that was requested from
	 * the form request data.
	 *
	 * @param array $data The form request data submitted
	 * @param Form $form The {@link Form} this was submitted on
	 */
	function docancel($data, $form) {
		$SQLData = Convert::raw2sql($data);
		$member = Member::currentUser();
		if($member) {
			if(isset($SQLData['OrderID']) && $order = DataObject::get_one('Order', "\"ID\" = ".intval($SQLData['OrderID'])." AND \"MemberID\" = ".$member->ID)){
				if($order->canCancel()) {
					$order->Cancel($member);
					$form->sessionMessage(
						_t(
							'OrderForm.CANCELLEDORDER',
							'Order has been cancelled.'
						),
						'good'
					);
					if($link = AccountPage::find_link()){
						//see issue 150
						AccountPage_Controller::set_message(_t("OrderForm.ORDERHASBEENCANCELLED","Order has been cancelled"));
						Director::redirect($link);
					}
					Director::redirectBack();
					return false;
				}
			}
		}
		$form->sessionMessage(
			_t(
				'OrderForm.COULDNOTCANCELORDER',
				'Sorry, order could not be cancelled.'
			),
			'bad'
		);
		Director::redirectBack();
		return false;
	}
}


