<?php declare(strict_types=1);
/**
 * @author kent
 * @package zesk/modules
 * @subpackage Polyglot
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Polyglot;

use zesk\Application;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_NoResults;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Locale;
use zesk\Locale\Validate;
use zesk\ORM\Database_Query_Select;
use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\JSONWalker;
use zesk\ORM\ORMBase;
use zesk\ORM\User;
use zesk\Timestamp;

/**
 *
 * @author kent
 *
 * @see Class_Token
 * @property int $id
 * @property string $language
 * @property string $dialect
 * @property string $md5
 * @property string $original
 * @property string $translation
 * @property string $context
 * @property User $user
 * @property string $status
 * @property Timestamp $updated
 */
class Token extends ORMBase {
	/**
	 *
	 * @var Validate
	 */
	private Validate $validator;

	/**
	 * Token to translate
	 *
	 * @var string
	 */
	public const STATUS_TODO = 'todo';

	/**
	 * Draft version of a token translation
	 *
	 * @var string
	 */
	public const STATUS_DRAFT = 'draft';

	/**
	 * Developer needs to review
	 *
	 * @var string
	 */
	public const STATUS_DEV = 'dev';

	/**
	 * Need more information
	 *
	 * @var string
	 */
	public const STATUS_INFO = 'info';

	/**
	 * Done
	 *
	 * @var string
	 */
	public const STATUS_DONE = 'done';

	/**
	 * Delete
	 *
	 * @var string
	 */
	public const STATUS_DELETE = 'delete';

	/**
	 * @return void
	 */
	protected function constructed(): void {
		parent::constructed();
		$this->validator = new Validate($this->application);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see ORMBase::store()
	 */
	public function store(): self {
		$this->md5 = md5($this->original);
		if ($this->memberIsEmpty('user')) {
			try {
				$request = $this->application->request();
				$this->user = $this->application->user($request);
			} catch (Exception_Semantics) {
			}
		}
		if ($this->memberIsEmpty('context')) {
			$this->context = $this->callHook('contextDefault');
		}
		if ($this->memberIsEmpty('status')) {
			$this->status = self::STATUS_TODO;
		}
		if ($this->status === self::STATUS_DELETE) {
			$this->dialect = null;
			$this->language = null;
		}
		$result = parent::store();
		if ($this->status === self::STATUS_DELETE) {
			$this->queryDelete()->appendWhere([
				'*md5' => "UNHEX('$this->md5')",
				'language|!=' => [
					null,
					'',
				],
			])->execute();
		}
		return $result;
	}

	public static function create(Application $app, string $language, string $dialect, string $original, string $translation, string $status =
	'') {
		$token = $app->ormFactory(__CLASS__, [
			'language' => $language,
			'dialect' => $dialect,
			'original' => $original,
			'translation' => $translation,
		]);
		assert($token instanceof self);
		$token->status = ($status === '') ? self::STATUS_TODO : $status;
		return $token;
	}

	/**
	 * Fetch all locale strings for the dialect/language
	 *
	 * @param Application $app
	 * @param string $language
	 * @param string $dialect
	 * @return array
	 * @throws Exception_ORMNotFound
	 */
	public static function fetchAll(Application $app, string $language, string $dialect = ''): array {
		$where = [
			'language' => $language,
			'dialect' => $dialect === '' ? null : $dialect,
		];
		$query = $app->ormRegistry(__CLASS__)->querySelect();
		$where = [
			[
				$where,
				[
					'status' => self::STATUS_DELETE,
				],
			],
		];
		$query->appendWhere($where);
		$query->setDistinct();
		$query->ormWhat();
		$query->setOrderBy(['updated ASC', 'original']);
		$result = $query->ormIterator()->toArray('original');
		if ($dialect === '') {
			return $result;
		}
		return $result + self::fetchAll($app, $language);
	}

	public function json(JSONWalker $options): array {
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
	 * @param Application $application
	 * @param string $locale
	 * @return Database_Query_Select
	 * @throws Exception_ORMNotFound
	 */
	public static function localeQuery(Application $application, string $locale): Database_Query_Select {
		return $application->ormRegistry(__CLASS__)
			->querySelect()
			->ormWhat()
			->appendWhere([
				'dialect' => Locale::parse_dialect($locale),
				'language' => Locale::parse_language($locale),
			]);
	}

	/**
	 * @return string[]
	 */
	public static function statusFilters_EN(): array {
		return [
			self::STATUS_TODO => 'Need translation',
			self::STATUS_INFO => 'Need more information',
			self::STATUS_DEV => 'Need developer review',
			self::STATUS_DRAFT => 'Draft',
			self::STATUS_DELETE => 'Deleted',
			self::STATUS_DONE => 'Translated',
		];
	}

	/**
	 * Used to convert HTML entities in translations to encoded entities. Not sure why this was used but let's
	 * leave it here for now in case it's relevant in the future.
	 *
	 * @param Application $app
	 * @throws Exception_Semantics
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	public static function htmlentities_all(Application $app): void {
		$iterator = $app->ormRegistry(__CLASS__)
			->querySelect()
			->appendWhat([
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
				$app->logger->debug('Updated #{id} {translation} to {entities}', [
					'id' => $id,
					'translation' => $translation,
					'entities' => $entities,
				]);
			}
		}
	}

	/**
	 *
	 * @return array
	 * @throws Exception_Configuration
	 */
	public function validate(): array {
		return $this->validator->checkTranslation($this->original, $this->translation);
	}
}
