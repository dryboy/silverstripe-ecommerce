<div id="OrderConformationPage">
	<p id="OrderConformationPageMessage" class="message">$Message</p>
<% if RetrievedOrder %>
	<% control Order %>
		<% include Order %>
	<% end_control %>
	<div id="SendCopyOfReceipt"><p><a href="{$Link}sendreceipt/$CurrentOrder.ID/"><% sprintf(_t("OrderConfirmation.SENDCOPYRECEIPT","send a copy of receipt to %s"),$CurrentOrder.Member.Email) %></a></p></div>
	<div id="PaymentForm">$PaymentForm</div>
	<div id="CancelForm">$CancelForm</div>
<% else %>
	<% if AllMemberOrders %>
	<div id="PastOrders">
		<h3 class="formHeading"><% _t("OrderConfirmation.HISTORY","Your Order History") %></h3>
		<% control AllMemberOrders %>
		<h4>$Heading</h4>
		<ul>
			<% control Orders %><li><a href="$Link">$Title</a></li><% end_control %>
		</ul>
			<% end_control %>
	</div>
		<% else %>
	<p><% _t("OrderConfirmation.NOHISTORY","You dont have any saved orders.") %></p>
		<% end_if %>
	<% end_if %>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
</div>

