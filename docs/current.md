## Release {version}

- `User::fetch` not called by `User::fetch_by_key` - need to make this consistent
- `Module_ORM::all_classes()` return properly capitalized class name
- `zesk eval` now supports state between command lines in interactive mode
- `zesk\Adapter_TestFramework` adding `::assertIsString`
- `zesk\File_Monitor` issues slews of warnings due to race condition upon deletion of vendor directory - silence warnings
- `zesk\File` fixes to handle no `memory_limit` ini setting
- `zesk\Net_HTTP_Client_Test` Setting up test URL as constant
- `zesk\PHP::ini_path()` added
- `zesk\Response\HTML` links now support all applicable attributes including `sizes`
- added `zesk\Net_SSL_Certificate` to sync certs from curl site
- adding `zesk\PHPUnit_TestCase::assertIsInteger`
- fixing `Kernel_Test.php` and allowing setting `$_SERVER['PATH']`
- Added `bin/link-vendor-to-dev-zesk.sh` to allow linking to development ZESK in any project
- support **http** and **https** in `zesk\Net_HTTP_Client::simple_get`

<!-- Generated automatically by release-zesk.sh, beware editing! -->
