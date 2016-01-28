<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/command/dkim.inc $
 * @package zesk
 * @subpackage bin
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 4:54 PM
 */

if (!defined('ZESK_ROOT')){ die("ZESK_ROOT must be defined before including this."); }

if (!zesk::get('DKIM::domain')) { die('DKIM::domain global must be set before including this.'); }
if (!zesk::get('DKIM::public_key')) { die('DKIM::public_key global must be set before including this.'); }
if (!zesk::get('DKIM::private_key')) { die('DKIM::private_key global must be set before including this.'); }

/*
 * Create a command line PHP which includes this.
 */

require_once ZESK_ROOT . 'lib/php-dkim/DKIM.inc';

$dkim = new DKIM(_G('DKIM::domain'),_G('DKIM::public_key'),_G('DKIM::private_key'));

echo $dkim->dns_txt_record();

