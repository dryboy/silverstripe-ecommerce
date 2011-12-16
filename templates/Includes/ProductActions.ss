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
		<li class=" <% if IsInCart %>show<% else %>hide<% end_if %>">
			<a class="ajaxBuyableRemove" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
		</li>
		<li class=" <% if IsInCart %>hide<% else %>show<% end_if %>">
			<a class="ajaxBuyableAdd" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
		</li>
		<li class=" <% if IsInCart %>show<% else %>hide<% end_if %>">
			<a href="$CheckoutLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></a>
		</li>
	</ul>
	<% end_if %>
<% end_if %>
