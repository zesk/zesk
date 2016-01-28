<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/import/outlook.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Contact_Import_Outlook extends Contact_Import {
	function empty_date_values() {
		return array(
			'0/0/00'
		);
	}
	
	function contact_hash_keys() {
		return array(
			"Title", 
			"First Name", 
			"Middle Name", 
			"Last Name", 
			"Suffix", 
			"Mobile Phone", 
			"Primary Phone"
		);
	
	}
	function header_map() {
		return array(
			"First Name" => "Contact_Person.FirstName", 
			"Middle Name" => "Contact_Person.MiddleName", 
			"Last Name" => "Contact_Person.LastName", 
			"Title" => "Contact_Person.Title", 
			"Suffix" => "Contact_Person.Suffix", 
			"Initials" => "Contact_Person.FirstName", 
			"Web Page" => new Contact_Builder_URL(array(
				"URL" => "{value}", 
				"Label" => "Personal"
			)), 
			"Gender" => new Contact_Builder_Person(array(
				'Gender' => '{value}'
			), array(
				'ignore_values' => "Unspecified"
			)), 
			"Birthday" => new Contact_Builder_Date(array(
				"Value" => "{value}", 
				"Label" => "Birthday"
			)), 
			"Anniversary" => new Contact_Builder_Date(array(
				"Value" => "{value}", 
				"Label" => "Marriage Anniversary"
			)), 
			"Location" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Language" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Internet Free Busy" => new Contact_Builder_URL(array(
				"URL" => "{value}", 
				"Label" => "{key}"
			)), 
			"Notes" => "Contact_Person.Notes", 
			"E-mail Address" => new Contact_Builder_Email(), 
			"E-mail 2 Address" => new Contact_Builder_Email(), 
			"E-mail 3 Address" => new Contact_Builder_Email(), 
			"Primary Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"IsPrimary" => true
			)), 
			"Home Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Phone 2" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Home"
			)), 
			"Mobile Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Mobile"
			)), 
			"Pager" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Pager"
			)), 
			"Home Fax" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Home Fax"
			)), 
			"Home Address" => null, 
			"Home Street" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Street 2" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Street 3" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Address PO Box" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Home PO Box"
			)), 
			"Home City" => new Contact_Builder_Address(array(
				"City" => "{value}", 
				"Label" => "Home"
			)), 
			"Home State" => new Contact_Builder_Address(array(
				"Province" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Postal Code" => new Contact_Builder_Address(array(
				"PostalCode" => "{value}", 
				"Label" => "Home"
			)), 
			"Home Country" => new Contact_Builder_Address(array(
				"Country" => "{value}", 
				"Label" => "Home"
			)), 
			"Spouse" => "Contact_Person.Spouse", 
			"Children" => "Contact_Person.Children", 
			"Manager's Name" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Assistant's Name" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Referred By" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Company Main Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Business Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Phone 2" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Fax" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Work Fax"
			)), 
			"Assistant's Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Assistant"
			)), 
			"Company" => "Contact_Person.Company", 
			"Job Title" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Department" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Office Location" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Organizational ID Value" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Profession" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Account" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Business Address" => null, 
			"Business Street" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Street 2" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Street 3" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Address PO Box" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Work PO Box"
			)), 
			"Business City" => new Contact_Builder_Address(array(
				"City" => "{value}", 
				"Label" => "Work"
			)), 
			"Business State" => new Contact_Builder_Address(array(
				"Province" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Postal Code" => new Contact_Builder_Address(array(
				"PostalCode" => "{value}", 
				"Label" => "Work"
			)), 
			"Business Country" => new Contact_Builder_Address(array(
				"Country" => "{value}", 
				"Label" => "Work"
			)), 
			"Other Phone" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Fax" => new Contact_Builder_Phone(array(
				"Value" => "{value}", 
				"Label" => "Other Fax"
			)), 
			"Other Address" => null, 
			"Other Street" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Street 2" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Street 3" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Address PO Box" => new Contact_Builder_Address(array(
				"Street_Line" => "{value}", 
				"Label" => "Other PO Box"
			)), 
			"Other City" => new Contact_Builder_Address(array(
				"City" => "{value}", 
				"Label" => "Other"
			)), 
			"Other State" => new Contact_Builder_Address(array(
				"Province" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Postal Code" => new Contact_Builder_Address(array(
				"PostalCode" => "{value}", 
				"Label" => "Other"
			)), 
			"Other Country" => new Contact_Builder_Address(array(
				"Country" => "{value}", 
				"Label" => "Other"
			)), 
			"Callback" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Car Phone" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"ISDN" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Radio Phone" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"TTY/TDD Phone" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Telex" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"User 1" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"User 2" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"User 3" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"User 4" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Keywords" => "Contact.Keywords", 
			"Mileage" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Hobby" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Billing Information" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Directory Server" => new Contact_Builder_URL(array(
				"URL" => "{value}", 
				"Contact_URL.Label" => "{key}"
			)), 
			"Sensitivity" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Priority" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Private" => new Contact_Builder_Other(array(
				"Value" => "{value}", 
				"Label" => "{key}"
			)), 
			"Categories" => new Contact_Builder_Tag()
		);
	}
	
	function can_import($file_name) {
		$csv = new CSV_Reader($file_name);
		$headers = $csv->readHeaders();
		$known_headers = $this->header_map();
		$intersect = array_intersect(array_keys($known_headers), $headers);
		if (count($intersect) >= (count($known_headers) - 4)) {
			return true;
		}
		return false;
	}
}
