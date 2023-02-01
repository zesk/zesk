(function (w) {
	var $ = w.jQuery;
	$(w.document).ready(function() {
		$('.exception-trace .method:not(.exception-instrumented)').off("click.exception").on("click.exception", function(e) {
			$('.args', this).toggle('fast');
			e.preventDefault();
			e.stopPropagation();
		}).addClass('.exception-instrumented');
	});
}(window));
