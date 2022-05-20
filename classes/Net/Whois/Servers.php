<?php declare(strict_types=1);
namespace zesk;

class Net_Whois_Servers {
	private static $servers = [
		'biz' => 'whois.neulevel.biz',
		'com' => 'whois.internic.net',
		'us' => 'whois.nic.us',
		'coop' => 'whois.nic.coop',
		'info' => 'whois.nic.info',
		'name' => 'whois.nic.name',
		'net' => 'whois.internic.net',
		'gov' => 'whois.nic.gov',
		'edu' => 'whois.internic.net',
		'mil' => 'rs.internic.net',
		'int' => 'whois.iana.org',
		'ac' => 'whois.nic.ac',
		'ae' => 'whois.uaenic.ae',
		'at' => 'whois.ripe.net',
		'au' => 'whois.aunic.net',
		'be' => 'whois.dns.be',
		'bg' => 'whois.ripe.net',
		'br' => 'whois.registro.br',
		'bz' => 'whois.belizenic.bz',
		'ca' => 'whois.cira.ca',
		'cc' => 'whois.nic.cc',
		'ch' => 'whois.nic.ch',
		'cl' => 'whois.nic.cl',
		'cn' => 'whois.cnnic.net.cn',
		'cz' => 'whois.nic.cz',
		'de' => 'whois.nic.de',
		'fr' => 'whois.nic.fr',
		'hu' => 'whois.nic.hu',
		'ie' => 'whois.domainregistry.ie',
		'il' => 'whois.isoc.org.il',
		'in' => 'whois.ncst.ernet.in',
		'ir' => 'whois.nic.ir',
		'mc' => 'whois.ripe.net',
		'to' => 'whois.tonic.to',
		'tv' => 'whois.tv',
		'ru' => 'whois.ripn.net',
		'org' => 'whois.pir.org',
		'aero' => 'whois.information.aero',
		'nl' => 'whois.domain-registry.nl',
	];

	public static function server_from_tld($tld) {
		return avalue(self::$servers, preg_replace('/[^a-z]/', '', strtolower(StringTools::right($tld, '.'))));
	}
}
