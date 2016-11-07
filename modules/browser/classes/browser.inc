<?php
/**
 * 
 */
use zesk\Timestamp;
use zesk\Cache;
/**
 * @see Class_Browser
 * @property id $id
 * @property hex $md5
 * @property boolean $is_phone
 * @property boolean $is_tablet
 * @property boolean $is_desktop
 * @property array $tags
 * @property string $name
 * @property Timestamp $created
 * @property timestamp $parsed
 */
class Browser extends Object {

	/**
	 * Register a browser based on a string user agent
	 *
	 * @param Request $request
	 * @return NULL|Browser
	 */
	public static function from_user_agent($name) {
		if (empty($name)) {
			return null;
		}
		$cache = Cache::register(__CLASS__)->expire_after(3600);
		if ($cache->has($name)) {
			return $cache->get($name);
		}
		$md5 = md5($name);
		/* @var $browser Browser */
		$browser = Object::factory(__CLASS__, array(
			'md5' => $md5,
			"name" => $name
		));
		if (!$browser->find()) {
			$browser->md5 = md5($name);
			$browser->name = $name;
			$browser->tags = $tags = Net_HTTP_UserAgent::parse($name);
			$browser->is_phone = $tags['phone'];
			$browser->is_tablet = $tags['tablet'];
			$browser->is_desktop = $tags['desktop'];
			$browser->parsed = Timestamp::now();
			$browser->store();
		}
		$cache->set($name, $browser);
		return $browser;
	}

	/**
	 * Register a browser based on the request user agent
	 *
	 * @param Request $request
	 * @return NULL|Browser
	 */
	public static function from_request(zesk\Request $request) {
		return self::from_user_agent($request->header('User-Agent'));
	}
}
