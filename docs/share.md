# Sharing resources in Zesk

On the one hand, we want Zesk to be modular, and we want to break functionality into discrete groupings. 

On the other hand, we're not a huge fan of placing `index.php` files in every directory, and exposing our application files by having the Apache document root point to the entire application code base. There's got to be an easier way to share module resources (JavaScript, images, etc.) in an application in a way which is modular, right?

No, in Zesk, you should have a very small document root directory which contains `index.php` and any static resources your application needs (e.g. images, JavaScript, CSS/LESS/SASS, etc.), structured something like this:

- **Application root**
 - my.application.inc
 - classes/
 - theme/
 - public/index.php
 - public/images/logo.gif
 - public/js/site.js
 - public/css/site.css
 
The `public` directory is the `zesk()->paths->document()` so that no application files are exposed.

So, how do we share our module resources?

## The `/share/` directory

Unfortunately, for now, this directory path is reserved. TODO: Change it to something no one would ever use (zesk-share, zshare?).

But here's how it works. You register a `share path` using:

	app()->share_path("/path/to/somwhere", "prefix");
	
So that now when I visit:

	http://zesk-site/share/prefix/special.js
	
It will serve the file:

	/path/to/somewhere/special.js
	
This basically allows you to choose points anywhere in your source tree to serve via the share path.

## How doth this sorcery work?

It's a simple `Controller` called `Controller_Share` which does the logic of mapping one to the other. It's an empty class (inherits from zesk\Controller_Share) so you can override it in your application if you want special functionality.

However, your first question is: Ain't that a tad slow?

The short answer is: Yes! 

The long answer is: Yes, but only the first time you load the resource.

By default, there's an option for `Controller_Share` which is `build` which should be set to `true` in production systems.

In your configuration file, this would look like:

`.conf` file:

	Controller_Share::build=true
	
`.json` file:

	"Controller_Share": {
		"build": true
	}

The controller supports parsing `If-Modified-Since` headers if the web browser supports them, and sends `Expires` headers for +1 year if it's a versioned resource (Query string contains a key `_ver`), or for +1 hour for all other resources.

## Modules and share paths

Setting up a module share path should be done in one of the following places:

- The `name.module.inc` file which is `require_once`d in PHP when your module is loaded.
- Your module's `Module::initialize` call (called right after creation of your `Module` subclass)
- By declaring a "share_path" setting in `your.module.conf` or `your.module.json` 