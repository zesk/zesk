<?php

class IPBan_Trigger extends Options {
	public $codename = null;

	/**
	 * Total duration for our sliding window.
	 *
	 * @var integer
	 */
	public $duration = null;

	/**
	 * Size of our sub-segment for the sliding window
	 *
	 * @var integer
	 */
	public $segment = null;

	/**
	 * Buckets indexed by time % segment
	 *
	 * @var array
	 */
	private $buckets = array();

	/**
	 *
	 * @param unknown $codename
	 * @param unknown $duration
	 * @param number $nsegments
	 * @param array $options
	 */
	function __construct($codename, array $options = null) {
		$this->codename = $codename;
		parent::__construct($options);
		$nsegments = $this->option_integer("segments", 10);
		$duration = $this->option_integer("duration");
		if (empty($duration)) {
			$this->duration = $this->segment = 0;
		} else {
			$this->duration = intval($duration);
			$this->segment = max($duration / $nsegments, 60); // 60 seconds
		}
		if ($this->option('count') === null || $this->option('type') === null) {
			throw new Exception_Configuration("triggers", "Invalid trigger definition in {configuration_file} {codename} - missing one of count:{count}, duration:{duration}, type: {type}", array(
				"codename" => $codename
			) + $this->options);
			return;
		}

	}

	function severity($set = null) {
		return ($set === null) ? $this->option('severity', 'notice') : $this->set_option('severity', $set);
	}

	function execute($max_timestamp) {
		$count = $this->option_integer("count");
		$type = $this->option('type');

		$duration = $this->duration;
		$where = array(
			"P.name" => $type
		);
		if ($duration) {
			$ts_start = gmdate('Y-m-d H:i:s', $max_timestamp - $duration);
			$ts_end = gmdate('Y-m-d H:i:s', $max_timestamp);
			$where["utc|>="] = $ts_start;
			$where["utc|<="] = $ts_end;
		}
		$count_method = $this->option('count_method', "id");
		$count_methods = array(
			'types' => 'T.id',
			'events' => 'E.utc',
		);
		$count_method = avalue($count_methods, $count_method, $count_methods['events']);
		$query = Object::class_query("IPBan_Event", "E")->link("IPBan_Tag", array(
			"alias" => "T"
		))
			->link("IPBan_Tag_Type", array(
			"alias" => "P",
			"path" => "T.type"
		))
			->what(array(
			"IP" => "E.ip",
			"*count" => "COUNT(DISTINCT $count_method)"
		))
			->where($where)
			->group_by('E.ip')
			->order_by('`count` DESC');
		$sql = "SELECT INET_NTOA(X.ip) AS ip,X.count FROM (" . $query . ") AS X WHERE X.count >= $count";
		log::notice("\$max_timestamp=$max_timestamp, \$duration=$max_timestamp \$sql=$sql");
		return $query->database()->query_array($sql, "ip", "count");
	}

	function description() {
		$count = $this->option_integer("count");
		$type = $this->option('type');
		$duration = $this->duration;
		return "severity " . $this->severity() . " - more than $count events of type $type" . (empty($duration) ? " ever." : " every $duration seconds.");
	}

	function add(array $ip_counts, $current_time) {
		$segment = $this->segment;
		$debug_values = array(
			'codename' => $this->codename
		);
		if ($segment === 0) {
			$bucket_id = 0;
			$debug_values['datetime'] = "single";
		} else {
			$bucket_id = intval($current_time / $segment) * $segment;
			$this->cull_buckets($bucket_id - $this->duration);
			$debug_values['datetime'] = date('Y-m-d
		H:
		i:s', $bucket_id);
		}
		$debug_values['id'] = $bucket_id;

		if (!array_key_exists($bucket_id, $this->buckets)) {
			log::debug("IPBan_Trigger::add {codename} adding bucket {id} {datetime}", $debug_values);
			// New bucket, all IPs receive complaints
			$this->buckets[$bucket_id] = $ip_counts;
			return $ip_counts;
		}

		$result_ip_counts = array();
		$bucket_ips = $this->buckets[$bucket_id];
		// Add IPs not in the bucket
		foreach ($ip_counts as $ip => $count) {
			if (!array_key_exists($ip, $bucket_ips)) {
				log::debug("IPBan_Trigger::add {codename} new IP {ip}:{count} adding bucket {id} {datetime}", $debug_values + array(
					"ip" => $ip,
					"count" => $count
				));
				$this->buckets[$bucket_id][$ip] = $count;
				$result_ip_counts[$ip] = $count;
			}
		}
		// Update IPs in the bucket but with a higher count
		foreach ($bucket_ips as $ip => $count) {
			if (array_key_exists($ip, $ip_counts)) {
				$ip_count = $ip_counts[$ip];
				if ($count < $ip_count) {
					log::debug("IPBan_Trigger::add {codename} bump IP {ip}:{old_count} -> {count} adding bucket {id} {datetime}", $debug_values + array(
						"ip" => $ip,
						"old_count" => $count,
						"count" => $ip_count
					));
					$result_ip_counts[$ip] = $ip_count - $count;
					$this->buckets[$bucket_id][$ip] = $ip_count;
				}
			}
		}
		return $result_ip_counts;
	}

	private function cull_buckets($before_time) {
		foreach ($this->buckets as $bucket_id => $data) {
			if ($bucket_id < $before_time) {
				log::debug("IPBan_Trigger::cull_buckets {codename} Removing bucket {datetime}", array(
					"codename" => $this->codename,
					"datetime" => date('Y-m-d
		H:
		i:s', $bucket_id)
				));
				unset($this->buckets[$bucket_id]);
			}
		}
	}
}