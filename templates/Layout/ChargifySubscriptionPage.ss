<% require css(chargify/css/ChargifySubscriptionPage.css) %>
<% require css(sapphire/thirdparty/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css) %>
<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery-ui-1.8rc3.custom.js) %>
<% require javascript(chargify/javascript/ChargifySubscriptionPage.js) %>

<div id="Content" class="typography">
	<h2>$Title</h2>

	<% if ChargifySubscription %>
		<div id="PaymentDetails">
			<p id="NextBilling">
				Next billing date $NextBillingDate.Nice ($NextBillingDate.Ago).
				<a href="$Link(transactions)" class="chargifyDialog" title="Transaction History">Transaction history.</a>
			</p>
			<p id="CreditCard">
				Your credit card is on file.
				<a href="$Link(creditcard)" class="chargifyDialog" title="Credit Card Details">Show card details.</a>
				<a href="$UpdateBillingLink">Update Billing Details.</a>
			</p>
		</div>
	<% end_if %>
</div>