<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\Controller;

class Controller_Province extends Controller {
	protected string $class = Province::class;
}
