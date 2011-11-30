/**
  *@description: update Cart using AJAX (JSON data source)
  **/

(function($){
	$(document).ready(
		function() {
			EcomCart.init();
		}
	);

})(jQuery);

EcomCart = {

	ajaxCountryFieldSelector: "select.ajaxCountryField",

	ajaxRegionFieldSelector: "select.ajaxRegionField",

	classToShowLoading: "loadingCartData",

	attachLoadingClassTo: "body",

	selectorShowOnZeroItems: ".showOnZeroItems",

	selectorHideOnZeroItems: ".hideOnZeroItems",

	selectorItemRows: "tr.orderitem",

	selectorChangeCountryLink: ".changeCountryLink",

	selectorChangeCountryFieldHolder: "#ChangeCountryHolder",

	selectorMainCountryField: "#Country",
		SET_selectorMainCountryField: function(s) {this.selectorMainCountryField = s;},

	init: function () {
		EcomCart.updateForZeroVSOneOrMoreRows();
		EcomCart.countryAndRegionUpdates();
		EcomCart.changeCountryFieldSwap();
	},

	countryAndRegionUpdates: function() {
		jQuery(EcomCart.ajaxCountryFieldSelector).live(
			"change",
			function() {
				var url = jQuery('base').attr('href') + "shoppingcart/setcountry/" + this.value + "/";
				EcomCart.getChanges(url, null);
			}
		);
		jQuery(EcomCart.ajaxRegionFieldSelector).live(
			"change",
			function() {
				var url = jQuery('base').attr('href') + "shoppingcart/setregion/" + this.value + "/";
				EcomCart.getChanges(url, null);
			}
		);
	},

	changeCountryFieldSwap: function() {
		jQuery(EcomCart.selectorChangeCountryFieldHolder).hide();
		jQuery(EcomCart.selectorChangeCountryLink).click(
			function(event) {
				if(jQuery(EcomCart.selectorChangeCountryFieldHolder).is(":hidden")) {
					var options = jQuery(EcomCart.ajaxCountryFieldSelector).html();
					var html = "<select>" + options + "</select>";
					jQuery(EcomCart.selectorChangeCountryFieldHolder).html(html).slideDown();
				}
				else {
					jQuery(EcomCart.selectorChangeCountryFieldHolder).slideUp(
						"slow",
						function() {
							jQuery(EcomCart.selectorChangeCountryFieldHolder).html("");
						}
					);
				}
				event.preventDefault();
			}
		);
		jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").live(
			"change",
			function() {
				var val = jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").val();
				jQuery(EcomCart.ajaxCountryFieldSelector).val(val);
				var url = jQuery('base').attr('href') + "shoppingcart/setcountry/" + val + "/";
				EcomCart.getChanges(url, null);
				jQuery(EcomCart.selectorChangeCountryLink).click();
			}
		);
	},

	//get JSON data from server
	getChanges: function(url, params) {
		jQuery(EcomCart.attachLoadingClassTo).addClass(EcomCart.classToShowLoading);
		jQuery.getJSON(url, params, EcomCart.setChanges);
	},

	//sets changes to Cart
	setChanges: function (changes) {
		EcomCart.updateForZeroVSOneOrMoreRows();
		for(var i in changes) {
			var change = changes[i];
			if(typeof(change.parameter) != 'undefined' && typeof(change.value) != 'undefined') {
				var parameter = change.parameter;
				var value = EcomCart.escapeHTML(change.value);
				//selector Types
				var id = change.id;
				var name = change.name;
				var className = change.className;
				var dropdownArray = change.dropdownArray;
				if(EcomCart.variableSetWithValue(id)) {
					var id = '#' + id;
					//hide or show row...
					if(parameter == "hide") {
						if(change.value) {
							jQuery(id).hide();
						}
						else {
							jQuery(id).show();
						}
					}
					//general message
					else if(EcomCart.variableSetWithValue(change.isOrderMessage)) {
						jQuery(id).html(value);
					}
					else if(parameter == 'innerHTML'){
						jQuery(id).html(value);
					}
					else{
						jQuery(id).attr(parameter, value);
					}
				}

				//used for form fields...
				else if(EcomCart.variableSetWithValue(name)) {
					jQuery('[name=' + name + ']').each(
						function() {
							jQuery(this).attr(parameter, value);
						}
					);
				}

				//used for class elements
				else if(EcomCart.variableSetWithValue(className)) {
					var className = '.' + className;
					jQuery(className).each(
						function() {
							jQuery(this).attr(parameter, value);
						}
					);
				}

				//used for dropdowns
				else if(EcomCart.variableSetWithValue(dropdownArray)) {
					var selector = '#' + dropdownArray+" select";
					if(jQuery(selector).length > 0){
						if(value.length > 0) {
							jQuery(selector).html("");
							for(var i = 0; i < value.length; i++) {
								if(parameter == value[i].id) {
									var selected = "selected=\"selected\" ";
								}
								else {
									var selected = "";
								}
								jQuery(selector).append("<option value=\""+value[i].id+"\""+selected+">"+value[i].name+"</option>");
							}
						}
					}
				}
			}
		}
		jQuery(EcomCart.attachLoadingClassTo).removeClass(EcomCart.classToShowLoading);
	},

	escapeHTML: function (str) {
		return str;
	},

	//if there are no items in the cart - then we hide the cart and we show a row saying: "nothing in cart"
	updateForZeroVSOneOrMoreRows: function() {
		if(EcomCart.CartHasItems()) {
			jQuery(EcomCart.selectorShowOnZeroItems).hide();
			jQuery(EcomCart.selectorHideOnZeroItems).each(
				function(i, el) {
					if(!jQuery(el).hasClass("hideForNow")) {
						jQuery(el).show();
					}
				}
			);
		}
		else {
			jQuery(EcomCart.selectorShowOnZeroItems).show();
			jQuery(EcomCart.selectorHideOnZeroItems).hide();
		}
	},

	//check if there are any items in the cart
	CartHasItems: function() {
		return jQuery(EcomCart.selectorItemRows).length > 0;
	},

	//check if a variable "isset"
	variableIsSet: function(variable) {
		if(typeof(variable) == 'undefined' || typeof variable == 'undefined' || variable == 'undefined') {
			return false;
		}
		return true;
	},

	//variables isset AND it has a value....
	variableSetWithValue: function(variable) {
		if(EcomCart.variableIsSet(variable)) {
			if(variable) {
				return true;
			}
		}
		return false;
	}

}


