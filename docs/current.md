## Release {version}

### Deprecated functionality

- `zesk\URL` marked deprecated `current_*` calls for future removal, added comments.
- `zesk\Application::query_foo` calls are all deprecated

### New modules

- `ORM`, `Session` and `Widget` are now modules.
- `Database_Query_*` have been moved to `ORM` (including comments such as `database-schema` etc.)
- `Widget` classes amoved to `Widget` module and depends on `ORM`

### Renamed classes

- Anything which had the term `Object` as a namespace or related has been renamed to `ORM` or possibly `Model`
- `Database_Schema` has been renamed to `ORM_Schema`

### ORM Rename

Yes, we've renamed the `zesk\Object` class to `zesk\ORM` because PHP 7.2 makes the term `Object` a reserved word for class or namespace names. So... Major changes in how system is organized:

- `modules/orm` added and all `Object` and `Class_Object` funtcionality moved into there and renamed `ORM` and `Class_ORM` respectively.
- Refactored `Session` and moved to its own module. Likely will need future detachment from the `zesk\Application` class
- Refactored references to `ORM`-related classes and moved into `ORM` module (`User`, `Lock`, `Domain`, `Meta`, )
- References to `ORM` will be detached from zesk core in this and upcoming releases
- When possible `zesk\ORM` references are migrated to `zesk\Model` references instead

Due to the fact that `Database_Query` subclasses all depend on `ORM` and all `zesk\Widget` support `Database_Query_Select`, so all widgets have been moved to their own module `widget` which depends on `orm` as well.

### Application registry and factory calls

The `zesk\Application` is the center of your application, naturally, but it has evolved to the central point for object creation. To allow distrubution of the factory resposibilities, `zesk\Application` now allows modules (or anything) to register a central factory method. So:

	class MyInvoiceModule extends \zesk\Module {
		public function initialize() {
			// This adds the method "invoice_factory" to the zesk\Application
			$application->register_factory("invoice", array($this, "invoice_factory"));
		}
		public function invoice_factory(Application $application, $code) {
			return new Invoice($application, $code);
		}
	}

There are a variety of new patterns, largely those which remove `ORM` functionality from the `zesk\Application` core.

The main point here is that shortcuts which previously pulled a bit of data from the `Class_Object` (now `Class_ORM`) should use the full call, so:

OLD method:

	$application->query_select(User::class, "user");
	$application->class_object_table(User::class);
	$application->class_object(User::class);
	$application->synchronize_schema();
	
NEW method:

	$application->orm_registry(User::class)->query_select("user")
	$application->class_orm_registry(User::class)->table()
	$application->class_orm_registry(User::class);
	$application->orm_registry()->schema_synchronize();

Two calls are available now from the `zesk\Application`:

	$application->orm_factory($class = null, $mixed = null, array $options = array())
	$application->class_orm_registry($class = null)
	$application->orm_registry($class = null)

The main difference between a `registry` and `factory` call is that the `registry` call returns the same object each time.

<!-- Generated automatically by release-zesk.sh, beware editing! -->
