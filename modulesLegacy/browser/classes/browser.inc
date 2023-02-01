<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\ORMBase;

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
class Browser extends ORMBase {
	/**
	 * Register a browser based on a string user agent
	 *
	 * @param Application $application
	 * @param string $name
	 * @return Browser
	 * @throws Exception_Parameter
	 * @throws Exception_ORMNotFound
	 */
	public static function fromUserAgent(Application $application, string $name): self {
		if (empty($name)) {
			throw new Exception_Parameter('Blank user agent');
		}
		$md5 = md5($name);
		/* @var $browser Browser */
		$browser = $application->ormFactory(self::class, [
			'md5' => $md5,
			'name' => $name,
		]);

		try {
			if (!$browser->find()) {
				$browser->md5 = md5($name);
				$browser->name = $name;
				$browser->tags = $tags = UserAgent::parse($name);
				$browser->is_mobile = $tags['mobile'];
				$browser->is_phone = $tags['phone'];
				$browser->is_tablet = $tags['tablet'];
				$browser->is_desktop = $tags['desktop'];
				$browser->parsed = Timestamp::now();
				$browser->store();
			}
			return $browser;
		} catch (Exception $e) {
			throw new Exception_ORMNotFound(self::class, $e->getRawMessage(), $e->variables(), $e);
		}
	}
}
