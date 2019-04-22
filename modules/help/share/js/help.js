/*global zesk: false, $: false */
(function(exports) {
	"use strict";
	var shown = 0,
		__ =
			exports.__ ||
			function(phrase, map) {
				map = map || {};
				return phrase.map(map, true);
			},
		helps = {},
		show = function() {
			var ids = [];
			$.each(helps, function(id) {
				var show_count = zesk.get_path("modules.help.show_count", 3),
					$target = $(this.target),
					$popover;

				if (show_count <= 1) {
					show_count = 1;
				}
				if ($target.length === 0) {
					return;
				}
				if (!$target.is(":visible")) {
					return;
				}
				if (shown >= show_count) {
					return false;
				}
				delete helps[id];
				ids.push(id);
				$target.popover("destroy");
				$target.popover({
					placement: this.placement || "auto right",
					title: this.title,
					html: true,
					container: "body",
					content: '<div class="help-popover-content">' + this.content + "</div>",
				});
				$target.popover("show");
				++shown;
				$popover = $(".help-popover-content").parents(".popover");

				//var $content;
				//$content = $('.help-popover-content');
				//$content.replaceWith($content.html());

				$popover.addClass("help-popover");
				$(".popover-title", $popover).before(
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
				);
				$(".popover-content", $popover).after(
					'<a type="button" class="close" data-dismiss="modal" aria-hidden="true">' + __("Dismiss") + "</a>"
				);
				$('<a class="">' + +"</a>").appendTo($(".popover-content", $popover));
				$(".close", $popover).on("click", function() {
					shown = shown - 1;
					$.ajax("/help/dismiss", {
						method: "POST",
						data: { id: [id] },
						success: function(data) {
							zesk.log("Closing popover-help id " + id + " message: " + data.message);
						},
					});
					$target.popover("destroy");
					show();
				});
			});
		};

	exports.Module_Help = {
		load_targets: function(success) {
			$.ajax("/help/user-targets", {
				dataType: "json",
				success: function(all_help) {
					helps = all_help;
					show();
					if (success) {
						success();
					}
				},
			});
		},
		user_reset: function(success) {
			$.ajax("/help/user-reset", {
				method: "POST",
				success: function() {
					exports.Module_Help.load_targets(success);
				},
			});
		},
	};
	zesk.add_hook("Module_Help::user_reset", exports.Module_Help.user_reset);
	$(document).ready(function() {
		exports.Module_Help.load_targets();
		$(".Module_Help-user_reset").on("click", function() {
			zesk.hook("Module_Help::user_reset");
		});
	});
})(window);
