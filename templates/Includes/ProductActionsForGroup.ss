<div class="productActionsHolder">
<% if HasVariations %>
	<% if IsInCart %><span class="inCart">in cart</span><% end_if %>
	<a href="$Link/selectoptions/" class="selectOptions" rel="VariationsTable{$ID}">select options</a>
	<div class="variationsTableHolder" style="display: none;" id="VariationsTable{$ID}">% include VariationsTable %></div>
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

