<span class="ajaxAddToCartLink">
	<a class="ajaxBuyableRemove <% if IsInCart %>show<% else %>doNotShow<% end_if %>" href="$RemoveLinkAjax"><% _t('Remove from Cart') %></a>
	<a class="ajaxBuyableAdd <% if IsInCart %>doNotShow<% else %>show<% end_if %>" href="$AddLinkAjax"><% _t('Add to Cart') %></a>
</span>
