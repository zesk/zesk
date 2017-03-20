#!/bin/bash 
zesk cannon --verbose 'extends Command_Base' 'extends zesk\Command_Base'
zesk cannon --verbose 'extends Command_Iterator_File' 'extends zesk\Command_Iterator_File'
zesk cannon --skip-when-matches 'zesk\Module_Cron' --verbose 'Module_Cron' 'zesk\Module_Cron'
zesk cannon --skip-when-matches 'namespace zesk' --skip-when-matches 'use zesk\Timer' --verbose 'new Timer(' 'new zesk\Timer('
zesk cannon --also-match 'namespace zesk' --verbose 'new \Timer(' 'new Timer('
zesk cannon ::status_exists ::object_status_exists
zesk cannon ::status_unknown ::object_status_unknown
zesk cannon ::status_insert ::object_status_insert
zesk cannon --dry-run '>memberBoolean(' '>member_boolean('
zesk cannon --dry-run '>memberInteger(' '>member_integer('
zesk cannon --dry-run '>memberSet(' '>set_member('
zesk cannon --dry-run '>className(' '>class_name('

for c in str::cexplode str::explode_chars; do
	echo "Removed call: $c"
	php-find.sh $c
done
