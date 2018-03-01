/**
 * 
 * @package zesk
 * @subpackage modal_url
 * @author Kent M. Davidson http://www.razzed.com/
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
/* global zesk: false */
(function(exports, $) {
	"use strict";
	var 
	plugin_name = 'modal_url', 
	random_string = function () {
		var n = new Date(), s = "";
		return (s + Math.random()).substring(2,12) + n.getTime() + n.getMilliseconds();
	},
	plugin_suffix = '.' + plugin_name, 
	error = function(jqXHR, textStatus, errorThrown) {
		zesk.log(textStatus);
		zesk.log(errorThrown);
	};
	var defaults = {
		url : null,
		refresh : false
	};
	var Modal_URL = function(element, options) {
		var self = this, $this = $(element);

		this.$element = $this;
		$.each(defaults, function(key) {
			var name = 'modal' + key.toCamelCase(), value = $this.data(name);
			self[key] = value ? value : this;
		});
		$.extend(this, options);
		this.url = this.url || $this.data('modal-url');
		this.target = this.target || $this.data('target');
		this.target_replace = this.target_replace || $this.data('targetReplace');
		this.target_action = $this.data('targetAction');
		if (this.target_action === "replace") {
			this.target_replace = true;
		}
		this.$target = $(this.target);

		this.init();

		$this.off("click" + plugin_suffix).on("click" + plugin_suffix, function(e) {
			self.click(e);
		});
	};
	function document_ready(context) {
		if (!context) {
			context = $('body');
		}
		$('[data-modal-url]', context).modal_url();
	}
	$.extend(Modal_URL.prototype, {
		init : function() {
			var $this = this.$element, template = $($this.data('template') || '#modal_url-template').html();
			this.$template = $('#modal_url-modal');
			if (this.$template.length === 0) {
				$('body').css("position", "default").append(template);
				this.$template = $('#modal_url-modal');
			}
		},
		close : function() {
			this.$template.modal('hide');
		},
		finished : function(json) {
			var $target = this.$target;
			if (this.target_replace) {
				$target.replaceWith(json.content);
				this.$target = $target = $(this.target);
			} else {
				$target.html(json.content);
			}
			zesk.hook('document::ready', $target);
			zesk.handle_json(json);
			document_ready($target);
			this.close();
			if (this.refresh) {
				document.location.reload();
			}
		},
		modal_body : function(json) {
			var self = this, $template = this.$template, $context = $('.modal-dialog', $template), $target = $('.modal-body', $template);

			$target.html(json.content);
			$('.modal-footer').hide();
			$('form', this.$template).off('submit' + plugin_suffix).on('submit' + plugin_suffix, function(e) {
				self.save(e);
			});
			$('button[type=submit]', $template).off('click' + plugin_suffix).on('click' + plugin_suffix, function(e) {
				self.save(e, $(this).attr("name"));
			});
			$context.removeClass().addClass('modal-dialog');
			if (json.context_class) {
				$context.addClass(json.context_class);
			}
			$template.modal().modal('show');
			$template.off('hide.bs.modal').on('hide.bs.modal', function() {
				self.modal_hide();
			});
			exports.modal_url = this;
			zesk.hook('document::ready', $template);
			zesk.handle_json(json);
		},
		modal_hide : function() {
			if (this.hide) {
				this.hide();
			}
			exports.modal_url = null;
		},
		save_success : function(json) {
			var self = this, action;
			if (!json.status) {
				this.modal_body(json);
				return;
			}
			if (is_string(json.content)) {
				this.finished(json);
				return;
			}
			action = this.$target.parents('form').attr('action');
			if (action) {
				$.ajax(action, {
					data : {
						"widget::target" : this.$target.attr("id"),
						ajax : 1
					},
					method : "GET",
					error : error,
					success : function(json) {
						if (!json.status) {
							zesk.log("URL refresh " + action + " failed, return value below");
							zesk.log(json);
							return;
						}
						self.finished(json);
					}
				});
			} else {
				this.finished(json);
			}
		},
		save : function(e, name) {
			var self = this, 
			suffix = (name ? "&" + name + "=1" : "") + "&ajax=1";
			e.stopPropagation();
			e.preventDefault();
			$.ajax(this.url, {
				type: 'POST',
				error: error,
				success: function(json) {
					self.save_success(json);
				},
				dataType: 'json',
				data: $('form', this.$template).serialize() + suffix
			});
		},
		click : function(e) {
			var self = this, $template = this.$template;

			if (e) {
				e.preventDefault();
			}
			$.ajax(this.url, {
				error: error,
				success: function(json) {
					if (json.status) {
						if (json.modal_skip) {
							self.save_success(json);
							return;
						} 
						if (json.title) {
							$('.modal-title', $template).html(json.title);
							$('.modal-header').show();
						} else {
							$('.modal-title', $template).html("-empty-");
							$('.modal-header').hide();
						}
						self.modal_body(json);
					} else if (json.status === false) {
						zesk.handle_json(json);
					} else {
						zesk.log("URL modal " + this.url + " failed, return value below");
						zesk.log(json);
					}
				},
				type: "GET",
				data: {
					ajax: 1,
					target: this.target,
					_r: random_string()
				},
				dataType: 'json'
			});
		},
	});
	$.fn[plugin_name] = function(options) {
		var element_apply = function() {
			var $this = $(this);
			$this.data(plugin_name, new Modal_URL($this, options));
		};
		$(this).each(function(index, item) {
			element_apply.call(item);
		});
	};
	$[plugin_name] = function(element, options) {
		var murl = new Modal_URL(element, options);
		murl.click();
	};
	$.modal_url_ready = document_ready;
	zesk.add_hook('document::ready', document_ready);
})(window, window.jQuery);
