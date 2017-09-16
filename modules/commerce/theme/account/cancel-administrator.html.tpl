<?php
/**
 * @version $URL$
 * @package zesk
 * @subpackage commerce
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:10 PM
 */
?>
<h1>{title('Cancel account &quot;{Account.Name}&quot; #
	{Account.ID}',true,true)}</h1>
{actionlink($Account,edit,'Edit this account')} &middot;
{actionlink($Account,view,'View this account')} &middot;
{actionlink('craccountold({Account.ID})',view,'View 1.0 Account')}
&middot;
<p>
	This account {if('{Account.IsBillable}','<strong>is</strong>','<em>is
		not</em>')} billable.
</p>
<p>Current balance is {view:Price(Value=$Account.Balance)}.</p>
<p>Current unreconciled balance is
	{view:Price(Value=$Account.NewBalance)}.</p>
<h2>Uninstallation Status</h2>
<ul>
	<!-- Account.Sites { -->
	<li>{Account.Sites.Name} last page view was <strong>{view:DateTime(format="{delta}",Value=$Account.Sites.LegacyLastPageView)}</strong>
		on <em>{View:DateTime(Value='$Account.Sites.LegacyLastPageView',Format="{long}")}</em>
		<a target="installed_pages"
		href="http://www.conversionruler.com/admin/site/change.php?siteid={Account.Sites.ID}&ref=/admin/setup/snippage.php">View
			installed pages</a></li>
	<!-- } -->
</ul>
<!-- Cancelled { -->
<h2>Cancelled</h2>
<p>Cancelled as of {view:DateTime(Value=$Account.Cancelled)}.</p>
<!-- ifne('{Account.NewBalance}',0) { -->
<p class="error">This account has an unreconciled balance of
	{Account.NewBalance}.</p>
<!-- } -->
<!-- ifgt('{Account.Balance}',0) { -->
<p class="error">This account has an reconciled balance of
	{Account.NewBalance}.</p>
{actionlink($account.transactions,klist,'Process refund')}
<!-- } -->
<!-- } -->
<h2>Cancel as of:</h2>
<!-- CancellationDateError { -->
<p class="error">Please select the date you'd like to cancel this
	account.</p>
<!-- } -->
<p>
	<input type="radio" name="CancellationDate" value="last" /> Last bill
	date <strong>{view:Date(Format='{delta}',Value=$Account.LastBillDate)}</strong>
	on {view:Date(Format='{long}',Value=$Account.LastBillDate)}.
</p>
<p>
	<input type="radio" name="CancellationDate" value="next" /> Next bill
	date <strong>{view:Date(Format='{delta}',Value=$Account.NextBillDate)}</strong>
	on {view:Date(Format='{long}',Value=$Account.NextBillDate)}.
</p>
<input type="submit" name="Cancel" value="Cancel this account" />
<div class="menus">
	<div class="menu">
		<h1>{Account.Name} Sites</h1>
		<ul>
			<!-- Account.Sites { -->
			<li>{actionlink($Account.Sites,view,'{Account.Sites.Name} #
				{Account.Sites.ID}')}
				({if('{Account.Sites.IsActive}','Active','Inactive')})</li>
			<!-- } -->
		</ul>
	</div>
	<div class="menu">
		<h1>{Account.Name} Users</h1>
		<ul>
			<!-- Account.ObjectOwners { -->
			<li>{actionlink($Account.ObjectOwners,view,'{Account.ObjectOwners.Login}
				# {Account.ObjectOwners.ID}')}</li>
			<!-- } -->
		</ul>
	</div>
	<div class="menu">
		<h1>{Account.Name} Paid Accounts</h1>
		<ul>
			<!-- Account.PayAccounts { -->
			<li>{actionlink($Account.PayAccounts,view,'{Account.PayAccounts.Name}')}
				next bill date {Account.PayAccounts.NextBillDate}</li>
			<!-- } -->
		</ul>
	</div>
</div>
<!-- } -->
