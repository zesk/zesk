<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage GitHub
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\GitHub;

use zesk\Exception_DomainLookup;
use zesk\Exception_File_Permission;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
use zesk\Exception_Protocol;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception_Unsupported;
use zesk\HTTP;
use zesk\JSON;
use zesk\MIME;
use zesk\Net\HTTP\Client as Net_HTTP_Client;
use zesk\Module as BaseModule;

/**
 *
 */
class Module extends BaseModule {
	/**
	 * @see https://developer.github.com/v3/repos/releases/#create-a-release
	 * @var string
	 */
	public const API_ENDPOINT_RELEASE = 'https://api.github.com/repos/{owner}/{repository}/releases?access_token={access_token}';

	public const MISSING_TOKEN = '*MISSING*OPTION*';

	/**
	 *
	 * @param array $settings
	 * @return array
	 */
	public function hook_version_updated(array $settings) {
		if (!$this->optionBool('tag-on-version-update')) {
			return $settings;
		}
		$version = null;
		$previous_version = null;
		$commitish = null;
		extract($settings, EXTR_IF_EXISTS);
		if (!$commitish) {
			$commitish = $this->option('commitish');
		}
		if ($version) {
			if (!$this->hasCredentials()) {
				$this->application->logger->warning('{class} is not configured: need options owner, repository, and access_token to generate release for version {version}', [
					'class' => get_class($this),
					'version' => $version,
				]);
				return $settings;
			}
			if (!$this->generateTag("v$version", $commitish)) {
				$this->application->logger->error('Unable to generate a tag for {version}', [
					'version' => $version,
				]);
			} else {
				$this->application->logger->info('Generated {version} for {owner}/{repository}', [
					'version' => $version,
				] + $this->options);
			}
		}
		return $settings;
	}

	/**
	 * Do we have credentials for GitHub?
	 *
	 * @return bool
	 */
	public function hasCredentials(): bool {
		return $this->hasOption('owner') && $this->hasOption('repository') && $this->hasOption('access_token');
	}

	/**
	 *
	 * @param string $name
	 * @param string $commitish
	 * @param string $description
	 * @return array
	 * @throws Net_HTTP_Client\Exception
	 * @throws Exception_DomainLookup
	 * @throws Exception_File_Permission
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Syntax
	 * @throws Exception_Unsupported
	 * @throws Exception_Protocol
	 */
	public function generateTag(string $name, string $commitish = '', string $description = ''): array {
		if (!$description) {
			$description = "Release of version $name";
		}
		if (!$commitish) {
			$commitish = 'master';
		}
		$json_struct = [
			'tag_name' => $name,
			'target_commitish' => $commitish,
			'name' => $name,
			'body' => $description,
			'draft' => false,
			'prerelease' => false,
		];
		$missing = self::MISSING_TOKEN;
		$url = map(self::API_ENDPOINT_RELEASE, $this->options + ['owner' => $missing, 'repository' => $missing, 'access_token' => $missing]);
		$client = new Net_HTTP_Client($this->application, $url);
		$client->setMethod(HTTP::METHOD_POST);
		$client->setData(JSON::encode($json_struct));
		$client->setRequestHeader(HTTP::REQUEST_CONTENT_TYPE, MIME::TYPE_APPLICATION_JSON);
		$content = $client->go();
		if ($client->response_code_type() === 2) {
			return JSON::decode($content);
		}

		throw new Exception_Protocol('Error with request: {response_code} {response_message} {response_data}', $client->responseVariables());
	}
}
