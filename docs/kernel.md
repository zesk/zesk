# Zesk Kernel

As of around July 2016, the zesk kernel was incorporated wholly into the `zesk::` core class, which was deprecated and split into individual classes which hang off of the master object `zesk\Kernel`.

The kernel can be accessed via the global variable `$zesk` (which may be overwritten accidentally), or via the `zesk()` function call which returns a static variable which can not be modified once zesk is intialized.

## Kernel initialization

The kernel can be initialized with globals prior to zesk initialization by setting the global `$_ZESK` to an array of name/value pairs.

## Autoloader

## Class Registry

## Hook Registry

## Object Registry

## Process Tools

### `zesk()->process->alive($pid)`

Safely tests if a process ID currently running on the current server, assuming operating system permissions allow signals to be sent and received to the process. Abstracts necessary posix module inclusion.

If `posix_kill` is not an available function, throws an exception.

Otherwise returns a boolean value indicating whether the process ID is running.

Note that some UNIX distributions re-use process IDs and as such, process IDs should not be re-used over long periods of time or between reboots of a system.

### zesk()->process->execute($command, ...)

Run a process, escaping arguments to the process. 

Usage with positional arguments:

    zesk()->process->execute("ls -laF {0} > {1}", $directory, $target_file);

Usage with named arguments:

	zesk()->process->execute("ls -laF {dir} > {target}", array("dir" => $directory, "target" => $target_file));

If the command returns a non-zero result, `zesk\CommandFailed` is thrown and the available output and return status is included within that exception. Otherwise returns the output of the command, which may be blank.

Calling functions can assume the process execution was successful if no exception is thrown.
	
### zesk()->process->execute_arguments($command, array $args = array(), $passthru = false)

Run a process, optionally invoking with `passthru` instead of `exec`.

Usage with named arguments:

	zesk()->process->execute_arguments("ls -laF {dir} > {target}", array("dir" => $directory, "target" => $target_file));

If the command returns a non-zero result, `zesk\CommandFailed` is thrown and the available output and return status is included within that exception. Otherwise returns the output of the command, which may be blank.

Calling functions can assume the process execution was successful if no exception is thrown.

## Logging

## State
