/**
 *@author Nicolaas [at] sunnysideup.co.nz
 *@description: adds ajax functionality to page
 *we have three options:
 * * addLinks (click to add to cart)
 * * delete links (remove from cart)
 * * and remove from cart (cart is expected to be as <li>......<a href="">click to remove</a>, with a tag being a direct child of li, and li holding item
 **/



;(function($) {
	$(document).ready(
		function() {
			//This needs testing...
			EcomAjaxCart.init();
		}
	);
})(jQuery);

var EcomAjaxCart = {

	/**
	 * NOTE: set to empty string to bypass confirmation step
	 */
	confirmDeleteText: 'Are you sure you would like to remove this item from your cart?',
		set_confirmDeleteText: function(s) {this.confirmDeleteText = s;},

	/**
	 * class used to identify that the cart is loading
	 */
	loadingClass: "loading",
		set_loadingClass: function(s) {this.loadingClass = s;},

	/**
	 * the class used to show add/remove buyable buttons
	 */
	showClass: "show",
		set_showClass: function(s) {this.showClass = s;},

	/**
	 * the class used to hide add/remove buyable buttons
	 */
	hideClass: "hide",
		set_hideClass: function(s) {this.hideClass = s;},

	/**
	 * the area in which the ajax links can be found.
	 */
	ajaxLinksAreaSelector: "body",
		set_ajaxLinksAreaSelector: function(v) {this.ajaxLinksAreaSelector = v;},

	/**
	 * the selector used to identify links that add buyables to the cart
	 */
	addLinkSelector: ".ajaxBuyableAdd",
		set_addLinkSelector: function(s) {this.addLinkSelector = s;},

	/**
	 * the selector used to identify links that remove buyables from the cart
	 * (outside the cart itself)
	 */
	removeLinkSelector: ".ajaxBuyableRemove",
		set_removeLinkSelector: function(s) {this.removeLinkSelector = s;},

	/**
	 * the selector used to identify "remove from cart" links within the cart.
	 */
	removeCartSelector: ".ajaxRemoveFromCart",
		set_removeCartSelector: function(s) {this.removeCartSelector = s;},


	init: function(element) {
		jQuery(element).addAddLinks();
		jQuery(element).addRemoveLinks();
		jQuery(element).addCartRemove();
	},

	reloadCart: function( url, el ) {
		jQuery(el).addClass(EcomAjaxCart.loadingClass);
		var clickedElement = el;
		jQuery.get(
			url,
			{},
			function(data) {
				jQuery(EcomAjaxCart.cartHolderSelector).html(data);
				jQuery(clickedElement).removeClass(EcomAjaxCart.loadingClass);
				//hide the clicked element
				jQuery(clickedElement).addClass(EcomAjaxCart.hideClass).removeClass(EcomAjaxCart.showClass);
				//show the previous OR next element (lazy option)
				jQuery(clickedElement).next("."+EcomAjaxCart.hideClass).addClass(EcomAjaxCart.showClass).removeClass(EcomAjaxCart.hideClass);
				jQuery(clickedElement).prev("."+EcomAjaxCart.hideClass).addClass(EcomAjaxCart.showClass).removeClass(EcomAjaxCart.hideClass);
			}
		);
		return true;
	}

}


jQuery.fn.extend(
	{
		addAddLinks: function() {
			jQuery(this).find(EcomAjaxCart.addLinkSelector).live(
				"click",
				function(){
					var url = jQuery(this).attr("href");
					EcomAjaxCart.loadAjax(url, this);
					return false;
				}
			);
		},

		addCartRemove: function () {
			jQuery(this).find(EcomAjaxCart.removeCartSelector).live(
				"click",
				function(){
					if(EcomAjaxCart.UnconfirmedDelete || confirm(EcomAjaxCart.ConfirmDeleteText)) {
						var url = jQuery(this).attr("href");
						var el = this;//we need this to retain link to element (this shifts focus)
						jQuery(el).parent("li").css("text-decoration", "line-through");
						jQuery.get(url, function(){ jQuery(el).parent("li").fadeOut();});
					}
					return false;
				}
			);
		},

		addRemoveLinks: function () {
			jQuery(this).find(EcomAjaxCart.removeLinkSelector).live(
				"click",
				function(){
					if(EcomAjaxCart.UnconfirmedDelete || confirm(EcomAjaxCart.ConfirmDeleteText)) {
						var url = jQuery(this).attr("href");
						EcomAjaxCart.loadAjax(url, this);
					}
					return false;
				}
			);
		}

	}
);



