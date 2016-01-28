/*global zesk: false, Locale: false */
(function ($) {
	"use strict";
	$(document).ready(function () {
		var
		selection_widgets = {},
		clickname = "click.control-selection",
		defaults = {
			idTarget: ".selection-id",
			target: ".row",
			container: ".list",
			count: 0,
			format: "{n} {nouns} selected",
			pageFormat: "All {nouns} shown ({n} unselected)",
			noun: "item"
		},
		extract_id = function ($item) {
			return $item.val() || $item.data('id') || $item.attr('id');
		},
		Control_Selection = function ($control) {
			var 
			self = this,
			options = $.extend({}, defaults, $control.data());
			
			// Init
			this.timer = null;
			this.send_add = {};
			this.send_remove = {};
			this.last_id = null;

			// Options
			this.options = options;
			this.$form = $control.parent('form');
			this.target = options.target;
			this.container = options.container;
			this.id_target = options.idTarget;
			this.name = options.name;
			this.noun = options.noun;
			this.count = options.count;
			this.total = options.total;
			this.format = options.format;
			this.page_format = options.pageFormat;
			
			// Derived
			this.$container = $control.parents(this.container);
			this.$control = $('.control-selection-widget', this.$container);
			this.$target = $(this.target, this.$container);
			this.$id_target = $(this.id_target, this.$container);
			
			// Init DOM
			this.$container.addClass('selectable');
			
			this.$target.on(clickname, function () {
				self.click($(this));
			});
			$('a', this.$target).on("click", function (e) {
				e.stopPropagation();
			});
			this.update();
		};
		$.extend(Control_Selection.prototype, {
			q_clear: function () {
				this.send_add = {};
				this.send_remove = {};
				return this.stop();
			},
			select_page: function () {
				var
				self = this;
				$(this.target + ":not(.selected)", this.$container).each(function () {
					var 
					$this = $(this), 
					id = extract_id($(self.id_target, $this));
					$this.addClass("selected");
					self.queue(id, true);
				});
				return this;
			},
			action_none: function () {
				this.$target.removeClass("selected");
				this.q_clear();
				this.count = 0;
				this.update().message({ action: "none" });
			},
			action_page: function () {
				this.select_page().update().start();
			},
			action_all: function () {
				var 
				self = this;
				this.select_page().q_clear();
				this.count += this.total;
				this.message({ action: "all" }, function (data) {
					self.count = data.count || this.count;
					self.update();
				});
			},
			click: function ($this) {
				var 
				id = extract_id($(this.id_target, $this));
				this.last_id = id;
				$this.toggleClass("selected");
				this.queue(id, $this.hasClass('selected'));
				this.update();
			},
			q_is_empty: function () {
				return Object.keys(this.send_add).length === 0 && Object.keys(this.send_remove).length === 0; 
			},
			stop: function () {
				if (this.timer) {
					clearTimeout(this.timer);
					this.timer = null;
				}
				return this;
			},
			start: function () {
				var self = this;
				this.stop();
				this.timer = setTimeout(function () {
					self.q_submit();
				}, zesk.get_path('control.selection.submit_delay', 500));
				return this;
			},
			queue: function (id, add) {
				if (add) {
					this.count++;
					if (this.send_remove[id]) {
						delete this.send_remove[id];
						return;
					}
					this.send_add[id] = true;
				} else {
					this.count--;
					if (this.send_add[id]) {
						delete this.send_add[id];
						return;
					}
					this.send_remove[id] = true;
				}
				if (this.q_is_empty()) {
					this.stop();
				} else {
					this.start();
				}
				return this;
			},
			update_a: function (name, cond, func, title) {
				var
				$a = $('a[data-select-action="' + name + '"]', this.$control);
				$a.off(clickname);
				if (title) {
					$a.text(title);
				}
				if (cond) {
					$a.on(clickname, func);
					$a.parent().removeClass("disabled");
				} else {
					$a.parent().addClass("disabled");
				}
				return this;
			},
			update_total: function () {
				var
				nouns = Locale.plural(this.noun, this.count),
				label = this.format.map({ 
					n: this.count, 
					nouns: nouns 
				});
				
				$('.title', this.$control).text(label);
			},
			update: function () {
				var
				self = this,
				nouns = Locale.plural(this.noun, this.count),
				$menu = $('.control-selection-menu', this.$container),
				$actions = $('.control-selection-actions-menu', this.$container);
				
				this.update_total();
				this.page_unselected = $(this.target + ":not(.selected)", this.$container).length;
				
				this.update_a("none", this.count !== 0, function () { self.action_none(); });
				this.update_a("page", this.page_unselected !== 0, function () { self.action_page(); }, this.page_format.map({ n: this.page_unselected, nouns: nouns }));
				this.update_a("all", this.count - this.total !== 0, function () { self.action_all(); });

				$actions[this.count > 0 ? 'show' : 'hide']("fast");
				$menu[this.count > 0 ? 'addClass' : 'removeClass']("has-selection");
				$menu[this.count === 0 ? 'addClass' : 'removeClass']("no-selection");
				return this;
			},
			message: function (data, success) {
				data['widget::target'] = this.name;
				$.ajax(this.$form.attr("action"), {
					method: "POST",
					dataType: "json",
					data: data,
					success: function (data) {
						if (success) {
							success(data);
						}
						zesk.handle_json(data);
					}
				});
				return this;
			},
			q_submit: function () {
				var 
				data = {
					add: Object.keys(this.send_add),
					remove: Object.keys(this.send_remove)
				};
				this.message(data);
				this.send_add = {};
				this.send_remove = {};
				return this.stop();
			}
		});
		$('.control-selection-widget').each(function () {
			var 
			$this = $(this),
			name = $this.data('name'),
			sel = $this.data('Control_Selection');
			if (!sel) {
				sel = selection_widgets[name];
				if (!sel) {
					selection_widgets[name] = sel = new Control_Selection($this);
				}
				$this.data('Control_Selection', sel);
			} 
		});
	});
}(window.jQuery));