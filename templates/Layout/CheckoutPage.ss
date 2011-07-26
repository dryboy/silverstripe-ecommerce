<div id="Checkout">

	<h1 class="pagetitle">$Title</h1>

	<% include CartActionsAndMessages %>

<% if CanCheckout %>
	<% control Order %><% include Order_Content_Editable %><% end_control %>
	<% if ModifierForms %><div id="ModifierFormsOuter"><% control ModifierForms %><div class="modifierFormInner">$Me</div><% end_control %></div><% end_if %>
	<div id="OrderFormOuter">$OrderForm</div>
<% end_if %>
	<% if Content %><div id="ContentHolder">$Content<% end_if %></div>
</div>


