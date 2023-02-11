<?php
declare(strict_types=1);
namespace zesk\Response;

use zesk\Exception_Semantics;
use zesk\JSON as zeskJSON;
use zesk\ORM\JSONWalker;
use zesk\Response;

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
		$this->parent->setContentType(Response::CONTENT_TYPE_JSON);
		$this->json = $set;
		return $this->parent;
	}

	/**
	 *
	 * @param array $set
	 * @return Response
	 */
	public function appendData(array $data): Response {
		$this->parent->setContentType(Response::CONTENT_TYPE_JSON);
		$this->json = $data + toArray($this->json);
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
	 * @throws Exception_Semantics
	 */
	public function toJSON(): mixed {
		return zeskJSON::prepare($this->json, $this->json_serializer_methods, $this->json_serializer_arguments);
	}

	/**
	 * @param array|string|null $content
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function render(array|string|null $content): string {
		if (is_array($content)) {
			$this->json = $content;
		} elseif (is_string($content) && $content !== '') {
			if (is_array($this->json)) {
				$this->json['content'] = $content;
			} else {
				$this->json = $content;
			}
		}
		$content = $this->toJSON();
		return $this->application->development() ? zeskJSON::encodePretty($content) : zeskJSON::encode($content);
	}

	/**
	 * @param $content
	 * @return void
	 * @throws Exception_Semantics
	 */
	public function output($content): void {
		echo $this->render($content);
	}
}
