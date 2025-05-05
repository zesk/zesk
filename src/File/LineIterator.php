<?php

namespace zesk\File;

use Iterator;
use zesk\Exception\FileNotFound;

class LineIterator implements Iterator {
	
	private string $file;
	private mixed $resource;
	
	private int $index = 0;
	private string $current = "";
	
	/**
	 * @param string $file
	 * @throws FileNotFound
	 */
	function __construct(string $file)
	{
		$this->file = $file;
		$this->open();
	}
	
	/**
	 * @return void
	 * @throws FileNotFound
	 */
	private function open(): void
	{
		if (!$this->resource = @fopen($this->file, "r")) {
			throw new FileNotFound($this->file, "Unable to open file for reading");
		}
	}
	
	public function next(): void
	{
		$this->current = fgets($this->resource);
		$this->index++;
	}
	
	public function key(): int
	{
		return $this->index;
	}
	
	public function valid(): bool
	{
		return feof($this->resource);
	}
	
	/**
	 * @return void
	 * @throws FileNotFound
	 */
	public function rewind(): void
	{
		fclose($this->resource);
		$this->open();
	}
	
	public function current(): string
	{
		return $this->current;
	}
}
