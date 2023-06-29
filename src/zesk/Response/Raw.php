<?php
declare(strict_types=1);

namespace zesk\Response;

use zesk\Exception\FileNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\MIME;
use zesk\Response;

class Raw extends Type
{
	/**
	 *
	 * @var string
	 */
	private string $file = '';

	/**
	 *
	 * @var string
	 */
	private string $binary = '';

	/**
	 *
	 */
	public function initialize(): void
	{
	}

	/**
	 *
	 */
	public function headers(): void
	{
		if ($this->file) {
			try {
				$this->parent->setContentType(MIME::fromExtension($this->file));
			} catch (KeyNotFound) {
			}
			$this->parent->setHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', filemtime($this->file)));
			$this->parent->setHeader('Content-Length', strval(filesize($this->file)));
		}
	}

	/**
	 *
	 * @see fpassthru()
	 */
	public function output($content): void
	{
		if ($this->file) {
			$fp = fopen($this->file, 'rb');
			fpassthru($fp);
			fclose($fp);
		} else {
			echo $content;
		}
	}

	/**
	 *
	 * @param string $file
	 * @return Response
	 * @throws FileNotFound
	 */
	public function setFile(string $file): Response
	{
		if (!file_exists($file)) {
			throw new FileNotFound($file);
		}
		$this->parent->setOutputHandler(Response::CONTENT_TYPE_RAW);

		try {
			$this->parent->setContentType(MIME::fromExtension($file));
		} catch (KeyNotFound) {
		}
		$this->file = $file;
		return $this->parent;
	}

	/**
	 * @throws SemanticsException
	 */
	public function file(): string
	{
		if (empty($this->file)) {
			throw new SemanticsException('file not set');
		}
		return $this->file;
	}

	/**
	 * Download a file
	 *
	 * @param string $file
	 *            Full path to file to download
	 * @param string $name
	 *            File name given to browser to save the file
	 * @param string $type
	 *            Content disposition type (attachment)
	 * @return Response
	 * @throws FileNotFound
	 */
	final public function download(string $file, string $name = '', string $type = ''): Response
	{
		if (!$name) {
			$name = basename($file);
		}
		$name = File::nameClean($name);
		if (!$type) {
			$type = 'attachment';
		}
		return $this->setFile($file)->setHeader('Content-Disposition', "$type; filename=\"$name\"")->noCache();
	}

	/**
	 *
	 * @return array
	 */
	public function toJSON(): array
	{
		return [
			'content' => $this->binary,
		];
	}
}
