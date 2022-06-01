# Zesk JavaScript tools

## Introduction

Zesk provides a front-end JavaScript library which contains useful tools which mimic much of the built-in tools available in Zesk's PHP library. When possible, semantics are as similar as possible between the JavaScript version of a function and the equivalent PHP version.

As well, note that all Zesk JavaScript depend on having jQuery present.

## Functions

### `mixed avalue(Object object, string key, mixed default)` Retrieve a optional value from an object

Returns a value in JavaScript Object `object`, or `default` if `key` not present. The following:

	var v = avalue(obj, 'time', 20);
	
can be more succinctly be written in JavaScript as:

	var v = obj.time || 20;
	
The function still proves useful, however.

### `mixed object_path(Object object, string path, mixed default)` Retrieve a value from a deep object

Like `avalue`, but traverses an object hierarchy.

	var v = object_path(obj, 'thing.to.retrieve', 20);

### Type functions

- `boolean is_bool(value)` - Returns true if vaue is a JavaScript boolean 
- `boolean is_numeric(value)` - Returns true if vaue is an JavaScript number
- `boolean is_string(value)` - Returns true if vaue is a JavaScript string
- `boolean is_array(value)` - Returns true if vaue is a JavaScript array
- `boolean is_object(value)` - Returns true if vaue is a JavaScript object
- `boolean is_integer(value)` - Returns true if vaue is an integer
- `boolean is_function(value)` - Returns true if vaue is a JavaScript function
- `boolean is_float(value)` - Returns true if vaue is a floating point number
- `boolean is_date(value)` - Returns true if vaue is a JavaScript Date
- `boolean is_url(value)` - Returns true if vaue is a URL string 
- `string gettype(value)` - Returns the type of object

## `html` tools - Generation/Manipulation of HTML code

### `String html.encode(String html)` Encode HTML

Like PHP's `htmlspecialchars`, used to encode attributes for HTML tags

### `String html.decode(String html)` Decode HTML

Reverse of `html.encode` - converts HTML entities into text values.

### `String html.to_attributes(mixed attributes)` Convert attributes into attributes Object

Takes a string or object parameter and returns an Object which is that 
	
### `String html.attributes(Object attributes)` Convert Object to HTML attributes string

Convert object to a string which can be used as attributes in an HTML tag.
	
### `String html.tag(string type, [ mixed attributes, ] string value)` Generate HTML tag (with attributes)

This first form receives two parameters, the tag type, and the value within the tag:

	$(elem).html(html.tag("strong", "There will be bold."));

The second form extends the function to support optional HTML attributes as the 2nd parameter. 

## `zesk` tools - Global state and settings

### `string zesk.query_get(name, default)`

Retrieve a query string parameter `name` or return `default` if not found.

### `string zesk.get_path(path, default)`

Sets Zesk Settings value within the global state. Module information is stored in:

	modules.module_name.value1

So, for the module "dropfile", the values are stored:

	modules.dropfile.

### `string zesk.setPath(path, value)`

Get Zesk Settings value within the global state.
