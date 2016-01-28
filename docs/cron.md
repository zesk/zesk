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

- `Zesk_Application` - Checks if the schema needs to be updated
- `Session_Database` - Deletes old sessions every minute

## Cron features

Cron will run any static function in any class of type `Module`, `Application`, or `Object` which has the one of the following function names:

- `cron` - Runs every cron run, on all servers
- `cron_minute` - Runs every minute, on all servers
- `cron_hour` - Runs every hour, on all servers
- `cron_day` - Runs every day, on all servers
- `cron_week` - Runs every week, on all servers
- `cron_month` - Runs every month, on all servers
- `cron_year` - Runs once a year (!), on all servers
- `cron_cluster` - Runs every cron run, once per database
- `cron_minute` - Runs every minute, once per database
- `cron_hour` - Runs every hour, once per database
- `cron_day` - Runs every day, once per database
- `cron_week` - Runs every week, once per database
- `cron_month` - Runs every month, once per database
- `cron_year` - Runs once a year (!), once per database

These methods should be declared as

	class Vehicle extends Object {
		public static function cron() {
		
		}
	}
	
Methods take no parameters and return no values for compatibility with potential upgrades.

The suffix indicates the frequency that the cron task will be run.

So, if I have an object, which requires regular maintenance or checking, I could write:

	class Automobile extends Object {
		...
		public static function cron_cluster_month() {
			foreach (Object::class_query("Automobile")->where("IsActive", true)->object_iterator() as $auto) {
				$auto->monthly_maintenance();
			}
		}
	}
	
And it would be run approximately every month on a single server in a multi-server cluster.

Note that the cron run scheduler simply ensures that each task is run at least once a month, by comparing the current run time with the previously run time for each group of tasks.

Cron **does use** a database lock to ensure that one copy of cron is running at any time, per server. Locks are made for each server, and then for the entire cluster to ensure that only one version is running at a time. 

## Cron via Command Line

To run cron tasks from the command line, use the `zesk` command line utility, and run

    zesk cron

This invokes cron using `Command_Cron` located at `$ZESK_ROOT/command/cron.inc`.

The available settings which change the behavior of cron are:

- `cron::time_limit` - The number of seconds the cron should run for, by default forever.

You can set this, if desired, via the command line settings, like so:

	zesk --cron::time_limit=600 cron
	
Which will quit after 10 minutes.

It's recommended to run cron for your application every minute using `crontab`, using a line like:

    * * * * * $HOME/zesk/bin/zesk.sh --cd $HOME/myapplication/ cron > $HOME/log/zesk-cron.log

## Cron via web page requests

On some shared hosts it's impossible to schedule a cron task using crontab. In these cases, you can run cron tasks whenever you have page requests to your web server. This runs by loading a small JavaScript file which, in fact, runs the cron tasks.

To do so, set the globals:

- `cron::page_runner` to `true`

This extends your `Router` to add a special page which runs cron, and adds it to every page request. 

## Debugging Cron tasks

The best way to run cron tasks is to run them via a web browser and use a debugger. Enable `cron::page_runner` and ensure that the page is added to your web application, then debug the page itself.

Similarly, you can use

	zesk cron --list 
	
To display the cron tasks which may possibly be run each occurance. 

You can run a database query such as:

    DELETE FROM Settings WHERE Name LIKE 'cron::%'
    DELETE FROM Server_Data WHERE Name LIKE 'cron::%'

It will run then every cron task on each page request. Do this with caution in live environments.

Alternately, you can invoke cron from the command line and use [Logging Commands](log.md) to output debugging information.