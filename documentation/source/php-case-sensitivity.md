## Understanding PHP's case-sensitivity

### PHP functions and methods

In PHP, classes, functions and methods are case-insensitive. That is:

    // Class
    $x = new User_Tag();
	$x = NEW USeR_tAG();
	
    // Methods
    $object->store();
	$object->StOrE();

    // Functions
    ksort($items);
    KSORT($items);

All are OK and compile fine, and behave identically. This is largely a problem only when needing to ensure case for class loading in file systems which are case sensistive (the majority of servers where PHP runs).

### Includes and Files

Whether file paths are case-sensitive or not, unfortunately, depends greatly on the platform (Mac OX S, Windows, UNIX), and particularly the file system settings. On the following platforms, file names are case-insensitive (in general):

- Mac OS X 
- Microsoft Windows 

On Unix variants such as Linux-kernel Operating Systems, *BSDs, etc. files are generally case sensitive.

### Paths and directory delimiter

On Unix and Mac OS X, the forward slash "`/`" is generally used as the [`DIRECTORY-SEPARATOR`](http://us2.php.net/manual/en/dir.constants.php), while on Windows the backslash "`\`" is used. So:

    /app/vendor/zesk/zesk/autoload.php # Unix
    C:\app\vendor\zesk\zesk\autoload.php # Windows

Note that Windows uses the colon "`:`" as the first portion of the path to identify the drive letter.
