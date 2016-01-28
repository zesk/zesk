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

All are OK and compile fine, and behave identically.

### Includes and Files

Whether file paths are case-sensitive or not, unfortunately, depends greatly on the platform (Mac OX S, Windows, UNIX), and particularly the file system settings. On the following platforms, file names are case-insensitive (in general):

- Mac OS X 
- Microsoft Windows 

On Unix variants such as Linux-kernel Operating Systems, *BSDs, etc. files are generally case sensitive.

### Paths and directory delimiter

On Unix and Mac OS X, the forward slash "`/`" is generally used as the [`DIRECTORY-SEPARATOR`](http://us2.php.net/manual/en/dir.constants.php), while on Windows the backslash "`\`" is used. So:

    /usr/local/zesk/zesk.inc # Unix
    C:\WINDOWS\zesk\zesk.inc # Windows

Note that Windows uses the colon "`:`" as the first portion of the path to identify the drive letter.

PHP will take Unix-style paths and translate them, automatically, to the local file system path syntax. This occurs only on the non-Unix systems. In addition, PHP accepts mixed style paths, such as:

    C:\WINDOWS\zesk/classes/net/http.inc

And will locate the file appropriately.
