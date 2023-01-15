<?php declare(strict_types=1);

/**
 * @version $Id: contact.inc 4481 2017-03-24 18:21:48Z kent $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @see Class_Contact
 * @author kent
 *
 */
class Contact extends ORMBase {
	/**
	 *
	 * @param Application $application
	 * @param string $hash
	 * @param array $where
	 * @return integer ID of found object
	 */
	public static function find_hash(Application $application, $hash, $where = null) {
		$query = $application->ormRegistry(__CLASS__)->querySelect();
		$class_object = $query->class_orm();
		$where['*hash'] = $query->sql()->function_unhex($hash);
		$id_column = $class_object->id_column;
		if (!$id_column) {
			throw new Exception_Semantics('Find hash on a contact but no ID column {class}', [
				'class' => $class_object::class,
			]);
		}
		return $query->addWhat($id_column)->where($where)->integer($id_column);
	}

	public function full_name($default = '') {
		if (!$this->memberIsEmpty('person')) {
			$result = $this->Person->full_name();
			if ($result) {
				return $result;
			}
		}
		if (!$this->memberIsEmpty('email')) {
			return $this->email->value;
		}
		return $default;
	}

	public function greeting_name($default = '') {
		if (!$this->memberIsEmpty('person')) {
			$result = $this->person->greeting_name();
			if ($result) {
				return $result;
			}
		}
		if (!$this->memberIsEmpty('Email')) {
			return $this->email->Value;
		}
		return $default;
	}

	public function store(): self {
		$is_new = $this->isNew();
		if ($is_new) {
			if ($this->memberIsEmpty('account')) {
				$this->account = $this->application->modelSingleton($this->option('account_model_singleton_class', 'zesk\\Account'));
			}
			if ($this->memberIsEmpty('user')) {
				$this->user = $this->application->user(null, false);
			}
			$this->accounts = $this->account;
		}
		return parent::store();
	}

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
		$contact->account = $user->memberInteger('account');
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
		return !$this->memberIsEmpty('Verified');
	}

	public function isConnected(User $user) {
		$user_account_id = $user->memberInteger('account');
		if ($user->memberInteger('account') === $user_account_id) {
			return true;
		}
		if ($user->is_linked('accounts', $user_account_id)) {
			return true;
		}
		return false;
	}
}
