# Daemon Module

A daemon is a process which is running in the background which does work for your application.

Why would you need a daemon for your application?

- Run a reporting engine in the background
- Poll a database table for pending tasks
- Reading and importing log files
- Converting or processing images

Zesk daemons allow you to run individual methods in your Zesk application as daemons to do work. It has features such as:

- Automatic detection of daemon methods in all loaded modules
- Automatic management of daemon processes, with ability to stop and start individual methods
- Reporting of daemon status via cron task
- Ability to have multiple processes for each method
- Hook invocation on fork on master and child processes

## Create your first daemon

TODO

## Running your daemon

TODO

## Controlling the number of daemon processes per method

TODO

## How the Daemon command works

TODO

## Daemon care and best practices

### 1. Daemon processes should auto-restart

With any scripting language and background process, memory issues and long-term process garbage collection may accumulate over time. If your daemon processes die, they should be restarted. 

> We recommend D.J. Bernstein's [`daemontools`](https://cr.yp.to/daemontools.html) which is packaged with many server distributions. Naturally, your daemon processes should use `setuidgid` to your web user prior to running.

A sample daemontools `run` script looks like this:

	#!/bin/bash
	failed() {
		echo "$*" 1>&2
		sleep 10
		exit 1
	}
	conf=/etc/aweseome.conf
	env_files="$conf $(dirname $(pwd))/.env $(pwd)/.env"
	for f in $env_files; do
		if [ ! -f $f ]; then
			echo "$f does not exist"
		else 
			source $f
		fi
	done
	if [ -z "$APP_ROOT" ]; then
		failed "APP_ROOT not defined in $env_files"
	fi
	if [ -z "$ZESK" ]; then
		ZESK=$APP_ROOT/vendor/bin/zesk
	fi
	if [ ! -x "$ZESK" ]; then
		failed "ZESK binary not installed at $ZESK"
	fi
	if [ -z "$APP_USER" ]; then
		failed "APP_USER not defined in $env_files"
	fi
	echo Starting in $APP_ROOT
	if ! cd $APP_ROOT; then
		failed "Can not cd to $APP_ROOT - permissions problem"
	fi
	exec setuidgid $APP_USER $ZESK daemon --watch $APP_ROOT
	
You can configure your application by creating `awesome.conf` and `/etc/service/awesome/.env`
### 2. Have your daemon monitor the codebase, links, and any other related media 

We recommend running your daemons using something which auto-restarts them when the processes quits; why? **It simplifies deployments and development, generally.**


That said