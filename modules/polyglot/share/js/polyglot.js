(function (exports, $) {
	var
	__ = exports.__ ? exports.__ : function (x) { return x.toString().right(":="); },
	zesk = exports.zesk,
	html = exports.html,
	avalue = exports.avalue,
	console = exports.console,
	strings = [],
	iterate_limit = 100,
	timer = null,
	selected = null,
	status_default = "todo",
	$filter_form = $('#translate-main nav form'),
	$list = $('#translate-list'),
	$locale = $("#locale", $filter_form),
	$status = $("#status", $filter_form),
	$search = $("#q", $filter_form),
	$progress = $(".control-progress", $filter_form),
	$edit_form = $('#translate-form'),
	$translation = $('#translation', $edit_form),
	translate_one = $("#translate-one").html(),
	translate_title = $("#translate-title").html(),
	$search_progress = $("#search-progress"),
	lang_status = {
		todo: __('Need translation'),
		dev: __('Developer review'),
		info: __('More info'),
		draft: __('Draft'),
		delete: __('Deleted'),
		done: __('Translated')
	},
	style_status = {
		default: 'default',
		todo: 'default',
		dev: 'warning',
		info: 'info',
		draft: 'info',
		delete: 'danger',
		done: 'success'
	},
	highlight_text = function (text, hi) {
		var re = new RegExp(zesk.regexp_quote(hi), "i"),
		parts = text.split(re);
		if (parts.length === 1) {
			return text;
		}
		return parts.join("<span class=\"hilite\">" + hi + "</span>");
	},
	XString = function (members) {
		this.id = members.id || null;
		this.raw_original = String(members.original);
		this.original = this.raw_original.right(":=", this.raw_original);
		this.low_raw_original = this.raw_original.toLowerCase();
		this.group = this.raw_original.left(":=", "");
		this.translation = members.translation;
		this.low_translation = this.translation.toLowerCase();
		this.set_status(members.status);
		this.css_id = this.id ? "object-" + this.id : 'new-' + strings.length;
		this.index = strings.length;
		this.render();
	};
	$.extend(XString.prototype, {
		set_status: function (value) {
			this.status = value || status_default;
			this.style_status = avalue(style_status, this.status, style_status.default);
			this.lang_status = avalue(lang_status, this.status, lang_status.todo);
			return this;
		},
		set_translation: function (value) {
			this.translation = value;
			this.low_translation = this.translation.toLowerCase();
			return this;
		},
		render_map: function (highlight) {
			var
			maph = highlight ? { 
				original: highlight_text(this.original, highlight),
				translation: highlight_text(this.translation, highlight) 
			} : {},
			map = $.extend({}, this, maph, {
				"#group": html.escape(this.group),
				"#original": this.original,
				"#translation": this.translation
			});
			return map;
		},
		title: function () {
			return translate_title.map(this.render_map());
		},
		render: function (highlight) {
			var
			self = this,
			map = this.render_map(highlight),
			content = translate_one.map(map);
			if (this.$element) {
				this.$element.replaceWith(content);
			} else {
				$list.append(content);
			}
			this.$element = $("#" + this.css_id);
			this.$element.on("click", function () {
				self.select();
			});
			this.highlighted = highlight ? true : false;
			return this;
		},
		match: function (status, query) {
			if (status !== "" && status !== this.status) {
				return this.hide();
			}
			if (query === "") {
				return this.render().show();
			}
			if (this.low_raw_original.indexOf(query) >= 0) {
				return this.render(query).show();
			}
			if (this.low_translation.indexOf(query) >= 0) {
				return this.render(query).show();
			}
			return this.hide();
		},
		select: function () {
			if (selected) {
				selected.selected(false);
			}
			XString.editor_close();
			XString.editor_open(this);
			this.selected(true);
		},
		selected: function (yes) {
			if (yes === false) {
				this.$element.removeClass("selected");
				return null;
			} else {
				this.$element.addClass("selected");
				return this;
			}
		},
		hide: function () {
			this.$element.hide();
			return this;
		},
		show: function () {
			this.$element.show();
			return this;
		},
		save: function (force) {
			var 
			self = this,
			new_value = String($translation.val());
			if (force || new_value !== this.translation) {
				this.set_translation(new_value);
				$.ajax({
					url: '/polyglot/token/' + $locale.val(),
					data: {
						id: this.id,
						original: this.raw_original,
						translation: this.translation,
						status: this.status
					},
					method: "POST",
					success: function (data) {
						if (data.status) {
							self.id = data.id;
						} else {
							self.select();
						}
						zesk.handle_json(data);
					}
				});
				this.match($status.val(), String($search.val()).toLowerCase());
			}
			return this;
		},
		next: function () {
			this.selected(false);
			XString.select_after(this.index);
		},
		previous: function () {
			this.selected(false);
			XString.select_before(this.index);
		}
	});
	$.extend(XString, {
		editor_close: function (animate) {
			if (selected) {
				selected.selected(false);
			}
			selected = null;
			if (animate) {
				$edit_form.slideUp();
			}
		},
		editor_open: function (x) {
			var value = $('<textarea />').html(x.translation).text();
			$('.original', $edit_form).html(x.title());
			$translation.val(value);
			if (!$edit_form.is(":visible")) {
				$edit_form.slideDown();
			}
			selected = x.selected(true);
			$translation.focus();
		},
		filter_list: function () {
			var status = $status.val(), q = $search.val();
			if (timer) {
				clearTimeout(timer);
				zesk.log("filter_list clear timeout");
			}
			q = q.toLowerCase();
			$progress.show();
			zesk.log("filter_list filter_iterate " + status + " " + q);
			document.location = "#" + $locale.val() + "/" + status + "/" + q;
			XString.filter_iterate(status, q, 0);
		},
		filter_iterate: function (status, q, start) {
			var 
			end,
			current_status = $status.val(), 
			current_q = String($search.val()).toLowerCase(),
			timer = null;
			
			if (start > strings.length) {
				return;
			}
			if (start !== 0 && status !== current_status || q !== current_q) {
				zesk.log("filter_list filters changed from " + status + "=>" + current_status + " " + q + "=>" + current_q);
				start = 0;
				status = current_status;
				q = current_q;
			}
			end = start + iterate_limit;
			if (end > strings.length) {
				end = strings.length;
			}
			for (; start < end; start++) {
				$search_progress.show().text(String(start) + " " + status + " " + q);
				strings[start].match(status, q);
			}
			if (start >= strings.length) {
				document.location = "#" + $locale.val() + "/" + $status.val() + "/" + $search.val();
				$progress.hide();
				$search_progress.hide();
				zesk.log("filter_list filters done " + status + " " + q);

				return;
			}
			timer = setTimeout(function () {
				XString.filter_iterate(status, q, end);
			}, 1);
		},
		delete_strings: function () {
			strings = [];
			$list.html("");
		},
		load_strings: function () {
			$progress.show();
			$.ajax({
				url: "/polyglot/load/" + $locale.val(),
				method: "GET",
				dataType: "json",
				success: function (data) {
					if (data.status) {
						XString.delete_strings();
						$.each(data.items, function () {
							strings.push(new XString(this));
						});
						XString.filter_list();
					}
					$progress.hide();
					zesk.handle_json(data);
				}
			});
		},
		select_after: function (index) {
			var i;
			for (i = index + 1; i < strings.length; i++) {
				var item = strings[i];
				if (item.$element.is(":visible")) {
					XString.editor_open(item);
					return;
				}
			}
			XString.editor_close(true);
		},
		select_before: function (index) {
			var i;
			if (index > 0) {
				for (i = index - 1; i >= 0; i--) {
					var item = strings[i];
					if (item.$element.is(":visible")) {
						XString.editor_open(item);
						return;
					}
				}
			}
			XString.editor_close(true);
		}
	});
	$(document).ready(function () {
		var 
		hash = String(document.location.hash).split("/"),
		locale = String(hash[0]).substring(1),
		up_shortcuts = function () {
			$(".shortcut", $edit_form).toggle($(this).prop("checked"));
		};
		$filter_form.on("submit", function (e) {
			e.preventDefault();
			e.stopPropagation();
			return false; 
		});
		$locale.on("change", function () {
			XString.editor_close();
			XString.load_strings($(this).val());
		});
		$search.on("keyup", function () {
			if (timer) {
				clearTimeout(timer);
			}
			timer = setTimeout(function () {
				timer = null;
				XString.filter_list();
			}, 500);
		});
		$status.on("change", function () {
			XString.filter_list();
		});
		$(window).on("keydown", function (e) {
			var
			key_status = {
				68: "dev",	// Ctrl-D
				73: "info", // Ctrl-I
				8: "delete", // Ctrl-DELETE
				84: "todo", // Ctrl-T
				70: "draft", // Ctrl-F
			};
			// Down (Right)
			if ((e.ctrlKey && e.keyCode === 39) || (e.keyCode === 40)) {
				e.preventDefault();
				e.stopPropagation();
				if (selected) {
					selected.save().next();
				} else {
					XString.select_after(-1);
				}
				return;
			}
			// Up/left
			if ((e.ctrlKey && e.keyCode === 37) || (e.keyCode === 38)) {
				e.preventDefault();
				e.stopPropagation();
				if (selected) {
					selected.save().previous();
				} else {
					XString.select_before(strings.length);
				}
				return;
			}
			if (!selected) {
				return;
			}
			if (e.keyCode === 27) {
				XString.editor_close(true);
				e.preventDefault();
				e.stopPropagation();
				return;
			}
			if (e.keyCode === 13) {
				e.preventDefault();
				e.stopPropagation();
				selected.set_status("done").save(true).next();
				return;
			}
			if (e.keyCode === 27) {
				e.preventDefault();
				e.stopPropagation();
				selected.set_status("todo").save(true).next();
				return;
			}
			if (e.ctrlKey && key_status[e.keyCode]) {
				e.preventDefault();
				e.stopPropagation();
				selected.set_status(key_status[e.keyCode]).save(true).next();
				zesk.log("Keycode=" + e.keyCode);
			} else {
				zesk.log("Keycode=" + e.keyCode);
			}
		});
		$(".actions a", $edit_form).on("click", function () {
			if (!selected) {
				XString.editor_close(true);
				return;
			}
			selected.set_status($(this).data("status")).save(true).next();
		});
		$("#translate-save", $filter_form).on("click", function () {
			$.ajax({
				url: '/polyglot/update/' + $locale.val(),
				success: zesk.handle_json
			});
		});
		
		$('#shortcuts').on("change", up_shortcuts).each(up_shortcuts);
		if (locale) {
			$locale.val(locale);
			$status.val(hash[1]);
			$search.val(hash[2]);
		}
		if ($locale.val()) {
			XString.load_strings($locale.val());
		}
	});
}(window, window.jQuery));