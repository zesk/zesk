<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage log_mail
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Session\Session\classes\SessionORM;

/**
 *
 * @see Class_Log_Mail
 * @property id $id
 * @property SessionORM $session
 * @property User $user
 * @property string $code
 * @property string $from
 * @property string $to
 * @property string $subject
 * @property string $body
 * @property Timestamp $created
 * @property timestamp $sent
 * @property string $type
 * @property array $data
 * @author kent
 */
class Log_Mail extends ORMBase {
}
