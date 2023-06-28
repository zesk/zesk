<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Application;
use zesk\Database_Exception;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\Semantics;

/**
 * @see Class_ServerMeta
 * @author kent
 * @property int $id
 * @property Server $server
 * @property string $name
 * @property mixed $value
 */
class ServerMeta extends ORMBase
{
	/**
	 *
	 * @param Application $app
	 */
	public static function hooks(Application $app): void
	{
		$app->hooks->add(Server::class . '::delete', [
			__CLASS__, 'serverDelete',
		]);
	}

	/**
	 * Clean up data of servers which have been deleted.
	 * Could probably use a foreign key constraint to handle this as well.
	 *
	 * @param Application $application
	 * @return void
	 * @throws Database_Exception
	 * @throws Database\Exception\Duplicate
	 * @throws Database\Exception\SQLException
	 * @throws Database\Exception\TableNotFound
	 * @throws Semantics
	 * @throws ConfigurationException
	 */
	public static function cron_cluster_hour(Application $application): void
	{
		/* TODO Proper foreign key constraints */
		// $deleted_servers = $application->ormRegistry(__CLASS__)->database()->queryArray('SELECT DISTINCT D.server
		// FROM ServerMeta D LEFT OUTER JOIN Server S on S.id=D.server WHERE S.id IS NULL', null, 'server');
		$deleted_servers = $application->ormRegistry(self::class)->querySelect('D')->addWhat('server', 'D.server')
			->setDistinct()->link(Server::class, ['left' => true])->addWhere('S.id', null)->toArray('server');
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
	 * @throws Database\Exception\Duplicate
	 * @throws Database\Exception\SQLException
	 * @throws Database\Exception\TableNotFound
	 * @throws Semantics
	 */
	public static function serverDelete(Server $server): void
	{
		$server->application->ormRegistry(__CLASS__)->queryDelete()->addWhere('server', $server)->execute();
	}
}
