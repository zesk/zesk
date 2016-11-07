<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/polyglot/classes/polyglot/token.inc $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
use zesk\Locale;
use zesk\Database_Query_Select;

/**
 * @author kent
 *
 * @see Class_PolyGlot_Token
 * @property id $id
 * @property string $language
 * @property string $dialect
 * @property hex $md5
 * @property string $original
 * @property string $translation
 * @property User $user
 * @property string $status
 * @property timestamp $updated
 */
class PolyGlot_Token extends Object {
	
	/**
	 *
	 * @var zesk\Locale_Validate
	 */
	private $validator = null;
	const status_todo = "todo";
	const status_draft = "draft";
	const status_dev = "dev";
	const status_question = "question";
	const status_done = "done";
	const status_delete = "delete";
	public function store() {
		$this->md5 = md5($this->original);
		if ($this->member_is_empty("user")) {
			$this->user = User::instance();
		}
		if ($this->member_is_empty("context")) {
			$this->context = $this->call_hook("context_default");
		}
		if ($this->member_is_empty("status")) {
			$this->status = self::status_todo;
		}
		if ($this->status === self::status_delete) {
			$this->dialect = null;
			$this->language = null;
		}
		$result = parent::store();
		if ($result && $this->status === self::status_delete) {
			$this->query_delete()->where(array(
				"*md5" => "UNHEX('$this->md5')",
				"language|!=" => array(
					null,
					""
				)
			))->execute();
		}
		return $result;
	}
	public static function create($language, $dialect, $original, $translation, $status = null) {
		$token = Object::factory(__CLASS__, array(
			"language" => $language,
			"dialect" => $dialect,
			"original" => $original,
			"translation" => $translation
		));
		$token->status = ($status === null) ? self::status_todo : $status;
		return $token;
	}
	
	/**
	 * Fetch all locale strings for the dialect/language
	 * 
	 * @param string $language
	 * @param string|null $dialect
	 * @return rray
	 */
	public static function fetch_all($language, $dialect = null) {
		$where = array(
			"language" => $language,
			"dialect" => $dialect
		);
		$query = Object::class_query(__CLASS__);
		$where = array(
			array(
				$where,
				array(
					"status" => self::status_delete
				)
			)
		);
		$query->where($where);
		$query->distinct(true);
		$query->what_object();
		$query->order_by("updated ASC,original");
		$result = $query->object_iterator()->to_array("original");
		if ($dialect === null) {
			return $result;
		}
		return $result + self::fetch_all($language, null);
	}
	public function to_json() {
		$members = $this->members(array(
			"id",
			"language",
			"dialect",
			"original",
			"translation",
			"status"
		));
		$members['user'] = $this->member_integer("user");
		return $members;
	}
	
	/**
	 *
	 * @param string $locale
	 * @return zesk\Database_Query_Select
	 */
	public static function locale_query($locale) {
		return Object::class_query(__CLASS__)->what_object()->where(array(
			"dialect" => Locale::dialect($locale),
			"language" => Locale::language($locale)
		));
	}
	public static function lang_status_filters() {
		return __(array(
			"todo" => "Need translation",
			"question" => "Need more information",
			"dev" => "Need developer review",
			"draft" => "Draft",
			"delete" => "Deleted",
			"done" => "Translated"
		));
	}
	public static function htmlentities_all() {
		$iterator = Object::class_query(__CLASS__)->what(array(
			"id" => "id",
			"translation" => "translation"
		))->iterator("id", "translation");
		foreach ($iterator as $id => $translation) {
			$entities = htmlentities($translation);
			if ($entities !== $translation) {
				Object::class_query_update(__CLASS__)->value("translation", $entities)->where("id", $id)->execute();
				log::debug("Updated #{id} {translation} to {entities}", compact("id", "translation", "entities"));
			}
		}
	}
	
	/**
	 *
	 * @return zesk\Locale_Validate
	 */
	private function validator() {
		if ($this->validator === null) {
			$this->validator = new zesk\Locale_Validate();
		}
		return $this->validator;
	}
	public function validate() {
		return $this->validator()->check_translation($this->original, $this->translation);
	}
}
