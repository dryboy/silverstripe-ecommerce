<?php

/**
 *@description: adds a few parameters for e-commerce to the SiteConfig.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package ecommerce
 * @sub-package integration
 **/

class SiteConfigEcommerceExtras extends DataObjectDecorator {

	function extraStatics(){
		return array(
			'db' => array(
				"ShopClosed" => "Boolean",
				"ShopPricesAreTaxExclusive" => "Boolean",
				"ShopPhysicalAddress" => "HTMLText",
				"ReceiptEmail" => "Varchar(255)",
				"ReceiptSubject" => "Varchar(255)",
				"DispatchEmailSubject" => "Varchar(255)",
				"PostalCodeURL" => "Varchar(255)",
				"PostalCodeLabel" => "Varchar(255)",
				"NumberOfProductsPerPage" => "Int",
				"OnlyShowProductsThatCanBePurchased" => "Int",
				"ProductsHaveWeight" => "Boolean",
				"ProductsHaveModelNames" => "Boolean",
				"ProductsHaveQuantifiers" => "Boolean",
				"ProductsAlsoInOtherGroups" => "Boolean"
			),
			'has_one' => array(
				"EmailLogo" => "Image",
				"DefaultProductImage" => "Image"
			),
			'defaults' =>array(
				'ShopClosed' => false
			)
		);
	}


	function updateCMSFields(FieldSet &$fields) {
		//new section
		$shoptabs = new TabSet('Shop',
			new Tab('General',
				new CheckboxField("ShopClosed", _t("SiteConfigEcommerceExtras.SHOPCLOSED", "Shop Closed"))
			),
			new Tab('Pricing',
				new CheckboxField("ShopPricesAreTaxExclusive", _t("SiteConfigEcommerceExtras.SHOPPRICESARETAXEXCLUSIVE", "Shop prices are tax exclusive (if this option is not ticked, it is assumed that prices are inclusive of tax)"))
			),
			new Tab('ProductDisplay',
				new NumericField("NumberOfProductsPerPage", _t("SiteConfigEcommerceExtras.NUMBEROFPRODUCTSPERPAGE", "Numer of products per page")),
				new CheckboxField("OnlyShowProductsThatCanBePurchased", _t("SiteConfigEcommerceExtras.ONLYSHOWPRODUCTSTHATCANBEPURCHASED", "Only show products that can be purchased")),
				new CheckboxField("ProductsHaveWeight",  _t("SiteConfigEcommerceExtras.PRODUCTSHAVEWEIGHT", "Products have weight (e.g. 1.2kg) - untick to hide weight field")),
				new CheckboxField("ProductsHaveModelNames", _t("SiteConfigEcommerceExtras.PRODUCTSHAVEMODELNAMES", "Products have model names / numbers -  untick to hide model field")),
				new CheckboxField("ProductsHaveQuantifiers", _t("SiteConfigEcommerceExtras.PRODUCTSHAVEQUANTIFIERS", "Products have quantifiers (e.g. per year, each, per dozen, etc...) - untick to hide model field")),
				new CheckboxField("ProductsAlsoInOtherGroups", _t("SiteConfigEcommerceExtras.PRODUCTSALSOINOTHERGROUPS", "Allow products to show in multiple product groups.")),
				new ImageField("DefaultProductImage", _t("SiteConfigEcommerceExtras.DEFAULTPRODUCTIMAGE", "Default Product Image"), null, null, null, "default-product-image")
			),
			new Tab('Checkout',
				new TextField("PostalCodeURL", _t("SiteConfigEcommerceExtras.POSTALCODEURL", "Postal code link"))
			),
			new Tab('Emails',
				new EmailField("ReceiptEmail", _t("SiteConfigEcommerceExtras.RECEIPTEMAIL", "From email address for shop receipt (e.g. sales@myshop.com)")),
				new TextField("ReceiptSubject", _t("SiteConfigEcommerceExtras.RECEIPTSUBJECT", "Subject for shop receipt email ('{OrderNumber}' will be replaced with actual order number - e.g. 'thank you for your order (#{OrderNumber})');")),
				new TextField("DispatchEmailSubject", _t("SiteConfigEcommerceExtras.DISPATCHEMAILSUBJECT", "Default subject for dispatch email (e.g. your order has been sent)")),
				new ImageField("EmailLogo", _t("SiteConfigEcommerceExtras.EMAILLOGO", "Email Logo"),  null, null, null, "logos")
			),
			new Tab('Legal',
				new HTMLEditorField("ShopPhysicalAddress", _t("SiteConfigEcommerceExtras.DEFAULTPRODUCTIMAGE", "Shop physical address"), 5,5)
			),
			new Tab('Process',
				new ComplexTableField($this->owner, "OrderSteps", "OrderStep")
			)
			/*$processtab = new Tab('OrderProcess',
				new LiteralField('op','Include a drag-and-drop interface for customising order steps (Like WidgetArea)')
			)*/
		);
		$fields->addFieldToTab('Root',$shoptabs);
		return $fields;
	}

}
