/**
	* @description:
	* This class provides extra functionality for the
	* Product and ProductGroup Page.
	* @author nicolaas @ sunny side up . co . nz
	**/

(function($){
	$(document).ready(
		function() {
			EcomProducts.init();
		}
	);
})(jQuery);

EcomProducts = {

	selectVariationSelector: 					'a.select',
	maskSelector: 										'#SelectVariationMask',
	selectVariationHolderSelector: 		'.selectVariation',

	init: function(){
		//select all the a tag with name equal to modal
		jQuery(EcomProducts.selectVariationSelector).click(function(e) {
			//Cancel the link behavior
			e.preventDefault();
			//Get the A tag
			var id = jQuery(this).attr('href');
			if(jQuery(EcomProducts.maskSelector).length == 0) {
				jQuery("body").append('<div id="'+EcomProducts.maskSelector+'"></div>');
			}
			//Get the screen height and width
			var maskHeight = jQuery(document).height();
			var maskWidth = jQuery(window).width();

			//Set height and width to mask to fill up the whole screen
			jQuery(EcomProducts.maskSelector).css({'width':maskWidth,'height':maskHeight});

			//transition effect
			//jQuery(EcomProducts.maskSelector).fadeIn(1000);
			jQuery(EcomProducts.maskSelector).fadeTo(
				"slow",
				0.8,
				function(){
					//Get the window height and width
					var winH = jQuery(window).height();
					var winW = jQuery(window).width();
					//Set the popup window to center
					jQuery(id).css('top',  winH/2-jQuery(id).height()/2);
					jQuery(id).css('left', winW/2-jQuery(id).width()/2);
					//transition effect
					jQuery(id).fadeIn("slow");
				}
			);
		});

		//if close button is clicked
		jQuery(EcomProducts.selectVariationHolderSelector+' a').click(function (e) {
				//do NOT Cancel the link behavior
				//e.preventDefault();
				jQuery(EcomProducts.maskSelector+', ' + EcomProducts.selectVariationHolderSelector).hide();
				return true;
		});

		//if mask is clicked
		jQuery(EcomProducts.maskSelector).click(function () {
			jQuery(this).hide();
			jQuery(EcomProducts.selectVariationHolderSelector).hide();
		});
	}

}



