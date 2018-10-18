## Release {version}

- Fix issue when `zesk\Request` is `POST`ed zero-length data with content type `application/json`.
- Fixing documentation to `zesk\Application::locale_path`
- Loosened definition of `zesk\Control_Login::submitted()` to be considered when the request is a `POST` and the variable `login` is present in the form.
- `zesk\Controller_Login::action_logout` supports `JSON` response.


<!-- Generated automatically by release-zesk.sh, beware editing! -->
