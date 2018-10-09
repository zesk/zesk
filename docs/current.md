## Release {version}

- Added ability to store user password hash in the database as well as handle changing it
- Adding `zesk\Interface_Member_Model_Factory` `zesk\Interface_Factory` support to `zesk\Application` and `zesk\ORM` and support throughout module `ORM`
- Adding `zesk\Database_Query::set_factory(zesk\Interface_Member_Model_Factory $factory)`
- Removed `zesk\Database_Query::module_factory`
- Better linking between interated objects to allow child objects to inherit state from parent, specifically for polymorphic classes
- Deprecated `zesk\Database_Query::object_cache`, `zesk\Database_Query::object_class`, `zesk\Database_Query::class_object`, `zesk\Database_Query::object_factory`
- Added `zesk\Interface_Member_ORM_Factory` 

<!-- Generated automatically by release-zesk.sh, beware editing! -->
