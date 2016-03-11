(function(exports, $) {
	var zesk = exports.zesk;
	zesk.add_hook("document::ready", function() {
		var progress = function($this, progress) {
			var done = progress >= 100;
			$('.progress-bar', $this).css('width', progress + "%");
			$('.sr-only', $this).html(done ? 'Completed' : progress + "% Complete");
			if (done) {
				var success = $($this).attr('data-success');
				$('.progress-bar').removeClass('active');
				if (success) {
					$.globalEval(success);
				}
			}
		}, monitor = function(id) {
			var $this = this;
			$.ajax('/job/' + id + '/monitor', {
			    success: function(data) {
				    if (data.content) {
					    $this.html(data.content);
				    }
				    if (data.message) {
					    $('.message', $this).html(data.message);
					    data.message = null;
				    }
				    if (data.progress) {
					    progress($this, parseInt(data.progress, 10));
				    }
				    if (data.wait) {
					    setTimeout(function() {
						    monitor.call($this, id);
					    }, data.wait);
				    } else {
					    progress($this, 100);
				    }
				    zesk.handle_json(data);
			    },
			    error: function(e) {
				    zesk.log(e);
			    }
			});
		};
		$('.job-monitor').each(function() {
			var $this = $(this), id = $(this).data('id');
			$('.progress', $this).addClass();
			$('.progress-bar', $this).attr('aria-valuenow', 0).attr('aria-valuemin', 0).attr('aria-valuemax', 100);
			monitor.call($this, id);
		});
	});
}(window, window.jQuery));
