<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\JoinColumn;
use zesk\Application;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use zesk\Doctrine\Trait\AutoID;

/**
 * @author kent
 * @property int $id
 * @property Server $server
 * @property string $name
 * @property mixed $value
 */
#[Entity]
#[UniqueConstraint(name: 'serverName', fields: ['server', 'name'])]
class ServerMeta extends Model
{
	#[ManyToOne(targetEntity: Server::class)]
	#[JoinColumn(name: 'server')]
	public Server $server;

	#[Column(type: 'string', nullable: false)]
	public string $name;

	#[Column(type: 'json', nullable: false)]
	public mixed $value;

	public function __construct(Application $application, Server $server, string $name, mixed $value, array $options = [])
	{
		parent::__construct($application, ['server' => $server, 'name' => $name, 'value' => $value], $options);
	}

	/**
	 * Delete all data associated with server
	 *
	 * @param Server $server
	 * @return void
	 */
	public static function serverDelete(Server $server): void
	{
		$em = $server->application->entityManager();
		$query = $em->createQueryBuilder()->delete(self::class)->where(['server' => $server])->getQuery();
		$query->execute();
	}

	/**
	 * @param Server $server
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 * @throws ORMException
	 */
	public static function register(Server $server, string $name, mixed $value): self
	{
		$em = $server->em;
		$meta = $em->getRepository(self::class)->findOneBy(['server' => $server, 'name' => $name]);
		if (!$meta) {
			$meta = new self($server->application, $server, $name, $value);
		} else {
			$meta->value = $value;
		}
		$em->persist($meta);
		return $meta;
	}
}
