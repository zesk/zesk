<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage feed
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class CachedFeed extends Feed {
	/**
	 *
	 * @var Timestamp
	 */
	private $last_updated = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Feed::execute()
	 */
	public function execute() {
		$cache_item = $this->application->cache->getItem(__CLASS__ . "-" . $this->url);
		if ($cache_item->isHit()) {
			[$this->posts, $this->last_updated] = $cache_item->get();
			foreach ($this->posts as $post) {
				$post->feed = $this;
			}
		} else {
			if (!parent::execute()) {
				return null;
			}
			$cache_item->set([
				$this->posts,
				Timestamp::now(),
			]);
			$cache_item->expiresAfter($this->option("ttl", 600));
			$this->application->cache->saveDeferred($cache_item);
		}
		return $this;
	}

	/**
	 *
	 * @return \zesk\Timestamp
	 */
	public function last_updated() {
		return $this->last_updated;
	}
}
