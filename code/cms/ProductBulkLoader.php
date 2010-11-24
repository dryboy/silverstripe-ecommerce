<?php
/**
 * ProductBulkLoader - allows loading products via CSV file.
 * 
 * Images should be uploaded before import, where the Photo/Image field corresponds to the filename of a file that was uploaded.
 * 
 * Variations can be specified in a "Variation" column this format:
 * Type:value,value,value
 * eg: Color: red, green, blue , yellow
 * up to 6 other variation columns can be specified by adding a number to the end, eg Variation2,$Variation3
 * 
 */

class ProductBulkLoader extends CsvBulkLoader{
	
	static $parentpageid = null;
	static $createnewproductgroups = false;
	
	public $columnMap = array(
	
		'Category' => '->setParent',
		'ProductGroup' => '->setParent',
		
		'Product ID' => 'InternalItemID',
		'ProductID' => 'InternalItemID',
		'SKU' => 'InternalItemID',
		
		'Long Description' => 'Content',
		'Short Description' => 'MetaDescription',
		
		'Short Title' => 'MenuTitle',
		
		'Variation' => '->processVariation', //TODO: need this to work on multiple rows
		'Variation1' => '->processVariation1',
		'Variation2' => '->processVariation2',
		'Variation3' => '->processVariation3',
		'Variation4' => '->processVariation4',
		'Variation5' => '->processVariation5',
		'Variation6' => '->processVariation6'
	);
	
	
	
	public $duplicateChecks = array(
		'InternalItemID' => 'InternalItemID', // use internalItemID for duplicate checks
		'Title' => 'Title'
	);
	
	public $relationCallbacks = array(
		'Image' => array(
			'relationname' => 'Image', // relation accessor name
			'callback' => 'imageByFilename'
		),
		'Photo' => array(
			'relationname' => 'Image', // relation accessor name
			'callback' => 'imageByFilename'
		)
	);
	
	
	protected function processAll($filepath, $preview = false) {
		$results = parent::processAll($filepath, $preview);
			
		//After results have been processed, publish all created & updated products
		$objects = new DataObjectSet();
		$objects->merge($results->Created());
		$objects->merge($results->Updated());
		foreach($objects as $object){
			
			if(!$object->ParentID){
				 //set parent page
				
				if(is_numeric(self::$parentpageid) &&  DataObject::get_by_id('ProductGroup',self::$parentpageid)) //cached option
					$object->ParentID = self::$parentpageid;
				elseif($parentpage = DataObject::get_one('ProductGroup',"\"Title\" = 'Products'",'"Created" DESC')){ //page called 'Products'
					$object->ParentID = self::$parentpageid = $parentpage->ID;
				}elseif($parentpage = DataObject::get_one('ProductGroup',"\"ParentID\" = 0",'"Created" DESC')){ //root page
					$object->ParentID = self::$parentpageid = $parentpage->ID;
				}elseif($parentpage = DataObject::get_one('ProductGroup',"",'"Created" DESC')){ //any product page
					$object->ParentID = self::$parentpageid = $parentpage->ID;
				}else
					$object->ParentID = self::$parentpageid = 0;
			}
			
			$object->extend('updateImport'); //could be used for setting other attributes, such as stock level
			
			$object->writeToStage('Stage'); 
			$object->publish('Stage', 'Live');
		}

		return $results;
	}
	
	// set image, based on filename
	function imageByFilename(&$obj, $val, $record){
		
		$filename = strtolower(Convert::raw2sql($val));
		if($filename && $image = DataObject::get_one('Image',"LOWER(\"Filename\") LIKE '%$filename%'")){ //ignore case
			$image->ClassName = 'Product_Image'; //must be this type of image
			$image->write();
			return $image;
		}
		return null;
	}
	
	// find product group parent (ie Cateogry)	
	function setParent(&$obj, $val, $record){
		$title = strtolower(Convert::raw2sql($val));
		if($title){
			if($parentpage = DataObject::get_one('ProductGroup',"LOWER(\"Title\") = '$title'",'"Created" DESC')){ // find or create parent category, if provided
				$obj->ParentID = $parentpage->ID;
				$obj->write();
				$obj->writeToStage('Stage'); 
				$obj->publish('Stage', 'Live');
			}elseif(self::$createnewproductgroups){
				//create parent product group
				$pg = new ProductGroup();
				$pg->setTitle($title);
				$pg->ParentID = (self::$parentpageid) ? $parentpageid :0;
				$pg->writeToStage('Stage');
				$pg->publish('Stage', 'Live');
				
				$obj->ParentID = $pg->ID;
				$obj->write();
				$obj->writeToStage('Stage'); 
				$obj->publish('Stage', 'Live');
			}
		}
	}
	
	function processVariation(&$obj, $val, $record){
		$parts = explode(":",$val);
		if(count($parts) == 2){
			
			$attributetype = trim($parts[0]);
			$attributevalues = explode(",",$parts[1]);
			if(count($attributevalues) >= 1){
				
				$attributetype = ProductAttributeType::find_or_make($attributetype);
				foreach($attributevalues as $key => $value)
					$attributevalues[$key] = trim($value); //remove outside spaces from values
					
				$attributetype->addValues($attributevalues);
				
				$obj->VariationAttributes()->add($attributetype);
			}			
		}
		
	}
	
	//work around until I can figure out how to allow calling processVariation multiple times
	function processVariation1(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}
	function processVariation2(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}
	function processVariation3(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}
	function processVariation4(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}
	function processVariation5(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}
	function processVariation6(&$obj, $val, $record){
		$this->processVariation(&$obj, $val, $record);
	}	

	
}

?>