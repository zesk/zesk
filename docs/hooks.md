# Hooks in Zesk

## What are hooks?

A hook is the ability to run code at a specific point in time during a program. Hooks in Zesk allow you to:

- Modify objects in the system from external modules or your application
- Enhance functionality to existing programs with no modifications

Hooks in zesk take a few forms, but ultimately are a combination of (we hope) the best worlds 
of Drupal __function name space__ ease and Wordpress' __registration and invocation system__.

[List of standard Zesk hooks](hooks-list.md)

So, there are basically two places to register and call hooks, in zesk:

## Call a hook

    zesk()->hooks->call($hook, ...);
	zesk()->hooks->call_arguments($hook, array $arguments, $default=null);
	
And in any object which inherits "Hookable" which ... is most of them:

    /* @var $u User */
    $u = User::instance();
	if ($u->call_hook("allowed_request", $request)) {
		// fun stuff
	}

You'll note that all hook methods tend to have two forms, one for ease, and one which allows for 
a default value to be returned when no hook exists:

	/* @var $u User */
	$u = User::instance();
	if ($u->call_hook_arguments("allowed_request", array($request), true)) {
		// fun stuff
	}

## Hookable call order

Conceptually, a hook attached to an object implements `hook_foo` and then whenever the `foo` hook is called, the function gets invoked.

	class Project extends Hookable {
		function hook_finish() {
			$this->clean_up_files();
			$this->put_away_resources();
			$this->zip_up_files();
			$this->move_to_archive_folder();
		}
		...
	}
	
Now, Captain Obvious would point out that "You can use regular PHP inheritance for that!" And Captain Obvious would be correct. However, the hook system allows for something which regular PHP inheritance does not allow: 

- programmatic interception of hooks on a per-object basis
- external access to internal hooks

Let's handle these each in order:

### Interception of hooks on a per-object basis

You'll note that [`zesk\Hookable`](`hookable.md`) inherits from [`zesk\Options`](options.md) so it inherits the ability to set arbitrary options on any `Hookable` object. There's a special option called "hooks" which allows the user to define a hook to be called. You can then turn this on and off for a specific object if you wish:

	$this->option_append("hooks", "delete", "project_deleted");
	
Then `project_deleted` will be called with our object upon deletion.

## External access to internal hooks

Note as well that we can also TODO
	
# Registration based vs. function and method name space

How do you get your hook called, you say? In one of three ways:

- Call `zesk()->hooks->add($hook, $function)` to register your hook in the global hook tables
- Create a method called "`hook_$hook`" inside of a hookable class or subclass.
- Register your class with `zesk()->classes->register()` and then invoke using `zesk()->hooks->all_call($method)`

`zesk()->hooks->call` and `zesk()->hooks->call_arguments` are essentially just buckets where you can register your hook. 
Zesk global hooks are registration-based. 

Object-hooks are method space declaration. Meaning if you create a method in your object which is
named 

	class Foo extends Hookable {
		function hook_dee() {
			echo "Dee called";
		}
	}
	$foo = new Foo();
	$foo->call_hook("dee");

Then your method will be called at the appropriate time.

The final mechanism allows for hooks to be registered as static class methods, and just by 
registering your class will you be able to be called at the appropriate place.

	class Foo extends zesk\Module {
 		static function add_to_cart() {
			echo "added to cart";
		}
	}
	
	$product = Object::factory('Product')->fetch(23);
	zesk()->hooks->call_all("Module::add_to_cart", $product);
	
Calls method "add_to_cart" in all classes of type "Module" (if the method exists)
	
# Configuring and manipulating arguments and return values

Typically, calling a hook requires a list of arguments to be passed to each hook, and then each hook may optionally return a value.

Things to consider is that a single hook may be a single function, or multiple functions which are called in order.

The question is: _How do we handle these cases for single, multiple hooks, and return values?_

The hooks system is designed to handle common cases for this.

## Return values

TODO