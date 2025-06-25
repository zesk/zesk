<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net;

use zesk\Exception\ConnectionFailed;
use zesk\Exception\NotFoundException;
use zesk\Net\Whois\Servers;
use zesk\StringTools;

class Whois
{
	private static function cleanDomain(string $domain): string
	{
		$domain = strtolower(trim($domain));
		$domain = StringTools::removePrefix($domain, [
			'http://',
			'https://',
		]);
		$domain = StringTools::removePrefix($domain, [
			'www.',
		]);
		[$domain] = explode('/', $domain, 2);
		return $domain;
	}

	/**
	 * @param string $domain
	 * @return string
	 * @throws ConnectionFailed
	 * @throws NotFoundException
	 */
	public static function query(string $domain): string
	{
		// fix the domain name:
		$domain = self::cleanDomain($domain);
		$extension = StringTools::reverseRight($domain, '.');
		$server = Servers::fromTLD($extension);
		if (!$server) {
			throw new NotFoundException('No whois server for {extension}', [
				'extension' => $extension,
			]);
		}

		$conn = fsockopen($server, 43);
		if (!$conn) {
			throw new ConnectionFailed($server);
		}
		$result = '';
		fwrite($conn, $domain . "\r\n");
		while (!feof($conn)) {
			$result .= fgets($conn, 128);
		}
		fclose($conn);
		return $result;
	}
}
