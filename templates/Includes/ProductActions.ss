<% if Variations %>
	$VariationForm
<% else %>
	<% if canPurchase %>
	<% if Price != 0 %><p class="priceDisplay">
		<% if HasDiscount %>
			<del>$Price.Nice</del> $CalculatedPrice.Nice
		<% else %>
			$CalculatedPrice.Nice
		<% end_if %>
		 $Currency $TaxInfo.PriceSuffix</p>
	<% end_if %>
	<ul class="productActions">
		<% if IsInCart %>
			<% control OrderItem %>
		<li><a href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a></li>
		<li><a href="$CheckoutLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></a></li>
			<% end_control %>
		<% else %>
		<li><a href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a></li>
		<% end_if %>
	</ul>
	<% end_if %>
<% end_if %>
