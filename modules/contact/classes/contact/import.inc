<?php
namespace zesk;

abstract class Contact_Import extends Options {
	/**
	 * @var Interface_Process
	 */
	protected $process;
	
	/**
	 * File to import
	 *
	 * @var string
	 */
	protected $filename;
	
	/**
	 * 
	 * @var string
	 */
	protected $import_class = "zesk\\CSV_Reader";
	
	/**
	 * 
	 * @var array
	 */
	private $objects = array();
	
	/**
	 * 
	 * @var array
	 */
	private $map = array();
	/**
	 * 
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * 
	 * @var unknown
	 */
	private $tag = null;
	function __construct(Interface_Process $process, $filename, $options = null) {
		parent::__construct($options);
		$this->process = $process;
		$this->filename = $filename;
		$tag = $this->option('tag');
		if ($tag instanceof Contact_Tag) {
			$this->tag = $tag;
		}
	}
	
	/**
	 * @return array Map of column headers to internal data structures
	 *
	 */
	abstract function header_map();
	
	/**
	 * 
	 */
	abstract function contact_hash_keys();
	
	/**
	 * 
	 * @param unknown $row
	 * @return string
	 */
	public function contact_hash($row) {
		$keys = $this->contact_hash_keys();
		$hash = array();
		foreach ($keys as $k) {
			$hash[] = avalue($row, $k, "");
		}
		return md5(implode("|", $hash));
	}
	
	/**
	 * 
	 * @return array
	 */
	public function empty_date_values() {
		return array();
	}
	
	/**
	 * 
	 * @param unknown $filename
	 */
	abstract function can_import($filename);
	
	/**
	 * 
	 * @return boolean
	 */
	function go() {
		$this->errors = array();
		$class = $this->import_class;
		$import_file = new $class($this->filename);
		/* @var $import_file CSV_Reader */
		$iterator = $import_file->iterator();
		foreach ($iterator as $row_index => $row) {
			$this->import_row($row_index, $row);
			if ($this->process->isDone()) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 
	 * @param Interface_Process $proc
	 * @param unknown $filename
	 * @param unknown $options
	 * @return \zesk\Import_Contact|NULL
	 */
	public static function factory(Interface_Process $proc, $filename, $options = null) {
		$classes = array(
			"Contact_Import_Outlook"
		);
		
		foreach ($classes as $class) {
			$class = new $class($proc, $filename, $options);
			/* @var $class Import_Contact */
			if ($class->can_import($filename)) {
				return $class;
			}
		}
		return null;
	}
	
	/**
	 * 
	 */
	function reset() {
		$this->objects = array();
	}
	
	/**
	 * 
	 * @param Contact_Info $object
	 * @param unknown $name
	 * @return \zesk\unknown
	 */
	function register_label(Contact_Info $object, $name) {
		$label_type = $object->label_type();
		$label = Contact_Label::find_global($label_type, $name);
		if (!$label) {
			$label = Contact_Label::register_local($label_type, $name, $this->option_integer('account'));
		}
		return $label;
	}
	
	/**
	 * 
	 * @param integer $row_index
	 * @param array $row
	 * @return boolean
	 */
	function import_row($row_index, array $row) {
		$this->reset();
		$map = $this->header_map();
		$contact_hash = $this->contact_hash($row);
		foreach ($row as $key => $value) {
			$value = trim($value);
			if (empty($value)) {
				continue;
			}
			if (array_key_exists($key, $map)) {
				$handler = $map[$key];
				if (is_string($handler)) {
					list($object, $field) = pair($handler, ".", null, null);
					if ($object) {
						$this->objects[$object][0][$field] = $value;
					}
				} else if ($handler instanceof Contact_Builder_Base) {
					$ignores = $handler->option_list('ignore_values');
					if (is_array($ignores) && in_array($value, $ignores)) {
						continue;
					}
					$handler->process($this, $key, $value);
				}
			}
		}
		$this->objects = map(array(
			'account' => $this->option_integer('account'),
			'user' => $this->option_integer('user')
		), $this->objects);
		
		throw new Exception_Unimplemented("Need to update this code");
		
		$account = $this->option_integer("account");
		$dup_contact = Contact::find_hash($contact_hash, array(
			'account' => $account
		));
		$contact = new Contact(avalue($this->objects, 'contact', array()));
		$contact->memberCanStore('*Hash');
		$contact->set_member('*Hash', $contact_hash);
		$contact->account = $account;
		$contact->user = $this->option_integer('User');
		// 		$contact->Duplicate = $dup_contact;
		
		if ($this->tag) {
			$contact->tags = $this->tag;
		}
		if (!$contact->store()) {
			$this->errors[$row_index] = array(
				"error" => "Can not store contact",
				"data" => serialize($this->objects)
			);
			return false;
		}
		
		foreach ($this->objects as $contact_class => $items) {
			if ($contact_class === 'Contact') {
				continue;
			}
			foreach ($items as $item) {
				$object = Object::factory($contact_class, $item);
				if (array_key_exists('Label', $item)) {
					$object->label = $this->registerLabel($object, $item['Label']);
				}
				if ($object->has_member('contact')) {
					$object->contact = $contact;
				}
				$object->register();
			}
		}
		
		$contact->imported();
		return true;
	}
	function has_item($type, $id) {
		if (!array_key_exists($type, $this->objects)) {
			return false;
		}
		$objects = $this->objects[$type];
		return avalue($objects, $id);
	}
	function set_item($type, $id, $data) {
		$this->objects[$type][$id] = $data;
	}
	function merge_item($type, $id, $data) {
		$result = $this->has_item($type, $id);
		if (!is_array($result)) {
			$this->objects[$type][$id] = array();
		}
		$this->objects[$type][$id] += $data;
	}
}

	