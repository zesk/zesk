#!/bin/bash 
cannon_opts=""

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

Color_Off='\033[0m'       # Text Reset
Red='\033[0;31m'          # Red
Blue='\033[0;34m'         # Blue

IBlack='\033[0;90m'       # Black

pause() {
	echo -ne $Blue"Return to continue: "$Color_Off
	read
}

heading() {
	echo -e $Red$*$Color_Off
	echo -ne $IBlack
}

heading "zesk\Server::DISK_UNITS_FOO constants are now ALL CAPS"
zesk cannon disk_units_bytes DISK_UNITS_BYTES
zesk cannon disk_units_kilobytes DISK_UNITS_KILOBYTES
zesk cannon disk_units_megabytes DISK_UNITS_MEGABYTES
zesk cannon disk_units_gigabytes DISK_UNITS_GIGABYTES
zesk cannon disk_units_terabytes DISK_UNITS_TERABYTES
zesk cannon disk_units_petabytes DISK_UNITS_PETABYTES
zesk cannon disk_units_exabytes DISK_UNITS_EXABYTES

