# zesk configure

This command-line tool is very helpful in managing remote deployments of code using a simple grammar for keeping files in sync with the local file system and a source code repository.

Sometimes, it's nice to be able to edit files in-place on a server system in order to get changes in place quickly, instead of having to run through editing a file in the repository and then copying it into place. 

The `configure` command supports prompting the user to determine which version of the file is correct, and either updating the source or the destination when there's changes.

## File Format

The `configure` file format is a simple `command parameter` line syntax, an example as follows:

	subversion			https://my.repo.com/project/trunk					{application_root}
	symlink				{configure_path}/php								{codehome}/host/webserver/php
	file				{configure_path}/php/php.ini						/etc/php5/apache2/php.ini
	file				configure_path}/php/php-cli.ini						/etc/php5/cli/php.ini

## Configure Commands

### `subversion repository-url directory-path`

The repository `repository-url` is updated or checked out to `directory-path`. Note that authentication is not supported by this command, so any authentication should be set up already in the `$HOME/.subversion` saved configuration.

### `mkdir target [want-owner [want-mode]]`

Create a directory **target** and optionally assign it owner and mode.

### `file source-path destination-path [want-owner [want-mode]]`

Copy a file from source to destination. Destination user/group and file permissions are always fixed to remain the same as it was previously.

### `file_catenate source`

Catenate files found in various host configurations for this system.

### `subversion repo-url target-path`

Check out and update `repo-url` to directory `target-path`. Authentication must be stored already or allow anonymous updates.

### `symlink symlink target`

Creates **symlink** to **target** and ensures it's updated or correct.
 
