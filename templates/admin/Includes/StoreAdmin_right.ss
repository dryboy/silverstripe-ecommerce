<div id="ModelAdminPanel">

<% if EditForm %>
	$EditForm
<% else %>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">
		<div class="??">
			<h1>Product Administration</h1>
			<p>
				An approach to custom product administration  <br />
				
			</p>
			<div class="request">
				
			</div>

		</div>
	</form>
<% end_if %>

</div>

<p id="statusMessage" style="visibility:hidden"></p>
