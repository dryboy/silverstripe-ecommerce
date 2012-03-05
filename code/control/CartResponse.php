<?php

/**
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: control
 * @Description: this class
 *
 **/

class CartResponse extends EcommerceResponse {


	/**
	 * Builds json object to be returned via ajax.
	 *
	 *@return JSON
	 **/
	public function ReturnCartData($messages = array(), $data = null, $status = "success") {
		//add header
		$this->addHeader('Content-Type', 'application/json');
		if($status != "success") {
			$this->setStatusCode(400, "not successful: ".$status." --- ".$messages[0]);
		}

		//init Order - IMPORTANT
		$currentOrder = ShoppingCart::current_order();
		$currentOrder->calculateOrderAttributes(true);

		// populate Javascript
		$js = array ();
		if ($items = $currentOrder->Items()) {
			foreach ($items as $item) {
				$item->updateForAjax($js);
			}
		}
		if ($modifiers = $currentOrder->Modifiers()) {
			foreach ($modifiers as $modifier) {
				$modifier->updateForAjax($js);
			}
		}
		$currentOrder->updateForAjax($js);
		if(is_array($messages)) {
			$messagesImploded = '';
			foreach($messages as $messageArray) {
				$messagesImploded .= '<span class="'.$messageArray["Type"].'">'.$messageArray["Message"].'</span>';
			}
			$js[] = array(
				"id" => $currentOrder->TableMessageID(),
				"parameter" => "innerHTML",
				"value" => $messagesImploded,
				"isOrderMessage" => true
			);
			$js[] = array(
				"id" =>  $currentOrder->TableMessageID(),
				"parameter" => "hide",
				"value" => 0
			);
		}
		else {
			$js[] = array(
				"id" => $currentOrder->TableMessageID(),
				"parameter" => "hide",
				"value" => 1
			);
		}

		//add basic cart
		$js[] = array(
			"id" => $currentOrder->SideBarCartID(),
			"parameter" => "innerHTML",
			"value" => $currentOrder->renderWith("CartShortInner")
		);

		//merge and return
		if(is_array($data)) {
			$js = array_merge($js, $data);
		}
		$uniqueJS = array();
		foreach($js as $row) {
			if(!in_array($row, $uniqueJS)) {
				$uniqueJS[] = $row;
			}
		}
		//$uniqueJS = array_map('unserialize', array_unique(array_map('serialize', $js)));
		//$uniqueJS = array_unique($js, SORT_REGULAR);
		return str_replace("{", "\r\n{", Convert::array2json($uniqueJS));
	}

}
