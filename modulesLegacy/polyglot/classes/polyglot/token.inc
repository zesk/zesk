<?php declare(strict_types=1);

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/polyglot/classes/polyglot/token.inc $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
namespace zesk;

use Validate;
use zesk\ORM\JSONWalker;

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
class PolyGlot_Token extends ORMBase {
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
	public const status_todo = 'todo';

	/**
	 * Draft version of a token translation
	 *
	 * @var string
	 */
	public const status_draft = 'draft';

	/**
	 * Developer needs to review
	 *
	 * @var string
	 */
	public const status_dev = 'dev';

	/**
	 * Need more information
	 *
	 * @var string
	 */
	public const status_info = 'info';

	/**
	 * Done
	 *
	 * @var string
	 */
	public const status_done = 'done';

	/**
	 * Delete
	 *
	 * @var string
	 */
	public const status_delete = 'delete';

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see ORMBase::store()
	 */
	public function store(): self {
		$this->md5 = md5($this->original);
		if ($this->memberIsEmpty('user')) {
			$request = $this->application->request();
			if ($request) {
				$this->user = $this->application->user($request);
			}
		}
		if ($this->memberIsEmpty('context')) {
			$this->context = $this->callHook('context_default');
		}
		if ($this->memberIsEmpty('status')) {
			$this->status = self::status_todo;
		}
		if ($this->status === self::status_delete) {
			$this->dialect = null;
			$this->language = null;
		}
		$result = parent::store();
		if ($result && $this->status === self::status_delete) {
			$this->query_delete()->where([
				'*md5' => "UNHEX('$this->md5')",
				'language|!=' => [
					null,
					'',
				],
			])->execute();
		}
		return $result;
	}

	public static function create(Application $app, $language, $dialect, $original, $translation, $status = null) {
		$token = $app->ormFactory(__CLASS__, [
			'language' => $language,
			'dialect' => $dialect,
			'original' => $original,
			'translation' => $translation,
		]);
		$token->status = ($status === null) ? self::status_todo : $status;
		return $token;
	}

	/**
	 * Fetch all locale strings for the dialect/language
	 *
	 * @param string $language
	 * @param string|null $dialect
	 * @return array
	 */
	public static function fetch_all(Application $app, $language, $dialect = null) {
		$where = [
			'language' => $language,
			'dialect' => $dialect,
		];
		$query = $app->ormRegistry(__CLASS__)->querySelect();
		$where = [
			[
				$where,
				[
					'status' => self::status_delete,
				],
			],
		];
		$query->where($where);
		$query->setDistinct(true);
		$query->ormWhat();
		$query->order_by('updated ASC,original');
		$result = $query->ormIterator()->to_array('original');
		if ($dialect === null) {
			return $result;
		}
		return $result + self::fetch_all($app, $language, null);
	}

	public function json(JSONWalker $walker) {
		$members = $this->members([
			'id',
			'language',
			'dialect',
			'original',
			'translation',
			'status',
		]);
		$members['user'] = $this->memberInteger('user');
		return $members;
	}

	/**
	 *
	 * @param string $locale
	 * @return Database_Query_Select
	 */
	public static function locale_query(Application $application, $locale) {
		return $application->ormRegistry(__CLASS__)
			->querySelect()
			->ormWhat()
			->where([
				'dialect' => Locale::parse_dialect($locale),
				'language' => Locale::parse_language($locale),
			]);
	}

	/**
	 *
	 * @return string
	 */
	public static function lang_status_filters(Locale $locale) {
		return $locale->__([
			'todo' => 'Need translation',
			'info' => 'Need more information',
			'dev' => 'Need developer review',
			'draft' => 'Draft',
			'delete' => 'Deleted',
			'done' => 'Translated',
		]);
	}

	/**
	 *
	 * @param Application $app
	 */
	public static function htmlentities_all(Application $app): void {
		$iterator = $app->ormRegistry(__CLASS__)
			->querySelect()
			->addWhat([
				'id' => 'id',
				'translation' => 'translation',
			])
			->iterator('id', 'translation');
		foreach ($iterator as $id => $translation) {
			$entities = htmlentities($translation);
			if ($entities !== $translation) {
				$app->ormRegistry(__CLASS__)
					->queryUpdate()
					->value('translation', $entities)
					->addWhere('id', $id)
					->execute();
				$app->logger->debug('Updated #{id} {translation} to {entities}', compact('id', 'translation', 'entities'));
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
	public function validate(): bool {
		return $this->validator()->check_translation($this->original, $this->translation);
	}
}
