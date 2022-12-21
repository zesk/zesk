(function (w) {
	var $ = w.jQuery;
	if (w.exception_instrumented) {
		return;
	}
	w.exception_instrumented = true;
	$(w.document).ready(function() {
		$('.exception-trace .method').off("click.exception").on("click.exception", function(e) {
			$('.args', this).toggle('fast');
			e.preventDefault();
			e.stopPropagation();
		});
	});
}(window));
