<% if Message %><p id="CartPageMessage" class="message">$Message</p><% end_if %>
<% if ActionLinks %>
	<ul id="ActionLinks">
		<% if Title %><% control ActionLinks %><li><a href="$Link">$Title</a></li><% end_control %><% end_if %>
	</ul>
<% end_if %>
