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
	 * @return Response
	 */
	public function setData(mixed $set): Response {
		$this->parent->content_type(Response::CONTENT_TYPE_JSON);
		$this->json = $set;
		return $this->parent;
	}

	/**
	 *
	 * @return mixed
	 */
	public function data(): mixed {
		return $this->json;
	}

	/**
	 *
	 * @return mixed
	 */
	public function toJSON(): string {
		return $this->render(null);
	}

	/**
	 * @param array|string|null $content
	 * @return string
	 * @throws \zesk\Exception_Semantics
	 */
	public function render(array|string|null $content): string {
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
		return $this->application->development() ? zeskJSON::encodePretty($content) : zeskJSON::encode($content);
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
