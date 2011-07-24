<div id="OrderInformation">

	<h2 class="orderHeading"><a href="$RetrieveLink">$Title</a></h2>

	<% include Order_Addresses %>

	<% include Order_Content %>

	<% include Order_Payments %>

	<% include Order_OutstandingTotal %>

	<% include Order_CustomerNote %>

	<% include Order_OrderStatusLogs %>


</div>


<% require themedCSS(Order) %>
<% require themedCSS(Order_Print, print) %>
