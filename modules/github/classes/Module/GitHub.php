<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 */
class Module_GitHub extends Module {
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
			if (!$this->has_credentials()) {
				$this->application->logger->warning('{class} is not configured: need options owner, repository, and access_token to generate release for version {version}', [
					'class' => get_class($this),
					'version' => $version,
				]);
				return $settings;
			}
			if (!$this->generate_tag("v$version", $commitish)) {
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
	 *
	 * @return boolean
	 */
	public function has_credentials() {
		return $this->hasOption('owner') && $this->hasOption('repository') && $this->hasOption('access_token');
	}

	/**
	 *
	 * @param unknown $version
	 * @return boolean
	 */
	public function generate_tag($name, $commitish = null, $description = null) {
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
			'prerelase' => false,
		];
		$missing = self::MISSING_TOKEN;
		$url = map(self::API_ENDPOINT_RELEASE, $this->options + ['owner' => $missing, 'repository' => $missing, 'access_token' => $missing]);
		$client = new Net_HTTP_Client($this->application, $url);
		$client->method(Net_HTTP::METHOD_POST);
		$client->data(JSON::encode($json_struct));
		$client->request_header(Net_HTTP::REQUEST_CONTENT_TYPE, MIME::TYPE_APPLICATION_JSON);
		$content = $client->go();
		if ($client->response_code_type() === 2) {
			$this->application->logger->info(JSON::encode_pretty(JSON::decode($content)));
			return true;
		}
		$this->application->logger->error('Error with request: {response_code} {response_message} {response_data}', $client->response_variables());
		return false;
	}
}
