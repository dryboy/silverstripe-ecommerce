<div id="Sidebar">
	<% include Sidebar_Cart %>
	<% include Sidebar %>
</div>
<div id="Product">
	<h1 class="pageTitle">$Title</h1>
	<div class="productDetails">
		<div class="productImage">
<% if Image.ContentImage %>
			<img class="realImage" src="$Image.ContentImage.URL" alt="<% sprintf(_t("Product.IMAGE","%s image"),$Title) %>" />
<% end_if %>
		</div>
<% include ProductActions %>
	</div>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include OtherProductInfo %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>
</div>




