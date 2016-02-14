(function (exports) {
	var 
	target_actions = {
		'html' : 'html',
		'replace' : 'replaceWith'
	},
	$ = exports.jQuery,
	zesk = exports.zesk,
	is_object = exports.is_object,
	checkbox_show_hide = function () {
		var 
		$this = $(this),
		checked = $this.prop("checked");
		$($this.data('show')).toggle(checked);
		$($this.data('hide')).toggle(!checked);
	};
	zesk.add_hook('document::ready', function (context) {
		$('[data-confirm]', context).off("click.confirm_zesk_ajax").on("click.confirm_zesk_ajax", function (e) {
			if (exports.confirm($(this).data("confirm"))) {
				return true;
			}
			e.stopPropagation();
			e.preventDefault();
			return false;
		});
		$('[data-url],[data-ajax]', context).off("click.url_zesk_ajax").on("click.url_zesk_ajax", function (e) {
			var $this = $(this), data = $this.data();
			if ($this.is('input[type=checkbox]')) {
				data.value = $this.prop('checked') ? true : false;
			} else {
				e.stopPropagation();
				e.preventDefault();
			}
			$.each(data, function(k) { 
				if (is_object(this)) {
					delete data[k];
				}
			});
			$.ajax($this.data("url") || $this.attr('href'), {
				success: function (data) {
					var
					target = $this.data('target'), 
					target_action = target_actions[$this.data('target-action')] || 'html', 
					redirect = $this.data('redirect'),
					parent_target = $this.attr('data-target-parent'),
					$target = null;
					if (data.content && target) {
						$target = $(target);
					}
					if (data.content && parent_target) {
						$target = $this.parents(parent_target);
					}
					if ($target) {
						$target[target_action](data.content);
						$target = $(target); // If replaceWith need to find it again
						zesk.hook('document::ready', $target);
					}
					zesk.handle_json(data);
					if (redirect) {
						exports.document.location = redirect;
					}
					if ($target) {
						zesk.hook('document::ready', target_action !== "html" ? $target.parent() : $target);
					}
				},
				type: $this.data('type') || 'POST',
				dataType: $this.data('dataType') || 'json',
				data: data
			});
		});
		$('[data-ajax-form] :input', context).off("change.form_zesk_ajax").on("change.form_zesk_ajax", function () {
			var
			$this = $(this),
			$form = $this.parents('form'),
			name = $this.attr("name"),
			target = $this.data('target'),
			target_action = target_actions[$this.data('target-action')] || 'html', 
			$target = null,
			data = {
				type: 'POST',
				dataType: 'json',
				success: function (data) {
					if (data.content && target) {
						$target = $(target);
					}
					if ($target) {
						$target[target_action](data.content);
						$target = $(target); // If replaceWith need to find it again
					}
					zesk.handle_json(data);
					if ($target) {
						zesk.hook('document::ready', target_action !== "html" ? $target.parent() : $target);
					}
				},
				data: { "ajax" : 1 }
			};
			data.data[name] = $this.prop('type') === 'checkbox' ? $this.prop('checked') : $this.val();
			$.ajax($form.attr('action'), data);
		});
		$('input[type=checkbox][data-show],input[type=checkbox][data-hide]').off("change.checkbox_zesk_ajax").on("change.checkbox_zesk_ajax", checkbox_show_hide).each(checkbox_show_hide);
	});
}(window));