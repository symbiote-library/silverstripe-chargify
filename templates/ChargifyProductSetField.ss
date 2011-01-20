<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(chargify/javascript/ChargifyProductSetField.js) %>
<% require css(chargify/css/ChargifyProductSetField.css) %>

<div id="$ID" class="$CSSClasses field">
	<p class="chargifyLink">
		You can manage available subscription types at <a href="$ManageLink" target="_blank">Chargify</a>.
	</p>
	<h3>$Title</h3>
	<% control Products.GroupedBy(Family) %>
		<h4>$Family</h4>
		<table>
			<thead>
				<tr>
					<th class="active"></th>
					<th class="name">Name</th>
					<th class="price">Price</th>
					<th class="groups">Groups</th>
				</tr>
			</thead>
			<tbody>
				<% control Children %>
					<tr>
						<td class="active">$ActiveField.Field</td>
						<td class="name"><h4><a href="$ChargifyLink" target="_blank">$Name</a></h4></td>
						<td class="price">
							$Price.Nice <span>every $Interval {$IntervalUnit}(s)</span><br>
							<% if InitialCharge %>$InitialCharge.Nice<% else %>No<% end_if %> <span>setup fee</span><br>
							<% if TrialInterval %>$TrialPrice.Nice <span>$TrialInterval {$TrialIntervalUnit}(s)<% else %>No <span><% end_if %> trial</span>
						</td>
						<td class="groups">$Groups</td>
					</tr>
				<% end_control %>
			</tbody>
		</table>
	<% end_control %>
</div>