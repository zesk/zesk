<?php
/**
 * @version $URL$
 * @package zesk
 * @deprecated
 * @subpackage commerce
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:09 PM
 */
namespace zesk;

/* @var $this zesk\Template */
$this->from = "ConversionRuler Billing Task <noreply@conversionruler.com>";
$this->to = "ConversionRuler Billing <kent@marketacumen.com>";
$this->subject = "Billing Task Run on {view:DateTime(Value='{TaskStartDateTime}',Format='{long}')}";

$this->content_type = "text/html";
?>
	{control:Template(Name=AdminMailCSS)}
<h1>Billing run on {DomainPrefix}</h1>
<h2>Summary</h2>
<table>
	<tr>
		<td class="input-label">Started on</td>
		<td>{view:DateTime(Value='{TaskStartDateTime}',Format='{long}')}</td>
	</tr>
	<tr>
		<td class="input-label">Ended on</td>
		<td>{view:DateTime(Value='{TaskEndDateTime}',Format='{long}')}</td>
	</tr>
	<tr>
		<td class="input-label">Earliest Unprocessed Batch</td>
		<td>{view:DateTime(Value='{EarlyUnprocessedDate}',Format='{long}<br />{delta}')}
		</td>
	</tr>
	<tr>
		<td class="input-label">Earliest Next Bill Date</td>
		<td>{view:DateTime(Value='{EarlyNextBillDate}',Format='{long}<br />{delta}')}
		</td>
	</tr>
	<tr>
		<td class="input-label">New Accounts</td>
		<td>{AccountActivatedCount}</td>
	</tr>
	<tr>
		<td class="input-label">Accounts with no Activation Date</td>
		<td>{AccountNotActivatedCount}</td>
	</tr>
	<tr>
		<td class="input-label">Invalid Payments</td>
		<td>{InvalidPaymentCount}</td>
	</tr>
	<tr>
		<td class="input-label">Updated Payments</td>
		<td>{InvalidPaymentFoundCount}</td>
	</tr>
	<tr>
		<td class="input-label">Newly Expired Payments</td>
		<td>{NewExpiredCount}</td>
	</tr>
	<tr>
		<td class="input-label">Invoices already paid/to pay</td>
		<td>{InvoicePaidCount}</td>
	</tr>
	<!-- CheckAccounts { -->
	<tr>
		<td class="input-label">Accounts to check</td>
		<td>{CheckAccountCount}</td>
	</tr>
	<!-- } -->
	<!-- PendingBatches { -->
	<tr>
		<td class="input-label">Pending Batches</td>
		<td>{PendingBatchCount}</td>
	</tr>
	<!-- } -->
</table>

<h1>Payment Batches</h1>
<!-- GenerateBatch { -->
<h2>New Batches Generated</h2>
<table>
	<tr>
		<td class="input-label">Billing through until</td>
		<td>{view:DateTime(Value='{BillingEndDateTime}',Format='{long}')}</td>
	</tr>
	<tr>
		<td class="input-label">Invoices billed by Payment Account</td>
		<td>{NotBilledCount}</td>
	</tr>
	<tr>
		<td class="input-label">Batches Generated</td>
		<td>{BatchesGeneratedCount}</td>
	</tr>
</table>

<ul>
	<!-- BatchGenerated { -->
	<li><a href="{DomainPrefix}{action($Batch,view)}">{Batch.CodeName}</a>
		bill date on {view:Date(Value='{Batch.EffectiveDate}',format="{short}
		(<strong>{delta}</strong>)")}</li>
	<!-- } else { -->
	<li>No batches were generated.</li>
	<!-- } -->
</ul>
<!-- } -->

<!-- PendingBatches { -->
<h2>Pending Batches</h2>
<p>The following payment batches are waiting to be be submitted:</p>
<ul>
	<!-- PendingBatch { -->
	<li>
		<!-- action($PendingBatch,process) { --> <a
		href="{DomainPrefix}/popup{Action.URI}">{PendingBatch.CodeName}</a> <!-- } else { -->
		{PendingBatch.CodeName} <!-- } --> <!-- action($PendingBatch,edit) { -->
		(<a href="{DomainPrefix}/popup{Action.URI}">Edit</a>) <!-- } --> on
		effective date {PendingBatch.EffectiveDate} for ${total} <!-- RecomputeData { -->
		<ul>
			<li class="error">This batch was recomputed. <!-- RecomputeData { -->
				<blockquote>
					<pre>{RecomputeData}</pre>
				</blockquote> <!-- } -->
			</li>
		</ul> <!-- } --> <!-- WillRecompute { -->
		<ul>
			<li class="error">This batch will be recomputed once the first batch
				has been submitted.</li>
		</ul> <!-- } -->
	</li>
	<!-- } -->
</ul>
<!-- } -->

<!-- RecomputeData { -->
<h2>Batch Recomputed</h2>
<pre>
			{RecomputeData}
		</pre>
<!-- } -->
<!-- EarlyBatch { -->
<p class="error">Accounts were found with bill dates prior to the
	earliest batch date. A new batch has been generated.</p>
<!-- } -->

<h1>Account Summary</h1>
<h2>Check accounts</h2>
<!-- CheckAccounts { -->
The following accounts need to be verified prior to billing. Please
click on each account and update the &quot;Checked&quot; field.
<ul>
	<!-- CheckAccount { -->
	<li><a
		href="{DomainPrefix}/popup{action($CheckAccount,edit)}?Ref=*close">{CheckAccount.Name}</a>
		({CheckAccount.ID}) needs to be checked.
		<ul>
			<li>Created Date: {CheckAccount.Created}</li>
			<li>Referrer: {CheckAccount.Referrer}</li>
			<li>BillString: {CheckAccount.BillString}</li>
			<li>Sites
				<ul>
					<!-- CheckAccount.Sites { -->
					<li><a
						href="{DomainPrefix}/popup{action($CheckAccount.Sites,edit)}?Ref=*close">{CheckAccount.Sites.Name}</a>
					</li>
					<li>Activated: {CheckAccount.Sites.Activated}</li>
					<li>Deactivated: {CheckAccount.Sites.Deactivated}</li>
					<!-- } -->
				</ul>
			</li>
		</ul> <!-- } -->

</ul>
<!-- } else { -->
<p>All accounts have been checked for accuracy.</p>
<!-- } -->

<h2>Accounts activated</h2>
<p>The following account were inactive and are now paying accounts.</p>
<ul>
	<!-- AccountActivated { -->
	<li>
		<!-- action($AccountActivated,edit) { --> <a
		href="{DomainPrefix}/popup{Action.URI}">{AccountActivated.Name}</a>
		next bill date on {AccountActivated.NextBillDate}
	</li>
	<!-- } -->
	<!-- } else { -->
	<li>No new accounts have been activated.</li>
	<!-- } -->
</ul>

<h2>Accounts de-activated</h2>
<p>The following accounts were active but were cancelled because of
	inactivity.</p>
<ul>
	<!-- AccountDeactivated { -->
	<!-- action($AccountDeactivated,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}?Ref=*close">{AccountDeactivated.Name}</a>
		next bill date on {AccountDeactivated.NextBillDate}</li>
	<!-- } -->
	<!-- } else { -->
	<li>No accounts were deactivated.</li>
	<!-- } -->
</ul>

<h2>Accounts Disabled</h2>
<p>The following accounts were disabled because of credit card declined
	or expiration.</p>
<ul>
	<!-- AccountDisabled { -->
	<!-- action($AccountDisabled,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{AccountDisabled.Name}</a>
		next bill date on {AccountDisabled.NextBillDate}</li>
	<!-- } -->
	<!-- } else { -->
	<li>No accounts were deactivated.</li>
	<!-- } -->
</ul>

<h2>Non-billed Accounts</h2>
<!-- ifblock(NonBilledAccount) { -->
The following accounts are billable, not referrers, and do not have a
payment associated with them:
<ul>
	<!-- NonBilledAccount { -->
	<!-- action($NonBilledAccount,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}?Ref=*close">{NonBilledAccount.Name}</a>
		({NonBilledAccount.ID}) has no payment.
		<ul>
			<li>Created Date: {NonBilledAccount.Created}</li>
		</ul></li>
	<!-- } -->
	<!-- } -->
</ul>
<!-- } else { -->
<p>All accounts are being billed correctly.</p>
<!-- } -->

<h2>No Activation Date</h2>
<p>The following account are active, but no activation date is set.</p>
<ul>
	<!-- AccountNotActivated { -->
	<!-- action($AccountNotActivated,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{AccountNotActivated.Name}</a>
		next bill date on {AccountNotActivated.NextBillDate}</li>
	<!-- } -->
	<!-- } else { -->
	<li>All accounts which are billable have activation dates.</li>
	<!-- } -->
</ul>

<h2>Accounts with no bill date</h2>
<!-- AccountNoBillDate { -->
<!-- action($AccountNoBillDate,edit) { -->
<li><a href="{DomainPrefix}/popup{Action.URI}">{AccountNoBillDate.Name}</a>
	was skipped because it has no next bill date.</li>
<!-- } -->
<!-- } else { -->
<p>No accounts were skipped in the batch generation.</p>
<!-- } -->

<h2>Accounts with no valid payment</h2>
<p>Thesse accounts have been disabled and will be asked for new payment
	information.</p>
<!-- AccountNoPayment { -->
<!-- action($AccountNoPayment,edit) { -->
<li><a href="{DomainPrefix}/popup{Action.URI}">{AccountNoPayment.Name}</a>
	generated an invoice, but no valid payment exists.</li>
<!-- } -->
<!-- } else { -->
<p>All billed accounts had valid payments.</p>
<!-- } -->

<h2>Accounts which were billed recently (after {BillInterval}) - skipped</h2>
<ul>
	<!-- AccountBilledRecently { -->
	<!-- action($AccountBilledRecently,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{AccountBilledRecently.Name}</a>
		was skipped because it was billed on {RecentBillDate}.</li>
	<!-- } -->
	<!-- } else { -->
	<li>No accounts skipped because of recent billing. (When caught-up,
		this should be empty always)</li>
	<!-- } -->
</ul>
<h2>Unbilled Accounts</h2>
<ul>
	<!-- NotBilled { -->
	<!-- action($NotBilledAccount,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{NotBilledAccount.Name}</a></li>
	<!-- } -->
	<!-- } else { -->
	<li>All accounts were billed within this batch.</li>
	<!-- } -->
</ul>

<h1>Payment Details</h1>
<h2>Updated Payments</h2>
<p>The following payments are for billable accounts (those with a site
	which is active). Payment information has been updated recently.</p>
<ul>
	<!-- InvalidPaymentFound { -->
	<!-- action($InvalidPaymentAccount,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{InvalidPaymentAccount.Name}</a>
		next bill date on {InvalidPaymentAccount.NextBillDate}</li>
	<!-- } -->
	<!-- } else { -->
	<li>No new payments have been updated.</li>
	<!-- } -->
</ul>

<h2>Invalid Payments</h2>
<p>The following payments are for billable accounts (those with a site
	which is active). Payment information needs to be updated to continue
	billing this account.</p>
<p>Some accounts may not have a bill date, in which case they need to
	have their Next Bill date set correctly, and their payment information
	updated, or the site should be deactivated.</p>
<ul>
	<!-- InvalidPayment { -->
	<!-- action($InvalidPaymentAccount,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{InvalidPaymentAccount.Name}</a>
		next bill date on {InvalidPaymentAccount.NextBillDate}</li>
	<!-- } -->
	<!-- } else { -->
	<li>All payments of billable accounts in the system are currently
		valid.</li>
	<!-- } -->
</ul>
<h1>Invoice Details</h1>
<h2>Unpaid Invoices</h2>
<p>The following invoices are outstanding: (Maximum {limit} are listed
	here)</p>
<ul>
	<!-- UnpaidInvoice { -->
	<li>
		<!-- action($UnpaidInvoice,view) { --> <a
		href="{DomainPrefix}/popup{Action.URI}">{UnpaidInvoice.CodeName}</a> <!-- } -->
		for <!-- action($UnpaidInvoice.Account,edit) { --> <a
		href="{DomainPrefix}/popup{Action.URI}">{UnpaidInvoice.Account.Name}</a>
		<!-- } --> paid by <!-- action($UnpaidInvoice.PaymentAccount,edit) { -->
		<a href="{DomainPrefix}/popup{Action.URI}">{UnpaidInvoice.PaymentAccount.Name}</a>
	</li>
	<!-- } -->
	<!-- } -->
	<li>
		<!-- action('invoice','list') { --> <!-- TODO --> <a
		href="{DomainPrefix}/popup{Action.URI}">List all unpaid invoices...</a>
		<!-- } -->
	</li>
</ul>

<h2>Invoices to notify</h2>
<p>The following invoices were found in the system which have never been
	sent to the account credit card holder:</p>
<ul>
	<!-- NotifyInvoice { -->
	<li>
		<!-- action($NotifyInvoice,notify) { --> <strong><a
			href="{DomainPrefix}/popup{Action.URI}">Notify</a>:</strong> <!-- } -->
		<a href="{DomainPrefix}/popup{action($NotifyInvoice,view)}">{NotifyInvoice.CodeName}</a>
		for <a
		href="{DomainPrefix}/popup{action($NotifyInvoice.Account,edit)}">{NotifyInvoice.Account.Name}</a>
		paid by <a
		href="{DomainPrefix}/popup{action($NotifyInvoice.PaymentAccount,edit)}">{NotifyInvoice.PaymentAccount.Name}</a>
		<ul>
			<li>Invoice Date: {NotifyInvoice.BillDate}</li>
			<li>Total: {NotifyInvoice.Total}</li>
			<li>Amount Due: {NotifyInvoice.AmountDueNew}</li>
		</ul>
	</li>
	<!-- } -->
</ul>
<h2>Invoices Generated</h2>
<p>The following invoices were generated during this billing run.</p>
<ul>
	<!-- Generated { -->
	<!-- action($GeneratedAccount,edit) { -->
	<li><a href="{DomainPrefix}/popup{Action.URI}">{GeneratedAccount.Name}</a></li>
	<!-- } -->
	<!-- GenerateError { -->
	<li class="error">An error occurred generating this invoice: {message}</li>
	<!-- } else { -->
	<li>
		<ul>
			<!-- action($GeneratedInvoice,view) { -->
			<li><a href="{DomainPrefix}/popup{Action.URI}">{GeneratedInvoice.CodeName}</a>
				for {GeneratedInvoice.Total}</li>
			<!-- } -->
			<!-- !PaymentError { -->
			<!-- GeneratedPayment { -->
			<li><a href="{DomainPrefix}/popup{action($GeneratedPayment,view)}">Payment
					information:</a> {GeneratedPayment}</li>
			<!-- } else { -->
			<li>No payment required</li>
			<!-- } -->
			<!-- } -->
			<!-- PaymentError { -->
			<li class="error">An error occurred during payment: {message}</li>
			<!-- } -->
		</ul>
	</li>
	<!-- } -->
	<!-- } -->
	<!-- !Generated { -->
	<li>No invoices were generated in this batch.</li>
	<!-- } -->
</ul>
<h2>Invoices already paid or with a balance due</h2>
<p>The following invoices have a zero balance, or require payment.</p>
<ul>
	<!-- InvoicePaid { -->
	<li><strong><a
			href="{DomainPrefix}/popup{action('invoice/notify/{InvoicePaid.ID}')}">Notify</a>:</strong>
		<a href="{DomainPrefix}/popup{action($InvoicePaid,view)}">{InvoicePaid.CodeName}</a>
		for <a
		href="{DomainPrefix}/popup{action($InvoicePaid.Account,edit}')}">{InvoicePaid.Account.Name}</a>
		paid by <a
		href="{DomainPrefix}/popup{action($InvoicePaid.PaymentAccount,edit}')}">{InvoicePaid.PaymentAccount.Name}</a>
		<ul>
			<li>Invoice Date: {InvoicePaid.BillDate}</li>
			<li>Total: {InvoicePaid.Total}</li>
			<li>Amount Due: {InvoicePaid.AmountDueNew}</li>
		</ul></li>
	<!-- } -->
</ul>
<!-- MigrateData? { -->
<h2>Account Import Log</h2>
<p>For insomniacs:</p>
<pre>
		{MigrateData}>
	</pre>
<!-- } -->
<!-- } -->
<h1>End of billing run</h1>
<?php
$this->end();
