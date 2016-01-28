<?php

use zesk\Locale as Locale;

/**
 * @see Class_PolyGlot_Update
 * @author kent
 *
 */
class PolyGlot_Update extends Object {

	public static function register_update($locale, User $user = null) {
		$object = Object::factory(__CLASS__, array(
			"locale" => Locale::normalize($locale)
		));
		try {
			$object->fetch();
		} catch (Exception_Object_NotFound $e) {
			$object->user = $user;
		}
		$object->updated = "now";
		return $object->store();
	}

	public static function cron_minute() {
		$server = Server::singleton();
		$path = zesk::get("Module_PolyGlot::update_path", zesk::application_root("etc/language"));
		if (!is_dir($path)) {
			log::warning("Module_PolyGlot::update_path is not a directory ({path}) on {server}", array(
				"path" => $path,
				"server" => $server->name
			));
			return;
		}
		$server_variable_name = __CLASS__ . "::last_update";
		$update_requested = Object::class_query(__CLASS__)->what("*updated", "MAX(updated)")->one_timestamp("updated");
		$server_updated = $server->data($server_variable_name);
		$update_locales = array();
		if ($server_updated) {
			$server_updated = Timestamp::factory($server_updated);
			if ($server_updated->before($update_requested)) {
				$update_locales = Object::class_query(__CLASS__)->what("locale", "locale")->where("updated|>=", $server_updated)->to_array(null, "locale");
			}
		} else {
			$update_locales = Object::class_query(__CLASS__)->what("locale", "locale")->to_array(null, "locale");
		}
		if (count($update_locales) === 0) {
			log::debug("No locale updates needed on {server}", array(
				"server" => $server->name
			));
			return;
		}
		$additional_locales = array();
		foreach ($update_locales as $update_locale) {
			$dialect = Locale::dialect($update_locale);
			if (!$dialect) {
				$expanded_locales = Object::class_query('PolyGlot_Token')->distinct(true)->what("*locale", "CONCAT(language,'_',dialect)")->where(array(
					"language" => $update_locale,
					array(
						"dialect|!=" => array(
							NULL,
							""
						)
					)
				))->to_array(null, "locale");
				$additional_locales = array_merge($additional_locales, $expanded_locales);
			}
		}
		$update_locales = array_merge($update_locales, $additional_locales);
		$update_locales = array_unique($update_locales);
		log::warning("Updating locales {locales} on {server}", array(
			"locales" => $update_locales,
			"server" => $server->name
		));
		$success = true;
		foreach ($update_locales as $update_locale) {
			$update_locale = Locale::normalize($update_locale);
			try {
				self::update_locale($path, $update_locale);
			} catch (Exception $e) {
				log::error("Updating locale {locale} on {server} FAILED due to {message}", array(
					"locales" => $update_locales,
					"server" => $server->name,
					"message" => $e->getMessage()
				));
				$success = false;
			}
		}
		if ($success) {
			$server->data($server_variable_name, time());
		}
	}

	private static function write_translation_line($f, $filename, $original, $translation) {
		if (!fwrite($f, '$tt[' . php::dump($original) . "] = " . php::dump($translation) . ";\n")) {
			throw new Exception_FileSystem($filename, "Unable to write to {filename}");
		}
	}

	public static function update_locale($path, $locale) {
		$iterator = PolyGlot_Token::locale_query($locale)->where("status", PolyGlot_Token::status_done)->order_by("updated DESC,original")->iterator("original", "translation");

		$target_file = path($path, "$locale.inc");

		$pid = zesk::pid();
		$temp_file = path($path, "$locale.inc.$pid");
		$f = fopen($temp_file, "w");
		if (!$f) {
			throw new Exception_File_Permission($temp_file, "Unable to open {filename} for writing");
		}
		fwrite($f, "<?php\n\n// This file was automatically generated on " . date("Y-m-d H:i:s") . " by " . __METHOD__ . "\n\n");
		$original_tt = array();
		if (file_exists($target_file)) {
			$original_tt = zesk::load($target_file);
		}
		if (!is_array($original_tt)) {
			$original_tt = array();
		}
		fwrite($f, "// Database translations\n");
		$written = array();
		try {
			foreach ($iterator as $original => $translation) {
				if (array_key_exists($original, $written)) {
					continue;
				}
				self::write_translation_line($f, $temp_file, $original, $translation);
				$written[$original] = true;
				unset($original_tt[$original]);
			}
			fwrite($f, "\n// Original file translations\n");
			foreach ($original_tt as $original => $translation) {
				self::write_translation_line($f, $temp_file, $original, $translation);
			}
		} catch (Exception $e) {
			file::unlink($temp_file);
			throw $e;
		}
		fwrite($f, "\nreturn \$tt;\n");
		file::move_atomic($temp_file, $target_file);
		file::unlink($temp_file);
		return true;
	}
}