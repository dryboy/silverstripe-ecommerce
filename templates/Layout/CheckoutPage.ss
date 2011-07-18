<div id="Checkout">

	<h1 class="pagetitle">$Title</h1>

	<% if Message %><div id="CheckoutMessage" class="message">$Message</div><% end_if %>
<% if ActionLinks %>
	<ul id="ActionLinks">
		<% control ActionLinks %><li><a href="$Link">$Title</a></li><% end_control %>
	</ul>
<% end_if %>

<% if CanCheckout %>
	<% control Order %><% include Order_Content_Editable %><% end_control %>
	<% if ModifierForms %><% control ModifierForms %>$Me<% end_control %><% end_if %>
	$OrderForm
<% else %>
	<div id="CanNotCheckOut"></div>
<% end_if %>
</div>
<% if Content %><div id="ContentHolder">$Content<% end_if %></div>


