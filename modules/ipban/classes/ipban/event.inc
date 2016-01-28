<?php

/**
 * @see Class_IPBan_Event
 * @author kent
 *
 */
class IPBan_Event extends IPBan {

	/**
	 * Array of [tag][ip] => count
	 *
	 * @var array
	 */
	private static $cache = array();

	private static function cache_load($period) {

	}
	public static function log($ip, $utc, $type, $name) {
		try {
			return Object::factory(__CLASS__, array(
				'tag' => IPBan_Tag::instance($type, $name),
				"utc" => $utc,
				"ip" => $ip
			))->store();
		} catch (Database_Exception_Duplicate $e) {
			return null;
		} catch (Exception_Object_Duplicate $e) {
			return null;
		}
	}

	/**
	 * Once every hour, purge events older than 10 days old
	 */
	public static function cron_cluster_hour() {
		$query = Object::class_query_delete(__CLASS__)->where("utc|<=", Timestamp::factory(zesk::get("IPBan_Event::lifetime", "-10 days")));
		log::notice($query->__toString());
		$query->execute();
	}
}