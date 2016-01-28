# Permission Module

The permission module is a default, plugin permissions system for role-based and object-based permissions in a web application.

The main purpose of the permissions module is to allow flexible definition of permissions in your application. Generally, this is on a per-object basis and based on a user's role.

A role is simply a grouping of various permissions. Users may have one or more roles in an application, and the permissions module enables permissions to be checked using standard lookups, or callbacks within the application itself.

# Permission Naming

Permissions are generally scoped by class, although they don't have to be. That means that most permission names are of the form `class::action`, for example:

	Account::view all
	User::become
	Application::view home page
	Application::view manage page

But you can name permissions any unique string, if desired, so:

    "view the main page"
    "save graph states"
    "let the dog out"

Are all valid permissions. 

For each permission, however, there is an implicit "class" associated with them which is "all classes" or the string "*". So:

    "view the main page" === "*::view the main page"

# Permission hierarchy

So, each permission name has a class associated with it. When checking permissions, the class hierarchy is used to test that permission. So, if I check the permission:

    Account::view

Then the parent classes of Account::view are also checked, in order:

	Account::view
	Object::view
	Model::view
	Hookable::view
	Options::view
	*::view

Note that the "*" class is checked last, and represents the root class.

Obviously, you should be cautious in granting permissions at the upper levels (`Hookable::view`) for example.

# Permission checking

All permissions should be checked by calling the `->can` method on any `User` in the system. All permissions take an optional `Model` as context (e.g. when acting on objects), and a third `options` parameter for additional parameters if needed. This allows for checking complex interactions between different objects, if needed. So:

	$user = User::instance();
	if (!$user->can("view", $account)) {
		// Fail
	}
	if (!$user->can("transfer", $account, array('target' => $savings_account))) {
		// Fail
	}
	$account->transfer_to($savings_account, $amount);

would be a valid way to employ the permissions checking model.

# Registering Permissions

Permissions are implemented by implementing a few hooks:

- `Application::permissions`
- `Module::permissions`
- `Object::permissions`

These callbacks are implemented similarly, and should return an array structure which defines the permission for that particular object in the form:

	class Module_Foo {
		...
		public static function permissions() {
			return array(
			    "manage" => array(
				    "title" => "Manage this module",
					"class" => "...",
					"before_hook" => array(
						...
					),
					...
				)
			);
		}
	}
	
The structure returned is of the form "name" => permissions object fields which map directly to the `Permission` object in `Module_Permission`:

- `title` - Human-readable name for this permission
- `class` - Optional class for which this permission applies (e.g. "User", "Account", etc.)
- `before_hook` - A list of rules of other permissions to check before running the object hook to check this permission.
- `hook` - An alternate hook to invoke to check this permission on an object. Only valid if `class` is non-null. If not specified, defaults to `permission`
- `cache` - Defaults to true. Whether the result of this permission can be cached.

# Checking permissions

When testing for permissions, the permissions module runs through a sequence which tests if the permissions is granted or denied. If any step in this process does not make a decision (true or false), the steps continue until a result is found.

The process is as follows:

1. Is the user the root user? (Based on having a role with is_root set to true) If so, permission granted.
1. Determine the class hierarchy of the permissions request
1. Starting at the leaf class, and ending at the root ("*") class - test permissions until a true or false is returned
1. If a permission is found, run `Permission::check()` 
1. Cache the resulting permission decision (if not specified otherwise)

