<?php
class StoreAdmin extends ModelAdmin{

	static $url_segment = 'store';
	static $menu_title = 'Store';
	
	static $menu_priority = 1;
	
	//static $url_priority = 50;
	
	public static $managed_models = array('Order','Payment','Product');

	public static $collection_controller_class = 'StoreAdmin_CollectionController';
	public static $record_controller_class = 'StoreAdmin_RecordController';

	public function init() {
		parent::init();

		Requirements::css('ecommerce/css/admin/admin.css');
		Requirements::javascript('ecommerce/javascript/admin/admin.js');
	}

}


class StoreAdmin_Request extends Controller {
	function init(){
		parent::init();
		if (!Permission::check("ADMIN")) {
			Security::permissionFailure ($this, "Please log in to access Store Admin");
		}		
	}
	
	function products(){
		$controller = new StoreAdmin_ContentController();
		return $controller->renderWith("StoreAdmin_products");
	}	
}

class StoreAdmin_ContentController extends Controller {
	//no actions allowed on this controller
	static $allowed_actions = array("index");	
	function init(){
		parent::init();
		if (!Permission::check("ADMIN")) {
			Security::permissionFailure ($this, "Please log in to access Ecommerce Admin");
		}		
	}
	function index(){
		return "";
	}

	function Link(){
		return Director::urlParam("Controller");
	}
	
	function URLSegment(){
		return $this->Link();
	}
	
	function Products(){
		return DataObject::get("Product");
	}
	
}






//remove side forms
class StoreAdmin_CollectionController extends ModelAdmin_CollectionController {

	//public function CreateForm() {return false;}
	public function ImportForm() {return false;}
}

//remove delete action
class StoreAdmin_RecordController extends ModelAdmin_RecordController {

	public function EditForm() {
		$form = parent::EditForm();
		$form->Actions()->removeByName('Delete');
		return $form;
	}
}
