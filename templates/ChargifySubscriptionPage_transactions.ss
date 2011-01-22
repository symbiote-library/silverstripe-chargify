<% if Transactions %>
	<table id="chargify-transactions">
		<thead>
			<tr>
				<th>ID</th>
				<th>Date</th>
				<th>Type</th>
				<th>Amount</th>
				<th>Ending Balance</th>
				<th>Success?</th>
			</tr>
		</thead>
		<tbody>
			<% control Transactions %>
				<tr>
					<td>$ID</td>
					<td>$Date.Nice</td>
					<td>$Type</td>
					<td>$Amount.Nice</td>
					<td>$Balance.Nice</td>
					<td><% if Success %>Yes<% else %>No<% end_if %></td>
				</tr>
			<% end_control %>
		</tbody>
	</table>
<% else %>
	<p id="chargify-no-transactions" class="ui-state-highlight">No transactions found.</p>
<% end_if %>