# List of standard Zesk Hooks

## Hookable::construct

Called upon creation of a Hookable object in Zesk. Handy for modifying default values, or registering classes objects to be tracked elsewhere.

### Class Method 

	class Dude extends Hookable {
		function hook_construct() {
			// Run from Hookable::__construct
		}
	}
	
### Class-specific Version

	zesk\Hooks::add("Project::construct", "track_project");
	
	function notify_new_project(Project $project) {
		// do something
	}

## Hookable::destruct

Called upon destruction of Hookable objects in Zesk. If a child class of a Hookable object implements the __destruct method and does not call the parent::__destruct() method - then this may not be called for a class.

Useful for reversing actions taken on "Hookable::construct".

### Class Method 

	class Dude extends Hookable {
		function hook_construct() {
			// Run from Hookable::__construct
		}
	}
	
#### Class-specific Version

	zesk\Hooks::add("Project::construct", "notify_new_project");
	
	function notify_new_project(Project $project) {
		// Send mail to someone
	}


## Hookable::...

