<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:07 PM
 */
namespace zesk;

/**
 * @see Class_Server_Data
 * @author kent
 * @property id $id
 * @property Server $server
 * @property string $name
 * @property mixed $value
 */
class Server_Data extends ORM {
	/**
	 *
	 * @param Kernel $app
	 */
	public static function hooks(Application $app): void {
		$app->hooks->add(Server::class . '::delete', [
			__CLASS__,
			'server_delete',
		]);
	}

	/**
	 * Clean up data of servers which have been deleted.
	 * Could probably use a foreign key constraint to handle this as well.
	 *
	 * @param Application $application
	 */
	public static function cron_cluster_hour(Application $application): void {
		$deleted_servers = $application->ormRegistry(__CLASS__)->database()->queryArray('SELECT DISTINCT D.server FROM Server_Data D LEFT OUTER JOIN Server S on S.id=D.server WHERE S.id IS NULL', null, 'server');
		if (count($deleted_servers) > 0) {
			$application->ormRegistry(__CLASS__)
				->query_delete()
				->addWhere('server', $deleted_servers)
				->execute();
		}
	}

	/**
	 * Delete all data associated with server
	 *
	 * @param Server $server
	 */
	public static function server_delete(Server $server): void {
		$server->application->ormRegistry(__CLASS__)
			->query_delete()
			->addWhere('server', $server)
			->execute();
	}
}
