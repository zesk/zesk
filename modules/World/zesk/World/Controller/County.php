<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\Controller;

class Controller_County extends Controller {
	protected string $class = County::class;
}
