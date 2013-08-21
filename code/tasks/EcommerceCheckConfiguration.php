<?php

/**
 * This class reviews all of the static configurations in e-commerce for review
 * (a) which configs are set, but not required
 * (b) which configs are required, but not set
 * (c) review of set configs
 *
 * @TODO: compare to default
 *
 * shows you the link to remove the current cart
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommerceCheckConfiguration extends BuildTask{

	/**
	 * Default Location for Configuration File
	 * @var String
	 */
	protected $defaultLocation = "ecommerce/_config/ecommerce.yml";

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $title = "Check Configuration";

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $description = "Runs through all static configuration for review.";

	/**
	 * Array of definitions - set like this:
	 * ClassName
	 * 		VariableName: Description
	 * @var Array
	 */
	protected $definitions = array();

	/**
	 * Array of defaults - set like this:
	 * ClassName
	 * 		VariableName: Default Variable Value
	 * @var Array
	 */
	protected $defaults = array();


	/**
	 * Array of configs - set like this:
	 * ClassName
	 * 		VariableName: VariableValue
	 * @var Array
	 */
	protected $configs = array();

	/**
	 * which values are derived from DB
	 * ClassName
	 * 		VariableName: TRUE | FALSE
	 * @var Array
	 */
	protected $databaseValues = array();

	/**
	 * set in default yml, but not customised.
	 * ClassName
	 * 		VariableName: TRUE | FALSE
	 * @var Array
	 */
	protected $customisedValues = array();

	/**
	 * Other configs
	 * ClassName
	 * 		VariableName: TRUE | FLASE
	 * @var Array
	 */
	protected $otherConfigs = array();

	/**
	 * Array of classes (partially) missing in configs.
	 * VariableName: VariableName
	 *  @var Array
	 */
	protected $missingClasses = array();



	/**
	 * Standard (required) SS method, runs buildtask
	 */
	function run($request){
		$definitionsObject = EcommerceConfigDefinitions::create();
		$this->definitions = $definitionsObject->Definitions();
		$configsObject = EcommerceConfig::create();
		$this->configs = $configsObject->getCompleteDataSet();
		$this->defaults = $this->getDefaultValues();
		if($this->definitions) {
			if($this->configs) {
				if($this->defaults) {
					$this->checkFiles();
					$this->classesThatDoNotExist();
					$this->definitionsNotSet();
					$this->configsNotSet();
					$this->addEcommerceDBConfigToConfigs();
					$this->addOtherValuesToConfigs();
					$this->addPages();
					$this->orderSteps();
					$this->checkoutAndModifierDetails();
					$this->getAjaxDefinitions();
					$this->definedConfigs();
				}
				else {
					DB::alteration_message("ERROR: could not find any defaults", "deleted");
				}
			}
			else {
				DB::alteration_message("ERROR: could not find any configs", "deleted");
			}
		}
		else {
			DB::alteration_message("ERROR: could not find any definitions", "deleted");
		}

	}

	/**
	 * Check what files is being used
	 */
	protected function checkFiles(){
		$configsObject = EcommerceConfig::create();
		DB::alteration_message("<h2>Files Used</h2>");
		$files = implode(", ", $configsObject->fileLocations());
		global $project;
		$baseFolder = Director::baseFolder();
		$projectFolder = $project."/_config";
		$baseAndProjectFolder = $baseFolder."/".$projectFolder;
		$file = "ecommerce.yml";
		$projectFolderAndFile = $projectFolder."/".$file;
		$fullFilePath = $baseFolder."/".$projectFolderAndFile;
		$defaultFileFullPath = Director::baseFolder()."/".$this->defaultLocation;
		DB::alteration_message("
			Current files used: <strong style=\"color: darkRed\">".$files."</strong>,
			unless stated otherwise, all settings can be edited in these file(s).",
			"created"
		);
		if(!file_exists($baseAndProjectFolder)) {
			mkdir($baseAndProjectFolder);
		}
		if(!file_exists($fullFilePath)) {
			copy($defaultFileFullPath, $fullFilePath);
			DB::alteration_message("We have created a new configuration file for you.", "created");
		}
		if($files == $this->defaultLocation) {
			if(file_exists($fullFilePath)) {
				DB::alteration_message("A customisable configuration file exists here: $projectFolderAndFile, you should add the following to your config.yml file:
<pre>
EcommerceConfig:
  folder_and_file_locations:
    - \"$projectFolderAndFile\"
</pre>", "created");
			}
		}
	}

	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function definitionsNotSet(){
		DB::alteration_message("<h2>Set in configs but not defined</h2>");
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!isset($this->definitions[$className])) {
				$allOK = false;
				$this->missingClasses[$className] = $className;
				DB::alteration_message("$className", "deleted");
			}
			else {
				$classConfigs = $this->configs[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->definitions[$className][$key])) {
						$allOK = false;
						DB::alteration_message("$className.$key", "deleted");
					}
				}
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
		else {
			DB::alteration_message("Recommended course of action: remove from your config as these are superfluous!", "edited");
		}
	}


	/**
	 * Work out items set in the configuration but not set in the config file.
	 */
	protected function classesThatDoNotExist(){
		DB::alteration_message("<h2>Classes that do not exist</h2>");
		$allOK = true;
		foreach($this->configs as $className => $setting) {
			if(!class_exists($className)) {
				$allOK = false;
				DB::alteration_message("$className", "deleted");
			}
		}
		if($allOK) {
			DB::alteration_message("Perfect match, nothing to report", "created");
		}
		else {
			DB::alteration_message("Recommended course of action: remove from your config file and review if any other action needs to be taken.", "edited");
		}

	}


	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function configsNotSet(){
		$allOK = true;
		foreach($this->definitions as $className => $setting) {
			if(!isset($this->configs[$className])) {
				$this->configs[$className] = array();
			}
			else {
				$classConfigs = $this->definitions[$className];
				foreach($classConfigs as $key => $classConfig) {
					if(!isset($this->configs[$className][$key])) {
						$this->customisedValues[$className][$key] = false;
						//fallback to Configs...
						$this->configs[$className][$key] = Config::inst()->get($className, $key);
					}
					else {
						$this->customisedValues[$className][$key] = false;
					}
				}
			}
		}

	}


	/**
	 * Work out items set in the definitions but not set in the config file.
	 */
	protected function definedConfigs(){
		$htmlHeader = "
		<style>
			th[scope='col'] {text-align: left; border-bottom: 3px solid blue;padding-top: 40px;}
			td {vertical-align: top; border-left: 1px solid grey; border-bottom: 1px solid grey;}
			td span {color: #333; font-size: 0.8em;}
			td span {color: #333; font-size: 0.8em; display: block}
			.sameConfig {color: #333;}
			.newConfig{background-color: green!important; color: #fff; }
			#TOC {
				-moz-column-count: 3;
				-moz-column-gap: 20px;
				-webkit-column-count: 3;
				-webkit-column-gap: 20px;
				column-count: 3;
				column-gap: 20px;
			}
			a.backToTop {display: block; font-size: 0.8em; }
			td.newConfig {width: 70%;}
			table td pre, table td sub {white-space:pre-wrap; font-size: 9px!important; font-weight: bold;margin: 5px; padding: 5px;}
		</style>
		<h2>Configuration Report</h2>";
		$htmlTable = "
		<table summary=\"list of configs\">
		";
		$oldClassName = "";
		$htmlTOC = "<ol id=\"TOC\">";
		$count = 0;
		foreach($this->configs as $className => $settings) {
			$count++;
			$htmlTOC .= "<li><a href=\"#$className\">$className</a></li>";
			if($className != $oldClassName) {
				$htmlTable .= "<tr id=\"$className\"><th colspan=\"2\" scope=\"col\">
					<a href=\"/dev/viewcode/$className\" target=\"_blank\">$count. $className</a>
					<a class=\"backToTop\" href=\"#TOC\">top</a>
					</th></tr>";
				$oldClassName = $className;
			}

			foreach($settings as $key => $classConfig) {
				$configError = "";
				$class = "";
				$hasDefaultvalue = false;
				$isDatabaseValues = isset($this->databaseValues[$className][$key]) ? $this->databaseValues[$className][$key] : false;
				$isOtherConfigs = isset($this->otherConfigs[$className][$key]) ? $this->otherConfigs[$className][$key] : false;
				$isCustomisedValues = isset($this->customisedValues[$className][$key]) ? $this->customisedValues[$className][$key] : false;
				if(!isset($this->defaults[$className][$key])) {
					//DB::alteration_message("Could not retrieve default value for: $className $key", "deleted");
				}
				else {
					$defaultValue = print_r($this->defaults[$className][$key], 1);
					$hasDefaultvalue = true;
				}
				$manuallyAddedValue = print_r($this->configs[$className][$key], 1);
				$actualValueRaw = EcommerceConfig::get($className, $key);
				if(!$actualValueRaw && $manuallyAddedValue) {
					$actualValueRaw = $manuallyAddedValue;
				}

				$actualValue = print_r($actualValueRaw, 1);
				if($defaultValue === $manuallyAddedValue && $isCustomisedValues) {
					$configError .= "This is a superfluous entry in your custom config as the default value is the same.";
				}
				$hasDefaultvalue = true;
				if($defaultValue === $actualValue) {
					$class .= "sameConfig";
					$defaultValue = "";
					$hasDefaultvalue = false;
				}
				else {
					$class .= " newConfig";
				}
				$actualValue = $this->turnValueIntoHumanReadableValue($actualValue);
				if($hasDefaultvalue) {
					$defaultValue = $this->turnValueIntoHumanReadableValue($defaultValue);
				}

				if(!isset($this->definitions[$className][$key])) {
					$description = "<span style=\"color: red; font-weight: bold\">ERROR: no longer required in configs!</span>";
				}
				else {
					$description = $this->definitions[$className][$key];
					$description .= $this->specialCases($className, $key, $actualValue);
				}
				$defaultValueHTML = "";
				if($defaultValue && !$isOtherConfigs) {
					$defaultValueHTML = "<sub>e-commerce defaults:</sub><pre>$defaultValue</pre>";
				}
				if($configError) {
					$configError = "<span style=\"color: red; font-size: 10px;\">$configError</span>";
				}
				$sourceNote = "";
				if($isDatabaseValues) {
					$sourceNote = "<span>Values are set in the database using the CMS.</span>";
				}
				$htmlTable .= "<tr>
			<td>
				$key
				<span>$description</span>
				$sourceNote
			</td>
			<td class=\"$class\">
				<pre>$actualValue</pre>
				$defaultValueHTML
				$configError
			</td>
		</tr>";
			}
		}
		$htmlEnd = "
		</table>
		<h2>--- THE END ---</h2>
		";
		$htmlTOC .= "</ol>";
		echo $htmlHeader.$htmlTOC.$htmlTable.$htmlEnd;
	}

	protected function getDefaultValues(){
		require_once 'thirdparty/spyc/spyc.php';
		$fixtureFolderAndFile = Director::baseFolder()."/".$this->defaultLocation;
		$parser = new Spyc();
		return $parser->loadFile($fixtureFolderAndFile);
	}

	/**
	 * Adding EcommerceDBConfig values
	 */
	protected function addEcommerceDBConfigToConfigs(){
		$ecommerceDBConfig = EcommerceDBConfig::current_ecommerce_db_config();
		$fields = $ecommerceDBConfig->fieldLabels();
		if($fields) {
			foreach($fields as $field => $description) {
				if($field != "Title" && $field != "UseThisOne") {
					$defaultsDefaults = $ecommerceDBConfig->stat("defaults");
					$this->definitions["EcommerceDBConfig"][$field] = "$description. <br />see: <a href=\"/admin/shop/EcommerceDBConfig/EditForm/field/EcommerceDBConfig/item/".$ecommerceDBConfig->ID."/edit\">Ecommerce Configuration</a>";
					$this->configs["EcommerceDBConfig"][$field] = $ecommerceDBConfig->$field;
					$this->databaseValues["EcommerceDBConfig"][$field] = true;
					$this->defaults["EcommerceDBConfig"][$field] = isset($defaultsDefaults[$field]) ? $defaultsDefaults[$field] : "no default set";
					$imageField = $field."ID";
					if(isset($ecommerceDBConfig->$imageField)) {
						if($image = $ecommerceDBConfig->$field()) {
							if($image->exists() && is_a($image, Object::getCustomClass("Image")) ) {
								$this->configs["EcommerceDBConfig"][$field] = "[Image]  --- <img src=\"".$image->Link()."\" />";
								$this->databaseValues["EcommerceDBConfig"][$field] = true;
							}
						}
					}
				}
			}
		}
	}


	protected function addOtherValuesToConfigs(){

		$this->definitions["Email"]["admin_email_address"] = "Default administrator email. <br />SET USING Email::\$admin_email_address = \"bla@ta.com\" in the _config.php FILES";
		$this->configs["Email"]["admin_email_address"] = Config::inst()->get("Email", "admin_email_address");
		$this->defaults["Email"]["admin_email_address"] = "[no default set]";
		$this->otherConfigs["Email"]["admin_email_address"] = true;

		$siteConfig = SiteConfig::current_site_config();
		$this->definitions["SiteConfig"]["website_title"] = "The name of the website. <br />see: <a href=\"/admin/settings/\">site configuration</a>.";
		$this->configs["SiteConfig"]["website_title"] = $siteConfig->Title;
		$this->defaults["SiteConfig"]["website_title"] = "[no default set]";
		$this->otherConfigs["SiteConfig"]["website_title"] = true;

		$this->definitions["SiteConfig"]["website_tagline"] = "The subtitle or tagline of the website. <br />see: <a href=\"/admin/settings/\">site configuration</a>.";
		$this->configs["SiteConfig"]["website_tagline"] = $siteConfig->Tagline;
		$this->defaults["SiteConfig"]["website_tagline"] = "[no default set]";
		$this->otherConfigs["SiteConfig"]["website_tagline"] = true;

	}

	protected function addPages(){


		if($checkoutPage = CheckoutPage::get()->First()) {
			$this->getPageDefinitions($checkoutPage);
			$this->definitions["Pages"]["CheckoutPage"] = "Page where customers finalise (checkout) their order. This page is required.<br />".($checkoutPage ? "<a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["CheckoutPage"] = $checkoutPage ? "view: <a href=\"".$checkoutPage->Link()."\">".$checkoutPage->Title."</a><br />".$checkoutPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["CheckoutPage"] = $checkoutPage ? $checkoutPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["CheckoutPage"] = true;
		}

		if($orderConfirmationPage = OrderConfirmationPage::get()->First()) {
			$this->getPageDefinitions($orderConfirmationPage);
			$this->definitions["Pages"]["OrderConfirmationPage"] = "Page where customers review their order after it has been placed. This page is required.<br />".($orderConfirmationPage ? "<a href=\"/admin/pages/edit/show/".$orderConfirmationPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["OrderConfirmationPage"] = $orderConfirmationPage ? "view: <a href=\"".$orderConfirmationPage->Link()."\">".$orderConfirmationPage->Title."</a><br />".$orderConfirmationPage->configArray: " NOT CREATED!";
			$this->defaults["Pages"]["OrderConfirmationPage"] = $orderConfirmationPage ? $orderConfirmationPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["OrderConfirmationPage"] = true;
		}

		if($accountPage = AccountPage::get()->First()) {
			$this->getPageDefinitions($accountPage);
			$this->definitions["Pages"]["AccountPage"] = "Page where customers can review their account. This page is required.<br />".($accountPage ? "<a href=\"/admin/pages/edit/show/".$accountPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["AccountPage"] = $accountPage ? "view: <a href=\"".$accountPage->Link()."\">".$accountPage->Title."</a><br />".$accountPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["AccountPage"] = $accountPage ? $accountPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["AccountPage"] = true;
		}

		if(
			$cartPage = CartPage::get()
				->Filter(array("ClassName" => "CartPage"))
				->First()
		) {
			$this->getPageDefinitions($cartPage);
			$this->definitions["Pages"]["CartPage"] = "Page where customers review their cart while shopping. This page is optional.<br />".($cartPage ? "<a href=\"/admin/pages/edit/show/".$cartPage->ID."/\">edit</a>" : "Create one in the <a href=\"/admin/pages/add/\">CMS</a>");
			$this->configs["Pages"]["CartPage"] = $cartPage ? "view: <a href=\"".$cartPage->Link()."\">".$cartPage->Title."</a>, <a href=\"/admin/pages/edit/show/".$cartPage->ID."/\">edit</a><br />".$cartPage->configArray : " NOT CREATED!";
			$this->defaults["Pages"]["CartPage"] = $cartPage ? $cartPage->defaultsArray : "[add page first to see defaults]";
			$this->defaults["Pages"]["CartPage"] = $cartPage ? $cartPage->defaultsArray : "[add page first to see defaults]";
			$this->databaseValues["Pages"]["CartPage"] = true;
		}
	}

	private function getPageDefinitions(SiteTree $page){
		if($page) {
			$fields = Config::inst()->get($page->ClassName, "db");
			$defaultsArray = $page->stat("defaults", true);
			$configArray = array();
			if($fields) {
				foreach($fields as $fieldKey => $fieldType) {
					$configArray[$fieldKey] = $page->$fieldKey;
					if(!isset($defaultsArray[$fieldKey])) {
						$defaultsArray[$fieldKey] = "[default not set]";
					}
				}
			}
			$page->defaultsArray = $defaultsArray;
			$page->configArray = print_r($configArray, 1);
		}
	}


	function orderSteps(){
		$steps = OrderStep::get();
		if($steps->count()) {
			foreach($steps as $step) {
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$ecommerceDBConfig = EcommerceDBConfig::current_ecommerce_db_config();
				$this->definitions["OrderStep"][$step->Code] = $step->Description."<br />see: <a href=\"/admin/shop/OrderStep/EditForm/field/OrderStep/item/".$step->ID."/edit\">Ecommerce Configuration</a>.";
				$this->configs["OrderStep"][$step->Code] = $configArray;
				$this->defaults["OrderStep"][$step->Code] = $defaultsArray;
				$this->databaseValues["OrderStep"][$step->Code] = true;
			}
		}
	}

	function checkoutAndModifierDetails(){
		$checkoutPage = CheckoutPage::get()->First();
		if(!$checkoutPage) {
			$task = new EcommerceDefaultRecords();
			$task->run(null);
			$checkoutPage = CheckoutPage::get()->First();
			if(!$checkoutPage) {
				user_error("There is no checkout page available and it seems impossible to create one.");
			}
		}
		$steps = CheckoutPage_StepDescription::get();
		if($steps->count()) {
			foreach($steps as $key => $step) {
				$stepNumber = $key + 1;
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$this->definitions["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $step->Description."<br />see: <a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">checkout page</a>.";
				$this->configs["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $configArray;
				$this->defaults["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = $defaultsArray;
				$this->databaseValues["CheckoutPage_Controller"]["STEP_$stepNumber"."_".$step->getCode()] = true;
			}
		}
		$steps = OrderModifier_Descriptor::get();
		if($steps->count()) {
			foreach($steps as $step) {
				$fields = Config::inst()->get($step->ClassName, "db");
				$defaultsArray = $step->stat("defaults", true);
				$configArray = array();
				foreach($fields as $fieldKey => $fieldType) {
					if($fields) {
						$configArray[$fieldKey] = $step->$fieldKey;
						if(!isset($defaultsArray[$fieldKey])) {
							$defaultsArray[$fieldKey] = "[default not set]";
						}
					}
				}
				$this->definitions["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $step->Description."<br />see: <a href=\"/admin/pages/edit/show/".$checkoutPage->ID."/\">checkout page</a>.";
				$this->configs["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $configArray;
				$this->defaults["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = $defaultsArray;
				$this->databaseValues["CheckoutPage_Controller"]["OrderModifier_Descriptor_".$step->ModifierClassName] = true;
			}
		}
	}

	private function getAjaxDefinitions(){
		$definitionsObject = EcommerceConfigDefinitions::create();
		$methodArray = $definitionsObject->getAjaxMethods();
		$requestor = new ArrayData(
			array(
				"ID" => "[ID]",
				"ClassName" => "[CLASSNAME]"
			)
		);
		$obj = EcommerceConfigAjax::get_one($requestor);
		foreach($methodArray as $method => $description) {
			if($method != "setRequestor") {
				if(strpos($method, "lassName")) {
					$selector ="classname";
				}
				else {
					$selector ="id";
				}
				$note = "
					This variable can be used like this: <pre>&lt;div $selector=\"\$AJAXDefinitions.".$method."\"&gt;&lt;/div&gt;</pre>
					<a href=\"/shoppingcart/ajaxtest/?ajax=1\">AJAX</a> will then use this selector to put the following content: ";
				$this->definitions["Templates"]["AJAXDefinitions_$method"] = $note."<br />".$description;
				$this->configs["Templates"]["AJAXDefinitions_$method"] = $obj->$method();
				$this->defaults["Templates"]["AJAXDefinitions_$method"] = "";
				$this->otherConfigs["Templates"]["AJAXDefinitions_$method"] = true;
			}
		}
	}

	/**
	 * check for any additional settings
	 *
	 */
	private function specialCases($className, $key, $actualValue){
		switch($className.".".$key) {
			case "Order_Email.css_file_location":
				if(!file_exists(Director::baseFolder()."/$actualValue")) {
					return "<span style=\"color: red\">ADDITIONAL CHECK: this file does not exist! For proper functioning of e-commerce, please make sure to create this file.</span>";
				}
				else {
					return "<span style=\"color: green\">ADDITIONAL CHECK: file exists.</span>";
				}
				break;
			case "Order.modifiers":
				$classes = ClassInfo::subclassesFor("OrderModifier");
				unset($classes[0]);
				$classesAsString = implode(", <br />", $classes);
				return "<br /><h4>Available Modifiers</h4>$classesAsString";
				break;
			case "OrderStatusLog.available_log_classes_array":
				$classes = ClassInfo::subclassesFor("OrderStatusLog");
				unset($classes[0]);
				$classesAsString = implode(", <br />", $classes);
				return "<br /><h4>Available Modifiers</h4>$classesAsString";
				break;
		}
	}

	private function turnValueIntoHumanReadableValue($actualValue){
		if($actualValue === "") {
			$actualValue = "[FALSE] / [EMPTY STRING] ";
		}
		if($actualValue === null) {
			$actualValue = "[NULL]";
		}
		if($actualValue === "1" || $actualValue === 1) {
			$actualValue = "[TRUE] / 1";
		}
		if($actualValue === "0" || $actualValue === false) {
			$actualValue = "[FALSE] / 0";
		}
		return $actualValue;
	}

}

