# `zesk\Object`s

Zesk contains a powerful `Object` implementation which supports automatic integration with databases and traversal of objects as linked by database tables. When schema changes are made, you can run a command to output SQL statements which will bring your current database up-to-date without having to write your own ALTER scripts and track what to deploy where. 

Objects support an Object-Relational Mapping interface; to create an object in the system:

	classes/task.inc - defines class Task
	classes/class/task.inc - defines class Class_Task
	classes/class/task.sql - SQL code to generate the table
	
`Task` will be the instance (usually a row) from the database, and `Class_Task` defines the columns and relationships of the object. `task.sql` is a `CREATE TABLE` statement and (optionally) `INSERT` statements to create the table the first time, and also provides the schema definition for this object.

## Definining `zesk\Class_Foo`

When defining your class, you much subclass `zesk\Class_Object` which is the base class for all `zesk\Class_Foo` classes.

In this case


## Synchronizing the Schema

To synchronize your application's concept of the database schema with the database itself, use:

	zesk database-schema

It will output a list of `CREATE`, `ALTER` and `DELETE` SQL statements to bring your database up-to-date.

To do an update, type:

	zesk database-schema --update
	
To execute each statement against the database.

## Keeping PHP code in-sync with SQL

