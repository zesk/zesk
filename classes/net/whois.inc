<?php

class Net_Whois {

	private static function clean_domain($domain) {
		$domain = strtolower(trim($domain));
		$domain = str::unprefix($domain, array(
			"http://",
			"https://"
		));
		$domain = str::unprefix($domain, array(
			"www."
		));
		list($domain) = explode('/', $domain, 2);
		return $domain;
	}

	public static function query($domain) {
		// fix the domain name:
		$domain = self::clean_domain($domain);
		$extension = str::rright($domain, ".");
		$server = Net_Whois_Servers::server_from_tld($extension);
		if (!$server) {
			throw new Exception_NotFound("No whois server for {extension}", array(
				"extension" => $extension
			));
		}

		$conn = fsockopen($server, 43);
		if (!$conn) {
			throw new Exception_Connect($server);
		}
		$result = '';
		fputs($conn, $domain . "\r\n");
		while (!feof($conn)) {
			$result .= fgets($conn, 128);
		}
		fclose($conn);
		return $result;
	}
}