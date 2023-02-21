<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use Throwable;
use zesk\Exception;

/**
 *
 * @author kent
 *
 */
class UploadException extends Exception {
	/**
	 * From http://us1.php.net/manual/en/features.file-upload.errors.php
	 *
	 * UPLOAD_ERR_INI_SIZE
	 * Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
	 *
	 * UPLOAD_ERR_FORM_SIZE
	 * Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
	 *
	 * UPLOAD_ERR_PARTIAL
	 * Value: 3; The uploaded file was only partially uploaded.
	 *
	 * UPLOAD_ERR_NO_FILE
	 * Value: 4; No file was uploaded.
	 *
	 * UPLOAD_ERR_NO_TMP_DIR
	 * Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
	 *
	 * UPLOAD_ERR_CANT_WRITE
	 * Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
	 *
	 * UPLOAD_ERR_EXTENSION
	 * Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.
	 *
	 * @var array
	 */
	public static array $messages = [
		UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
		UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
		UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
		UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Introduced in PHP 5.1.0.',
		UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.',
	];

	public function __construct(int|string $error_code, Throwable $previous = null) {
		parent::__construct(self::$messages[$error_code] ?? 'Unknown error code {error_code}', [
			'error_code' => $error_code,
		], $error_code, $previous);
	}
}
