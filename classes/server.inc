<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/server.inc $
 * @package zesk
 * @subpackage objects
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * Server
 *
 * Represents a physical machine. Override this in your own tree to extend
 * columns and functionality.
 *
 * @see Class_Server
 * @property id $id
 * @property string $name
 * @property string $name_internal
 * @property string $name_external
 * @property ip4 $ip4_internal
 * @property ip4 $ip4_external
 * @property integer $free_disk
 * @property double $load
 * @property Timestamp $alive
 */
class Server extends Zesk_Server {

}
