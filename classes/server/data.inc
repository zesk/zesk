<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/server/data.inc $
 * @package zesk
 * @subpackage objects
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:07 PM
 */

/**
 * @see Class_Server_Data
 * @author kent
 * @property id $id
 * @property Server $server
 * @property string $name
 * @property mixed $value
 */
class Server_Data extends Object {
	public static function hooks() {
		zesk::add_hook('Server::delete', array(
			__CLASS__,
			'server_delete'
		));
	}

	public static function cron_cluster_hour() {
		$deleted_servers = Object::class_database(__CLASS__)->query_array("SELECT DISTINCT D.server FROM Server_Data D LEFT OUTER JOIN Server S on S.id=D.server WHERE S.id IS NULL", null, 'server');
		if (count($deleted_servers) > 0) {
			Object::class_query_delete(__CLASS__)->where('server', $deleted_servers)->execute();
		}
	}

	public static function server_delete(Server $server) {
		Object::class_query_delete(__CLASS__)->where("server", $server)->execute();
	}
}

