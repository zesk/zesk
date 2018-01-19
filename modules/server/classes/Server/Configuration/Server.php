<?php
namespace server;

// KMD No XMLRPC

// use zesk\ORM;
// use zesk\Exception_Parameter;
// use zesk\ArrayTools;
// use zesk\IPv4;

// class Server_Configuration_Server extends \xmlrpc\Server {
// 	static $methods = array(
// 		'register_server' => array(
// 			'boolean',
// 			'this:register_server',
// 			false,
// 			'Gets the server capabilities structure'
// 		)
// 	);
// 	protected $export_methods = array(
// 		'register_server',
// 		'server_data',
// 		'feature_list'
// 	);
// 	const data_key = "XML_RPC_Configuration_Key";

// 	/**
// 	 * Register a server for the first time, and retrieve key.
// 	 *
// 	 * @param string $LocalName
// 	 * @param string $LocalIP
// 	 * @param string $PublicName
// 	 * @param string $PublicIP
// 	 * @return
// 	 */
// 	function rpc_register_server($LocalName, $LocalIP, $PublicName = null, $PublicIP = null) {
// 		if ($PublicIP === null) {
// 			$PublicIP = IPv4::remote(null);
// 		}
// 		if ($PublicName === null && $PublicIP !== null) {
// 			$PublicName = gethostbyname(implode(".", array_reverse(explode(".", $PublicIP))) . ".in-addr.arpa");
// 		}
// 		if (!IPv4::valid($LocalIP)) {
// 			throw new Exception_Parameter("Local IP is not an IP address: $LocalIP");
// 		}
// 		if (!IPv4::valid($PublicIP)) {
// 			throw new Exception_Parameter("Public IP is not an IP address: $PublicIP");
// 		}
// 		$fields = compact("PublicIP4", "PublicName", "LocalIP4", "LocalName");
// 		$fields = ArrayTools::clean($fields, null);

// 		/* @var $server Server */
// 		$server = ORM::factory("Server", $fields + array(
// 			"Name" => $fields['PublicName']
// 		))->register();
// 		if (!$server) {
// 			throw new \zesk\Exception("Register failed");
// 		}
// 		if ($server->object_status() == ORM::object_status_exists) {
// 			$server->set_member($fields)->store();
// 			$key = $server->data(self::data_key);
// 		} else {
// 			$key = md5(microtime() . $PublicIP . mt_rand());
// 			$server->data(self::data_key, $key);
// 		}
// 		return $server->members("ID", "Name") + array(
// 			"Key" => $key
// 		);
// 	}

// 	/**
// 	 * Set data for this server
// 	 *
// 	 * @param string $key Access key for this server
// 	 * @param array $values
// 	 * @return boolean
// 	 */
// 	function rpc_server_data($key, array $values) {
// 		$FreeDisk = $Load = $Alive = $Uptodate = null;
// 		$server = ORM::factory("Server");
// 		/* @var $server Server */
// 		if (!$server->data_find(array(
// 			self::data_key => $key
// 		))) {
// 			return false;
// 		}
// 		$fields = $server->columns();
// 		$object_values = ArrayTools::include_exclude($values, $fields);
// 		$data_values = ArrayTools::include_exclude($values, null, $fields);
// 		if (count($object_values) > 0) {
// 			$server->set_member($object_values)->store();
// 		}
// 		if (count($data_values) > 0) {
// 			$server->data($data_values);
// 		}
// 		return true;
// 	}

// 	/**
// 	 * @return array List of features to install for this server.
// 	 */
// 	function rpc_feature_list() {
// 		return array(
// 			"subversion",
// 			"php"
// 		);
// 	}
// }
