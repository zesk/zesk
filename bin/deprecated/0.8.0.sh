#!/bin/bash
zesk cannon zesk::add_hook 'zesk()->hook->add'
zesk cannon zesk::hooks 'zesk()->hook->register_class'
zesk cannon zesk::has_hook 'zesk()->hook->has'
zesk cannon zesk::hook_arguments 'zesk()->hook->call_arguments'
zesk cannon 'zesk::hook(' 'zesk()->hook->call('
zesk cannon 'zesk::hook_array(' 'zesk()->hook->call_arguments('


zesk cannon zesk::class_hierarchy 'zesk()->classes->hierarchy'
zesk cannon zesk\\Classes::class_hierarchy 'zesk()->classes->hierarchy'
zesk cannon zesk::register_class 'zesk()->classes->register'

zesk cannon zesk::version 'zesk\Version::release'

fix_manually() {
	local pattern message
	
	pattern=$1
	message=$2
	echo "Manual fix for $1 - $message"
	zdeopen $1
	echo -n "Hit enter when changes are all fixed ... "
	read
}
fix_manually 'zesk\\Classes' 'fix occurrances'
#zdeopen 'zesk\\Hooks'
zdeopen 'zesk\\Autoloader'
fix_manually 'Cache::path' 'Use "$zesk->paths->cache = newvalue" instead'
fix_manually 'zesk::data_path' 'Use "$zesk->paths->data = newvalue" instead'

# global deprecated -> zesk::deprecated
# global assert -> zesk::assert
# global assert_callback -> zesk::assert_callback
# global share_path is now zesk::paths::share
# global command_path is now zesk::paths::commands
# global document_cache is now zesk::paths::document_cache
# global uid_path is now zesk::paths::uid
# global zesk_command_path is now zesk::paths::zesk_commands
# global zesk::data_path is now zesk::paths::data
# global zesk::temporary_path is now zesk::paths::temporary
# global zesk::cache_path is now zesk::paths::cache
# Setting global Cache::path does not immediately affect cache path anymore
# Setting global zesk::data_path does not immediately affect cache path anymore
# Setting global document_cache does not immediately affect document_cache path anymore
# Setting global uid_path does not immediately affect uid_path path anymore
# global http_document_root no longer set
# global document_root no longer set
# global script_filename no longer set