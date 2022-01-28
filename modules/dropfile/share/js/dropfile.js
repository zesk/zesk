/*
 * DropFile implementation, resources used:
 * 
 * http://hayageek.com/drag-and-drop-file-upload-jquery/
 * http://www.html5rocks.com/en/tutorials/dnd/basics/
 * http://stackoverflow.com/questions/9544977/using-jquery-on-for-drop-events-when-uploading-files-from-the-desktop
 * 
 * Simple drop uploads for Zesk.
 * 
 * @author Kent M. Davidson kent@marketacumen.com
 * @copyright Copyright (C) 2022 Market Acumen, Inc.
 */
// Requires zesk.js
// Optional locale.js
(function(exports, $) {
	"use strict";
	var // zesk.js
		zesk = exports.zesk,
		html = exports.html,
		document = exports.document,
		avalue = exports.avalue,
		is_object = exports.is_object,
		to_integer = exports.to_integer,
		to_list = exports.to_list,
		// locale.js
		__ =
			exports.__ ||
			function(x) {
				return (arguments[1] || {}).map(x.right(":=", x));
			},
		plugin_name = "dropfile",
		selector = "." + plugin_name + "[data-" + plugin_name + "-url]",
		implemented = 0,
		document_over = false,
		doc_timer = null,
		event_suffix = "." + plugin_name,
		document_target_class = zesk.get(
			"modules." + plugin_name + ".document_target_class",
			"dropfile-document-target"
		),
		/*
	 * Set these defaults via attributes like so:
	 *
	 * <div class="dropfile" 
	 *     data-dropfile-target-class="dropfile-target"
	 *     data-dropfile-document-target-class="dropfile-document-target"
	 *     data-dropfile-extensions="png,jpeg,jpg,gif"
	 * ></div>
	 */
		clean_path = function(n) {
			return n
				.toString()
				.replace(/\\/g, "/")
				.rright("/");
		},
		defaults = {
			column: "file",
			url: null,
			max_files: 10,
			target: null,
			accept: null, // List of extensions or mime types
			enabled_class: "dropfile-enabled", // Class added to all selectors above
			target_class: "dropfile-target", // Class added to dropfile when drag is on them
			active_class: "dropfile-active", // Class added to dropfile when upload is active
			allowed_types: null,
			start: function(file) {
				this.progress_start(file);
			},
			remove: false,
			progress: function(percent) {
				this.progress_progress(percent);
			},
			success: function(data) {
				this.progress_success(data);
			},
			type_error: function(files) {
				$.each(files, function() {
					zesk.message(
						__("DropFile:=The file {file} was not uploaded because it is the wrong type. ({type})", {
							file: clean_path(this.name),
							type: this.type,
						})
					);
				});
			},
			max_error: function(files) {
				var dropfile = this;
				$.each(files, function() {
					zesk.message(
						__(
							"DropFile:=The file {file} was not uploaded because you can only upload {n} files maximum.",
							{
								file: clean_path(this.name),
								n: dropfile.max_files,
							}
						)
					);
				});
			},
			error: function() {
				$(".dropfile-progress", this.$element).remove();
			},
		},
		DropFile = function($this, options) {
			var self = this;

			options = is_object(options) ? options : {};
			this.$element = $this;
			$.each(defaults, function(key) {
				self[key] = avalue(options, key, $this.data(plugin_name + key.toCamelCase()) || this);
			});

			this.target_class = this.target_class || plugin_name + "-target";

			this.allowed_types = to_list(this.allowed_types, null);

			if (!this.url) {
				this.url = $this.parents("form").attr("action");
				if (!this.url) {
					this.url = document.URL;
				}
			}
			this.url += (this.url.indexOf("?") < 0 ? "?" : "&") + "ajax=1";

			this.max_files = Math.min(to_integer(this.max_files), 1);
			this.queue = [];
			this.has_input = false;

			$.each(["dragover", "dragleave", "drop", "click"], function() {
				var event_type = this;
				$this.off(event_type + event_suffix).on(event_type + event_suffix, function(e) {
					self[event_type](e);
				});
			});
			this.setup_file_input();

			if (this.enabled_class) {
				$this.addClass(this.enabled_class);
			}
		};
	$.extend(DropFile.prototype, {
		setup_file_input: function() {
			var accept = "",
				self = this,
				$input,
				$iframe,
				$element = this.$element;
			if ($("form input", $element).length > 0) {
				return;
			}
			if ($(".dropfile-value", $element).val() && this.remove) {
				$element.append(
					'<a class="dropfile-remove-button"><span class="glyphicon glyphicon-remove"></span></a>'
				);
				$(".dropfile-remove-button", $element).on("click", function() {
					$("img", $element).remove();
					$(".dropfile-value", $element).val("");
					$(".dropfile-remove-button", $element).remove();
					$element.append('<div class="no-image" />');
				});
			}
			if (this.accept) {
				accept = ' acccept="' + html.encode(this.accept) + '"';
			}
			$element.append(
				'<form action="' +
					this.url +
					'" method="post" target="dropfile-' +
					this.column +
					'" enctype="multipart/form-data"><input type="file" name="' +
					this.column +
					'"' +
					accept +
					' /><iframe name="dropfile-' +
					this.column +
					'" style="display: none"></iframe><input type="hidden" name="ok" value="ok" /></form>'
			);
			$input = $("form input", this.$element);
			$input
				.on("change", function() {
					zesk.log("file.change");
					if ($input.val()) {
						self.call_start({
							name: clean_path($input.val()),
						});
						if (self.progress) {
							self.progress(0);
						}
						this.form.submit();
					}
				})
				.on("focus", function() {
					zesk.log("file.focus");
				})
				.on("blur", function() {
					zesk.log("file.blur");
				});
			$iframe = $("iframe", this.$element);
			$iframe.on("load", function() {
				var data = $.parseJSON(
					$iframe
						.contents()
						.find("body")
						.text()
				);
				self.progress(100);
				self.call_success(data);
				// Reset when done
				self.reset_file_input();
			});
		},
		reset_file_input: function() {
			$("form", this.$element).remove();
			this.setup_file_input();
		},
		click: function(/*e*/) {},
		dragover: function(e) {
			e.stopPropagation();
			e.preventDefault();
			if (!this.over) {
				this.$element.addClass(this.target_class);
				this.over = true;
			}
		},
		dragleave: function(e) {
			e.stopPropagation();
			e.preventDefault();
			if (this.over) {
				this.$element.removeClass(this.target_class);
				this.over = false;
			}
		},
		drop: function(e) {
			var files = e.originalEvent.dataTransfer.files;
			e.preventDefault();
			this.$element.removeClass(this.target_class);
			this.over = false;
			if (doc_timer) {
				clearTimeout(doc_timer);
				doc_timer = null;
			}
			this.upload_files(files);
		},
		valid_file: function(f) {
			var i, type;
			if (!this.allowed_types) {
				return true;
			}
			for (i = 0; i < this.allowed_types.length; i++) {
				type = this.allowed_types[i];
				if (type.indexOf("/") > 0) {
					if (f.type === type) {
						return true;
					}
				} else if (type.indexOf("/") === 0) {
					if (f.type.ends(type)) {
						return true;
					}
				} else if (f.type.begins(type)) {
					return true;
				}
			}
			zesk.log("Skipping " + f.name + " - not of type " + this.allowed_types);
			return false;
		},
		upload_files: function(files) {
			var i,
				fd,
				type_errors = [],
				max_errors = [],
				nfiles = 0;
			for (i = 0; i < files.length; i++) {
				if (!this.valid_file(files[i])) {
					type_errors.push(files[i]);
					continue;
				}
				if (nfiles > this.max_files) {
					max_errors.push(files[i]);
					continue;
				}
				fd = new FormData();
				fd.append(this.column, files[i]);
				this.call_start(files[i]);
				this.upload(fd);
				++nfiles;
			}
			if (this.type_error && type_errors.length) {
				this.type_error.call(this, type_errors);
			}
			if (this.max_error && max_errors.length) {
				this.max_error.call(this, max_errors);
			}
		},
		call_start: function(file) {
			if (this.start) {
				this.start.call(this, file);
			}
		},
		call_success: function(data) {
			if (this.progress) {
				this.progress(100);
			}
			if (this.success) {
				this.success(data);
			} else {
				zesk.handle_json(data);
			}
			this.setup_file_input();
		},
		upload: function(file) {
			var self = this;
			this.xhr = $.ajax(this.url, {
				xhr: function() {
					var xhr = $.ajaxSettings.xhr();
					if (xhr.upload && self.progress) {
						xhr.upload.addEventListener(
							"progress",
							function(event) {
								var percent = 0,
									position = event.loaded || event.position,
									total = event.total;
								if (event.lengthComputable) {
									percent = Math.ceil(position / total * 100);
								}
								self.progress.call(self, percent);
							},
							false
						);
					}
					return xhr;
				},
				type: "POST",
				contentType: false,
				processData: false,
				cache: false,
				data: file,
				success: function(data) {
					self.call_success(data);
				},
				error: function() {
					if (self.error) {
						self.error();
					}
				},
			});
		},
		progress_start: function(file) {
			var name = file.name;
			this.filename = name;
			this.$element.prepend(
				'<div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"><span class="sr-only">60%</span></div><div class="dropfile-name">' +
					name +
					"</div></div>"
			);
			$(this.$element)
				.removeClass(document_target_class)
				.removeClass(this.target_class);
			$(this.$element).addClass(this.active_class);
		},
		progress_progress: function(percent) {
			$(".progress .progress-bar", this.$element).css("width", percent + "%");
			$(".progress .progress-bar .sr-only", this.$element).html(percent + "%");
		},
		progress_success: function(data) {
			$(".progress", this.$element).remove();
			$(this.$element).removeClass(this.active_class);
			if (this.target) {
				$(this.target).html(data.content);
				zesk.hook("document::ready", $(this.target));
			}
			zesk.handle_json(data);
		},
	});
	$.fn[plugin_name] = function(options) {
		$(this).each(function() {
			var $this = $(this);
			implemented++;
			$this.data(plugin_name, new DropFile($this, options));
		});
	};
	// Set up defaults from global state
	$.extend(defaults, zesk.get_path("modules." + plugin_name, {}));

	/**
	 * Add $.dropfile() to allow calls on AJAX calls.
	 */
	$.extend({
		dropfile: function(options) {
			$(selector)[plugin_name](options);
		},
	});
	$.each(
		{
			dragover: function() {
				if (!document_over) {
					$(selector).addClass(document_target_class);
					document_over = true;
				}
			},
			dragleave: function() {
				if (document_over) {
					$(selector).removeClass(document_target_class);
					document_over = false;
				}
			},
			drop: function(e) {
				if (document_over) {
					var self = $(selector).data(plugin_name);
					if (self) {
						doc_timer = setTimeout(function() {
							self.drop(e);
							doc_timer = null;
						}, 500);
					}
				}
			},
		},
		function(event) {
			var method = this;
			$(document).on(event, function(e) {
				if (implemented) {
					zesk.log("document." + event);
					e.stopPropagation();
					e.preventDefault();
					method(e);
				}
			});
		}
	);
})(window, window.jQuery);
