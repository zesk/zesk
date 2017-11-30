<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/server/method.inc $
 * @package zesk
 * @subpackage xmlrpc
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\HTML;

/**
 * Server_Method
 *
 * Long description
 *
 * @package zesk
 * @subpackage system
 */
class Server_Method {
	public $Method;
	public $PHPMethod;
	public $ParameterTypes = array();
	public $ReturnType;
	public $Help;
	public $ParameterHelp;
	
	/**
	 *
	 * @param unknown $name
	 * @param unknown $returnType
	 * @param unknown $phpMethod
	 * @param array $parameterTypes
	 * @param string $help
	 * @param string $parameterHelp
	 * @throws Exception_Internal
	 */
	function __construct($name, $returnType, $phpMethod, $parameterTypes = array(), $help = false, $parameterHelp = false) {
		$this->Method = $name;
		$this->ReturnType = $returnType;
		$this->PHPMethod = $phpMethod;
		$this->ParameterTypes = to_array($parameterTypes);
		
		$this->Help = $help;
		$this->ParameterHelp = $parameterHelp;
		if (is_array($parameterTypes) !== is_array($parameterHelp)) {
			throw new Exception_Internal("Server_Method::construct($returnType, $phpMethod): No parameter help");
		}
	}
	function checkArguments($methodName, $args) {
		$types = $this->ParameterTypes;
		if (!is_array($types)) {
			if (is_array($args) && count($args) > 0) {
				throw new Exception(Exception::ERROR_SERVER_METHOD_PARAMETERS, "$methodName expecting no parameters, " . count($args) . " arguments passed.");
			}
			return true;
		}
		if (!is_array($args)) {
			throw new Exception(Exception::ERROR_SERVER_METHOD_PARAMETERS, "$methodName received no parameters, expecting parameters: \"" . implode(", ", array_values($types)) . "\"");
		}
		$nArgs = count($args);
		$i = 0;
		$argErrors = array();
		foreach ($types as $t) {
			if (count($args) == 0) {
				$argErrors[] = "Expecting " . count($types) . " arguments, but only $nArgs passed.";
				break;
			}
			$arg = array_shift($args);
			$argType = new Value($arg);
			if ($argType->Type !== $t) {
				$argErrors[] = "Argument $i is a '$argType->Type', should be a '$t'";
			}
			$i++;
		}
		if (count($args)) {
			$argErrors[] = "Expecting " . count($types) . " arguments, but $nArgs were passed.";
		}
		if (count($argErrors)) {
			throw new Exception(Exception::ERROR_SERVER_METHOD_PARAMETERS, "$methodName parameter error:\n\t" . implode("\n\t", $argErrors) . "\n");
		}
		return true;
	}
	function invoke(Server $object, $arguments) {
		if (!is_array($arguments)) {
			$arguments = array();
		}
		$php_method = $this->PHPMethod;
		$method = $this->Method;
		
		$object->log("php_method is $php_method");
		// Are we dealing with a function or a method?
		if (begins($php_method, 'this:', true)) {
			// It's a class method - check it exists
			$safeMethod = "rpc_" . substr($php_method, 5);
			if (!method_exists($object, $safeMethod)) {
				throw new Exception(Exception::ERROR_SERVER_METHOD_PARAMETERS, "Server error. " . get_class($object) . " class method \"$method\" does not exist. ($safeMethod)");
			}
			return call_user_func_array(array(
				$object,
				$safeMethod
			), $arguments);
		}
		
		if (!function_exists($method)) {
			return new Exception(Exception::ERROR_SERVER_METHOD_PARAMETERS, "Server error. Requested function \"$method\" does not exist. ($method)");
		}
		
		return call_user_func_array($method, $arguments);
	}
	function methodSignature() {
		$sig = array_values($this->ParameterTypes);
		array_unshift($sig, $this->ReturnType);
		return array(
			$sig
		);
	}
	function getDocumentation() {
		$doc_path = zesk()->configuration->path_get('xmlrpc\Server::documentation_path');
		$doc_file = path($doc_path, $this->Method);
		return app()->theme($doc_file);
	}
	function methodHelp() {
		$methodName = $this->Method;
		$html[] = "<h1>$methodName</h1>";
		$html[] = "<pre>";
		if (is_array($this->ParameterTypes)) {
			$params = array();
			$max_len = 0;
			foreach ($this->ParameterTypes as $n => $t) {
				$max_len = max($max_len, strlen($t));
			}
			$max_len += 4;
			foreach ($this->ParameterTypes as $n => $t) {
				$params[] = $t . str_repeat(" ", $max_len - strlen($t)) . "$n";
			}
			$html[] = $this->ReturnType . " $methodName(" . ((count($params) > 0) ? "\n\t" : "") . implode(",\n\t", $params) . ");";
		} else {
			$html[] = $this->ReturnType . " $methodName()";
		}
		$html[] = "</pre>";
		$html[] = "<h2>Parameters</h2>";
		if (is_array($this->ParameterTypes) && count($this->ParameterTypes) > 0) {
			$help = $this->ParameterHelp;
			$html[] = "<dl>";
			foreach ($help as $n => $help) {
				$type = avalue($this->ParameterTypes, $n, "no-type");
				$html[] = "<dt>$n</dt><dd><em>$type</em>. $help</dd>";
			}
		} else {
			$html[] = "<em>No parameters.</em>";
		}
		$html[] = "<h2>Return Value</h2>";
		$html[] = $this->ReturnType;
		//$this->getReturnDocumentation();
		
		$html[] = "<h2>Description</h2>";
		$doc = $this->getDocumentation();
		if ($doc) {
			if ($this->Help)
				$html[] = $this->Help;
			$html[] = HTML::tag("h2", null, "Detailed Description");
			$html[] = $doc;
		} else {
			$html[] = $this->Help ? $this->Help : "<em>No help description</em>";
		}
		return implode("\n", $html);
	}
}
