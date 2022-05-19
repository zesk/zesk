<?php declare(strict_types=1);
namespace zesk\Response;

use zesk\JSON as zeskJSON;
use zesk\Response;
use zesk\ORM\JSONWalker;

class JSON extends Type {
	/**
	 * Typically an array
	 *
	 * @var mixed
	 */
	private mixed $json = null;

	/**
	 *
	 * @var array
	 */
	private array $json_serializer_arguments = [];

	/**
	 *
	 * @var array
	 */
	private array $json_serializer_methods = [];

	/**
	 *
	 * @param \zesk\Response $response
	 */
	public function initialize(): void {
		$this->json = null;
		$this->json_serializer_arguments = [
			JSONWalker::factory(),
		];
		$this->json_serializer_methods = [];
	}

	/**
	 *
	 * @param mixed $set
	 * @return \zesk\Response|array
	 */
	public function setData(mixed $set) {
		$this->parent->content_type(Response::CONTENT_TYPE_JSON);
		$this->json = $set;
		return $this->parent;
	}

	/**
	 *
	 * @param mixed $set ignored
	 * @return mixed
	 */
	public function data($set = null) {
		assert($set === null);
		return $this->json;
	}

	/**
	 *
	 * @return array
	 */
	public function to_json() {
		return $this->json;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::render()
	 */
	public function render($content) {
		if (is_array($content)) {
			$this->json = $content;
		} elseif (is_string($content)) {
			if (is_array($this->json)) {
				$this->json['content'] = $content;
			} else {
				$this->json = $content;
			}
		}
		$content = zeskJSON::prepare($this->json, $this->json_serializer_methods, $this->json_serializer_arguments);
		return $this->application->development() ? zeskJSON::encode_pretty($content) : zeskJSON::encode($content);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::output()
	 */
	public function output($content): void {
		echo $this->render($content);
	}
}
