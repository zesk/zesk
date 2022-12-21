<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:07 PM
 */

namespace zesk\ORM;

use zesk\Application;
use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Semantics;

/**
 * @see Class_Server_Data
 * @author kent
 * @property id $id
 * @property Server $server
 * @property string $name
 * @property mixed $value
 */
class Server_Data extends ORMBase {
	/**
	 *
	 * @param Kernel $app
	 */
	public static function hooks(Application $app): void {
		$app->hooks->add(Server::class . '::delete', [
			__CLASS__, 'server_delete',
		]);
	}

	/**
	 * Clean up data of servers which have been deleted.
	 * Could probably use a foreign key constraint to handle this as well.
	 *
	 * @param Application $application
	 * @return void
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws \zesk\Exception_Configuration
	 */
	public static function cron_cluster_hour(Application $application): void {
		/* TODO Proper foreign key constraints */
		$deleted_servers = $application->ormRegistry(__CLASS__)->database()->queryArray('SELECT DISTINCT D.server FROM Server_Data D LEFT OUTER JOIN Server S on S.id=D.server WHERE S.id IS NULL', null, 'server');
		if (count($deleted_servers) > 0) {
			$application->ormRegistry(__CLASS__)->queryDelete()->addWhere('server', $deleted_servers)->execute();
		}
	}

	/**
	 * Delete all data associated with server
	 *
	 * @param Server $server
	 * @return void
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Semantics
	 */
	public static function server_delete(Server $server): void {
		$server->application->ormRegistry(__CLASS__)->queryDelete()->addWhere('server', $server)->execute();
	}
}
