<ul class="productActions">
	<li class=" <% if IsInCart %>show<% else %>hide<% end_if %>">
		<a class="ajaxBuyableRemove" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
	</li>
	<li class=" <% if IsInCart %>hide<% else %>show<% end_if %>">
		<a class="ajaxBuyableAdd" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
	</li>
	<li class=" <% if IsInCart %>show<% else %>hide<% end_if %>">
		<a class="goToCheckoutLink" href="$CheckoutLink"><% _t("Product.GOTOCHECKOUTLINK","Go to the checkout") %></a>
	</li>
</ul>
