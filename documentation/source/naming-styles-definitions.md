## Naming Styles Definition

When writing code, programmers often adopt a naming convention for terms used in their code. 

In particular, the following styles exist:

### First-lower [CamelCase][]

This convention is used in languages such as Java and PHP for class methods.

	$x = new userGroup();
    $a->newSession();
	openFileAndParse($filename, stripWhitespace);
	writeToDisk($fd);
	
### First-upper [CamelCase][] Convention

This convention is used for class names in Java and PHP, and sometimes object methods.

    $x = new UserGroup();
    $a->NewSession();
	OpenFileAndParse($filename, StripWhitespace);
	WriteToDisk($fd);
	
### Lower Underscored

This convention separates distinct words by an underscore.

    $x = new user_group();
	$a->new_session();
	open_file_and_parse($filename, strip_whitespace);
	write_to_disk();

### Upper Underscored

This convention is usually used for constants in many languages.

	$X = NEW USER_GROUP();
	$A->NEW_SESSION();
	OPEN_FILE_AND_PARSE($FILENAME, STRIP_WHITESPACE);
	WRITE_TO_DISK($FD);

It's rarely used for methods or functions, although in PHP it could be as methods and functions in PHP are case-insensitive.

[CamelCase]: http://en.wikipedia.org/wiki/CamelCase "CamelCase"
