<?php
declare(strict_types=1);

namespace zesk\Route;

use zesk\ArrayTools;
use zesk\Exception\NotFoundException;
use zesk\JSON;
use zesk\Route;

use zesk\Request;
use zesk\Response;
use zesk\Exception\FileNotFound;
use zesk\StringTools;

/**
 *
 * @author kent
 *
 */
class Content extends Route
{
	/**
	 * @param Request $request
	 * @return Response
	 * @throws NotFoundException
	 */
	protected function internalExecute(Request $request): Response
	{
		$file = $this->optionString('file');
		if ($file) {
			if (StringTools::cleanTokens($file) !== $file) {
				$file = $this->_mapVariables($request, $file);
				if (!is_string($file)) {
					throw new NotFoundException($request->uri(), ['file' => $file, 'badType' => gettype($file)], 0);
				}
			}

			try {
				$file = $this->application->paths->expand($file);
				return $this->application->responseFactory($request)->setRawFile($file);
			} catch (FileNotFound $f) {
				throw new NotFoundException($f->getRawMessage(), $f->variables(), 0, $f);
			}
		}
		$content = $this->option('content', $this->option('default content'));
		$map = [];
		foreach ($this->optionIterable('map') as $item) {
			$map[$item] = match ($item) {
				'request' => $request->variables(),
				'route' => $this->options() + ['class' => get_class($this)],
				default => [],
			};
		}
		if (count($map)) {
			$map = ArrayTools::keysFlatten($map, '.') + array_map(JSON::encodePretty(...), $map);
			$content =  ArrayTools::map($content, $map);
		}
		if ($this->option('json')) {
			return $this->application->responseFactory($request)->json()->setData($content);
		} else {
			return $this->application->responseFactory($request)->setContent($content);
		}
	}
}
