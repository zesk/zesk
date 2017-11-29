(function ($) {
	"use strict";
	$(document).ready(function() {
		var update_values = function () {
			var $this = $(this), val = [];
			$(".filter-selector li.filter-item.active a", $this).each(function() {
				val.push($(this).data("id"));
			});
			$(".filter-selector-input", $this).val(val.join(";"));
		};
		
		$('.filters').each(update_values);
		//
		// Menu, when enabled
		//
		$(".filters .filter-item a").on("click", function() {
			var 
			$this = $(this),
			$filters = $this.parents('.filters'),
			id = $this.data("id");
			$this.parents("li").toggleClass("active");
			$("#" + id).parents(".form-group").toggleClass("hidden");
			update_values.call($filters);
		}).each(function () {
			var
			$this = $(this),
			$filters = $this.parents('.filters'),
			id = $this.data("id");
			$("#" + id).parents(".form-group")[$this.parents("li").hasClass("active") ? "removeClass" : "addClass"]("hidden");
			update_values.call($filters);
		});
		$(".filters .filter-selector-all a").on("click", function() {
			var 
			$this = $(this), 
			$filters = $this.parents('.filters'),
			show = !$this.data('showing');
			
			$this.data('showing', show);
			$this.text($this.data('text-' + (show ? "show" : "hide")));
			$(".filters li.filter-item a").each(function () {
				var 
				$this = $(this),
				id = $this.data("id");
				$this.parents("li")[show ? 'addClass' : 'removeClass']("active");
				$("#" + id).parents(".form-group")[!show ? 'addClass' : 'removeClass']("hidden");
			});
			update_values.call($filters);
		});
		//
		// Toggle
		//
		$(".filters .selector-toggle-mode").on("click", function () {
			var
			$this = $(this),
			$filters = $this.parents('.filters'),
			$filter_input = $(".filter-selector-input", $filters),
			val = $filter_input.val(),
			verb = val === "" ? "addClass" : "removeClass",
			not_verb = val === "" ? "removeClass" : "addClass";

			$this[verb]("active");
			$(".filter-selector li.filter-item", $filters)[verb]("active");
			$($this.data('target')).parents(".form-group")[not_verb]("hidden");
			update_values.call($filters);
		});
	});
}(window.jQuery));
