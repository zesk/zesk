## Release {version}

### Deprecated calls

- Deprecated `zesk\Database_Query_Select::object_iterator` and `zesk\Database_Query_Select::objects_iterator` and related calls to use new term `ORM`
 - `zesk\ORMIterator zesk\Database_Query_Select_Base::orm_iterator($class = null, array $options = array())`
 - `zesk\ORMSIterator zesk\Database_Query_Select_Base::orms_iterator($class = null, array $options = array())`
 - `zesk\Class_ORM zesk\Database_Query_Select_Base::class_orm()`
 - `string|self zesk\Database_Query_Select_Base::orm_class($class = null)`
- Updated `bin/deprecated/0.15.sh` to make above changes automatically

### Bugs fixed

- Fixing `zesk\Cleaner\Module` configuration errors when instance `lifetime` is `NULL`
- `zesk\Module_Permission` - Fixing cache saving to actually save it

<!-- Generated automatically by release-zesk.sh, beware editing! -->
