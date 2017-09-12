## Release {version}

- Fixing `zesk\Contact_Address_Parser` processing for `zesk\Contact_Address::store()`
- `Country::find_country` now takes `$application` as first parameter for context
- `Model::factory`, `Object::factory`, and `Widget::factory` now support `$application->factory()` instead
- `zesk\Application::factory` is a shortcut to `$application->objects->factory()`
- `zesk\Database`: Removed references to global `$zesk`
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\\Options::inherit_global_options` now takes `$application` as first parameter.


<!-- Generated automatically by release-zesk.sh, beware editing! -->
