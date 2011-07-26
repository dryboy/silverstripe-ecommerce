<% if Message %><p id="CartPageMessage" class="message">$Message</p><% end_if %>
<% if ActionLinks %>
	<ul id="ActionLinks">
		<% control ActionLinks %><li><a href="$Link">$Title</a></li><% end_control %>
	</ul>
<% end_if %>
