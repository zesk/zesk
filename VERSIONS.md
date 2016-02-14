# Versions

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- Elimination of all globals, moving from `zesk::` to `Application::` in places where it makes sense
- Ability to initialize the application context and then serialize the configuration in a way which allows for faster startup
- PSR-4 
- `zesk\` Namespace for all objects in the system
- Move `log::` to `Module_Log::`
