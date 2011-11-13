<?php

class SetFixedPriceForSubmittedOrderItems extends BuildTask{

	protected $title = "Set Fixed Price for Submitted Order Items";

	protected $description = "Migration taks to fix the price for submitted order items";

	function run($request){
		$db = DB::getConn();
		$fieldArray = $db->fieldList("OrderModifier");
		$hasField =  isset($fieldArray["CalculationValue"]);
		if($hasField) {
			DB::query("
				UPDATE \"OrderAttribute\"
				INNER JOIN \"OrderModifier\"
					ON \"OrderAttribute\".\"ID\" = \"OrderModifier\".\"ID\"
				SET \"OrderAttribute\".\"CalculatedTotal\" = \"OrderModifier\".\"CalculationValue\"
				WHERE \"OrderAttribute\".\"CalculatedTotal\" = 0"
			);
			DB::query("ALTER TABLE \"OrderModifier\" DROP \"CalculationValue\" ");
		}
		$limit = 1000;
		$orderItems = DataObject::get(
			"OrderItem",
			"\"Quantity\" <> 0 AND \"OrderAttribute\".\"CalculatedTotal\" = 0",
			"\"Created\" ASC",
			"INNER JOIN
				\"Order\" ON \"Order\".\"ID\" = \"OrderAttribute\".\"OrderID\"",
			1000
		);
		$count = 0;
		if($orderItems) {
			foreach($orderItems as $orderItem) {
				if($orderItem->Order()) {
					if($orderItem->Order()->IsSubmitted()) {
						$orderItem->CalculatedTotal = $orderItem->UnitPrice(true) * $orderItem->Quantity;
						DB::alteration_message($orderItem->UnitPrice(true)." * ".$orderItem->Quantity  ." = ".$orderItem->CalculatedTotal, "edited");
						$orderItem->write();
						$count++;
					}
				}
			}
		}
		DB::alteration_message("Fixed price for all submmitted orders without a fixed one - affected: $count order items", "created");
	}

}
