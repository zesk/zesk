<?php declare(strict_types=1);
namespace zesk;

class Feed_Post {
	/**
	 *
	 * @var Feed
	 */
	public $feed = null;

	/**
	 *
	 * @var Timestamp
	 */
	public $date = null;

	/**
	 *
	 * @var string
	 */
	public $raw_date = null;

	/**
	 *
	 * @var string
	 */
	public $link = null;

	/**
	 *
	 * @var string
	 */
	public $title = null;

	/**
	 *
	 * @var string
	 */
	public $description = null;

	/**
	 *
	 * @return string[]
	 */
	public function __sleep() {
		return [
			'feed',
			'date',
			'raw_date',
			'link',
			'title',
			'description',
		];
	}
}
