<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World\Controller;

use zesk\Doctrine\Controller;

class Province extends Controller {
	protected string $repository = Province::class;
}
