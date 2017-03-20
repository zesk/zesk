(function(exports, $) {
	"use strict";
	var module = "logger_footer", zesk = exports.zesk, context = exports.document;

	$(context).ready(
        function($) {
	        var $top = $('#logger_footer', context);
	        var Filter = function(code, name, $item) {
		        this.count = 1;
		        this.code = code;
		        this.name = name;
		        this.css = $item.clone().removeClass("label").attr("class");
		        this.li_selector = "." + code;
		        this.id = "filter-" + code;
		        this.id_selector = "#" + this.id;
		        this.$element = null;
		        this.cookie_name = module + "-" + code;
		        this.active = parseInt(zesk.cookie(this.cookie_name), 10) || 0;
	        }, filters = {}, total = 0;

	        $.extend(Filter.prototype, {
	            render: function() {
		            return "<button data-code=\"" + this.code + "\" id=\"" + this.id + "\" class=\"btn btn-xs " + this.css + "\">" + this.name + "<span class=\"badge\">" + this.count
		                    + "</span></button>";
	            },
	            update_state: function() {
		            var $id = $(this.id_selector, $top);
		            $(this.li_selector, $top)[this.active ? "show" : "hide"]();
		            if (this.active) {
			            $id.addClass("active").removeClass("inactive");
		            } else {
			            $id.addClass("inactive").removeClass("active");
		            }
	            },
	            toggle: function() {
		            this.active = 1 - this.active;
		            zesk.cookie(this.cookie_name, this.active);
	            }
	        });
	        $("label.label", $top).each(function() {
		        var $this = $(this), code = $this.parents("li").attr("class");
		        if (filters[code]) {
			        filters[code].count++;
		        } else {
			        filters[code] = new Filter(code, $.trim($this.text()), $this);
		        }
		        ++total;
	        });
	        $top.prepend("<div class=\"filters\"></div>");
	        $.each(filters, function(code) {
		        var self = this;
		        $('.filters', $top).append(this.render());
		        this.$element = $("#" + this.id);
		        this.bgcolor = this.$element.css("background-color");
		        this.update_state();
		        this.$element.off("click." + module).on("click." + module, function() {
			        self.toggle();
			        self.update_state();
		        });
	        });
	        $('.filters', $top).prepend("<button class=\"btn btn-xs\" id=\"button-all\">all<span class=\"badge\">" + total + "</span></button>");
	        $("#button-all").off("click." + module).on("click." + module, function() {
	        	var nactive = 0;
	        	$.each(filters, function () {
	        		if (this.active) {
	        			++nactive;
	        			this.toggle();
	        			this.update_state();
	        		}
	        	});
	        	if (nactive === 0) {
		        	$.each(filters, function () {
	        			this.toggle();
	        			this.update_state();
		        	});
	        	}
	        });
        });
}(window, window.jQuery));
