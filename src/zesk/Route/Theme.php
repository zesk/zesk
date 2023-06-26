<?php
declare(strict_types=1);

namespace zesk\Route;

use zesk\ArrayTools;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\SemanticsException;
use zesk\HTTP;
use zesk\Request;
use zesk\Response;
use zesk\Route;

use zesk\Exception\FileNotFound;
use zesk\Exception\Redirect as RedirectException;
use zesk\StringTools;
use zesk\Types;

class Theme extends Route {
	/**
	 * Whether the theme path is set by variables
	 *
	 * @var bool
	 */
	protected bool $dynamic_theme = false;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$this->dynamic_theme = self::isThemeDynamic($this->option('theme'));
	}

	/**
	 * Does our theme contain tokens?
	 *
	 * @param array|string $theme
	 * @return bool
	 */
	private static function isThemeDynamic(array|string $theme): bool {
		foreach (Types::toList($theme) as $themeItem) {
			if (StringTools::cleanTokens($themeItem) !== $themeItem) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate this route
	 *
	 * @return bool
	 * @throws FileNotFound
	 */
	public function validate(): bool {
		$application = $this->application;
		$themes = $application->themes;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options() + $this->named;
		$args = ArrayTools::map($this->optionArray('theme arguments'), $parameters) + $parameters;
		$theme = $this->option('theme');
		if ($themes->themeExists($theme, $args)) {
			return true;
		}

		throw new FileNotFound('No theme {theme} found in {themePaths}', [
			'theme' => $theme, 'themePaths' => $themes->themePath(),
		]);
	}

	/**
	 * @param Request $request
	 * @return Response
	 * @throws RedirectException
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws SemanticsException
	 */
	public function internalExecute(Request $request): Response {
		$application = $this->application;
		$themes = $application->themes;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options() + $this->named;
		$args = ArrayTools::map($this->optionArray('theme arguments'), $parameters) + $parameters;
		$mapped_theme = $theme = $this->option('theme');
		$theme_options = $this->optionArray('theme options');
		if ($this->dynamic_theme) {
			$mapped_theme = ArrayTools::map($theme, $parameters);
			if (!$themes->themeExists($mapped_theme, $args)) {
				$response = $this->application->responseFactory($request);
				$response->setStatus(HTTP::STATUS_FILE_NOT_FOUND);
				$response->setContent("Theme $mapped_theme not found");
				return $response;
			}
			$application->debug('Executing theme={theme} mapped_theme={mapped_theme} args={args}', compact('theme', 'mapped_theme', 'args'));
		}
		$content = $themes->theme($mapped_theme, $args, $theme_options); //TODO

		$response = $this->application->responseFactory($request);
		$response->content = $content;

		$json_html = $this->option('json_html', false);
		if ($json_html && $response->isJSON() || $this->optionBool('json')) {
			$response->json()->setData($response->html()->toJSON() + [
				'status' => true,
			]);
		}
		return $response;
	}
}
