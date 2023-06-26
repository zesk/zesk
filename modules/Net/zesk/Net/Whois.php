<?php
declare(strict_types=1);
namespace zesk;

class Net_Whois {
	private static function clean_domain($domain) {
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

	public static function query($domain) {
		// fix the domain name:
		$domain = self::clean_domain($domain);
		$extension = StringTools::reverseRight($domain, '.');
		$server = Net_Whois_Servers::fromTLD($extension);
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
