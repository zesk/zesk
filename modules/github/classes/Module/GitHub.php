<?php
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
	const API_ENDPOINT_RELEASE = "https://api.github.com/repos/{owner}/{repository}/releases?access_token={access_token}";
	
	/**
	 * 
	 * @param array $settings
	 * @return array
	 */
	public function hook_version_updated(array $settings) {
		if (!$this->option_bool("tag-on-version-update")) {
			return;
		}
		$version = null;
		$previous_version = null;
		extract($settings, EXTR_IF_EXISTS);
		if ($version) {
			if (!$this->has_credentials()) {
				$this->application->logger->warning("{class} is not configured: need options owner, repository, and access_token to generate release for version {version}", array(
					"class" => get_class($this),
					"version" => $version
				));
				return $settings;
			}
			if (!$this->generate_tag($version)) {
				$this->application->logger->error("Unable to generate a tag for {version}", array(
					"version" => $version
				));
			} else {
				$this->application->logger->info("Generated {version} for {owner}/{repository}", array(
					"version" => $version
				) + $this->options);
			}
		}
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function has_credentials() {
		return $this->has_option("owner") && $this->has_option("repository") && $this->has_option("access_token");
	}
	
	/**
	 * 
	 * @param unknown $version
	 * @return boolean
	 */
	public function generate_tag($version, $description = null) {
		$version_name = "v$version";
		if (!$description) {
			$description = "Release of version $version_name";
		}
		$json_struct = array(
			"tag_name" => $version_name,
			"target_commitish" => "master",
			"name" => $version_name,
			"body" => $description,
			"draft" => false,
			"prerelase" => false
		);
		$url = map(self::API_ENDPOINT_RELEASE, $this->options);
		$client = new Net_HTTP_Client($url);
		$client->method(Net_HTTP::Method_POST);
		$client->data(JSON::encode($json_struct));
		$client->request_header(Net_HTTP::request_Content_Type, MIME::TYPE_APPLICATION_JSON);
		$content = $client->go();
		if ($client->response_code_type() === 2) {
			$this->application->logger->info(JSON::encode_pretty(JSON::decode($content)));
			return true;
		}
		$this->application->logger->error("Error with request: {response_code} {response_message} {response_data}", $client->response_variables());
		return false;
	}
}
