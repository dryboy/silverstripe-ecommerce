<span class="ajaxAddToCartLink">
	<a class="ajaxBuyableRemove <% if IsInCart %>show<% else %>doNotShow<% end_if %>" href="$RemoveAllLink"><% _t('Cart.REMOVEFROMCART', 'Remove from Cart') %></a>
	<a class="ajaxBuyableAdd <% if IsInCart %>doNotShow<% else %>show<% end_if %>" href="$AddLink"><% _t('Cart.ADDTOCART', 'Add to Cart') %></a>
</span>
