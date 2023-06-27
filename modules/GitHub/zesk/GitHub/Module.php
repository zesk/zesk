<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage GitHub
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\GitHub;

use Throwable;
use zesk\ArrayTools;
use zesk\Command\Version;
use zesk\Exception;
use zesk\Exception\FilePermission;
use zesk\Exception\DomainLookupFailed;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\UnsupportedException;
use zesk\HookMethod;
use zesk\HTTP;
use zesk\JSON;
use zesk\MIME;
use zesk\Module as BaseModule;
use zesk\Net\HTTP\Client;

/**
 *
 */
class Module extends BaseModule
{
	/**
	 * @see https://developer.github.com/v3/repos/releases/#create-a-release
	 * @var string
	 */
	public const API_ENDPOINT_RELEASE = 'https://api.github.com/repos/{owner}/{repository}/releases';

	public const MISSING_TOKEN = '*MISSING*OPTION*';

	/**
	 * @param array $settings
	 * @return void
	 * @see self::versionWasUpdated()
	 */
	#[HookMethod(handles: Version::HOOK_UPDATED)]
	public function versionWasUpdated(array $settings): void
	{
		if (!$this->optionBool('tagOn')) {
			return;
		}
		$logger = $this->application->logger();
		extract($settings, EXTR_IF_EXISTS);
		$version = $settings['version'] ?? null;
		$commitish = $settings['commitish'] ?? null;
		if (!$commitish) {
			$commitish = $this->optionString('commitish');
		}
		if ($version) {
			if (!$this->hasCredentials()) {
				$logger->warning('{class} is not configured: need options owner, repository, and access_token to generate release for version {version}', [
					'class' => get_class($this), 'version' => $version,
				]);
				return;
			}

			try {
				$result = $this->generateTag("v$version", $commitish);
				$logger->info('Generated {version} for {owner}/{repository}: {result}', [
					'version' => $version, 'result' => $result,
				] + $this->options());
			} catch (Throwable $t) {
				$logger->error('Unable to generate a tag for {version}: {throwableClass} {message}', [
					'version' => $version,
				] + Exception::exceptionVariables($t));
			}
		}
	}

	/**
	 * Do we have credentials for GitHub?
	 *
	 * @return bool
	 */
	public function hasCredentials(): bool
	{
		return $this->hasOption('owner') && $this->hasOption('repository') && $this->hasOption('access_token');
	}

	/**
	 *
	 * @param string $name
	 * @param string $commitish
	 * @param string $description
	 * @return array
	 * @throws Client\Exception
	 * @throws DomainLookupFailed
	 * @throws FilePermission
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @throws UnsupportedException
	 */
	public function generateTag(string $name, string $commitish = '', string $description = ''): array
	{
		if (!$description) {
			$description = "Release of version $name";
		}
		$json_struct = [
			'tag_name' => $name, 'name' => $name, 'body' => $description, 'draft' => false, 'prerelease' => false,
			'generate_release_notes' => false,
		];
		if ($commitish) {
			$json_struct['target_commitish'] = $commitish;
		}
		$missing = self::MISSING_TOKEN;
		$options = $this->options() + [
			'owner' => $missing, 'repository' => $missing, 'accessToken' => $missing,
		];
		$url = ArrayTools::map(self::API_ENDPOINT_RELEASE, $options);
		$client = new Client($this->application, $url);
		$client->setRequestHeader('Authorization', ArrayTools::map('token {accessToken}', $options));
		$client->setMethod(HTTP::METHOD_POST);
		$client->setData(JSON::encode($json_struct));
		$client->setRequestHeader(HTTP::REQUEST_CONTENT_TYPE, MIME::TYPE_APPLICATION_JSON);
		$content = $client->go();
		if ($client->response_code_type() === 2) {
			return JSON::decode($content);
		}

		throw new SemanticsException('Error with request: {responseCode} {responseMessage} {responseData}', $client->responseVariables());
	}
}
