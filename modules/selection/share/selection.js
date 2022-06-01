/*global zesk: false, Locale: false */
(function($) {
	"use strict";
	$(document).ready(function() {
		var selection_widgets = {}, clickname = "click.control-selection", defaults = {
		    idTarget: ".selection-id",
		    target: ".row",
		    container: ".list",
		    count: 0,
		    format: "{n} {nouns} selected",
		    noun: "item"
		}, extract_id = function($item) {
			return $item.val() || $item.data('id') || $item.attr('id');
		}, Control_Selection = function($control) {
			var self = this, options = $.extend({}, defaults, $control.data());

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
			this.zero_format = options.zeroFormat;

			// Derived
			this.$container = $control.parents(this.container);
			this.$control = $('.control-selection-widget', this.$container);
			this.$target = $(this.target, this.$container);
			this.$id_target = $(this.id_target, this.$container);

			// Init DOM
			this.$container.addClass('selectable');

			this.$target.on(clickname, function() {
				self.click($(this));
			});
			$('a', this.$target).on("click", function(e) {
				e.stopPropagation();
			});
			this.update();
		};
		$.extend(Control_Selection.prototype, {
		    q_clear: function() {
			    this.send_add = {};
			    this.send_remove = {};
			    return this.stop();
		    },
		    select_all_visible: function() {
			    $(this.target + ":not(.selected)", this.$container).addClass("selected");
			    return this;
		    },
		    deselect_all_visible: function() {
		    	this.$target.removeClass("selected");
			    return this;
		    },
		    action_none: function() {
			    this.$target.removeClass("selected");
			    this.q_clear();
			    this.count = 0;
			    this.update().message({
				    action: "none"
			    });
		    },
		    action_all: function(action, add) {
			    var self = this;
			    if (add) {
				    this.select_all_visible();
				    this.count += this.total;
			    } else {
				    this.deselect_all_visible();
				    this.count -= this.total;
				    this.count = Math.max(0, this.count);
			    }
			    this.message({
				    action: action
			    }, function(data) {
				    self.count = data.count || self.count;
				    self.update();
			    });
		    },
		    click: function($this) {
			    var id = extract_id($(this.id_target, $this));
			    this.last_id = id;
			    $this.toggleClass("selected");
			    this.queue(id, $this.hasClass('selected'));
			    this.update();
		    },
		    q_is_empty: function() {
			    return Object.keys(this.send_add).length === 0 && Object.keys(this.send_remove).length === 0;
		    },
		    stop: function() {
			    if (this.timer) {
				    clearTimeout(this.timer);
				    this.timer = null;
			    }
			    return this;
		    },
		    start: function() {
			    var self = this;
			    this.stop();
			    this.timer = setTimeout(function() {
				    self.q_submit();
			    }, zesk.get_path('control.selection.submit_delay', 500));
			    return this;
		    },
		    queue: function(id, add) {
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
			    if (this.q_isEmpty()) {
				    this.stop();
			    } else {
				    this.start();
			    }
			    return this;
		    },
		    update_a: function(name, cond, func, title) {
			    var $a = $('a[data-select-action="' + name + '"]', this.$control);
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
		    update_total: function() {
			    var nouns = Locale.plural(this.noun, this.count);
			    var format = this.count === 0 ? this.zero_format : this.format;
			    var label = format.map({
			        n: this.count,
			        nouns: nouns
			    });
			    $('.title', this.$control).text(label);
		    },
		    update: function() {
			    var self = this;
			    var nouns = Locale.plural(this.noun, this.count);
			    var $menu = $('.control-selection-menu', this.$container);
			    var $actions = $('.control-selection-actions-menu', this.$container);

			    this.update_total();
			    this.page_unselected = $(this.target + ":not(.selected)", this.$container).length;

			    this.update_a("none", this.count !== 0, function() {
				    self.action_none();
			    });
			    this.update_a("add-all", this.total > 0 && this.count - this.total !== 0, function() {
				    self.action_all('add-all', true);
			    });
			    this.update_a("remove-all", this.total > 0 && this.count !== 0, function() {
				    self.action_all('remove-all', false);
			    });

			    $('button', $actions)[this.count === 0 ? 'addClass' : 'removeClass']("disabled");
			    $menu[this.count > 0 ? 'addClass' : 'removeClass']("has-selection");
			    $menu[this.count === 0 ? 'addClass' : 'removeClass']("no-selection");
			    return this;
		    },
		    message: function(data, success) {
			    data['widget::target'] = this.name;
			    $.ajax(this.$form.attr("action"), {
			        method: "POST",
			        dataType: "json",
			        data: data,
			        success: function(data) {
				        if (success) {
					        success(data);
				        }
				        zesk.handle_json(data);
			        }
			    });
			    return this;
		    },
		    q_submit: function() {
			    var data = {
			        add: Object.keys(this.send_add),
			        remove: Object.keys(this.send_remove)
			    };
			    this.message(data);
			    this.send_add = {};
			    this.send_remove = {};
			    return this.stop();
		    }
		});
		$('.control-selection-widget').each(function() {
			var $this = $(this), name = $this.data('name'), sel = $this.data('Control_Selection');
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
