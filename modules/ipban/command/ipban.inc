<?php
/**
 * Ban IP addresses from the command line. Make sure your own IP is listed in the whitelist!
 * Works with Module_IPBan which must be running as a root daemon.
 *
 * @category Security
 *
 * @package zesk
 * @subpackage ipban
 * @copyright (c) 2013 Market Acumen, Inc.
 */

/**
 *
 * @author kent
 * @package zesk
 * @subpackage ipban
 * @copyright (c) 2013 Market Acumen, Inc.
 */
class Command_IPBan extends Command_Base {
	protected $option_types = array(
		"*" => "string",
		"allow" => "boolean",
		"status" => "boolean",
		"stat" => "boolean",
		"severity" => "integer",
		"message" => "string"
	);
	protected $option_help = array(
		"*" => "IP Addresses to operate on",
		"allow" => "Pass this flag to add the IPs to the allowed whitelist",
		"status" => "Get status of parsing",
		"stat" => "Synonym for --status",
		"severity" => "Severity level for the complaint: 0=BAN NOW, 1=Seems to be hacking, 2=Suspicious, 3=Notice",
		"message" => "Message to pass along with the complaint"
	);
	protected function run() {
		if ($this->option_bool("status") || $this->option_bool('stat')) {
			/* @var $parser IPBan_Parser */
			foreach (Object::class_query("IPBan_Parser")->object_iterator() as $parser) {
				$files = $parser->file_status();
				$server = $parser->server;
				foreach ($files as $name => $data) {
					$files[$name] = map("({size} bytes) {percent}% completed", $data);
				}
				echo $server->id . ": " . $server->name . " # Parser " . $parser->handler . " " . $parser->path . "\n";
				echo text::format_pairs($files);
				echo "\n";
			}
			return 0;
		}
		$allow = $this->option_bool('allow');
		if ($this->has_arg()) {
			$complained = false;
			do {
				$ip = $this->get_arg("ip");
				if (!IPv4::valid($ip)) {
					$this->error("Not an IP {ip}", compact("ip"));
				} else if ($allow) {
					try {
						IPBan_IP::add_whitelist($ip);
						$this->log("Added {ip} to whitelist", array(
							"ip" => $ip
						));
					} catch (Exception_Object_Duplicate $e) {
						$this->error("{ip} already exists in whitelist", array(
							"ip" => $ip
						));
					}
				} else {
					IPBan::complain($ip, $this->option_integer("severity", IPBan::severity_known), $this->option("message", __CLASS__));
					$complained = true;
				}
				if ($complained) {
					Application_IPBan::fifo()->write("Hello");
				}
			} while ($this->has_arg());
		} else {
			$this->usage();
		}
		return 0;
	}
}
