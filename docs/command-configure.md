# zesk configure

This command-line tool is very helpful in managing remote deployments of code using a simple grammar for keeping files in sync with the local file system and a source code repository.

Sometimes, it's nice to be able to edit files in-place on a server system in order to get changes in place quickly, instead of having to run through editing a file in the repository and then copying it into place. 

The `configure` command supports prompting the user to determine which version of the file is correct, and either updating the source or the destination when there's changes.

## File Format

The `configure` file format is a simple `command parameter` line syntax, an example as follows:

	subversion			https://code.marketacumen.com/zesk/tags/STABLE		{codehome}/zesk
	subversion			https://my.repo.com/project/trunk					{codehome}
	file				{codehome}/host/webserver/php/php.ini				/etc/php5/apache2/php.ini
	file				{codehome}/host/webserver/php/php-cli.ini			/etc/php5/cli/php.ini

## Configure Commands

### `subversion repository-url directory-path`

The repository `repository-url` is updated or checked out to `directory-path`. Note that authentication is not supported by this command, so any authentication should be set up already in the `$HOME/.subversion` saved configuration.

### `file source-path destination-path`

Copy a file 

### `file_catenate source`
