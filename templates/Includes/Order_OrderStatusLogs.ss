<% if CustomerViewableOrderStatusLogs %>
<table id="StatusLogs" class="infotable">
	<thead>
		<tr class="gap mainHeader">
			<th class="left" colspan="2" scope="col"><% _t("UPDATES","Updates") %></th>
		</tr>
	</thead>
	<tbody>
	<% control CustomerViewableOrderStatusLogs %>
		<tr>
			<th class="left" scope="row">$Title</th>
			<td class="left">$CustomerNote</td>
		</tr>
	<% end_control %>
	</tbody>
</table>
<% end_if %>

