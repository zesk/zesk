# jQuery Modal URL usage

Modal URL automatically instruments form values which have an attribute of `data-modal-url` of any type.

	<a href="#" data-modal-url="/dialog/content">Open Modal URL</a>

## HTML Attributes

The additional attributes which can be added are:

- `data-modal-url` - **String**. *Required*. The URL to load the remote content, expects a JSON response from the server
- `data-modal-refresh` - **Boolean**. *Optional*. Refresh the current page loaded upon dialog closing. Defaults to `false`.
- `data-target` - **String**. *Optional*. The target element selector to update content with a validated dialog submission. Defaults to `null`.
- `data-target-replace` - **Boolean**. *Optional*. Replace the target instead of just the target content. Defaults to `false`.
- `data-template` - **String**. *Optional*. Use the selector to retrieve the dialog content on the page. Defaults to `#modal_url-template`.

Example:

	<a id="#mymodal" 
		data-modal-url="/dialog-mymodal" 
		data-modal-refresh="false" 
		data-target="" 
		data-target-replace="false" 
		template="#modal_url-template">Modal URL Link</a>
	
## JavaScript Options

The following are the JavaScript options for the Modal URL:

	$("a#mymodal").modal_url({
		url: "/dialog/mymodal",
		refresh: false,
		target: null,
		target_replace: false,
		template: "#modal_url-template
	});

## Modal URL JSON Response Specification

The JSON response from the server should have the following values:

- `status`: Boolean value which indicates whether the request was successful or failed.
- `content`: Content of the dialog box
- `title`: String to place in the dialog box title block


## Other Examples

To load a dialog box immediately, do this:

	$.modal_url($('a#mylink'), options);
	
The above code will display the dialog box immediately after instrumentation.

To enable the Modal URL functionality using JavaScript:

	$('a#mylink').modal_url(options);

This example requires a click on the link to enable the modal functionality.
