<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage metafile
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Binary file reading tool
 *
 * @author kent
 */
abstract class Metafile extends Options {
	/**
	 * Stream reading
	 * @var Stream
	 */
	protected $stream = null;

	/**
	 * Whether this is a big-endian or little-endian file format
	 * @var boolean
	 */
	protected $big_endian = false;

	/**
	 * Warnings
	 * @var array
	 */
	protected $warnings = [];

	public function __construct(Stream $stream, array $options = []) {
		parent::__construct($options);
		$this->stream = $stream;
	}

	public function read_byte($count = 1) {
		assert($count > 0);
		$data = $this->stream->read($count);
		if ($count === 0) {
			return ord($data[0]);
		}
		$result = [];
		for ($i = 0; $i < $count; $i++) {
			$result[] = ord($data[$i]);
		}
		return $result;
	}

	public function read_integer($size = 4, $count = 1) {
		$offset = 0;
		$data = $this->read_byte($size * $count);
		$result = [];
		switch ($size) {
			case 4:
				$remain = $count;
				while ($remain > 0) {
					if ($this->big_endian) {
						$result[] = intval($data[$offset + 0] << 24 | $data[$offset + 1] << 16 | $data[$offset + 2] << 8 | $data[$offset + 3]);
					} else {
						$result[] = intval($data[$offset + 3] << 24 | $data[$offset + 2] << 16 | $data[$offset + 1] << 8 | $data[$offset + 0]);
					}
					$offset += $size;
					--$remain;
				}

				break;
			case 2:
				$remain = $count;
				while ($remain > 0) {
					if ($this->big_endian) {
						$result[] = intval($data[$offset + 0] << 8 | $data[$offset + 1]);
					} else {
						$result[] = intval($data[$offset + 1] << 8 | $data[$offset + 0]);
					}
					$offset += $size;
					--$remain;
				}

				break;
			case 1:
				return $data;
			default:
				throw new Exception_Semantics('read_integer reads 1,2,4 byte integers');
		}
		if ($count === 1) {
			return $result[0];
		}
		return $result;
	}

	abstract public function validate();

	public function warning($message = null) {
		if ($message === null) {
			return $this->warnings;
		}
		$this->warnings[] = $message;
		return $this;
	}
}
