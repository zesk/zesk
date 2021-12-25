<?php declare(strict_types=1);
namespace zesk\Response;

use zesk\JSON as zeskJSON;
use zesk\Response;
use zesk\ORM\JSONWalker;

class JSON extends Type {
	/**
	 *
	 * @var array
	 */
	private $json = [];

	/**
	 *
	 * @var array
	 */
	private $json_serializer_arguments = null;

	/**
	 *
	 * @var array
	 */
	private $json_serializer_methods = null;

	/**
	 *
	 * @param \zesk\Response $response
	 */
	public function initialize(): void {
		$this->json = [];
		$this->json_serializer_arguments = [
			JSONWalker::factory(),
		];
		$this->json_serializer_methods = null;
	}

	/**
	 *
	 * @param mixed $set
	 * @return \zesk\Response|array
	 */
	public function data($set = null) {
		if ($set !== null) {
			$this->parent->content_type(Response::CONTENT_TYPE_JSON);
			$this->json = $set;
			return $this->parent;
		}
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
			$this->json['content'] = $content;
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
