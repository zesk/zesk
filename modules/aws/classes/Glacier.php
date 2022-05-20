<?php declare(strict_types=1);

/**
 * @package zesk-modules
 * @subpackage aws
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
namespace zesk\AWS;

use zesk\Hookable;
use zesk\Exception_NotFound;
use Aws\Glacier\GlacierClient;
use Aws\Glacier\Exception\GlacierException;

/**
 * Class wrapper around Aws\Glacier\GlacierClient
 *
 * Converts most AWS internal objects into PHP-friendly and platform neutral data types
 *
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
class Glacier extends Hookable {
	/**
	 *
	 * @var Aws\Glacier\GlacierClient
	 */
	protected $glacier_client = null;

	/**
	 * Lazy create client
	 */
	private function _init(): void {
		if (is_object($this->glacier_client)) {
			return;
		}
		$options = $this->options_include('key;secret;credentials;token;credentials;region');
		$this->glacier_client = GlacierClient::factory($options);
	}

	/**
	 * List vaults
	 *
	 * @return array of array
	 */
	public function vaults_list() {
		$this->_init();
		/* @var $response Guzzle\Service\Resource\Model */
		$response = $this->glacier_client->listVaults();
		$result = $response->get('VaultList');
		return $result;
	}

	/**
	 * Upload a file to a vault
	 *
	 * @param string $vault
	 * @param string $filename
	 * @return string Archive ID
	 */
	public function vault_store_file($vault, $filename) {
		$this->_init();
		$result = $this->glacier_client->uploadArchive([
			'vaultName' => $vault,
			'sourceFile' => $filename,
		]);
		$archiveId = $result->get('archiveId');
		return $archiveId;
	}

	public function vault_list($vault) {
		$this->_init();

		try {
			$result = $this->glacier_client->initiateJob([
				'vaultName' => $vault,
				'Type' => 'inventory-retrieval',
				'Format' => 'JSON',
				'Description' => "Listing of $vault",
			]);
			return [
				'job_id' => $result->get('jobId'),
				'uri' => $result->get('location'),
			];
		} catch (GlacierException $e) {
			throw new Exception_NotFound($e->getMessage());
		}
	}

	public function jobs_list($vault): void {
	}

	public function job_status($vault, $job_id) {
		$this->_init();
		$response = $this->glacier_client->describeJob([
			'vaultName' => $vault,
			'jobId' => $job_id,
		]);
		return $response->getAll();
	}

	public function vault_delete_file($vault, $archive_id) {
		$this->_init();
		$result = $this->glacier_client->deleteArchive([
			'vaultName' => $vault,
			'archiveId' => $archive_id,
		]);
		return true;
	}
}
