/**
* $URL$
* @package zesk
* @subpackage image_picker
* @author Kent M. Davidson https://razzed.com/
* @copyright Copyright &copy; 2019, Market Acumen, Inc.
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
	zesk = exports.zesk,
	plugin_name = 'image_picker',
	plugin_suffix = '.' + plugin_name,
	defaults = {
			
	},
	Image_Picker = function (element, options) {
		var
		self = this,
		$q = $(element),
		picker_options = {
			loaded: function () {
				self.loaded();
			},
			no_submit: true
		};
		$.extend(picker_options, options);
		$q.picker(picker_options);
		this.$form = $q.parents("form");
		this.$form.off('submit.image_picker').on('submit.image_picker', function (e) {
			e.stopPropagation();
			e.preventDefault();
			return false;
		});
		this.$q = $q;
		this.$results = $('.control-picker-results', this.$form);
		$.each(defaults, function (key) {
			var
			name = plugin_name + key.toCamelCase(),
			value = $q.data(name);
			self[key] = value ? value : this;
		});
		$.extend(this, options);

		this.init();
	};
	$.extend(
		Image_Picker.prototype, {
			init: function () {
				var self = this;
				$('.dropfile', this.$form).dropfile({
					allowed_types: 'image',
					success: function (data) {
						this.progress_success(data);
						self.$results.prepend(data.content);
						self.loaded();
						zesk.hook("document::ready", self.$results);
					},
				});
				this.loaded();
			},
			newContent: function ($context) {
			},
			loaded: function () {
				var self = this;
				$('.image-picker-item', this.$form).off('click' + plugin_suffix).on('click' + plugin_suffix, function (e) {
					self.click.call(this, e);
				});
				$('.item .action-delete', this.$form).off('click' + plugin_suffix).on('click' + plugin_suffix, function (e) {
					e.stopPropagation();
					e.preventDefault();
					return self.action_delete.call(null, this);
				});
			},
			click: function (e) {
				var 
				$img = $("img", this),
				source = $img.data('original') || $img.data('src');
				e.stopPropagation();
				e.preventDefault();
				if (exports.tinymce) {
					exports.tinymce.activeEditor.insertContent('<img src="' + source + '" />');
					if (exports.modal_url) {
						exports.modal_url.close();
					}
				}
			},
			action_delete: function (item) {
				var $item = $(item);
				$.ajax($item.attr('href'), {
					type: "POST",
					dataType: 'json',
					success: function (data) {
						zesk.handle_json(data);
						$item.parents('.item').remove();
					}
				});
				return false;
			}
		}
	);
	$.fn[plugin_name] = function (options) {
		var element_apply = function () {
			var $this = $(this);
			$this.data(plugin_name, new Image_Picker($this, options));
		};
		$(this).each(function(index, item) {
			element_apply.call(item);
		});
	};
	$[plugin_name] = function (editor) {
		$.modal_url($(editor.contentAreaContainer), { 
			url: '/imagepicker?widget::target=image&ajax=1&action=selector',
		});
	};
}(window, window.jQuery));

