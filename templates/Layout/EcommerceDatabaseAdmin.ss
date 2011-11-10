<p><a href="$Link(clearoldcarts)">Cleanup old carts</a> - removes abandoned carts</p>
<p><a href="{$BaseHref}shoppingcart/clear">Clear the current shopping cart</a> - useful for testing purposes</p>
<p><a href="{$BaseHref}shoppingcart/debug">Debug the shopping cart</a></p>
<!--  <p>Load test products and categories</p>  -->

<h3>Delete actions - be careful</h3>
<p><a href="$Link(deleteproducts)">Delete all products</a></p>
<!--  <p>Delete all product categories</p> -->
<!--  <p>Delete all sales data (Orders, Items, Members, Addresses, etc)</p> -->

<h3>Migrate actions - be careful</h3>
<p><a href="$Link(updateproductgroups)">Reset product groups to show default level of products</a> </p>
<p><a href="$Link(setfixedpriceforsubmittedorderitems)">Set fixed price for submitted order items (that currently do not have a fixed price) - <strong>you may need to run this several times!</strong> </a> </p>

<h3>Ecommerce Unit Tests</h3>
<p><a href="{$BaseHref}dev/tests/$AllTests">Run all ecommerce unit tests</a></p>


<p>Individual tests:</p>
<% if Tests %>
<ul>
<% control Tests %>
	<li><a href="{$BaseHref}dev/tests/$Class">$Name</a></li>
<% end_control %>
</ul>
<% end_if %>
