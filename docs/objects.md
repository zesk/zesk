# Object Relational Mapping (ORM)

Zesk contains a powerful `zesk\ORM` implementation which supports automatic integration with databases and traversal of objects as linked by database tables. When schema changes are made, you can run a command to output SQL statements which will bring your current database up-to-date without having to write your own ALTER scripts and track what to deploy where. 

Objects support an Object-Relational Mapping interface; to create an object in the system:

	classes/Task.php - defines class Task
	classes/Class/Task.php - defines class Class_Task
	classes/Class/Task.sql - SQL code to generate the table
	
`Task` will be the instance (usually a row) from the database, and `Class_Task` defines the columns and relationships of the object. `Task.sql` is a `CREATE TABLE` statement and (optionally) `INSERT` statements to create the table the first time, and also provides the schema definition for this object.

## Definining `zesk\Class_Foo`

When defining your class, you much subclass `zesk\Class_Base` which is the base class for all `zesk\Class_Foo` classes.

In this case:

	namespace awesome;
	class Class_Task extends zesk\Class_Base {
		public string $id_column = "id";
		
		public array $column_types = [
			"id" => self::type_id,
			"name" => self::type_string,
			"created" => self::type_created,
			"completed" => self::type_timestamp,
		];
	}
	
And our `Task.sql` file:

	-- Database: MySQL
	CREATE TABLE `{table}` (
		id		integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
		name	varchar(255),
		created	timestamp NOT NULL,
		completed NULL,
		INDEX c (completed)
	);

And finally our `Task.php` class:

	namespace awesome;
	class Task extends zesk\ORM {}

## Including it in our application

In order to get Zesk to see our ORM instance, we have to pass it back as a class to the ORM module. You can do this by returning the class name string in any of the following methods:

- Via the `$application` [hook](hooks.md) `orm_classes`
- Via the `zesk\Module_ORM` [hook](hooks.md) `classes`
- Via a module's `$model_classes` protected member (easily overridden in subclasses)
- Via a module's `model_classes()` method (which defaults to returning the value of the `$model_classes` member above)
- Reference the class name from any other `zesk\Class_Base` via the `$has_one` or `$has_many` members.

In our case, we'll add it our our main application:

	namespace awesome;
	class Application extends zesk\Application {
		function hook_orm_classes() {
			return [
				Task::class,
			];
		}
	}

And then to confirm we'll use the command-line utility (in the `ORM` module) `zesk classes` to see it:

	zesk classes
	
(Note: This tool also can show the table and database names of your objects as well when you start to connect to different data sources)

	zesk classes --table --database
	
## Synchronizing the Schema

To synchronize your application's concept of the database schema with the database itself, use:

	> zesk schema
	NOTICE: Running all update hooks - no hooks found
	NOTICE: Running module update hooks - no hooks found
	NOTICE: Running application update hooks - no hooks found
	-- Synchronizing schema for class: aweseome\Task;
	CREATE TABLE `Task` (
		id		integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
		name	varchar(255),
		created	timestamp NOT NULL,
		completed NULL,
		INDEX c (completed)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

It will output a list of `CREATE`, `ALTER` and `DELETE` SQL statements to bring your database up-to-date.

To do an update, type:

	zesk schema --update
	
To execute each statement against the database.

For sites which connect to multiple databases, it will output a SQL comment indicating that the other database needs to be updated, e.g.

	> zesk schema
	-- Other database updates:
	-- zesk database-schema --name central --update

## Keeping PHP code in-sync with SQL
