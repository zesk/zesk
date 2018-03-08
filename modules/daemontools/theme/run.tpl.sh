<?php

/* @var $conf string */
/* @var $app_user_name string */
/* @var $app_path_name string */
/* @var $args string */

ob_start();
?>#!/bin/bash
failed() {
	echo "$*" 1>&2
	sleep 10
	exit 1
}
CONF={CONF}
env_files="$CONF $(dirname $(pwd))/.env $(pwd)/.env"
for f in $env_files; do
	if [ ! -f $f ]; then
		echo "$f does not exist"
	else
		source $f
	fi
done
if [ -z "${APP_ROOT}" ]; then
	failed "{APP_ROOT} not defined in $env_files"
fi
if [ -z "$ZESK" ]; then
	ZESK=${APP_ROOT}/vendor/bin/zesk
fi
if [ ! -x "$ZESK" ]; then
	failed "ZESK binary not installed at $ZESK"
fi
if [ -z "${APP_USER}" ]; then
	failed "{APP_USER} not defined in $env_files"
fi
echo Starting in ${APP_ROOT}
if ! cd ${APP_ROOT}; then
	failed "Can not cd to ${APP_ROOT} - permissions problem"
fi
exec setuidgid "${APP_USER}" "$ZESK" daemon {args}--watch "${APP_ROOT}"
<?php

echo map(array(
	"CONF" => $conf
	"ARGS" => $args ? $args . " " : "",
	"APP_ROOT" => $app_path_name,
	"APP_USER" => $app_user_name,
), ob_get_clean());
