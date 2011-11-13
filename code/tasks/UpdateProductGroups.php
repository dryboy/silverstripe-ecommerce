<?php

class UpdateProductGroups extends BuildTask{

	protected $title = "Update Product Groups";

	protected $description = "Sets the product groups 'show products' to the default.";

	function run($request){
		DB::query("UPDATE ProductGroup SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]);
		DB::query("UPDATE ProductGroup_Live SET \"LevelOfProductsToShow\" = ".ProductGroup::$defaults["LevelOfProductsToShow"]);
		DB::alteration_message("resetting product 'show' levels", "created");
	}

}
