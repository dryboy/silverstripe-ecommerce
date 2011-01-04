<?php
 /**
	* Order form that allows a user to purchase their
	* order items on the
	*
	* @see CheckoutPage
	*
	* @package ecommerce
	*/
class OrderForm extends Form {



	function __construct($controller, $name) {
		//Requirements::themedCSS('OrderForm');

		Requirements::javascript('ecommerce/javascript/OrderForm.js');

		// 1) Member fields
		$member = Member::currentUser() ? Member::currentUser() : singleton('Member');

		$memberFields = new CompositeField($member->getEcommerceFields());

		$requiredFields = $member->getEcommerceRequiredFields();

		$leftFields = new CompositeField($memberFields);
		$leftFields->setID('LeftOrder');

		$rightFields = new CompositeField();
		$rightFields->setID('RightOrder');

		if(!$member || !$member->ID || $member->Password == '') {
			//login invite right on the top
			$rightFields->push(new HeaderField(_t('OrderForm.MEMBERSHIPDETAILS','Membership Details'), 3));
			$rightFields->push(new LiteralField('MemberInfo', '<p class="message good">'._t('OrderForm.MEMBERINFO','If you are already a member please')." <a href=\"Security/login?BackURL=" . CheckoutPage::find_link(true) . "/\">"._t('OrderForm.LOGIN','log in').'</a>.</p>'));
			$rightFields->push(new LiteralField('AccountInfo', '<p>'._t('OrderForm.ACCOUNTINFO','Please choose a password, so you can login and check your order history in the future').'</p><br/>'));
			$rightFields->push(new FieldGroup(new ConfirmedPasswordField('Password', _t('OrderForm.PASSWORD','Password'))));
			$requiredFields[] = 'Password[_Password]';
			$requiredFields[] = 'Password[_ConfirmPassword]';
		}


		// 2) Payment fields
		$bottomFields = new CompositeField();
		$currentOrder = ShoppingCart::current_order();
		$totalAsCurrencyObject = $currentOrder->TotalAsCurrencyObject(); //should instead be $totalobj = $currentOrder->dbObject('Total');
		$paymentFields = Payment::combined_form_fields($totalAsCurrencyObject->Nice());
		foreach($paymentFields as $field) {
			$bottomFields->push($field);
		}
		if($paymentRequiredFields = Payment::combined_form_requirements()) {
			$requiredFields = array_merge($requiredFields, $paymentRequiredFields);
		}

		// 3) Terms and conditions field
		// If a terms and conditions page exists, we need to create a field to confirm the user has read it

		if($controller->TermsPageID && $termsPage = DataObject::get_by_id('Page', $controller->TermsPageID)) {
			$bottomFields->push(new CheckboxField('ReadTermsAndConditions', _t("OrderForm.AGREEWITHTERMS","I agree to the terms and conditions stated on the ")." <a href=\"".$termsPage->URLSegment."\">".$termsPage->Title."</a> "._t("OrderForm.PAGE","page.")));
			$requiredFields[] = 'ReadTermsAndConditions';
		}
		$bottomFields->push(new TextareaField('CustomerOrderNote', 'Note / Question', 7, 30));
		$bottomFields->setID('BottomOrder');
		// 4) Put all the fields in one FieldSet
		$fields = new FieldSet($rightFields, $leftFields, $bottomFields);

		// 5) Actions and required fields creation
		$actions = new FieldSet(new FormAction('processOrder', _t('OrderForm.PROCESSORDER','Place order and make payment')));
		$requiredFields = new CustomRequiredFields($requiredFields);
		$this->extend('updateValidator',$requiredFields);
		$this->extend('updateFields',$fields);
		Requirements::javascript('ecommerce/javascript/OrderFormWithShippingAddress.js');
		if(CheckoutPage::get_add_shipping_fields()) {
			$countryField = new DropdownField('ShippingCountry',  _t('OrderForm.COUNTRY','Country'), Geoip::getCountryDropDown(), EcommerceRole::find_country());
			$shippingFields = new CompositeField(
				new HeaderField(_t('OrderForm.SENDGOODSTODIFFERENTADDRESS','Send goods to different address'), 3),
				new LiteralField('ShippingNote', '<p class="message warning">'._t('OrderFormWithShippingAddress.SHIPPINGNOTE','Your goods will be sent to the address below.').'</p>'),
				new LiteralField('Help', '<p>'._t('OrderFormWithShippingAddress.HELP','You can use this for gift giving. No billing information will be disclosed to this address.').'</p>'),
				new TextField('ShippingName', _t('OrderFormWithShippingAddress.NAME','Name')),
				new TextField('ShippingAddress', _t('OrderFormWithShippingAddress.ADDRESS','Address')),
				new TextField('ShippingAddress2', _t('OrderFormWithShippingAddress.ADDRESS2','')),
				new TextField('ShippingCity', _t('OrderFormWithShippingAddress.CITY','City')),
				new TextField('ShippingPostalCode', _t('OrderFormWithShippingAddress.SHIPPINGPOSTALCODE','Postal Code')),
				$countryField
			);
			//Need to to this because 'FormAction_WithoutLabel' has no text on the actual button
			//$requiredFields[] = 'ShippingName';
			//$requiredFields[] = 'ShippingAddress';
			//$requiredFields[] = 'ShippingCity';
			//	$requiredFields[] = 'ShippingCountry';
			$shippingFields->SetID('ShippingFields');
			$shippingFields->setForm($this);
			$fields->insertBefore(new CheckboxField("UseShippingAddress", _t("", "Use Alternative Delivery Address")), "BottomOrder");
			$fields->insertBefore($shippingFields, "BottomOrder");
		}
		// 6) Form construction
		parent::__construct($controller, $name, $fields, $actions, $requiredFields);

		// 7)  Load saved data
		$order = ShoppingCart::current_order();
		if($order) {
			$this->loadDataFrom($order);
			if(CheckoutPage::get_add_shipping_fields()) {
				if ($shippingAddress = $order->ShippingAddress()) {
					$this->loadDataFrom($shippingAddress);
				}
			}
		}
		if($member->ID) {
			if(!$member->Country) {
				$member->Country = EcommerceRole::find_country();
			}
			$this->loadDataFrom($member);
		}

		//allow updating via decoration
		$this->extend('updateForm',$this);
	}


	function addValidAction($action){
		$this->validactions[] = $action;
	}

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
		$paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
		$payment = class_exists($paymentClass) ? new $paymentClass() : null;

		if(!($payment && $payment instanceof Payment)) {
			user_error(get_class($payment) . ' is not a valid Payment object!', E_USER_ERROR);
		}
		$this->saveDataToSession($data); //save for later if necessary

		//check for cart items
		if(!ShoppingCart::has_items()) {
			$form->sessionMessage(_t('OrderForm.NOITEMSINCART','Please add some items to your cart'), 'bad');
			Director::redirectBack();
			return false;
		}

		//check that price hasn't changed
		$oldtotal = ShoppingCart::current_order()->Total();

		// Create new Order from shopping cart, discard cart contents in session
		$order = ShoppingCart::current_order();
		//TO DO: HOW CAN THESE TWO BE DIFFERENT????
		if($order->Total() != $oldtotal) {
			$form->sessionMessage(_t('OrderForm.PRICEUPDATED','The order price has been updated'), 'warning');
			Director::redirectBack();
			return false;
		}

		// Create new OR update logged in {@link Member} record
		$member = EcommerceRole::ecommerce_create_or_merge($data);
		if(!$member) {
			$form->sessionMessage(
				_t(
					'OrderForm.MEMBEREXISTS', 'Sorry, a member already exists with that email address.
					If this is your email address, please log in first before placing your order.'
				),
				'bad'
			);

			Director::redirectBack();
			return false;
		}
		$member->write();
		$member->logIn();
		if($member)	{
			$payment->PaidByID = $member->ID;
		}
		// Write new record {@link Order} to database
		$form->saveInto($order);
		$order->save(); //sets status from CanEdit to Next available Status
		$order->MemberID = $member->ID;
		$order->write();
		if(isset($data['UseShippingAddress']) && $data['UseShippingAddress']){
			$shippingAddress = new ShippingAddress();
			$form->saveInto($shippingAddress);
			$shippingAddress->OrderID = $order->ID;
			$shippingAddress->write();
			$order->ShippingAddressID = $shippingAddress->ID;
			$order->write();
		}
		else {
			die("NOT GOOD");
		}
		$this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed
		// Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		$payment->PaidForID = $order->ID;
		$payment->PaidForClass = $order->class;
		$payment->Amount->Amount = $order->Total(); //TODO: should this instead be TotalOutstanding ...incase for some reason orders are partially paid.
		$payment->write();
		//prepare $data - ie put into the $data array any fields that may need to be there for payment
		// Process payment, get the result back
		$result = $payment->processPayment($data, $form);
		// isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
		if($result->isProcessing()) {
			return $result->getValue();
		}
		if($result->isSuccess()) {
			$order->sendReceipt();
		}
		Director::redirect($order->Link());
		return true;
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
		Session::set("FormInfo.{$this->FormName()}.data", null);
	}

}
