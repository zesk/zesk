/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/inplace/share/js/jquery.inplace.js $
* @package zesk
* @subpackage inplace
* @author Kent M. Davidson http://www.razzed.com/
* @copyright Copyright &copy; 2022, Market Acumen, Inc.
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
* LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
* WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
(function (exports, $) {
	"use strict";
	var
	noop = function () {},
	zesk = exports.zesk || {
		hook: noop,
		handle_json: noop,
		log: noop,
		add_hook: noop
	},
	defaults = {
		on: "click", // When to invoke the InPlace editor
		
		// HIDDEN/CHECKBOX options
		delay: 2, // For hidden inputs, delay n seconds before saving.
		
		// CHECKBOX options
		invert: false, // For checkboxes, send opposite value to server when recording
		
		// INPUT options
		margin: 20, // Margin, in pixels, of the input element
		classes: "inplace", // For the INPUT textbox, set the class to this
		column: "Name", // Column being edited
		autoWidth: true, // Automatically determine the width of an INPUT text box
		autoColor: true, // Automatically determine the color of an INPUT text box
		autoFont: false, // Automatically determine the font-size/font-family of an INPUT text box
		autoFontSize: false, // Automatically determine the font-size of an INPUT text box
		autoFontFamily: true, // Automatically determine the font-family of an INPUT text box
		
		// Generic options
		url: null, // URL to submit changes to
		render: null, // Function to render final version once edited
		change: null, // When values change, call this function
		focus: null, // When focus occurs on the widget, call this function
		revert: null, // If the editing is reverted due to a server error, call this function
		value: null, // Function to determine the value of the element
		propagate: null, // Selector to propagate values to when changed
		requireParent: null // Require this parent to exist for InPlace widgets to work at all - useful for setting a body editing class to enable functionality
	},
	valid_input_types = { 
		'hidden': 'hidden', 
		'checkbox': 'checkbox', 
		'radio': 'radio'
	},
	InPlace = function (element, options) {
		var
		self = this,
		$this = $(element);

		this.$element = $this;
		$.each(defaults, function (key) {
			var
			name = 'inplace' + key.toCamelCase(),
			value = $this.data(name);
			self[key] = value ? value : this;
		});
		this.type = 'text';
		if ($this.prop('tagName') === 'INPUT') {
			this.type = $this.attr('type').toLowerCase();
			this.type = valid_input_types[this.type] || 'text';
		}
		$.extend(this, options);
		this['init_' + this.type]();
	},
	active = null,
	get_color = function(node, style) {
		var
		$node = $(node),
		color = null;
		while ($node.length > 0) {
			color = $node.css(style);
			if (color !== "rgba(0, 0, 0, 0)") {
				return color;
			}
			$node = $node.parent();
		}
		return null;
	};
	
	$.extend(
		InPlace.prototype, {
			init_text: function () {
				this.close_bind();
			},
			init_checkbox: function () {
				this._bind_click();
			},
			init_radio: function () {
				this._bind_click();
			},
			init_hidden: function () {
				var self = this;
				this.timer = null;
				this.$element.on("change", function () {
					self.delay_message(self.$element.val());
				});
			},
			click: function () {
				var
				$this = this.$element,
				requireParent = this.requireParent || null;

				if (requireParent) {
					if ($this.closest(requireParent).length === 0) {
						return;
					}
				}
				if (active) {
					active.close();
				}
				if (!this.url && !this.save ) {
					return;
				}
				if (this.focus) {
					this.focus.call($this, this);
				}
				this['click_' + this.type]();
			},
			click_checkbox: function () {
				var
				$this = this.$element,
				checked = $this.prop('checked');
				
				this.delay_message(this.invert ? !checked : checked);
			},
			click_radio: function () {
				var
				$this = this.$element,
				checked = $this.prop('checked');
				
				this.delay_message(this.invert ? !checked : checked);
			},
			click_hidden: function () {
				// Bupkis - should never happen
			},
			click_text: function () {
				var
				$this = this.$element,
				style = {},
				value,
				form;

				if (typeof this.value === "function") {
					value = this.value.call($this, this);
				} else {
					value = $this.data('inplaceValue');
					if (value === undefined) {
						value = $this.text();
					}
				}
				this.value_original = value;
				this.text_original = $this.text();
				if (this.autoWidth) {
					style.width = ($this.width() + this.margin) + "px; ";
				}
				if (this.autoFont || this.autoFontSize) {
					style['font-size'] = $this.css("font-size");
				}
				if (this.autoFont || this.autoFontFamily) {
					style["font-family"]= $this.css("font-family");
				}
				if (this.autoColor) {
					style.color = get_color($this, "color");
					style['background-color'] = get_color($this, "background-color");
				}
				form =
					"<form class=\"inplace\" action=\"" + (this.url || ".") + "\" method=\"post\" onsubmit=\"return false\">" +
					"<input type=\"text\" class=\"" + this.classes + "\" name=\"" + this.column + "\" value=\"" + value + "\" />" +
					"</form>";
				$this.html(form);
				$('input', $this).css(style);
				this.open_bind();
				// Chrome removes the form element when it's within another form (which it is), so we just attach it to the parent form and remove it on close
				active = this;
			},

			/**
			 * Bind our "click" action (this.on) to the click method
			 */
			_bind_click: function () {
				var
				self = this,
				$this = this.$element;
				$this.off(this.on + ".inplace").on(this.on + ".inplace", function () { 
					self.click(this); 
				});
			},
			
			/**
			 * Set bindings to inplace object after close. Removes old bindings, adds "on" binding.
			 */
			close_bind: function () {
				var
				$this = this.$element;
				this._bind_click();
				$this.parents("form").off("submit.inplace");
				$("input", $this).off("blur");
			},
			/**
			 * After we open the inplace editor, set up jQuery bindings.
			 */
			open_bind: function () {
				var self = this, $this = this.$element;
				$this.unbind(this.on + ".inplace");
				$this.parents("form").on("submit.inplace", function () { self.close(); });
				$("input", $this).on("blur", function () { self.close(); }).focus();
				$("input", $this).on("keydown", function (e) {
					if (e.keyCode === 13) {
						/* Return */
						self.close();
						e.stopPropagation();
						e.preventDefault();
					} else if (e.keyCode === 27) {
						/* Escape */
						self._revert().close();
						e.stopPropagation();
						e.preventDefault();
					}
					
				});
			},
			finish: function (value) {
				var $this = this.$element;
				if (typeof this.render === "function") {
					$this.data('inplaceValue', value);
					$this.html(this.render.call(value, this.$element));
					zesk.hook('document::ready', $this);
				} else {
					if ($this.data('inplaceValue')) {
						$this.data('inplaceValue', value);
					}
					this['finish_' + this.type](value);
				}
				if (this.change) {
					this.change.call($this, this);
				}
			},
			finish_checkbox: function () {
				// Bupkis
			},
			finish_radio: function () {
				// Bupkis
			},
			finish_hidden: function () {
				// Bupkis
			},
			finish_text: function (value) {
				this.$element.html(value);
				if (this.propagate) {
					$(this.tokenize(this.propagate)).html(value);
				}
			},
			_revert: function () {
				this['_revert_' + this.type]();
				if (typeof this.revert === "function") {
					this.revert.call(this.$element, this);
				}
				return this;
			},
			_revert_checkbox: function () {
				this.$element.prop("checked", !this.$element.prop("checked"));
			},
			_revert_radio: function () {
				this._revert_checkbox();
			},
			_revert_hidden: function () {
				// Not handled
			},
			_revert_text: function () {
				this.$element.text(this.text_original);
			},
			delay_message: function(value) {
				if (this.delay > 0) {
					if (this.timer) {
						clearTimeout(this.timer);
					}
					var self = this;
					this.timer = setTimeout(function () {
						self.message(value);
						self.timer = null;
					}, 1000 * this.delay);
				} else {
					this.message(value);
				}
			},
			message: function (value) {
				var
				self = this,
				options = {},
				url;

				url = (typeof this.url === "function") ? this.url.call(this.$element, self) : this.url;
				url = this.tokenize(url);
				options.error = function (jqXHR, textStatus, errorThrown) {
					zesk.log(textStatus);
					zesk.log(errorThrown);
					self._revert();
				};
				options.success = function (json) {
					if (json.status) {
						self.finish(value);
					} else {
						self._revert();
					}
					zesk.handle_json(json);
				};
				options.type = 'POST';
				options.data = { ajax: 1 };
				options.data[this.column] = value;
				$.ajax(url, options);
			},
			close: function () {
				var
				self = this,
				$this = this.$element,
				value = $("input", $this).val();
				
				active = null;
				this.close_bind();
				if (value === this.value_original) {
					// Nothing changed
					this._revert();
				} else if (typeof this.save === "function") {
					if (this.save.call(this.$element, value)) {
						self.finish(value);
					} else {
						this._revert();
					}
				} else {
					this.message(value);
				}
				return false;
			},
			tokenize: function(string) {
				var
				$this = this.$element,
				matches = string.match(/\{[^}]+\}/g),
				new_string = string;
				if (matches === null) {
					return new_string;
				}
				$.each(matches, function () {
					var attr_name = this.substr(1, this.length - 2), // Remove {}
						attr = $this.attr(attr_name) || "";
					new_string = new_string.split(this).join(attr);
				});
				return new_string;
			}
		}
	);
	$.fn.inplace = function (options) {
		var inplace_element = function () {
			var $this = $(this);
			$this.data('inplace', new InPlace($this, options));
		};
		$(this).each(function(index, item) {
			inplace_element.call(item);
		});
	};
	function document_ready(context) {
		if (!context) {
			context = $('body');
		}
		$('[data-inplace-url]', context).inplace();
		$('[data-modal-url-NEVER]', context).click(function (e) {
			var
			$this = $(this),
			url = $this.data('modal-url'),
			target_id = $this.data('target'),
			$target = $(target_id),
			$template = $('#inplace-modal'),
			error = function (jqXHR, textStatus, errorThrown) {
				zesk.log(textStatus);
				zesk.log(errorThrown);
			},
			finished = function (json) {
				$target.html(json.content);
				zesk.hook('document::ready', $target);
				zesk.handle_json(json);
				document_ready($target);
				$template.modal('hide');
			},
			modal_body = function (json) {
				var 
				$target = $('.modal-body', $template),
				$context = $('.modal-dialog', $template);
				$target.html(json.content);
				$('.modal-footer').hide();
				$('form', $template).on('submit', save);
				$('.btn-primary', $template).off('click.inplace').on('click.inplace', save);
				$context.removeClass().addClass('modal-dialog');
				if (json.context_class) {
					$context.addClass(json.context_class);
				}
				$template.modal().modal('show');
				zesk.hook('document::ready', $template);
				zesk.handle_json(json);
			},
			save = function (e) {
				e.stopPropagation();
				e.preventDefault();
				$.ajax(url, {
					type: 'POST',
					error: error,
					success: function (json) {
						var action;
						if (!json.status) {
							modal_body(json);
							return;
						}
						if (json.content) {
							finished(json);
							return;
						} 
						action = $target.parents('form').attr('action');
						if (action) {
							$.ajax(action, {
								data: {
									"widget::target": $target.attr("id"),
									ajax: 1
								},
								method: "GET",
								error: error,
								success: function (json) {
									if (!json.status) {
										zesk.log("URL refresh " + action + " failed, return value below");
										zesk.log(json);
										return;
									}
									finished(json);
								}
							});
						} else {
							finished(json);
						}
					},
					dataType: 'json',
					data: $('form', $template).serialize()
				});
			};
			e.preventDefault();
			if ($template.length === 0) {
				$('body').append($($this.data('template') || '#inplace-template').html());
				$template = $('#inplace-modal');
			}
			$.ajax(url, {
				error: error,
				success: function (json) {
					if (json.status) {
						if (json.title) {
							$('.modal-title', $template).html(json.title);
							$('.modal-header').show();
						} else {
							$('.modal-title', $template).html("-empty-");
							$('.modal-header').hide();
						}
						modal_body(json);
					} else {
						zesk.log("URL modal " + url + " failed, return value below");
						zesk.log(json);
					}
				},
				type: "GET",
				data: { ajax: 1, target: target_id },
				dataType: 'json'
			});
		});
	}
	zesk.add_hook('document::ready', document_ready);
})(window, window.jQuery);
