<% if UseShippingAddress %>
	<% if ShippingAddress %>
		<% control ShippingAddress %>
<address class="addressSection" id="ShippingAddressSection">
	$ShippingFirstName $ShippingSurname<br />
	<% if ShippingAddress %>$ShippingAddress<br /><% end_if %>
	<% if ShippingAddress2 %>$ShippingAddress2<br /><% end_if %>
	<% if ShippingCity %>$ShippingCity<br /><% end_if %>
	<% if ShippingState %>$ShippingState<br /><% end_if %>
	<% if ShippingPostalCode %>$ShippingPostalCode<br /><% end_if %>
	<% if ShippingFullCountryName %>$ShippingFullCountryName<br /><% end_if %>
	<% if ShippingPhone %>$ShippingPhone<% end_if %>
</address>
		<% end_control %>
	<% else  %>
		<p>No shipping address available.</p>
	<% end_if %>
<% else %>
	<% if BillingAddressID %>
		<% control BillingAddress %>
<address class="addressSection" cellspacing="0" cellpadding="0" id="ShippingAddressSection">
	$FirstName $Surname<br />
	<% if Address %>$Address<br/><% end_if %>
	<% if Address2 %>$Address2<br /><% end_if %>
	<% if City %>$City<br /><% end_if %>
	<% if State %>$State<br /><% end_if %>
	<% if PostalCode %>$PostalCode<br /><% end_if %>
	<% if FullCountryName %>$FullCountryName<br /><% end_if %>
</address>
		<% end_control %>
	<% end_if %>
<% end_if %>
