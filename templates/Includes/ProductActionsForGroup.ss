<div class="productActionsHolder">
<% if HasVariations %>
	<a href="{$AddVariationsLink}" class="selectVariation" rel="VariationsTable{$ID}">
		<% if VariationIsInCart %>
			<% _t("Product.REMOVELINK","Remove from cart") %>
		<% else %>
			<% _t("Product.ADDLINK","Add to cart") %>
		<% end_if %>
	</a>

<% else %>
	<% if canPurchase %>
	<% if Price != 0 %>
		<p class="priceDisplay">
		<% if HasDiscount %>
			<del>$Price.Nice</del> $CalculatedPrice.Nice
		<% else %>
			$CalculatedPrice.Nice
		<% end_if %>
			<% if Currency %><span class="currencyQuantifier">$Currency</span><% end_if %>
			<% if TaxInfo.PriceSuffix %><span class="taxQuantifier">$TaxInfo.PriceSuffix</span><% end_if %>
			<% if Quantifier %><span class="mainQuantifier">$Quantifier</span><% end_if %>
		</p>
	<% end_if %>
	<% include ProductActionsInner %>
	<% end_if %>
<% end_if %>
</div>

