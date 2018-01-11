# Cron Tasks in Zesk

<!--
@related classes/cron.inc
@related command/cron.inc
-->

Like most application frameworks, it's useful to be able to run tasks intermittently which are not a part of your regular application framework. Things like:

- Garbage collection
- Data processing which is slow, or does not need to happen on page view
- Longer processes 

Specific examples of cron tasks which run within Zesk:

- `zesk\Application` - Checks if the schema needs to be updated
- `zesk\Session_Database` - Deletes old sessions every minute

## Cron features

Cron will run any **static function** in any class of type `zesk\Module`, `zesk\Application`, or `zesk\ORM` which has the one of the following function names:

- `cron` - Runs every cron run, on all servers
- `cron_minute` - Runs every minute, on all servers
- `cron_hour` - Runs every hour, on all servers
- `cron_day` - Runs every day, on all servers
- `cron_week` - Runs every week, on all servers
- `cron_month` - Runs every month, on all servers
- `cron_year` - Runs once a year (!), on all servers
- `cron_cluster` - Runs every cron run, on a single server only
- `cron_cluster_minute` - Runs every minute, on a single server only
- `cron_cluster_hour` - Runs every hour, on a single server only
- `cron_cluster_day` - Runs every day, on a single server only
- `cron_cluster_week` - Runs every week, on a single server only
- `cron_cluster_month` - Runs every month, on a single server only
- `cron_cluster_year` - Runs once a year (!), on a single server only

All methods take a single parameter, the application. If you know your cron task will only run within your application, you can type your cron call to take your application type.

These methods should be declared as

	class Vehicle extends ORM {
		public static function cron(zesk\Application $application) {
		
		}
	}
	
Methods take a single parameter (the application) and return no values for compatibility with potential upgrades.

The suffix indicates the frequency that the cron task will be run.

So, if I have an object, which requires regular maintenance or checking, I could write:

	class Automobile extends ORM {
		...
		public static function cron_cluster_month(zesk\Application $application) {
			foreach ($application->orm_registry($1)->query_select()->where("IsActive", true)->orm_iterator() as $auto) {
				$auto->monthly_maintenance();
			}
		}
	}
	
And it would be run approximately every month on a single server in a multi-server cluster.

Note that the cron run scheduler simply ensures that each task is run at least once a month, by comparing the current run time with the previously run time for each group of tasks.

Cron **does use** a database lock to ensure that one copy of cron is running at any time, per server. Locks are made for each server, and then for the entire cluster to ensure that only one version is running at a time. 

## No ordering

Cron methods within a specific invocation are not run in any particular order, and applications should make efforts to avoid requiring ordering of functionality. If ordering of method invocation is needed, methods should be combined into single procedures to ensure correct ordering, or processing can be placed in `Module::hook_cron_before` (before all cron methods are run) or `Module::hook_cron_after` (after all cron methods are run).

## Short tasks

Cron tasks should be short tasks, less than a few seconds each. You can set the directive `zesk\Module_Cron::elapsed_warn` to the number of seconds you wish to see warnings about. Cron functions which takes longer than the number of seconds specified will output a warning to the logger.

## Cron via Command Line

To run cron tasks from the command line, use the `zesk` command line utility, and run

    zesk cron

This invokes cron using `zesk\Command_Cron` located at `$ZESK_ROOT/command/cron.php`.

The available settings which change the behavior of cron are:

- `zesk\Module_Cron::time_limit` - The number of seconds the cron should run for, by default forever.

You can set this, if desired, via the command line settings, like so:

	zesk --zesk\\Module_Cron::time_limit=600 cron
	
Which will quit after 10 minutes.

It's recommended to run cron for your application every minute using `crontab`, using a line like:

    * * * * * $HOME/zesk/bin/zesk.sh --cd $HOME/myapplication/ cron > $HOME/log/zesk-cron.log

## Cron via web page requests

On some shared hosts it's impossible to schedule a cron task using crontab. In these cases, you can run cron tasks whenever you have page requests to your web server. This runs by loading a small JavaScript file which, in fact, runs the cron tasks.

To do so, set the globals:

- `zesk\Module_Cron::page_runner` to `true`

This extends your `Router` to add a special page which runs cron, and adds it to every page request. 

## Debugging Cron tasks

The best way to run cron tasks is to run them via a web browser and use a debugger. Enable `zesk\Module_Cron::page_runner` and ensure that the page is added to your web application, then debug the page itself.

Similarly, you can use

	zesk cron --list 
	
To display the cron tasks which may possibly be run each occurance. 

You can run a database query such as:

    DELETE FROM Settings WHERE Name LIKE 'zesk\Module_Cron::%'
    DELETE FROM Server_Data WHERE Name LIKE 'zesk\Module_Cron::%'

It will run then every cron task on each page request. Do this with caution in live environments.

Alternately, you can invoke cron from the command line and use [Logging Commands](log.md) to output debugging information.
