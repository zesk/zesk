if (!window.exception_instrumented) {
	(function (document, $) {
		$(document).ready(function() {
			$('.exception-trace .method').off("click.exception").on("click.exception", function(e) {
				$('.args', this).toggle('fast');
				e.preventDefault();
				e.stopPropagation();
			});
		});
	}(window.document, window.jQuery));
	window.exception_instrumented = true;
}