# zesk configure

This command-line tool is very helpful in managing remote deployments of code using a simple grammar for keeping files in sync with the local file system and a source code repository.

Sometimes, it's nice to be able to edit files in-place on a server system in order to get changes in place quickly, instead of having to run through editing a file in the repository and then copying it into place. 

The `configure` command supports prompting the user to determine which version of the file is correct, and either updating the source or the destination when there's changes.

**Author's note**: It would be nicer if this was a client-server relationship with a symmetric key or some other authentication mechanism, but for now this is a simple way to keep hosts updated in a predictable manner.

## Configure Command Configuration

Configuration occurs on a host based on that hosts `uname -n` value. You can assign multiple "roles" to a single host. The process works as follows:

- The `etc/configure.conf` configuration file is loaded from your application directory

This file contains two settings used to configure your environment:

	zesk\Command_Configure::environment_files=["/etc/app.conf","./etc/app.conf"]
	zesk\Command_Configure::host_setting_name="COMMAND_CONFIGURE_ROOT"

Within the `/etc/app.conf` will be a setting `COMMAND_CONFIGURE_ROOT` which contains a path to the host configuration for your site. This path MUST exist and SHOULD contain a file called `aliases.conf`.

The `configure` command loads `aliases.conf` to and looks up your host name. The value should be a list of directories in the same directory which represent the configuration files related to the the role.

So, for example, your aliases.conf file may look like:

	mail1.example.com=["all","mailserver"]
	web0.example.com=["all","webserver"]
	web1.example.com=["all","webserver"]
	cache0.example.com=["all","cacheserver"]

And the directory will contain the directories: "all", "mailserver", "webserver", "cacheserver".

Note that the first time running `configure` on an application will attempt to determine and save the configuration using interactive prompts with the user.

## File Format

The `configure` file format is a simple `command parameter` line syntax, an example as follows:

	subversion			https://my.repo.com/project/trunk					{application_root}
	symlink				{configure_path}/php								{codehome}/host/webserver/php
	file				{configure_path}/php/php.ini						/etc/php5/apache2/php.ini
	file				{configure_path}/php/php-cli.ini					/etc/php5/cli/php.ini
	file_catenate		etc/app.conf										/etc/app.conf				no-map

Variables as defined in the "environment file" above will be replaced (`map()`ped) into each command line. Use the `defined` command to ensure variables are defined.

## Configure Commands

### `defined var1 var2`

Do not continue unless the variables listed are defined and have a non-empty value.

### `subversion repository-url directory-path`

The repository `repository-url` is updated or checked out to `directory-path`. Note that authentication is not supported by this command, so any authentication should be set up already in the `$HOME/.subversion` saved configuration.

*Only available when the Subversion module is loaded.*

### `mkdir target [want-owner [want-mode]]`

Create a directory **target** and optionally assign it owner and mode.

### `file source-path destination-path [want-owner [want-mode]]`

Copy a file from source to destination. Destination user/group and file permissions are always fixed to remain the same as it was previously.

### `file_catenate source target [flags]`

Catenate files found in various host configurations for this system. `source` is a relative path to the host directories for this server.

So, if my host is set up as:

	mail1.example.com=["all","mailserver"]

In the `aliases.conf` file, then the following line:

	file_catenate	app.conf	/etc/app.conf	no-map
	
Will search for

	all/app.conf
	mailserver/app.conf
	
Within the `host_setting_name` directory Concateate them into a file and then compare it with `/etc/app.conf` upon configuration.

Flags can be:

- `no-map` - Do not apply the configuration mapping to the files prior to generating a new source file
- `no-trim` - Each file is trimmed for whitespace and then catenated with a newline prior to catenation. This skips this step.

### `symlink symlink target`

Creates **symlink** to **target** and ensures it's updated or correct.
 
