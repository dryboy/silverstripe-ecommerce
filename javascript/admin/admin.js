(function($) {
	$(function () {

		var requestHolder = $("#Form_EditForm .request");
		
		requestHolder.html('<div style="text-align:center;margin-top:120px;margin-bottom:120px;"><img src="/ecommerce/images/admin/ajax-loader-big.gif" /></div>');

		$.get("/StoreAdmin_Request/products", function(data){
			requestHolder.html(data);
		});			
		

	});
})(jQuery);