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
 * @copyright Copyright (C) 2014 Market Acumen, Inc. 
 */
// Requires zesk.js
// Optional locale.js
;(function(exports, $) {
	"use strict";
	var
	// zesk.js
	zesk = exports.zesk,
	plugin_name = 'picker',
	plugin_suffix = '.' + plugin_name,
	// locale.js
	__ = exports.__ || function (x) { return (arguments[1] || {}).map(x.right(":=", x)); },
	defaults = {
		loaded: null,
		no_submit: false,
		empty_search: false,
		require_minimum: 1,
		data_search: {}
	},
	input_prop = function () {
		var $this = $(this);
		$("input", $this).prop("disabled", !$this.hasClass("selected"));
	},
	Picker = function (element, options) {
		var
		self = this,
		$q = $(element);

		options = options || {};
		this.$q = $q;
		this.$form = $q.parents('form');
		this.$results = $('.control-picker-results', this.$form);
		this.data_search = {};
		this.timer = null;
		this.last_q = null;
		this.source = $q.data('source');
		this.$source = this.source ? $($q.data('source')) : null;
		$.each(defaults, function (key) {
			var
			name = plugin_name + key.toCamelCase(),
			value = $q.data(name);
			self[key] = value || options[key] || this;
		});
		$.extend(this, options);

		this.init();
	};
	$.extend(
		Picker.prototype, {
			init: function () {
				var
				self = this;
				if (!this.no_submit) { 
					this.$form.off('submit' + plugin_suffix).on('submit' + plugin_suffix, function () { 
						self.submit(); 
					});
					$('btn', this.$form).off('click' + plugin_suffix).on('click' + plugin_suffix, function () { 
						self.submit(); 
					});
				}
				this.$q.on('keyup', function () {
					self.keyup();
				});
				if (this.empty_search) {
					this.results_search("");
				} else if (this.$source) {
					this.$results.html(this.$source.html());
					this.results_ready();
				} else {
					this.results_ready();
				}
			},
			refresh_state: function () {
				var
				num_selected = $('.item.selected', this.$results).length,
				none_selected = num_selected === 0,
				is_valid = num_selected >= this.require_minimum,
				has_results = this.$results.html().trim() !== "";
				$('.control-picker-none-selected')[has_results && none_selected ? 'slideDown' : 'slideUp']('slow');
				$('.btn', this.$form).prop('disabled', !is_valid ? true : false)[!is_valid ? 'addClass' : 'removeClass']('disabled');
				$('.control-picker-empty')[!has_results ? 'show' : 'hide']();
			},
			keyup: function () {
				var 
				self = this,
				q = this.$q.val().trim();
				if (this.timer) {
					clearTimeout(this.timer);
					this.timer = null;
				}
				if (q === "" && !this.empty_search) {
					this.results_clear();
					this.last_q = "";
					return;
				} 
				if (q === this.last_q) {
					return;
				}
				this.timer = setTimeout(function () { 
					self.results_search(q); 
				}, 500);
			},
			submit: function () {
				$('.item:not(.selected) input', this.$results).remove();
				return true;
			},
			results_ready: function () {
				var 
				$results = $('.item', this.$results),
				self = this; 
				$results.off("click.picker").on("click.picker", function () {
					var $this = $(this);
					$this.toggleClass("selected");
					input_prop.call($this);
					self.refresh_state();
				}).each(input_prop);
				$results.off("mouseover.picker").on("mouseover.picker", function () {
					var $this = $(this);
					$this.addClass($this.hasClass('selected') ? 'selected-over' : 'unselected-over');
				});
				$results.on("mouseout.picker").on("mouseout.picker", function () {
					$(this).removeClass('selected-over unselected-over');
				});
				if (this.loaded) {
					this.loaded(this.$results);
				}
				this.refresh_state();
			},
			results_clear: function () {
				$('.item:not(.selected)', this.$results).remove();
				this.refresh_state();
			},
			results_success: function (q, data) {
				var 
				self = this,
				$results = this.$results;
				if (data.status) {
					var n = 0;
					this.last_q = q;
					this.results_clear();
					$.each(data.results, function (id) {
						if ($("[data-id=" + id + "]", $results).length === 0) {
							$results.append(this);
							++n;
						}
					});
					if (n === 0) {
						zesk.message(__("No matches found for search &ldquo;{q}&rdquo;.", {q:q}));
					}
					self.results_ready();
				}
			},
			results_search: function (q) {
				var 
				self = this,
				$form = this.$form;
				$.ajax($form.attr('action'), {
					data: $.extend({
						"widget::target": this.$q.attr("data-widget-target") || $('[name="widget::target"]', $form).val(),
						action: "search",
						q: q
					}, this.data_search),
					dataType: "json",
					success: function (data) {
						self.results_success(q, data);
					}
				});
			}
		}
	);

	$.extend(defaults, zesk.get_path('modules.picker'));
	$.fn[plugin_name] = function (options) {
		var element_apply = function () {
			var $this = $(this);
			$this.data(plugin_name, new Picker($this, options));
		};
		$(this).each(function(index, item) {
			element_apply.call(item);
		});
	};
	$[plugin_name] = function () {
		$('.control-picker-state.selectable .item.selected').off("click.picker").on("click.picker", function () {
			$(this).remove();
		});
	};
	$(exports.document).ready(function () {
		$.picker();
	});
}(window, window.jQuery));



