<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Contact_Builder_Base extends Options {
	/**
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 *
	 * @var string
	 */
	protected $contact_class = null;

	/**
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct($data = null, array $options = []) {
		parent::__construct($options);
		$this->data = is_array($data) ? $data : [];
	}

	/**
	 *
	 * @param Contact_Import $import
	 * @param string $key
	 * @param mixed $value
	 */
	abstract public function process(Contact_Import $import, $key, $value);
}
