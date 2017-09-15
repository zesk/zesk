<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/qmail/classes/qmail.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Fri Nov 07 10:22:34 EST 2008 10:22:34
 */

/*
 
 EXIT CODES
 command's  exit  codes  are  interpreted  as  follows: 0 means that the
 delivery was successful; 99 means that the delivery was successful, but
 that  qmail-local  should ignore all further delivery instructions; 100
 means that the delivery failed permanently (hard error); 111 means that
 the  delivery  failed but should be tried again in a little while (soft
 error).
 
 Currently 64, 65, 70, 76, 77, 78, and 112 are considered  hard  errors,
 and  all  other  codes  are  considered soft errors, but command should
 avoid relying on this.
 
 */
define('QMAIL_EXIT_CODE_SUCCESS_CONTINUE', 0);
define('QMAIL_EXIT_CODE_SUCCESS_STOP', 99);
define('QMAIL_EXIT_CODE_FAIL', 100);
define('QMAIL_EXIT_CODE_FAIL_RETRY', 111);
class qmail {
	const EXIT_CODE_SUCCESS_CONTINUE = QMAIL_EXIT_CODE_SUCCESS_CONTINUE;
	const EXIT_CODE_SUCCESS_STOP = QMAIL_EXIT_CODE_SUCCESS_STOP;
	const EXIT_CODE_FAIL = QMAIL_EXIT_CODE_FAIL;
	const EXIT_CODE_FAIL_RETRY = QMAIL_EXIT_CODE_FAIL_RETRY;
}


