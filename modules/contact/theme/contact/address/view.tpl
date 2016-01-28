<?php
$object = $this->get1('object;content');

/* @var $object Contact_Address  */
$members = $object->members();
foreach (to_list('street;city;province') as $k) {
	$members[$k] = str::capitalize(avalue($members, $k));
}
$street = $city = $province = $postalcode = $country = null;
extract($members, EXTR_IF_EXISTS);

?>
<div class="contact-view-address contact-view">
<?php
if ($this->getb('show_street', true)) {
	echo html::span('.contact-address-street', $street);
}
if ($city && $province) {
	echo html::tag("span", ".contact-address-city-province", __("contact/address/view:={city}, {province}", $members));
} else {
	echo html::etag('span', '.contact-address-city', str::capitalize($city));
	echo html::etag('span', '.contact-address-province', str::capitalize($province));
}
echo html::etag('span', '.contact-address-postalcode', $postalcode);
if ($object->county) {
	echo html::etag('span', array(
		'class' => 'contact-address-county',
		'title' => __('County')
	), $object->county->name);
}
if ($object->country) {
	echo html::etag('span', array(
		'class' => 'contact-address-country',
		'title' => __('Country')
	), $object->country->name);
}
if ($this->distance_from instanceof Contact_Address) {
	$distance = $object->distance($this->distance_from);
	if ($distance !== null) {
		?><span class="contact-address-distance"><?php
		echo theme('distance', array(
			'content' => $distance,
			'units' => 'miles'
		));
		?></span><?php
	}
}
?>
</div>
