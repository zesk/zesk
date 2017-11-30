<?php
/**
 * jQuery File Upload Plugin PHP Class 6.1.2 https://github.com/blueimp/jQuery-File-Upload 
 * 
 * Copyright 2010, Sebastian Tschan 
 *
 * https://blueimp.net 
 * 
 * Licensed under the MIT license: http://www.opensource.org/licenses/MIT 
 *
 * Heavily modified for use in Zesk by Kent M. Davidson http://marketacumen.com/?crsource=$Id: upload.inc 4594 2017-05-15 21:20:56Z kent $
 */
use zesk\Directory;
use zesk\Controller;
use zesk\Exception_Directory_Create;
use zesk\Exception_Directory_Permission;

/**
 * 
 * @author kent
 *
 */
class Controller_JQuery_File_Upload extends Controller {
	
	/*
	 * Map error codes to strings http://php.net/manual/en/features.file-upload.errors.php
	 */
	static $error_types = array(
		UPLOAD_ERR_INI_SIZE => 'upload_max_file_size_php_ini',
		UPLOAD_ERR_FORM_SIZE => 'upload_max_file_size_html_form',
		UPLOAD_ERR_PARTIAL => 'upload_partial',
		UPLOAD_ERR_NO_FILE => 'upload_empty',
		UPLOAD_ERR_NO_TMP_DIR => 'upload_missing_temporary_directory',
		UPLOAD_ERR_CANT_WRITE => 'upload_write_error',
		UPLOAD_ERR_EXTENSION => 'upload_php_extension'
	);
	public function action_index() {
		$result = array(
			"error" => "Unhandled request method"
		);
		switch ($this->request->method()) {
			case "POST":
				$result = $this->post();
				break;
		}
		$this->response->json($result);
	}
	private function head() {
		$response = $this->response;
		
		$response->nocache();
		/* Security Issue: Prevent JavaScript being stored in other file types: IESUX */
		$response->header('X-Content-Type-Options', 'nosniff');
	}
	private function post() {
		$name = $this->option('name', 'files');
		$upload = $this->request->file($name);
		
		$file_name = $this->request->header('Content-Disposition');
		$content_range = $this->request->header('Content-Range');
		
		$file_name = isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ? rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $_SERVER['HTTP_CONTENT_DISPOSITION'])) : null;
		
		/*
		 * Parse the Content-Range header, which has the following form: Content-Range: bytes 0-524287/2000000
		 */
		$content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ? preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
		$size = $content_range ? $content_range[3] : null;
		
		$files = array();
		if (is_array($upload)) {
			$index = to_integer(avalue($upload, 'index', 0));
			$total = to_integer(avalue($upload, 'total', 1));
			while ($index < $total) {
				$files[] = $this->handle_upload($upload, $content_range);
				$index++;
			}
		}
		if ($content_range) {
			$this->response->header('Range', '0-' . ($this->fix_ulong(intval($content_range)) - 1));
		}
		$this->head();
		$this->response->json(array(
			$name => $files
		));
	}
	private function fix_ulong($size) {
		if ($size < 0) {
			$size += 2.0 * (PHP_INT_MAX + 1);
		}
		return $size;
	}
	protected function handle_upload(array $upload, $content_range = null) {
		$tmp_name = $name = $type = $size = $index = $error = null;
		extract($upload, EXTR_IF_EXISTS);
		$result = array();
		$result['name'] = $name;
		$result['size'] = $size = $this->fix_ulong($size);
		$result['type'] = $type;
		if ($error !== UPLOAD_ERR_OK) {
			$result['error'] = avalue(self::$error_types, $error, "unknown-error: $error");
			return $result;
		}
		$path = $this->option("upload_diretory", $this->application->data_path('upload'));
		$filename = md5_file($tmp_name);
		try {
			Directory::depend($path, $this->option('upload_directory_mode'));
		} catch (Exception_Directory_Create $e) {
			$result['error'] = 'upload_directory_create';
			return $result;
		} catch (Exception_Directory_Permission $e) {
			$result['error'] = 'upload_directory_permission';
			return $result;
		}
		
		$accept_types = $this->option_list('accept_file_types', array());
		if (count($accept_types) > 0 && !in_array($type, $accept_types)) {
			$result['error'] = 'accept_file_types';
			return $result;
		}
		$max_file_size = $this->option_integer("max_file_size", null);
		if ($max_file_size > 0 && $size > $max_file_size) {
			$result['error'] = 'max_file_size_options';
			return $result;
		}
		$min_file_size = $this->option_integer("min_file_size", null);
		if ($min_file_size > 0 && $size < $min_file_size) {
			$result['error'] = 'min_file_size_options';
			return $result;
		}
		
		$file_path = path($path, $filename);
		$append_file = $content_range && is_file($file_path) && $size > $this->get_file_size($file_path);
		if (is_uploaded_file($tmp_name)) {
			// multipart (POST)
			if ($append_file) {
				file_put_contents($file_path, fopen($tmp_name, 'r'), FILE_APPEND);
			} else {
				move_uploaded_file($tmp_name, $file_path);
			}
		} else {
			// Non-multipart (PUT)
			file_put_contents($file_path, STDIN, $append_file ? FILE_APPEND : 0);
		}
		clearstatcache(true, $file_path);
		$new_file_size = $this->fix_ulong(filesize($file_path));
		if ($new_file_size === $size) {
			$result['url'] = '/download/something'; // TODO
		} else if (!$content_range && $this->option('discard_canceled_uploads')) {
			unlink($file_path);
			$result['error'] = 'cancel';
		}
		$result['size'] = $new_file_size;
		return $result;
	}
}
