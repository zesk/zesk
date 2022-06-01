<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage help
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @param show_credit boolean Whether to show the credits or not
 */
$emails_id = 0;

$client_email = $this->client_email;
$from_email = $this->from_email;
$email_host = strtolower(StringTools::right($from_email, '@'));
?>

<?php echo Control_Arrow::open('BlackBerry', $email_host === 'blackberry.com'); ?>
<ol>
	<li>Scroll up to the message header.</li>
	<li>Get to the field where their name is listed, click the Berry button
		and then click Show Address.</li>
	<li>Select and copy that address to the clipboard.</li>
	<li>Go into Address Book and find the user.</li>
	<li>Select Save.</li>
	<li>Click to edit it, and then click the Berry button to add another
		email address.</li>
	<li>Paste it in and click Save.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('AOL (for version 9.0)', $email_host === 'aol.com'); ?>
<p>New subscribers need to add the &quot;From&quot; address, &quot;<?php echo $from_email?>&quot; to their address book:</p>
<ol>
	<li>Click the Mail menu and select Address Book.</li>
	<li>Wait for the Address Book window to pop up, then click the Add
		button.</li>
	<li>Wait for the Address Card for New Contact window to load.</li>
	<li> Once loaded, cut and paste &quot;<?php echo $from_email?>&quot; into the &quot;Other E-Mail&quot; field.
</li>
	<li>Make our From address the Primary E-Mail address by checking the
		associated check box.</li>
	<li>Click the Save button.</li>
	<li>For existing subscribers that are seeing messages in the spam
		folder, open the newsletter and click the This Is Not Spam button.</li>
	<li> Add &quot;<?php echo $from_email?>&quot; onto your Address Book as outlined in the New Subscribers information above.
</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Comcast', $email_host ==='comcast.com'); ?>
<ol>
	<li>Sign into Webmail.</li>
	<li>On the left navigation menu, click Address Book.</li>
	<li>Click Add Contact.</li>
	<li> Under the General tab, in the box under the Email Address, enter &quot;<?php echo $from_email?>&quot;.
</li>
	<li>Click the Add button.</li>
</ol>
<p>If you have enabled &quot;Restrict Incoming Email,&quot; also do the
	following:</p>
<ol>
	<li>Sign into Webmail.</li>
	<li>Select Preferences.</li>
	<li>Select Restrict Incoming Email. Note: If Enable Email Controls is
		set to Yes, then you are restricting incoming emails.</li>
	<li>Select Allow email from addresses listed below.</li>
	<li> Enter &quot;<?php echo $from_email?>&quot;.
</li>
	<li>Click the Add button.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Earthlink', $email_host ==='earthlink.com'); ?>
<ol>
	<li>Click the Address Book button to open your address book in the
		browser.</li>
	<li>Click the Add Contact button (if you use EarthLink</li>
	<li>0 or higher, click the Add button).</li>
	<li> Type in &quot;<?php echo $from_email?>&quot; into the email address slot and then click OK.
</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Gmail', $email_host ==='gmail.com'); ?>
<ol>
	<li>Click on Contacts in the left column.</li>
	<li>Click on Add Contact on the upper right-hand-side of the Contacts
		screen.</li>
	<li> Enter &quot;<?php echo $from_email?>&quot; in the Primary Email field.
</li>
	<li>Click on Save.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Hotmail', $email_host ==='hotmail.com'); ?>
<ol>
	<li>Click on the Contacts tab at the top of your account.</li>
	<li>In the left hand menu, click on Safe List.</li>
	<li> Enter &quot;<?php echo $from_email?>&quot; into the blank field.
</li>
	<li>Click the Add button to the right of the field.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo  Control_Arrow::open('Mozilla Thunderbird', $email_host ==='thunderbird'); ?>
<ol>
	<li>Click the Address Book button.</li>
	<li>Make sure the Personal Address Book is highlighted.</li>
	<li>Click the New Card button. This will launch a New Card window that
		has 3 tabs: Contact, Address and Other.</li>
	<li>Under the Contact tab, copy and paste the &quot;From&quot; address, &quot;<?php echo  $from_email ?>&quot; into the email dialogue box.</li>
	<li>Click OK.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Outlook 2003', $email_host ==='outlook'); ?>
<ol>
	<li>Go to your Contacts page.</li>
	<li>Click on New in the upper-left-hand corner.</li>
	<li> Enter &quot;<?php echo $from_email?>&quot; into the email address field.
</li>
	<li>Click Save in the upper left of your window.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('SBC Global', $email_host ==='sbcglobal'); ?>
<ol>
	<li>Go to the SBC Global Mail page and click the Options link.</li>
	<li>In the Management section, click the Filters link.</li>
	<li>Click the Add button.</li>
	<li> In the &quot;From header&quot; rule, in the field to the right of contains, enter &quot;<?php echo $from_email?>&quot;.
</li>
	<li>From the Move the message to pull-down list, choose inbox.</li>
	<li>Click the Add Filter button to save the filter.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Yahoo!', $email_host ==='yahoo.com'); ?>
<ol>
	<li>Click on the Addresses tab in the upper-left part of your account
		screen.</li>
	<li>Click on Add contact just under the Addresses tab.</li>
	<li> Enter &quot;<?php echo $from_email?>&quot; in the email field � the rest can be left blank, if desired.
</li>
	<li>Click on Save at the bottom of the page. You should see a
		confirmation screen.</li>
	<li>Click Done in the upper left.</li>
</ol>
<?php echo Control_Arrow::close(); ?>

<?php echo Control_Arrow::open('Verizon.net', $email_host ==='verizon.net'); ?>
<ol>
	<li>Go to your account and click on the Address Book link in the left
		column.</li>
	<li>Select Create Contact.</li>
	<li> The Add Address Book Entry screen appears. In the Email field, type &quot;<?php echo $from_email?>&quot;.
</li>
	<li>In the Nickname field, type [YourCompany].</li>
	<li>Select Save.

</ol>
<?php echo Control_Arrow::close(); ?>

<p class="tiny">
	Credit: <a
		href="http://www.marketingsherpa.com/article.php?ident=30194">Marketing
		Sherpa</a>
</p>
