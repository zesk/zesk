<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use zesk\Application;
use zesk\Application\Paths;
use zesk\ArrayTools;
use zesk\Cron\Attributes\Cron;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\File;
use zesk\Net\Sync;
use zesk\Text;
use zesk\URL;

/**
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
class Domain extends Model
{
	use AutoID;

	#[Column(type: 'string')]
	public string $name;

	#[Column(type: 'string', length: 8)]
	public string $tld;

	/**
	 * @see http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/
	 * @var string
	 */
	public const DEFAULT_PUBLIC_SUFFIX_LIST = "com\norg\nedu\ngov\nuk\nnet\nca\nde\njp\nfr\nau\nus\nru\nch\nit\nnl\nse\nno\nes\nmil";

	/**
	 *
	 * @todo credit
	 * @var string
	 */
	public const URL_TOP_LEVEL_DOMAINS = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';

	/**
	 *
	 * @var array
	 */
	private static array $publicTopLevelDomains = [];

	/**
	 *
	 * @param Application $application
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 */
	#[Cron(schedule: '0 * * * *', scope: Cron::SCOPE_APPLICATION)]
	public static function cronUpdateDomains(Application $application): void
	{
		$urlPublic = $application->environment()->get(self::URL_PUBLIC_DOMAIN_SUFFIX);
		foreach ([
			$urlPublic => self::publicSuffixListFile($application),
			self::URL_TOP_LEVEL_DOMAINS => self::topLevelDomainsFile($application->paths),
		] as $url => $path) {
			try {
				Sync::urlToFile($application, $url, $path);
			} catch (FilePermission|DirectoryNotFound $e) {
				$application->error($e);
			}
		}
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	public function hook_schema_updated(): void
	{
		self::updateDataFiles($this->application);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $name
	 * @return Domain
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	public static function domainFactory(Application $application, string $name): self
	{
		$domain = new Domain($application);
		$domain->name = $name;
		return $domain->nameChanged();
	}

	/**
	 * Compute the TLD for a domain
	 *
	 * @return $this
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	protected function nameChanged(): self
	{
		$this->tld = $this->computeTLD();
		return $this;
	}

	/**
	 * @return self
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws ORMException
	 * @throws SemanticsException
	 */
	public function store(): self
	{
		if (!$this->tld) {
			$this->tld = $this->computeTLD();
		}
		return parent::store();
	}

	/**
	 *
	 * @return string
	 * @return string
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	public function computeCookieDomain(): string
	{
		$domains = $this->_lazyLoadTLDs();
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$last = null;
		while (count($x) >= 3) {
			$last = strrev(array_pop($x));
		}
		$default = strrev(implode('.', $x));
		do {
			$try = strrev(implode('.', $x));
			if (isset($domains[$try])) {
				return "$last.$try";
			}
			$last = strrev(array_pop($x));
		} while (count($x) > 0);
		return $default;
	}

	/**
	 * @return array
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	private function _lazyLoadTLDs(): array
	{
		if (!self::$publicTopLevelDomains) {
			self::$publicTopLevelDomains = $this->loadPublicTLDs($this->application);
		}
		return self::$publicTopLevelDomains;
	}

	/**
	 *
	 * @return string
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	public function computeTLD(): string
	{
		$domains = $this->_lazyLoadTLDs();
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$default = strrev($x[0]);
		while (count($x) >= 2) {
			array_pop($x);
		}
		do {
			$try = strrev(implode('.', $x));
			if (isset($domains[$try])) {
				return "$try";
			}
			array_pop($x);
		} while (count($x) > 0);
		return $default;
	}

	public const URL_PUBLIC_DOMAIN_SUFFIX = 'URL_PUBLIC_DOMAIN_SUFFIX';

	/**
	 * @param Application $application
	 * @return array
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 */
	private static function urlsToUpdate(Application $application): array
	{
		$urlPublic = $application->environment()->get(self::URL_PUBLIC_DOMAIN_SUFFIX);
		return [
			$urlPublic => self::publicSuffixListFile($application),
			self::URL_TOP_LEVEL_DOMAINS => self::topLevelDomainsFile($application->paths),
		];
	}

	/**
	 * @param Application $application
	 * @return string
	 * @return string
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 */
	private static function publicSuffixListFile(Application $application): string
	{
		$domainSuffix = $application->environment()->get(self::URL_PUBLIC_DOMAIN_SUFFIX);

		try {
			$parts = URL::parse($domainSuffix);
			$file = basename($parts['path']);
			if (empty($file)) {
				throw new ConfigurationException(self::URL_PUBLIC_DOMAIN_SUFFIX, "URL_PUBLIC_DOMAIN_SUFFIX is missing file name: $domainSuffix");
			}
		} catch (SyntaxException $e) {
			throw new ConfigurationException(self::URL_PUBLIC_DOMAIN_SUFFIX, "URL_PUBLIC_DOMAIN_SUFFIX is not a valid URL: $domainSuffix");
		}
		return $application->paths->zesk('etc/db/' . $file);
	}

	/**
	 * @param Paths $paths
	 * @return string
	 */
	private static function topLevelDomainsFile(Paths $paths): string
	{
		return $paths->zesk('etc/db/tlds.txt');
	}

	/**
	 * Update our data files from our remote URLs
	 *
	 * @param Application $application
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	private static function updateDataFiles(Application $application): void
	{
		foreach (self::urlsToUpdate($application) as $url => $path) {
			Sync::urlToFile($application, $url, $path);
		}
	}

	/**
	 * Load the public TLDs from the file
	 *
	 * @param Application $application
	 * @return array
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws SemanticsException
	 */
	private static function loadPublicTLDs(Application $application): array
	{
		$contents = strtolower(File::contents(self::publicSuffixListFile($application)));
		$topDomainSuffixList = ArrayTools::listTrimClean(explode("\n", Text::removeLineComments($contents, '//')));
		return array_change_key_case(ArrayTools::valuesFlipCopy($topDomainSuffixList));
	}
}
