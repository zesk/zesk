<?php declare(strict_types=1);
namespace zesk;

$this->response->javascript('/share/zesk/jquery/jquery.overlabel.js');
$this->response->javascript('/share/zesk/jquery/jquery.glow.js');

$contact = $this->object;
$account = $this->account;
$user = $this->user;

/* @var $contact Contact */
/* @var $account Account */
/* @var $user User */

$id = $contact->id();

$person = $contact->Person;

$sections = [
	'email' => [
		'head_label' => "Email",
		'object_class' => "Contact_Email",
	],
	'phone' => [
		'head_label' => "Phone",
		'object_class' => "Contact_Phone",
	],
	'address' => [
		'head_label' => "Address",
		'object_class' => "Contact_Address",
	],
	'url' => [
		'head_label' => "Web sites",
		'object_class' => "Contact_URL",
	],
	'dates' => [
		'head_label' => "Dates",
		'object_class' => "Contact_Date",
	],
];
if ($this->get('show_other', true)) {
	$sections['other'] = [
		'head_label' => "Other",
		'object_class' => "Contact_Other",
	];
}

$this->response->jquery("contact_view_load()");

/* @var $this zesk\Template */
/* @var $person Person */

$show_links = $this->get('show_links', true);

?>
<div id="contact-<?php echo $id?>" class="contact-view">
	<?php
	if ($show_links && $user->can($contact, "edit")) {
		?>
		<?php
		if ($user->member_is_empty('Contact')) {
			?>
		<a href="/account/assign-contact/<?php echo $id?>"
		onclick="alert('Will make this contact primary contact for account'); return false"
		class="action-button">This is me</a>
		<?php
		} ?>
		<a href="/contact/edit/<?php echo $id?>" class="action-button">Edit</a>
	<?php
	}
	?>
	<div class="contact-person">
		<?php echo HTML::etag('h1', '.person-full-name', $person->full_name())?>
		<?php
		if ($person->Title && $person->Company) {
			?>
		<h2 class="person-title-company"><?php echo $person->Title?>, <?php echo $person->Company?></h2>
		<?php
		} else {
			?>
		<?php echo HTML::etag('h2', '.person-title', $person->Title)?>
		<?php echo HTML::etag('h2', '.person-company', $person->Company)?>
		<?php
		}
		?>
	</div>
<?php

$label_table = ORM::class_table_name("Contact_Label");
foreach ($sections as $section => $variables) {
	$object_class = $head_label = null;
	extract($variables, EXTR_IF_EXISTS);
	$values = [];
	$query = ORM::class_query($object_class)->link("Contact_Label", [
		"alias" => "L",
	])->what('label_name', 'L.name')->where('contact', $id);
	$values = $query->to_array();
	if (count($values) > 0) {
		?><div class="contact-section"
		id="contact-section-<?php echo $section?>"><?php
		foreach ($values as $i => $data) {
			$object = new $object_class($data);
			echo $object->output(null, [
				'show_links' => $show_links,
			]);
		} ?></div><?php
	}
}
if (!$contact->member_is_empty('Notes')) {
	?>
	<div class="contact-section" id="contact-section-notes">
		<div class="section-head">
			<h2><?php echo __('Notes')?></h2>
		</div>
		<div class="section-control section-control-notes" id="Contact_Notes">
			<?php echo  HTML::urlify(nl2br(htmlspecialchars($contact->Notes)))?>
		</div>
	</div>
<?php
}
?>
</div>
