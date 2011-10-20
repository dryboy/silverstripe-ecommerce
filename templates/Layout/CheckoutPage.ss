<div id="Checkout">

	<h1 class="pagetitle">$Title</h1>

	<% include CartActionsAndMessages %>

<% if CanCheckout %>

	<!-- step 1 OrderItems -->
	<% if CanShowStep(orderitems) %>
	<div id="OrderItemsOuter" class="orderStep">
		<% control Order %><% include Order_Content_Editable %><% end_control %>
		<% if HasOrderSteps %>
		<div class="orderStepPrevNextHolder next">
			<a href="{$Link}orderstep/ordermodifiers/#OrderModifiersOuter">continue</a>
		</div>
		<% end_if %>
	</div>
	<% end_if %>


	<!-- step 2 OrderModifiers -->
	<% if CanShowStep(ordermodifiers) %>
	<div id="OrderModifiersOuter" class="orderStep">
			<% if HasOrderSteps %>
		<div class="orderStepPrevNextHolder prev">
			<a href="{$Link}orderstep/orderitems/#OrderItemsOuter">go back</a>
		</div>
			<% end_if %>
		<% if ModifierForms %><% control ModifierForms %><div class="modifierFormInner">$Me</div><% end_control %><% end_if %>
			<% if HasOrderSteps %>
		<div class="orderStepPrevNextHolder next">
			<a href="{$Link}orderstep/orderconfirmation/#OrderConfirmationOuter">continue</a>
		</div>
	</div>
		<% end_if %>
	<% end_if %>

	<!-- step 3 OrderConfirmation -->
	<% if CanShowStep(orderconfirmation) %>
		<% if HasOrderSteps %>
	<div id="OrderConfirmationOuter" class="orderStep">
		<div class="orderStepPrevNextHolder prev">
			<a href="{$Link}orderstep/ordermodifiers/#OrderModifiersOuter">go back</a>
		</div>
		<% control Order %><% include Order_Content %><% end_control %>
		<div class="orderStepPrevNextHolder next">
			<a href="{$Link}orderstep/orderformandpayment/#OrderFormAndPaymentOuter">continue</a>
		</div>
	</div>
		<% end_if %>
	<% end_if %>

	<!-- step 4 OrderFormAndPayment -->
	<% if CanShowStep(orderformandpayment) %>
	<div id="OrderFormAndPaymentOuter" class="orderStep">
		<% if HasOrderSteps %>
		<div class="orderStepPrevNextHolder prev">
			<a href="{$Link}orderstep/orderconfirmation/#OrderConfirmationOuter">go back</a>
		</div>
		<% end_if %>
		$OrderForm
	</div>

	<% end_if %>

<% end_if %>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
</div>
