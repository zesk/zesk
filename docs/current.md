## Release {version}

- Adding `zesk\Application::model_factory` method
- Fixing `Model::__construct` takes `$application` as first parameter issues
- Moving `Browser` module to `zesk` namespace
- Removed some references from `global $zesk` and use local context instead
- Stop using deprecated `Class_Object::cache`
- `Domain::domain_factory` now takes `$application` as first parameter.
- `Response::cdn_javascript` and `Response::cdn_css` are deprecated, removing from all Zesk core
- `zesk\Browser` application global passing
- `zesk\Logger` used to die when log handler threw an exception; now silently continues.
- `zesk\Model` remove redundant initialization code in factory method

<!-- Generated automatically by release-zesk.sh, beware editing! -->
