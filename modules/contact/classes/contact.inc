<?php

/**
 * @version $Id: contact.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @see Class_Contact
 * @author kent
 *
 */
class Contact extends ORM {
	/**
	 *
	 * @param Application $application
	 * @param string $hash
	 * @param array $where
	 * @return integer ID of found object
	 */
	public static function find_hash(Application $application, $hash, $where = null) {
		$query = $application->orm_registry(__CLASS__)->query_select();
		$class_object = $query->class_orm();
		$where['*hash'] = $query->sql()->function_unhex($hash);
		$id_column = $class_object->id_column;
		if (!$id_column) {
			throw new Exception_Semantics("Find hash on a contact but no ID column {class}", array(
				"class" => get_class($class_object)
			));
		}
		return $query->what($id_column)->where($where)->one_integer($id_column);
	}
	public function full_name($default = "") {
		if (!$this->member_is_empty("person")) {
			$result = $this->Person->full_name();
			if ($result) {
				return $result;
			}
		}
		if (!$this->member_is_empty("email")) {
			return $this->email->value;
		}
		return $default;
	}
	public function greeting_name($default = "") {
		if (!$this->member_is_empty("person")) {
			$result = $this->person->greeting_name();
			if ($result) {
				return $result;
			}
		}
		if (!$this->member_is_empty("Email")) {
			return $this->email->Value;
		}
		return $default;
	}
	public function store() {
		$is_new = $this->is_new();
		if ($is_new) {
			if ($this->member_is_empty('account')) {
				$this->account = $this->application->model_singleton($this->option("account_model_singleton_class", 'zesk\\Account'));
			}
			if ($this->member_is_empty('user')) {
				$this->user = $this->application->user();
			}
			$this->accounts = $this->account;
		}
		return parent::store();
	}
	public function find_linked_data($member, $value) {
		return $this->has_many_query($member)
			->where("value", $value)
			->what("X.*")
			->object();
	}
	public static function find_by_email($email) {
		$contact_id = self::class_query('Contact_Email')->where('value', $email)->what('contact')->one('contact');
		$contact = self::factory('contact', $contact_id);
		if ($contact->fetch()) {
			return $contact;
		}
		// TODO: Clean Contact_Email table
		return null;
	}

	/**
	 * Returns an array of contacts
	 *
	 * @param Contact_Tag $tag
	 * @return unknown
	 */
	// 	public static function tag_status_query(Contact_Tag $tag) {
	// 		$user_id = $tag->memberInteger("User");
	// 		return self::class_query('Contact', 'Contact')->what('DISTINCT Contact.id,IF(Invite.Status IS NULL,\'uninvited\',Invite.status),Invite.senddate')
	// 			->join("INNER JOIN Contact_Tag_Contact CTC ON CTC.Contact=Contact.ID")
	// 			->join("INNER JOIN Contact_Tag Tag ON CTC.Contact_Tag=Tag.ID")
	// 			->join("INNER JOIN User_Contact_Tag UCT ON UCT.Contact_Tag=Tag.ID")
	// 			->join("LEFT OUTER JOIN Contact_Invite Invite ON Invite.Contact=Contact.ID AND Invite.User=$user_id")
	// 			->where("UCT.User", $user_id)
	// 			->where("Tag.ID", $tag);
	// 	}

	/**
	 * Create a contact with just an email
	 *
	 * @param User $user
	 * @param string $email
	 * @return Contact_Email
	 */
	public static function from_email(User $user, $email, $tag = null) {
		$contact = new Contact();
		$contact->user = $user;
		$contact->account = $user->member_integer('account');
		if ($tag instanceof Contact_Tag) {
			$contact->Tags = $tag;
		}
		if (!$contact->store()) {
			return null;
		}

		$contact_email = new Contact_Email();
		$contact_email->contact = $contact;
		$contact_email->label = Contact_Label::find_global(Contact_Label::LabelType_Email, 'Home');
		$contact_email->value = $email;

		if (!$contact_email->store()) {
			$contact->delete();
			return null;
		}

		$contact_person = new Contact_Person();
		$contact_person->contact = $contact;
		$contact_person->nickname = StringTools::left($email, '@');
		if (!$contact_person->store()) {
			$contact_email->delete();
			$contact->delete();
			return null;
		}

		return $contact_email;
	}
	public function is_verified() {
		return !$this->member_is_empty("Verified");
	}
	public function is_connected(User $user) {
		$user_account_id = $user->memberInteger("account");
		if ($user->memberInteger("account") === $user_account_id) {
			return true;
		}
		if ($user->is_linked("accounts", $user_account_id)) {
			return true;
		}
		return false;
	}
	public function has_labels($labels) {
		$objects = array(
			Contact_Address::class,
			Contact_Date::class,
			Contact_Email::class,
			Contact_Other::class,
			Contact_Person::class,
			Contact_Phone::class,
			Contact_URL::class
		);
		$found_labels = array();
		foreach ($objects as $object) {
			$found_labels = array_merge($found_labels, self::class_query($object)->where("contact", $this->id())
				->what("DISTINCT label")
				->where("label", $labels)
				->to_array("label", "Label"));
		}
		if (count($found_labels) === count($labels)) {
			return true;
		}
		return array_diff($labels, array_values($found_labels));
	}
}
