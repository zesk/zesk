# List of standard Zesk Hooks

The **Specification** section describes each hook's details as follows:

- **Type**: Either `zesk\Hookable` hook, or a **system hook**
- **Hookable method**: The name of the method called in the related `zesk\Hookable` class.
- **Hookable arguments**: A list of types and names passed to the `zesk\Hookable` method
- **System hook arguments**: A list of types and names passed to the system hook
- **System hook name**: The string you should pass to `$application->hooks->add()` to register this hook
- **Filter**: Whether this hook passes one or more of its parameters back to be processed or accumulated in a result.
- **Return value**: Each hook implementation may return a value 

##  `zesk\Application::configured_files`

### When is it called?

Called immediately after `zesk\Application` loads all configuration files but before it loads modules and configuration.

### Why override it?

You want custom behavior based on the configuration file, particularly related to loading additional configuration, or dynamically loading application modules.

### Specification

- **Type**: Hookable hook
- **Hookable method**: `hook_configured_files`
- **Hookable arguments**: none
- **System hook name**: `zesk\Application::configured_files`
- **System hook arguments**: `zesk\Application`
- **Filter**: no
- **Return value**: void

### Implementations

As a system hook:

	$application->hooks->add("zesk\\Application::configured_files", function (zesk\Application $app) {
		$custom = $app->configuration->custom;
		$app->loader->loadFile("etc/custom/$custom");
	});

As an application hook:

	namespace awesome;
	class Application extends \zesk\Application {
		...
		function hook_configured_files() {
			if ($this->development()) {
				$this->load_modules[] = "test";
			}
		}
		...
	}

## `zesk\Application::request`

### When is it called?

Called at the beginning of `zesk\Application::index()`, right after the `zesk\Request` is created.

### Why override it?

You need to:

- Modify the `zesk\Request` prior to the application handling it

### Specification

- **Type**: Hookable hook
- **Hookable method**: `hook_request`
- **Hookable arguments**: `zesk\Request $request`
- **System hook name**: `zesk\Application::request`
- **System hook arguments**: `zesk\Application $application, zesk\Request $request`
- **Filter**: no
- **Return value**: void

### Implementations

As a system hook:

	$application->hooks->add("zesk\\Application::request", function (zesk\Application $app, zesk\Request $request) {
		if (strpos($request->path(), ".exe") !== false) {
			$request->setOption("possible_hack", true);
		}
	});

As an application hook:

	namespace awesome;
	class Application extends \zesk\Application {
		...
		function hook_request(\zesk\Request $request) {
			if (strpos($request->path(), ".exe") !== false) {
				$request->setOption("possible_hack", true);
			}
		});
		...
	}

##  `zesk\Application::main`

### When is it called?

Called at the beginning of `zesk\Application::main()` which is generally called to deliver a response to a request by `zesk\Application::index()`.

### Why override it?

You need to:

- Modify the application state prior to having it handle a request
- Handle a request completely independently (say, for a different kind of caching, or to short-circuit the zesk\Router)

### Specification

- **Type**: Hookable hook
- **Hookable method**: `hook_main`
- **Hookable arguments**: none
- **System hook name**: `zesk\Application::main`
- **System hook arguments**: `zesk\Application`
- **Filter**: No
- **Return value**: `zesk\Response|void`

### Implementations

As a system hook:

	$application->hooks->add("zesk\\Application::main", function (zesk\Application $app, zesk\Request $request) {
		if (strpos($request->path(), ".exe") !== false) {
			$app->log_abuse();
			return $app->responseFactory($request)->status(404, "Not found")->content("Nope.");
		}
	});

As an application hook:

	namespace awesome;
	class Application extends \zesk\Application {
		...
		function hook_main(\zesk\Request $request) {
			if (strpos($request->path(), ".exe") !== false) {
				$this->log_abuse();
				return $this->responseFactory($request)->status(404, "Not found")->content("Nope.");
			}
		}
		...
	}

## `zesk\Application::router_loaded`
## `zesk\Application::router`
## `zesk\Application::router_prematch`
## `zesk\Application::router_matched`
## `zesk\Application::router_no_match`
## `zesk\Application::template_defaults`
## `zesk\Application::response_output_before`
## `zesk\Application::content`
## `zesk\Application::response_output_after`

## - `zesk\Application::mainException`

## `zesk\Application::cache_clear`
## `zesk\Application::command`
## `zesk\Application::maintenance_context`
## `zesk\Application::maintenance`
## `zesk\Application::set_cache`
## `zesk\Application::setLocale`
## `zesk\Application::cache_clear`
## `zesk\Application::configured`
## `zesk\Application::repositories`

## `zesk\Command::run_after`
## `zesk\Command::run_before`

## `zesk\Controller::classes`
## `zesk\Controller::json`

### When is it called?

Called whenever anyone calls `zesk\Controller::json`.

### Why override it?

You need to manipulate the output of another JSON call.

### Specification

- **Type**: Hookable hook
- **Hookable method**: `hook_configured_files`
- **Hookable arguments**: array $json
- **System hook name**: `zesk\Application::configured_files`
- **System hook arguments**: `zesk\Application`
- **Filter**: yes
- **Return value**: array

### Implementations

As a system hook:

	$application->hooks->add("zesk\\Application::configured_files", function (zesk\Application $app) {
		$custom = $app->configuration->custom;
		$app->loader->loadFile("etc/custom/$custom");
	});

As an application hook:

	namespace awesome;
	class Application extends \zesk\Application {
		...
		function hook_configured_files() {
			$custom = $this->configuration->custom;
			$this->loader->loadFile("etc/custom/$custom");
		}
		...
	}


## `zesk\Controller::initialize`

## `zesk\Controller_Theme::control_execute` (* can be renamed using an option)

## `zesk\Deploy::extension_php`
## `zesk\Deploy::extension_sql`
## `zesk\Deploy::extension_tpl`
## `zesk\Deploy::extension_*`

## `zesk\Mail::send`

## `zesk\Model::construct`
## `zesk\Model::router_argument`
## `zesk\Model::route_options`
## `zesk\Model::router_derived_classes`

## `zesk\Route::get_route_map`

## `zesk\Module::construct`

## `zesk\Module_JSLib::ready`

## `zesk\Process_Mock::done`

## `zesk\Router::construct`

## `zesk\Request::initialize`
## `zesk\Request::initializeFromGlobals`
## `zesk\Request::initializeFromRequest`
## `zesk\Request::initializeFromSettings`

## `zesk\Response::output_before`
## `zesk\Response::output_after`
## `zesk\Response::headers_before`
## `zesk\Response::headers`

## `zesk\Response::links_preprocess`
## `zesk\Response::link_process`
## `zesk\Response::process_cached_js`
## `zesk\Response::process_cached_css`
## `zesk\Response::compress_css`
## `zesk\Response::compress_script`

## `zesk\Hookable::tr`
