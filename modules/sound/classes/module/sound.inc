<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/sound/classes/module/sound.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Fri Jan 02 19:20:23 EST 2009 19:20:23
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Sound extends Module {
	public static function scripts_register() {
		HTML::javascript('/share/sounds/FABridge.js', ZESK_ROOT . '/share/');
		HTML::javascript('/share/sounds/sound.js', ZESK_ROOT . '/share/');
		
		HTML::jquery("zeskSound.init('/sounds/');");
	}
}
