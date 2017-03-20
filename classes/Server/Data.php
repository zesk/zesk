<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Server/Data.php $
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
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
class Server_Data extends Object {
	/**
	 * 
	 * @param Kernel $zesk
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add('zesk\Server::delete', array(
			__CLASS__,
			'server_delete'
		));
	}
	
	/**
	 * Clean up data of servers which have been deleted.
	 * Could probably use a foreign key constraint to handle this as well.
	 * 
	 * @param Application $application
	 */
	public static function cron_cluster_hour(Application $application) {
		$deleted_servers = $application->class_object_database(__CLASS__)->query_array("SELECT DISTINCT D.server FROM Server_Data D LEFT OUTER JOIN Server S on S.id=D.server WHERE S.id IS NULL", null, 'server');
		if (count($deleted_servers) > 0) {
			$application->query_delete(__CLASS__)->where('server', $deleted_servers)->exec();
		}
	}
	
	/**
	 * Delete all data associated with server
	 * 
	 * @param Server $server
	 */
	public static function server_delete(Server $server) {
		$server->application->query_delete(__CLASS__)->where("server", $server)->exec();
	}
}

