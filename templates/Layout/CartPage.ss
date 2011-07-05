<h1 class="pagetitle">$Title</h1>
<% if Content %><div id="ContentHolder">$Content<% end_if %></div>

<div id="OrderHolder">
<% control Order %><% end_control %>
<% control Order %>
	<% if IsSubmitted %>
		<% include Order %>
	<% else %>
		<% include Order_Content_Editable %>
	<p id="ContinueLinks">
		<% if ContinuePage %><a class="continueLink button" href="$ContinuePage.Link"><% _t('Cart.CONTINUESHOPPING','continue shopping') %></a><% end_if %>
		<% if CheckoutPage %><a class="checkoutLink button" href="{$CheckoutPage.Link}/showorder/$Order.ID"><% _t('Cart.CHECKOUTGOTO','proceed to checkout') %></a><% end_if %>
	</p>
		
	<% end_if %>
<% end_control %>
</div>


<% require themedCSS(CartPage) %>
