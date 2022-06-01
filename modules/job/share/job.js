(function(exports, $) {
	var zesk = exports.zesk;
	zesk.add_hook("document::ready", function() {
		var Job = function($element) {
			this.$element = $element;
			this.id = $element.data("id");
			this.timer = null;
			this.success_remove = $element.data("success-remove") ? true : false;
			$('.progress', $element).addClass();
			$('.progress-bar', $element).attr('aria-valuenow', 0).attr('aria-valuemin', 0).attr('aria-valuemax', 100);
		};

		$.extend(Job.prototype, {
		    monitor: function() {
			    var self = this;
			    if (self.timer) {
			    	clearTimeout(self.timer);
			    	self.timer = null;
			    }
			    $.ajax('/job/' + this.id + '/monitor', {
			        success: function(data) {
				        var $element = self.$element;
				        if (data.content) {
					        $element.html(data.content);
				        }
				        if (data.message) {
					        $('.message', $element).html(data.message);
					        data.message = null;
				        }
				        if (data.progress) {
					        self.progress(parseInt(data.progress, 10));
				        }
				        zesk.handle_json(data);
				        if (data.wait) {
				        	self.timer = setTimeout(function() {
						        self.monitor();
					        }, data.wait);
				        } else {
					        self.progress(100);
				        }
			        },
			        error: function(e) {
				        zesk.log(e);
			        }
			    });
		    },
		    progress: function(progress) {
			    var $element = this.$element;
			    var done = progress >= 100;
			    $('.progress-bar', $element).css('width', progress + "%");
			    $('.sr-only', $element).html(done ? 'Completed' : progress + "% Complete");
			    if (done) {
				    this.completed();
			    }
		    },
		    completed: function () {
			    var $element = this.$element;
			    var success = $element.attr('data-success');
			    $('.progress-bar', $element).removeClass('active');
			    if (success) {
				    $.globalEval(success);
			    }
			    this.$element.data("job", null);
			    if (this.success_remove) {
					this.$element.keysRemove();
			    }
			    delete this;
		    }
		});
		$('.job-monitor').each(function() {
			var $this = $(this), job = $this.data("job");
			if (!job) {
				$this.data("job", job = new Job($this));
				job.monitor();
			}
		});
	});
}(window, window.jQuery));
