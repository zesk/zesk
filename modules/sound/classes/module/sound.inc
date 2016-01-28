<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/sound/classes/module/sound.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Fri Jan 02 19:20:23 EST 2009 19:20:23
 */

class sound
{
	public static function scripts_register()
	{
		html::javascript('/sounds/FABridge.js', ZESK_ROOT . '/share/');
		html::javascript('/sounds/sound.js', ZESK_ROOT . '/share/');

		html::jquery("zeskSound.init('/sounds/');");
	}
}
