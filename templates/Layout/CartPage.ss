<h1 class="pagetitle">$Title</h1>
<% if Content %><div id="ContentHolder">$Content<% end_if %></div>
<% if Message %>
	<p id="Message">$Message</p>
<% end_if %>
<div id="OrderHolder">
<% if Order %>
	<% if CanEditOrder %>
		<% control Order %><% include Order_Content_Editable %><% end_control %>
		<p id="ContinueLinks">
			<% if ContinuePage %><a class="continueLink button" href="$ContinuePage.Link"><% _t('Cart.CONTINUESHOPPING','continue shopping') %></a><% end_if %>
			<% if CheckoutPage %><a class="checkoutLink button" href="{$CheckoutPage.Link}/showorder/$Order.ID"><% _t('Cart.CHECKOUTGOTO','proceed to checkout') %></a><% end_if %>
		</p>
	<% else %>
		<% control Order %><% include Order %><% end_control %>
	<% end_if %>
<% else %>
	<p>Sorry, you can not view this order.</p>
<% end_if %>
</div>


<% require themedCSS(CartPage) %>
