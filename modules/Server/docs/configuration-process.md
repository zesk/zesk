# Zesk Feature configuration process

## Goals

- Dynamically set up features of a server
- Automatically install and keep up-to-date components
- Allow features to interact with each other - to enhance available features and augment them
- Easy to deploy new features

## Process

### Feature Class

The Feature class is the main configuration environment for a server feature. It contains protected variables to configure:

- `platforms` - If this feature is only available on certain platforms, they are listed here. Platforms are named after their `uname -s` setting (`php_uname("s")`). e.g. "Linux", "FreeBSD", "Darwin"

- `commands` - A list of shell commands required to set up and configure this feature. So if your configuration depends on having `curl` installed, list it here.

- `packages` - A list of required packages for this feature to operate correctly. As feature package names are different across systems, the package name may be translated before installed.

- `dependencies` -  A list of other required features for this feature to be configured

- `settings` - A list of settings for this feature. By default all settings are optional unless specified. Settings are configured by type, e.g.

		protected $settings = array(
			'log_path' => array(
				'type' => 'path',
				'required' => true,
			),
			'group' => 'group'
		);
	
- `configure_root` - A directory where secondary template files, settings, and default files can be found.

