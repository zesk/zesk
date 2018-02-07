<?php

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/polyglot/classes/polyglot/token.inc $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
namespace zesk;

use zesk\Locale\Validate;

/**
 *
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
class PolyGlot_Token extends ORM {

	/**
	 *
	 * @var Locale_Validate
	 */
	private $validator = null;
	/**
	 * Token to translate
	 *
	 * @var string
	 */
	const status_todo = "todo";

	/**
	 * Draft version of a token translation
	 *
	 * @var string
	 */
	const status_draft = "draft";

	/**
	 * Developer needs to review
	 *
	 * @var string
	 */
	const status_dev = "dev";

	/**
	 * Question about translation
	 *
	 * @var string
	 */
	const status_question = "question";

	/**
	 * Done
	 *
	 * @var string
	 */
	const status_done = "done";

	/**
	 * Delete
	 *
	 * @var string
	 */
	const status_delete = "delete";

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see ORM::store()
	 */
	public function store() {
		$this->md5 = md5($this->original);
		if ($this->member_is_empty("user")) {
			$request = $this->application->request();
			if ($request) {
				$this->user = $this->application->user($request);
			}
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
	public static function create(Application $app, $language, $dialect, $original, $translation, $status = null) {
		$token = $app->orm_factory(__CLASS__, array(
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
	public static function fetch_all(Application $app, $language, $dialect = null) {
		$where = array(
			"language" => $language,
			"dialect" => $dialect
		);
		$query = $app->orm_registry(__CLASS__)->query_select();
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
		$result = $query->orm_iterator()->to_array("original");
		if ($dialect === null) {
			return $result;
		}
		return $result + self::fetch_all($app, $language, null);
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
	 * @return Database_Query_Select
	 */
	public static function locale_query(Application $application, $locale) {
		return $application->orm_registry(__CLASS__)
			->query_select()
			->what_object()
			->where(array(
			"dialect" => Locale::prase_dialect($locale),
			"language" => Locale::parse_language($locale)
		));
	}

	/**
	 *
	 * @return string
	 */
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

	/**
	 *
	 * @param Application $app
	 */
	public static function htmlentities_all(Application $app) {
		$iterator = $app->orm_registry(__CLASS__)
			->query_select()
			->what(array(
			"id" => "id",
			"translation" => "translation"
		))
			->iterator("id", "translation");
		foreach ($iterator as $id => $translation) {
			$entities = htmlentities($translation);
			if ($entities !== $translation) {
				$app->orm_registry(__CLASS__)
					->query_update()
					->value("translation", $entities)
					->where("id", $id)
					->execute();
				$app->logger->debug("Updated #{id} {translation} to {entities}", compact("id", "translation", "entities"));
			}
		}
	}

	/**
	 *
	 * @return Validate
	 */
	private function validator() {
		if ($this->validator === null) {
			$this->validator = new Validate($this->application);
		}
		return $this->validator;
	}

	/**
	 *
	 * @return array
	 */
	public function validate() {
		return $this->validator()->check_translation($this->original, $this->translation);
	}
}
