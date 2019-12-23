(function(w) {
	var $ = w.$ || w.jQuery;
	var html = w.html;
	var code = "tagsWidget";
	var columnClasses = ".col-xs-12 .col-state";
	var buttonClasses = ".btn .btn-sm";

	var defaults = {
		columns: {
			noop: {
				title: "Available tags " + html.tag("span", ".badge .badge-info", "# Tags"),
				button: "Undo",
				empty: "All available tags are applied or removed",
				badge_class: "tag-action-noop",
			},
			add: {
				title: "Add tags",
				button: "+",
				empty: "No tags will be added",
				badge_class: "tag-action-add",
			},
			remove: {
				title: "Remove tags",
				button: "-",
				empty: "No tags will be removed",
				badge_class: "tag-action-remove",
			},
		},
		badge: {
			class: "badge-default",
			separator: " > ",
		},
	};

	var TagWidget = function($item, options) {
		var self = this;

		this.options = $.extend(true, defaults, options);

		this.$item = $item;
		this.total = this.options.total || 0;
		this.name = this.options.name || "tags";
		this.labels = this.options.labels || [];
		this.by_id = {};

		delete this.options.total;
		delete this.options.name;
		delete this.options.labels;

		this.button_add_label = options.button_add_label || "+";
		this.button_remove_label = options.button_remove_label || "-";
		this.button_noop_label = options.button_noop_label || "Undo";

		this.labels.forEach(function(label) {
			label.total = parseInt(label.total, 10) || 0;
			self.by_id[label.id] = label;
		});

		this.valueToState(options.value || "");
	};
	$.extend(TagWidget.prototype, {
		refresh: function() {
			var index = 0;
			var self = this;
			var col = {
					add: [],
					remove: [],
					noop: [],
				},
				value = [],
				updateValue = {
					add: function(id) {
						value.push("+" + id);
					},
					remove: function(id) {
						value.push("-" + id);
					},
				};
			this.labels.reduce(function(ac, label) {
				label.index = index;
				if (label.total === self.total) {
					label.disabled = true;
				}
				if (label.action !== "add" && label.action !== "remove") {
					label.action = "noop";
				} else {
					updateValue[label.action](label.id);
				}
				var html = self.renderLabel(label, label.action);
				col[label.action].push(html);

				index = index + 1;
				return ac;
			}, []);
			value = value.join(",");
			this.$item.html(
				html.tag("input", { name: this.name, value: value, type: "hidden" }) +
					this.renderColumn(this.options.columns.noop, col.noop) +
					this.renderColumn(this.options.columns.add, col.add) +
					this.renderColumn(this.options.columns.remove, col.remove)
			);
			$(".tag-label a", this.$item).on("click", function() {
				var $this = $(this);
				var index = $this.parents(".tag-label").data("index");
				self.labels[index].action = $this.data("action");
				self.refresh();
			});
		},
		valueToState: function(value) {
			var self = this;
			this.labels.forEach(function(label) {
				label.action = "noop";
			});
			this.value = String(value);
			if (!this.value) {
				return;
			}
			String(this.value)
				.split(",")
				.forEach(function(token) {
					var handler = {
						"+": function(label) {
							label.action = "add";
						},
						"-": function(label) {
							label.action = "remove";
						},
					};
					if (!token) {
						return;
					}
					var action = token.substr(0, 1),
						id = token.substr(1),
						label = self.by_id[id];
					if (label && handler[action]) {
						handler[action](label);
					}
				});
		},
		renderColumn: function(column, labels) {
			var blank = column.empty,
				title = column.title,
				code = column.code;
			var content = labels.join("\n");
			if (!content) {
				content = html.tag("span", ".empty-string", blank);
			}
			return html.tag(
				"div",
				columnClasses + " ." + code + "-col",
				html.tag("h2", title) + html.tag("div", ".tag-labels", content)
			);
		},
		renderButton: function(code, title, disabled) {
			var classes = buttonClasses;
			if (disabled) {
				classes += " disabled";
			}
			return html.tag("a", { class: classes, "data-action": code }, title);
		},
		renderLabel: function(label, disableLink) {
			var badge = label.total || 0,
				badge_sep = this.options.badge.separator,
				badge_suffix = { add: badge_sep + this.total, remove: badge_sep + "0" };
			badge_suffix = badge_suffix[label.action] || "";

			return html.tag(
				"div",
				{
					id: "tag-label-" + label.id,
					class: "tag-label" + (label.disabled ? " disabled" : ""),
					"data-index": label.index,
				},
				html.tag("span", ".name", label.name) +
					this.renderButton("noop", this.options.columns.noop.button, "noop" === disableLink) +
					this.renderButton(
						"add",
						this.options.columns.add.button,
						"add" === disableLink || label.total === this.total
					) +
					this.renderButton(
						"remove",
						this.options.columns.remove.button,
						"remove" === disableLink || label.total === 0
					) +
					html.tag(
						"span",
						".badge " +
							this.options.badge.class +
							" " +
							(this.options.columns[label.action].badge_class || "") +
							(badge_suffix ? " .separator" : ""),
						badge + badge_suffix
					)
			);
		},
	});

	$.fn[code] = function(options) {
		$(this).each(function() {
			var $this = $(this);
			var widget = $this.data(code);
			if (!widget) {
				widget = new TagWidget($this, options);
			}
			$this.data(code, widget);
			widget.refresh();
		});
	};
})(window || exports);
