<% require css(chargify/css/ChargifySubscriptionPage.css) %>
<% require css(sapphire/thirdparty/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css) %>
<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery-ui-1.8rc3.custom.js) %>
<% require javascript(chargify/javascript/ChargifySubscriptionPage.js) %>

<div id="Content" class="typography">
	<h2>$Title</h2>

	<% if ChargifySubscription %>
		<div id="chargify-billing-details">
			<p id="chargify-next-billing">
				<% if HasActiveSubscription %>
					Next billing date $NextBillingDate.Nice ($NextBillingDate.Ago).
				<% end_if %>
				<a href="$Link(transactions)" class="chargify-dialog" title="Transaction History">
					Transaction history.
				</a>
			</p>
			<p id="chargify-credit-card">
				Your credit card is on file.
				<a href="$Link(creditcard)" class="chargify-dialog" title="Credit Card Details">
					Show card details.
				</a>
				<a href="$UpdateBillingLink" class="chargify-update-billing" target="_blank">
					Update Billing Details.
				</a>
			</p>
		</div>
	<% end_if %>

	<% if Message %>
		<p class="chargify-note ui-state-highlight ui-corner-all">$Message</p>
	<% end_if %>

	<% if HasActiveSubscription %>
	<% else %>
		<% if ChargifySubscription %>
			<p class="chargify-note ui-state-highlight ui-corner-all">
				Your subscription is currently canceled or suspended. You can
				re-activate it below.
			</p>
		<% end_if %>
	<% end_if %>

	<table id="chargify-products">
		<thead>
			<tr>
				<th class="chargify-products-name">Name</th>
				<th class="chargify-products-description">Description</th>
				<th class="chargify-products-price">Price</th>
				<th class="chargify-products-action"></th>
			</tr>
		</thead>
		<tbody>
			<% if Products %>
				<% control Products %>
					<tr class="<% if Active %>ui-state-highlight<% end_if %>">
						<td class="chargify-products-name">$Name</td>
						<td class="chargify-products-description">$Description</td>
						<td class="chargify-products-price">
							$Price.Nice <span>every $Interval {$IntervalUnit}(s)</span><br>
							<% if InitialCharge %>$InitialCharge.Nice<% else %>No<% end_if %> <span>setup fee</span><br>
							<% if TrialInterval %>$TrialPrice.Nice <span>$TrialInterval {$TrialIntervalUnit}(s)<% else %>No <span><% end_if %> trial</span>
						</td>
						<td class="chargify-products-action">
							<% if Active %>
								<strong>Currently active</strong>
							<% else_if ActionLink %>
								<a href="$ActionLink" class="chargify-button <% if ActionConfirm %>chargify-confirm<% end_if %> ui-state-default ui-corner-all">
									$ActionTitle
								</a>
							<% end_if %>
						</td>
					</tr>
				<% end_control %>
			<% else %>
				<tr class="ui-state-highlight">
					<td class="chargify-no-products" colspan="4">There are no products available.</td>
				</tr>
			<% end_if %>
		</tbody>
	</table>

	<% if HasActiveSubscription %>
		<div id="chargify-cancel">
			<p id="chargify-show-cancel">
				<a href="#" id="chargify-show-cancel-link" class="chargify-button ui-state-error ui-corner-all">Cancel Subscription</a>
			</p>
			<p id="chargify-do-cancel">
				<strong>Are you sure you wish to cancel your subscription?</strong>
				<a href="$CancelLink" id="chargify-do-cancel-link" class="chargify-button ui-state-error ui-corner-all">Yes, cancel my subscription.</a>
				<a href="#" id="chargify-cancel-changed-mind">No, I've changed my mind.</a>
			</p>
		</div>
	<% end_if %>
</div>