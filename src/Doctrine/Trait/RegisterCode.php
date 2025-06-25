<?php
declare(strict_types=1);
/**
 * @package zesk
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 * @subpackage Hookable
 */

namespace zesk\Doctrine\Trait;

use zesk\Application;
use zesk\Configuration;
use zesk\Doctrine\Model;

trait RegisterCode
{
	public static function register(Application $application, string $code, array $members = [], array|Configuration $options = []): self
	{
		$em = $application->entityManager();
		$item = $em->getRepository(self::class)->findOneBy(['code' => $code]);
		/* @var $item RegisterCode|Model */
		if ($item) {
			return $item;
		}
		/* @var $item RegisterCode|Model */
		$item = new self($application);
		$item->setOptions($options);
		$item->fromArray(['code' => $code] + $members);
		$em->persist($item);
		$em->refresh($item);
		return $item;
	}
}
