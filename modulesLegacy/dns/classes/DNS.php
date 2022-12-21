<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class DNS {
	public static function host($name, $type = null, $options = null) {
		if (is_string($options)) {
			$options = [
				'server' => $options,
			];
		} elseif (!is_array($options)) {
			$options = [];
		}
		if (empty($name)) {
			throw new Exception_Semantics('dns::host(empty name)');
		}
		return self::_lookup_shell_host($name, $type, $options);
	}

	private static function _lookup_shell_host($name, $type, array $options) {
		if (is_string($options)) {
			$options = [
				'host' => $options,
			];
		} elseif (!is_array($options)) {
			$options = [];
		}
		$append = [
			'query' => $name,
			'type' => $type,
		];
		$typearg = is_string($type) ? '-t' . preg_replace('/[^a-z0-9]/', '', $type) . ' ' : '-ta ';
		$hostarg = $options['server'] ?? null;
		if ($hostarg) {
			$append['server'] = $hostarg;
			$hostarg = " $hostarg";
		}
		$command = "host $typearg$name$hostarg";
		exec($command, $output, $result);
		if ($result === 0) {
			return $append + [
				'result' => self::_parse_host_response($output),
				'result_raw' => $output,
			];
		}
		//		throw new Exception_? TODO
		//		die(implode("\n", $output));
		return null;
	}

	private static function _parse_host_response($lines) {
		$result = [];
		$lines = to_list($lines);
		foreach ($lines as $line) {
			foreach ([
				'mx' => 'mail is handled by',
				'aaaa' => 'has IPv6 address',
				'a' => 'has address',
				'txt' => 'descriptive text',
				'cname' => 'is an alias for',
			] as $type => $pattern) {
				[$host, $value] = pair($line, $pattern, null, null);
				if ($host !== null) {
					$host = trim($host);
					$value = trim($value);
					// 					if (apath($result, "$host.$type") === null) {
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
