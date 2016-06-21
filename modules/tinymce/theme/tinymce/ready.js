tinymce.baseURL = "<?php echo $this->base_url; ?>"; 
tinymce.init(<?php echo $this->json; ?>);

zesk.add_hook("document::ready", function (context) {
	context = context || window.jQuery("body");
	$("textarea.richtext", context).each(function () {
		tinymce.EditorManager.execCommand('mceRemoveEditor', true, $(this).attr("id"));
		tinymce.EditorManager.execCommand('mceAddEditor', true, $(this).attr("id"));
	});
});
