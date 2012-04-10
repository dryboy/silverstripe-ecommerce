<ul class="productActions">
	<li class=" <% if IsInCart %>show<% else %>hide<% end_if %>">
		<a class="ajaxBuyableRemove action" href="$RemoveAllLink"><% _t("Product.REMOVELINK","Remove from cart") %></a>
	</li>
	<li class=" <% if IsInCart %>hide<% else %>show<% end_if %>">
		<a class="ajaxBuyableAdd action" href="$AddLink"><% _t("Product.ADDLINK","Add to cart") %></a>
	</li>
</ul>
