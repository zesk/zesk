<?php
class dns {
	public static function host($name, $type = null, $options = null) {
		if (is_string($options)) {
			$options = array(
				'server' => $options
			);
		} else if (!is_array($options)) {
			$options = array();
		}
		if (empty($name)) {
			throw new Exception_Semantics("dns::host(empty name)");
		}
		return self::_lookup_shell_host($name, $type, $options);
	}
	private static function _lookup_shell_host($name, $type = null, array $options) {
		if (is_string($options)) {
			$options = array(
				'host' => $options
			);
		} else if (!is_array($options)) {
			$options = array();
		}
		$append = array(
			'query' => $name, 
			'type' => $type
		);
		$typearg = is_string($type) ? "-t" . preg_replace("/[^a-z0-9]/", "", $type) . " " : "-ta ";
		$hostarg = avalue($options, "server");
		if ($hostarg) {
			$append['server'] = $hostarg;
			$hostarg = " $hostarg";
		}
		$command = "host $typearg$name$hostarg";
		exec($command, $output, $result);
		if ($result === 0) {
			return $append + array(
				'result' => self::_parse_host_response($output), 
				'result_raw' => $output
			);
		}
		//		throw new Exception_? TODO
		//		die(implode("\n", $output));
		return null;
	}
	private static function _parse_host_response($lines) {
		$result = array();
		$lines = to_list($lines);
		foreach ($lines as $line) {
			foreach (array(
				"mx" => "mail is handled by", 
				'aaaa' => 'has IPv6 address', 
				"a" => "has address", 
				"txt" => "descriptive text", 
				'cname' => 'is an alias for'
			) as $type => $pattern) {
				list($host, $value) = pair($line, $pattern, null, null);
				if ($host !== null) {
					$host = trim($host);
					$value = trim($value);
					// 					if (arr::path($result, "$host.$type") === null) {
					// 						$result[$host][$type] = array();
					// 					}
					$result[$host][$type][] = $value;
					break;
				}
			}
		}
		return $result;
	}
}

