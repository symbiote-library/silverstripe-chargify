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

	<table id="Products">
		<thead>
			<tr>
				<th class="name">Name</th>
				<th class="description">Description</th>
				<th class="price">Price</th>
				<th class="action"></th>
			</tr>
		</thead>
		<tbody>
			<% if Products %>
				<% control Products %>
					<tr <% if Active %>class="ui-state-highlight"<% end_if %>>
						<td class="name">$Name</td>
						<td class="description">$Description</td>
						<td class="price">
							$Price.Nice <span>every $Interval {$IntervalUnit}(s)</span><br>
							<% if InitialCharge %>$InitialCharge.Nice<% else %>No<% end_if %> <span>setup fee</span><br>
							<% if TrialInterval %>$TrialPrice.Nice <span>$TrialInterval {$TrialIntervalUnit}(s)<% else %>No <span><% end_if %> trial</span>
						</td>
						<td class="action">
							<% if Active %>
								<strong>Currently active</strong>
							<% else %>
								<a href="$ActionLink" class=button>$ActionTitle</a>
							<% end_if %>
						</td>
					</tr>
				<% end_control %>
			<% else %>
				<tr class="ui-state-highlight">
					<td colspan="4">There are no products available.</td>
				</tr>
			<% end_if %>
		</tbody>
	</table>

	<% if ChargifySubscription %>
		<div id="CancelSubscription">
			<p class="showCancelLink">
				<a href="#" class="showCancelLink cancel">Cancel Subscription</a>
			</p>
			<p class="cancelLink">
				<strong>Are you sure you wish to cancel your subscription?</strong>
				<a href="$CancelLink" class="cancelLink cancel">Yes, cancel my subscription.</a>
				<a href="#" class="changedMind">No, I've changed my mind.</a>
			</p>
		</div>
	<% end_if %>
</div>