# Router file format `.router`

The router file format uses an indentation-delimited file with some special syntax handling to make mapping out your application simpler.

Most applications take requests and return content. The router determines when someone requests a specific URL what code or content will be delivered, and what format that content will be in.

Let's start with a few defintions:

- Router: A group of routes for your application
- Route: A path which matches a HTTP Request and contains information on how to respond
- Route Path: A pattern like `user/{ID}` which matches a request

Routes are stored in a file format for simple management, typically located right next to your application file. So, if your application is:

	classes/application/myapp.inc
	
Then the router will be loaded from:

	classes/application/myapp.router

**Important**: You must have the following field defined in your `\zesk\Application` subclass:

	class MyApp extends \zesk\Application {
		public $file = __FILE__;
		...
	}

The `\zesk\Application` locates the router file using this field and by changing the file extension with `File::extension_change()`; this is implemented in `\zesk\Application::hook_router()`. If you have special handling of your router file, you can instead override `zesk\Application::hook_router()` to load your router in your own manner.

## Routing process

Routing occurs as follows:

1. A request is made to your web application
2. The application loads/creates the router
 - The router is built of many "routes" and are sorted in order of increasing weight
 - The router attempts to match each route to the passed in URI (path)
3. When a match is made, the URI is broken into segments using the slash (`/`) delimiter
4. Each segment is numbered (starting from zero) and passed to the route
5. The route converts each segment into the appropriate type
6. The route is executed and the page is output, or an alternate HTTP response is sent to the browser

To define your application's routes, you use a special file format called a `.router` file.

## .router format
	
The format of the router file is fairly straightforward. It contains one of three types of lines, and is based on whether the first character in the line is a space or not. (This is similar to Python, except there's only one depth.) It also supports line-comments using the `#` character.

### Lines beginning with # or blank are ignored

This allows you to put comments in the file if you want.

	#
	# myapp.router: Routes for the rest of us
	#
    login
	# Load the Controller_Login
        controller=zesk\Controller_Login
		# User login actions
		actions=["login"]
		theme=page/login
		# theme=page/newlogin
		
You can comment on any line as shown above, at any place on the line as long as the first non-white character on a line is a `#` character.

### Lines with no whitespace characters in the beginning are route path definitions

They are the page a user will visit, and contain some pattern-matching abilities:

	user(/{action}(/{ID}))
	help/*
	({controller}(/{ID}/({action})))
	user/report/{User user}
	state/preference/{name}

### Lines beginning with whitespace characters are route options

They are a set of name value pairs which is applied to ... wait for it ... **all of the routes** defined on previous lines.

So, for example:

	routea
	routeb
	routec
		content="Route: {0}"
		
Creates three routes each for `routea`, `routeb`, `routec`, each with identical route options.

## Route patterns
	
Let's break down what's available when using route patterns. It's a simplified pattern matching which allows for variable naming and typing, and specifying optional segments of the URL. A route pattern is something like:

	user(/{comma-list ids}(/{action}))
	

### Not required: `(optional)`

Putting parenthesis around a term in a route path makes it optional - it doesn't have to appear in the URL to match.

### Variable naming: `{name}`

Putting brackets around a term in the route name captures any non-slash characters and uses that name internally. You can pass variables through to your scripts using these names.

So this route:

    user(/{action}(/{ID}))

Will match:

	/user/view/51
	
And parameters available to the route are:

	action="view"
	ID="51"
	
### Variable typing: {class name}

You can alternately type and "load" object classes to be passed into your functions. So:

	user(/{User user}(/{action})
	
Will match the following:

    user/42
	user/42/
	user/42/edit

But not:

	user/42/edit/member

(You have to specify a `user(/{User user}(/{action}*))` for that.)

However, the value of `user` will be an object of class `User` and loaded using an object's hook `router_argument` which has the following syntax:

	class User extends zesk\User {
		...
		public function hook_router_argument(Route $route, $argument) {
			return $this->id($argument)->fetch();
		}
		...
	}
	
### Primitive types: {type name}

The primitive types currently supported are:

- `array`, `list`, `semicolon-list`, `comma-list`, `dash-list`, `integer`, `double`, `string`, `option`

You can use the following route-specific types to handle basic primitive types:

##### Lists of things

You can use:

- `{array items}`, 
- `{list items}`, 
- `{semicolon-list items}`

Which are are semicolon-separated lists: e.g. `12;53;74;92;wonky`

- `{comma-list items}`, Example: `12,53,74,red`

Allows you to use commas, if you prefer, or even dashes:

- `{dash-list items}`, Example: `S-O-S-404-123`

#### Primitive types

You can use your basic PHP types:

- `{integer a}`, Example: `59123`
- `{double b}`, Example: `51e+4`
- `{string s}`, Example: `John`

And you can force the variable to be set as a route option automatically (more on this later):

- `{option action}`, Example: `view`

## Route options

So, each route has a set of name/value pairs which set up its configuration. 

First, value types are autotyped using `\zesk\PHP::autotype()` which converts from a string to:

- null
- integer
- double
- boolean
- string
- json-decoded array

Second, the type of route will be created depending on the presence of one of the following named values in your route:

`\zesk\Route_Controller`:

	controller="Controller_{0}"
	
`\zesk\Route_Method`:

	method="output_page"
	
`\zesk\Route_Content`:

	content="content to output"

`\zesk\Route_Theme`:

	theme="page/body/welcome"

Note that each route type has additional optional options which affect the default behavior of the route. But first ... 
	
## Route options for all types of routes

The following route options apply to all types of routes and can be used in each.

### `content type`: Set default content type for route

For example, to return non-HTML content:

	js/dyna.js
		method=Application::dynajs
		content type="application/javascript"

### `status code`: Set the response status code

For redirects, for example:

	oldarticle
		redirect="/newarticle"
		status code=301
		content="That article has moved"

### `status message`: Set the HTTP response status message 

This is solely for development purposes to see responses in the HTTP response:

	oldarticle
		redirect="/newarticle"
		status code=301
		status message="I'm gumby, damnit!"
		content="That article has moved"

### ` redirect`: Redirect after route execution

If you want to do an automatic redirect after your route is invoked, set the "redirect" option in your route:

	oldarticle
		redirect="home"
		content="That article has moved"
 
### `weight`: Determines ordering of route evaluation

Routes are sorted by weight before matching occurs, so low weights are first, and high weights are last.

### `permission` determines what permissions are required to visit this route

See [permissions](). You can pass in a single permission, or multiple permissions:

	user(/{User user}(/edit))
		permissions=[{"action":"edit","context":"{user}","options":{"columns": "Login"}}]
		
Or using the singular form:

	user(/{User user}(/edit))
		permission="edit"
		permission context="{user}"
		permission options={"columns": "Login"}
		
## Route options for `zesk\Route_Content`

### `content`: Content to output

You can set the content to a string:

	js/my.js
		content="/* todo */"
		content type="application/javascript"

### `default content`: When no content is set, use this instead

	out/results.txt
		default content="Empty"

## Route options for `zesk\Route_Theme`

### `theme`: Theme to render

	out/{tpl}
		theme="outputtemplates/{tpl}"

### `theme`: Multiple themes

	out/{tpl}
		theme="outputtemplates/{tpl};homepage"

### `theme`: Multiple themes with different arguments

	out/{User user}/{tpl}
		theme[]="usertemplates/{tpl}.tpl"
		theme[]={"theme": "homepage.tpl", "arguments": { "user" : "{user}" }}
