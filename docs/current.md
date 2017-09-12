## Release {version}

- Fixed a variety of tests so they pass
- Fixing `zesk\Contact_Address_Parser` processing for `zesk\Contact_Address::store()`
- Renamed tests to be in the `_Test.php` format for eventual migration to `PHPUnit`
- `Country::find_country` now takes `$application` as first parameter for context
- `Model::factory`, `Object::factory`, and `Widget::factory` now support `$application->factory()` instead
- `new Net_HTTP_CLient()` and other calls now take `$application` as first parameter
- `zesk\Application::factory` is a shortcut to `$application->objects->factory()`
- `zesk\Database`: Removed references to global `$zesk`
- `zesk\Mail`, `zesk\Options::inherit_global_options`, and `new Net_Foo` calls all now take `$application` as the first parameter.
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\Server` remove references to `$zesk`
- `zesk\Timestamp::parse` fix non-string values passed to `preg_match`
- `zesk\\Options::inherit_global_options` now takes `$application` as first parameter.


<!-- Generated automatically by release-zesk.sh, beware editing! -->
