
<% if DataCleanups %>
<h3>Debug</h3>
<ul>
<% control DataCleanups %>
	<li><a href="$Link">$Title</a>: $Description</li>
<% end_control %>
</ul>
<% end_if %>

<% if Migrations %>
<h3>Migration</h3>
<ul>
	<li><a href="{$BaseHref}dev/runallmigrations"><strong>Run all migration tasks</strong></a></li>
<% control Migrations %>
	<li><a href="$Link">$Title</a>: $Description</li>
<% end_control %>
</ul>
<% end_if %>

<% if Tests %>
<h3>Ecommerce Unit Tests</h3>
<ul>
	<li><a href="{$BaseHref}dev/tests/$AllTests"><strong>Run all ecommerce unit tests</strong></a></li>
<% control Tests %>
	<li><a href="{$BaseHref}dev/tests/$Class">$Name</a></li>
<% end_control %>
</ul>
<% end_if %>
