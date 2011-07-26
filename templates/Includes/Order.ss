<div id="OrderInformation">

	<h2 class="orderHeading"><a href="$RetrieveLink">$Title</a></h2>

	<% include Order_Addresses %>

	<% include Order_Content %>

	<% include Order_Payments %>

	<% include Order_OutstandingTotal %>

	<% include Order_CustomerNote %>

	<% include Order_OrderStatusLogs %>
	
	<% is EmailLink %>
	<div id="SendCopyOfReceipt">
		<p>
			<a href="$EmailLink">
				<% sprintf(_t("OrderConfirmation.SENDCOPYRECEIPT","send a copy of receipt to %s"),$Member.Email) %>
			</a>
		</p>
	</div>
	<% end_if %>
	
	<% is PrintLink %>
	<div id="SendCopyOfReceipt">
		<p>
			<a href="$PrintLink">
				<% _t("OrderConfirmation.PRINTINVOICE","print invoice") %>
			</a>
		</p>
	</div>
	<% end_if %>

</div>


<% require themedCSS(Order) %>
<% require themedCSS(Order_Print, print) %>
