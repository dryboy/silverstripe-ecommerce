<?php


/**
 * @description: cleans up old (abandonned) carts...
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: cms
 *
 **/


class CartCleanupTask extends HourlyTask {


	static $allowed_actions = array(
		'*' => 'ADMIN',
		'*' => 'SHOPADMIN'
	);


	protected $title = 'Clear old carts';

	protected $description = "Deletes abandonned carts";

	public static function run_on_demand() {
		$obj = new CartCleanupTask();
		$obj->run($verbose = true);
		$obj->cleanupUnlinkedOrderObjects($verbose = true);
	}


/*******************************************************
	 * CLEARING OLD ORDERS
*******************************************************/

	protected static $clear_days = 30;
		function set_clear_days($i){self::$clear_days = $i;}
		function get_clear_days(){return self::$clear_days;}

	/**
	 * We need to protect the system from falling over by limiting the number of objects that can be deleted at any one time
	 *@var Integer
	 **/
	protected static $maximum_number_of_objects_deleted = 2;
		function set_maximum_number_of_objects_deleted($i){self::$maximum_number_of_objects_deleted = $i;}
		function get_maximum_number_of_objects_deleted(){return self::$maximum_number_of_objects_deleted;}

	protected static $never_delete_if_linked_to_member = true;
		function set_never_delete_if_linked_to_member($b){self::$never_delete_if_linked_to_member = $b;}
		function get_never_delete_if_linked_to_member(){return(boolean)self::$never_delete_if_linked_to_member;}

	/**
	 *
	 *key = table where OrderID is saved
	 *value = table where LastEdited is saved
	 **/
	protected static $linked_objects_array = array(
		"OrderAttribute" =>"OrderAttribute",
		"BillingAddress" => "OrderAddress",
		"ShippingAddress" => "OrderAddress",
		"OrderStatusLog" =>"OrderStatusLog",
		"OrderEmailRecord" =>"OrderEmailRecord"
	);
		static function set_linked_objects_array($a) {self::$linked_objects_array = $a;}
		static function get_linked_objects_array() {return self::$linked_objects_array;}
		static function add_linked_object($s) {self::$linked_objects_array[] = $s;}
/*******************************************************
	 * DELETE OLD SHOPPING CARTS
*******************************************************/

	/**
	 *@return Integer - number of carts destroyed
	 **/
	public function run($verbose = false){
		$count = 0;
		$time = date('Y-m-d H:i:s', strtotime("-".self::$clear_days." days"));
		$where = "\"StatusID\" = ".OrderStep::get_status_id_from_code("CREATED")." AND \"Order\".\"LastEdited\" < '$time'";
		$sort = "\"Order\".\"Created\" ASC";
		$join = "";
		$limit = "0, ".self::get_maximum_number_of_objects_deleted();
		if(self::$never_delete_if_linked_to_member) {
			$where .= " AND \"Member\".\"ID\" IS NULL";
			$join .= "LEFT JOIN \"Member\" ON \"Member\".\"ID\" = \"Order\".\"MemberID\" ";
		}
		$oldCarts = DataObject::get('Order',$where, $sort, $join, $limit);
		if($oldCarts){
			if($verbose) {
				$totalToDeleteSQLObject = DB::query("SELECT COUNT(*) FROM \"Order\" $join WHERE $where");
				$totalToDelete = $totalToDeleteSQLObject->value();
				DB::alteration_message("<h2>Total number of abandonned carts: ".$totalToDelete." .... now deleting: ".self::get_maximum_number_of_objects_deleted()." from ".self::get_clear_days()." days ago or more.</h2>", "created");
				if(self::get_never_delete_if_linked_to_member()) {
					DB::alteration_message("<h3>Carts linked to a member will NEVER be deleted.</h3>", "edited");
				}
				else {
					DB::alteration_message("<h3>We will also delete carts in this category that are linked to a member.</h3>", "edited");
				}
			}
			foreach($oldCarts as $oldCart){
				$count++;
				if($verbose) {
					DB::alteration_message("$count ... deleting abandonned order #".$oldCart->ID, "deleted");
				}
				$oldCart->delete();
				$oldCart->destroy();
			}
		}
		else {
			if($verbose) {
				$count = DB::query("SELECT COUNT(\"ID\") FROM \"Order\"")->value();
				DB::alteration_message("There are no abandonned orders. There are $count 'live' Orders.", "created");
			}
		}
		return $count;
	}

	function cleanupUnlinkedOrderObjects($verbose = false) {
		$classNames = self::get_linked_objects_array();
		if(is_array($classNames) && count($classNames)) {
			foreach($classNames as $classWithOrderID => $classWithLastEdited) {
				if($verbose) {
					DB::alteration_message("looking for $classWithOrderID objects without link to order.", "deleted");
				}
				$time = date('Y-m-d H:i:s', strtotime("-".self::$clear_days." days"));
				$where = "\"Order\".\"ID\" IS NULL AND \"$classWithLastEdited\".\"LastEdited\" < '$time'";
				$sort = '';
				$join = "LEFT JOIN \"Order\" ON \"Order\".\"ID\" = \"$classWithOrderID\".\"OrderID\"";
				$limit = "0, ".self::get_maximum_number_of_objects_deleted();
				$unlinkedObjects = DataObject::get($classWithLastEdited, $where, $sort, $join, $limit);
				if($unlinkedObjects){
					foreach($unlinkedObjects as $unlinkedObject){
						if($verbose) {
							DB::alteration_message("Deleting ".$unlinkedObject->ClassName." with ID #".$unlinkedObject->ID." because it does not appear to link to an order.", "deleted");
						}
						$unlinkedObject->delete();
						$unlinkedObject->destroy();
					}
				}
			}
		}
	}

}
