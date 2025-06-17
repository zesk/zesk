<?php
declare(strict_types=1);
/**
 * @author     kent
 * @package    zesk/modules
 * @subpackage Polyglot
 * @copyright  &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Polyglot;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\QueryBuilder;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Doctrine\Event\PrePersist;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\User;
use zesk\Exception\ConfigurationException;
use zesk\Locale\Locale;
use zesk\Locale\Validate;
use zesk\Timestamp;
use zesk\JSONWalker;

#[Entity]
#[EntityListeners([ModelListener::class])]
#[Index('languageAndDialect', [
	'language',
	'dialect',
	'hash',
	'user',
	'context',
])]
#[Index('user', [
	'context',
	'user',
])]
#[Index('indexOriginal', ['original'])]
#[Index('indexTranslation', ['translation'])]
class Token extends Model implements PrePersist
{
	use AutoID;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $language;

	#[Column(type: 'string', length: 2, nullable: false)]
	public string $dialect;

	#[Column(type: 'string', length: 32, nullable: false)]
	public string $hash;

	#[Column(type: 'text', nullable: false)]
	public string $original;

	#[Column(type: 'text', nullable: false)]
	public string $translation;

	#[Column(type: 'string', length: 64, nullable: false)]
	public string $context = '';

	/**
	 * User who translated this
	 *
	 * @var User|null
	 */
	#[ManyToOne]
	#[JoinColumn(name: 'user', nullable: true)]
	protected null|User $user = null;

	#[Column(type: 'string', length: 16, nullable: false)]
	public string $status;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $updated;

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

	public function __construct(Application $application, string $language, string $dialect, string $original, string $translation, string $status = '', array $options = [])
	{
		parent::__construct($application, $options);
		$this->language = $language;
		$this->dialect = $dialect;
		$this->original = $original;
		$this->translation = $translation;
		$this->status = ($status === '') ? self::STATUS_TODO : $status;
		$this->validator = new Validate($this->application);
	}

	public const HOOK_CONTEXT_DEFAULT = __CLASS__ . '::contextDefault';

	/**
	 * @see ModelListener
	 */
	public function prePersist(PrePersistEventArgs $args): void
	{
		$this->hash = md5($this->original);
		$this->user = $this->application->invokeFilters(Application::HOOK_USER, null, [$this->application]);
		if (!$this->user) {
			try {
				$request = $this->application->request();
				$this->user = $this->application->user($request);
			} catch (Throwable) {
			}
		}
		if (!$this->context) {
			$this->context = $this->invokeFilters(self::HOOK_CONTEXT_DEFAULT, '', [$this]);
		}
		if (!$this->status) {
			$this->status = self::STATUS_TODO;
		}
		if ($this->status === self::STATUS_DELETE) {
			$this->dialect = '';
			$this->language = '';
		}
	}

	public static function factory(Application $app, string $language, string $dialect, string $original, string $translation, string $status = ''): self
	{
		return new self($app, $language, $dialect, $original, $translation, $status);
	}

	/**
	 * Fetch all locale strings for the dialect/language
	 *
	 * @param Application $app
	 * @param string      $language
	 * @param string      $dialect
	 * @return array
	 */
	public static function fetchAll(Application $app, string $language, string $dialect = ''): array
	{
		$where = [
			'language' => $language,
			'dialect'  => $dialect === '' ? null : $dialect,
		];
		$em = $app->entityManager();
		$query = $em->getRepository(self::class)->findBy($where, [
			'updated'  => 'ASC',
			'original' => 'ASC',
		]);
		$result = [];
		foreach ($query as $entity) {
			$result[$entity->original] = $entity;
		}
		if ($dialect === '') {
			return $result;
		}
		return $result + self::fetchAll($app, $language);
	}

	public function json(JSONWalker $options): array
	{
		$members = ArrayTools::filter($this->members(), [
			'id',
			'language',
			'dialect',
			'original',
			'translation',
			'status',
		]);
		$members['user'] = $this->user->id;
		return $members;
	}

	/**
	 *
	 * @param Application $application
	 * @param string      $locale
	 * @return QueryBuilder
	 */
	public static function localeQuery(Application $application, string $locale): QueryBuilder
	{
		$ex = Criteria::expr();
		$qb = $application->entityManager()->getRepository(self::class)->createQueryBuilder('q');
		$qb->select('q.*')->where($ex->eq('q.language', $qb->createNamedParameter(Locale::parseLanguage($locale))));
		$qb->andWhere($ex->eq('q.dialect', $qb->createNamedParameter(Locale::parseDialect($locale))));
		return $qb;
	}

	/**
	 * @return string[]
	 */
	public static function statusFilters_EN(): array
	{
		return [
			self::STATUS_TODO   => 'Need translation',
			self::STATUS_INFO   => 'Need more information',
			self::STATUS_DEV    => 'Need developer review',
			self::STATUS_DRAFT  => 'Draft',
			self::STATUS_DELETE => 'Deleted',
			self::STATUS_DONE   => 'Translated',
		];
	}

	/**
	 * Used to convert HTML entities in translations to encoded entities.
	 * Leave it here for now in case it's relevant in the future.
	 *
	 * @param Application $app
	 * @throws ORMException
	 */
	public static function fixTranslations(Application $app): void
	{
		$em = $app->entityManager();
		foreach ($em->getRepository(self::class)->findAll() as $token) {
			/* @var $token self */
			$translation = $token->translation;
			$entities = htmlentities($translation);
			if ($entities !== $translation) {
				$token->translation = $entities;
				$em->persist($token);
			}
			$app->debug('Updated #{id} {translation} to {entities}', [
				'id'          => $token->id,
				'translation' => $translation,
				'entities'    => $entities,
			]);
		}
		$em->flush();
	}

	/**
	 *
	 * @return array
	 * @throws ConfigurationException
	 */
	public function validate(): array
	{
		return $this->validator->checkTranslation($this->original, $this->translation);
	}
}
