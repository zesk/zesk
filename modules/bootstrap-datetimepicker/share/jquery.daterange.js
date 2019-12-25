(function(w) {
	var $ = w.$ || w.jQuery;
	var html = w.html;
	var code = "dateRangeWidget";
	var scratchId = "DateRangeWidget-Dialog";
	var scratch = "#" + scratchId;
	var DATE_PREFIX = "custom";
	var DATE_SEP = "-";
	var DATE_FORMAT = "YYYY_MM_DD";
	var defaults = {
		title: "Choose dates",
		custom: {
			prefix: "",
			format: "MMM D, YYYY",
			separator: " - ",
		},
		cancel: {
			label: "Cancel",
			class: "btn btn-default",
		},
		submit: {
			label: "Set date range",
			class: "btn btn-primary",
		},
	};

	var DateRangeWidget = function(select, options) {
		var self = this;

		if ($(scratch).length === 0) {
			$("body").append('<div id="' + scratchId + '" />');
		}

		this.$scratch = $(scratch);

		this.options = $.extend(true, defaults, options);

		this.$modal = null;
		this.select = select;
		this.$select = $(select);
		this.name = "daterange-" + this.$select.attr("name");
		this.rendering = false;

		this.value = this.options.value || null;

		delete this.options.value;

		this.savedHTML = this.$select.html();
		this.savedState = [];
		this.values = {};

		$("option", this.$select).each(function() {
			var $this = $(this),
				value = $this.val(),
				text = $this.html();
			self.savedState.push({ value: value, text: text });
			self.values[value] = text;
		});
		this.$select.off("change." + code).on("change." + code, function() {
			self.changed();
		});
		this.startId = "#" + this.name + "-start";
		this.endId = "#" + this.name + "-end";

		if (this.value && !this.values[this.value]) {
			var dates = this.value.split(DATE_SEP),
				repl = function(x) {
					return String(x)
						.split("_")
						.join("-");
				};
			if (dates.length === 3 && dates[0] === DATE_PREFIX) {
				this.renderSelect(repl(dates[1]), repl(dates[2]));
			}
		}

		this.lastValidValue = this.$select.val();
	};
	$.extend(DateRangeWidget.prototype, {
		changed: function() {
			var val = this.$select.val();
			if (val === "custom") {
				this.showDialog();
			} else {
				this.lastValidValue = this.$select.val();
				if (this.$select.data("refresh")) {
					this.$select[0].form.submit();
				}
			}
		},
		showDialog: function() {
			var self = this,
				modalId = "#" + this.name + "-id";
			this.$scratch.html(
				html.tag(
					"div",
					{ class: "modal fade", id: modalId.substr(1), tabindex: "-1", role: "dialog" },

					html.tag(
						"div",
						{ class: "modal-dialog" },
						html.tag(
							"div",
							{ class: "modal-content" },
							html.tag(
								"div",
								{ class: "modal-header" },
								html.tag(
									"button",
									{ type: "button", class: "close", "data-dismiss": "modal" },
									"&times;"
								) + html.tag("h4", { class: "modal-title" }, this.options.title)
							) +
								html.tag(
									"div",
									{ class: "modal-body" },
									html.tag(
										"div",
										".row",
										html.tag("div", ".col-md-6", html.tag("div", { id: this.startId.substr(1) })) +
											html.tag("div", ".col-md-6", html.tag("div", { id: this.endId.substr(1) }))
									)
								) +
								html.tag(
									"div",
									{ class: "modal-footer" },
									html.tag(
										"div",
										{ class: "form-control-static result-text pull-left" },
										this.text_label
									) +
										html.tag(
											"button",
											{
												type: "button",
												class: this.options.cancel.class,
												"data-dismiss": "modal",
											},
											this.options.cancel.label
										) +
										html.tag(
											"button",
											{ type: "button", class: "btn btn-primary" },
											this.options.submit.label
										)
								)
						)
					)
				)
			);

			var dt_options = {
				inline: true,
				format: "YYYY-MM-DD",
				showTodayButton: true,
			};
			$(this.startId)
				.datetimepicker(
					$.extend(
						{
							defaultDate: this.start || null,
						},
						dt_options
					)
				)
				.on("dp.change", function(e) {
					$(self.endId)
						.data("DateTimePicker")
						.minDate(e.date);
					self.start = e.date;
					self.updateResultText();
				});
			$(this.endId)
				.datetimepicker(
					$.extend(
						{
							useCurrent: false, //Important! See issue #1075
						},
						dt_options
					)
				)
				.on("dp.change", function(e) {
					$(self.startId)
						.data("DateTimePicker")
						.maxDate(e.date);
					self.end = e.date;
					self.updateResultText();
				});

			if (this.end) {
				$(this.startId)
					.data("DateTimePicker")
					.maxDate(this.end);
				$(this.endId)
					.data("DateTimePicker")
					.defaultDate(this.end);
			}
			if (this.start) {
				$(this.startId)
					.data("DateTimePicker")
					.defaultDate(this.start);
				$(this.endId)
					.data("DateTimePicker")
					.minDate(this.start);
			}

			this.$modal = $(modalId);
			$(".btn-primary", this.$modal).click(function() {
				self.finished();
			});
			this.$modal.modal("show");
			this.$modal.off("hide.bs.modal").on("hide.bs.modal", function() {
				self.hiding();
			});
			this.updateResultText();
		},
		hiding: function() {
			if (this.rendering) {
				return;
			}
			this.$select.val(this.lastValidValue);
		},
		finished: function() {
			this.rendering = true;
			this.$modal.modal("hide");
			this.renderSelect(this.start, this.end);
			if (this.$select.data("refresh")) {
				this.$select[0].form.submit();
			}
			this.rendering = false;
		},
		updateResultText: function(message) {
			if (!this.$modal) {
				return;
			}
			$(".result-text", this.$modal).html(message || this.renderText(this.start, this.end));
		},
		renderValue: function(start, end) {
			var moment = w.moment;
			return (
				DATE_PREFIX + DATE_SEP + moment(start).format(DATE_FORMAT) + DATE_SEP + moment(end).format(DATE_FORMAT)
			);
		},
		renderText: function(start, end) {
			var moment = w.moment;
			return (
				this.options.custom.prefix +
				" " +
				moment(start).format(this.options.custom.format) +
				this.options.custom.separator +
				moment(end).format(this.options.custom.format)
			);
		},
		renderSelect: function(start, end) {
			var moment = w.moment,
				mstart = moment(start),
				mend = moment(end),
				new_value = this.renderValue(mstart, mend),
				new_name = this.renderText(mstart, mend),
				newOption = html.tag("option", { value: new_value, selected: "selected" }, new_name);
			this.$select.html(this.savedHTML + newOption);
			this.$select = $(this.select);
			this.start = mstart;
			this.end = mend;
			this.updateResultText();
		},
		initialize: function() {},
	});

	$.fn[code] = function(options) {
		$(this).each(function() {
			var $this = $(this);
			var widget = $this.data(code);
			if (!widget) {
				widget = new DateRangeWidget($this, options);
			}
			$this.data(code, widget);
			widget.initialize();
		});
	};
})(window || exports);
