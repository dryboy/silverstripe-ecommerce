<?php

class EcommerceDatabaseAdmin extends Controller{

	static $url_handlers = array(
		//'' => 'browse',
	);

	static $allowed_actions = array(
		'deleteproducts' => "ADMIN",
		'clearoldcarts' => "ADMIN",
		'updateproductgroups' => "ADMIN",
		'setfixedpriceforsubmittedorderitems' => "ADMIN"
	);

	function init() {
		parent::init();

		// We allow access to this controller regardless of live-status or ADMIN permission only
		// if on CLI or with the database not ready. The latter makes it less errorprone to do an
		// initial schema build without requiring a default-admin login.
		// Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		$canAccess = (
			Director::isDev()
			|| !Security::database_is_ready()
			// We need to ensure that DevelopmentAdminTest can simulate permission failures when running
			// "dev/tests" from CLI.
			|| (Director::is_cli() && !$isRunningTests)
			|| Permission::check("ADMIN")
		);
		if(!$canAccess) {
			return Security::permissionFailure($this,
				"This page is secured and you need administrator rights to access it. " .
				"Enter your credentials below and we will send you right along.");
		}
	}

	function clearoldcarts() {
		CartCleanupTask::run_on_demand();
	}

	function deleteproducts($request){
		$task = new DeleteEcommerceProductsTask();
		$task->run($request);
	}

	function updateproductgroups() {
		DB::query("UPDATE ProductGroup SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]);
		DB::query("UPDATE ProductGroup_Live SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]);
		DB::alteration_message("resetting product 'show' levels", "created");
	}

	function setfixedpriceforsubmittedorderitems() {
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
						$orderItem->write();
						$count++;
					}
				}
			}
		}
		DB::alteration_message("Fixed price for all submmitted orders without a fixed one - affected: $count order items", "created");
	}

	private $tests = array(
		'ShoppingCartTest' => 'Shopping Cart'
	);

	function Tests(){
		$dos = new DataObjectSet();
		foreach($this->tests as $class => $name){
			$dos->push(new ArrayData(array(
				'Name' => $name,
				'Class' => $class
			)));
		}
		return $dos;
	}

	function AllTests(){
		return implode(',',array_keys($this->tests));
	}

	public function Link($action = null) {
		$action = ($action) ? $action : "";
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/ecommerce/'.$action);
	}

}

