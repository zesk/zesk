/**
 * $URL$
 * 
 * @package zesk
 * @subpackage Control_DropDown
 * @author Kent M. Davidson http://www.razzed.com/
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
/* global zesk: false */
(function(exports, $) {
	"use strict";
	var plugin_name = 'bootstrap_dropdown';
	var plugin_suffix = '.' + plugin_name;
	var defaults = {
		onupdate: null,
	};
	var Bootstrap_Dropdown = function(element, options) {
		var self = this, $this = $(element);

		this.$element = $this;
		$.each(defaults, function(key) {
			var name = 'dropdown' + key.toCamelCase(), value = $this.data(name);
			self[key] = value ? value : this;
		});
		$.extend(this, options);
		this.init();
	};
	function document_ready(context) {
		if (!context) {
			context = $('body');
		}
		$('[data-bootstrap-dropdown]', context).bootstrap_dropdown();
	}
	$.extend(Bootstrap_Dropdown.prototype, {
	    init: function() {
		    var self = this;
		    var $this = this.$element;
		    var $input = this.$input = $('input', $this);
		    this.$button = $('button.dropdown-toggle', $this);
		    this.content = this.$button.data("content") || '{label}';
		    this.update();

		    $('li a', $this).on("click", function() {
			    $('li.active', $this).removeClass('active');
			    $(this).parents('li').addClass('active');
			    self.update();
		    });
		    $input.on('change', function() {
			    self.update();
		    });
	    },
	    update: function() {
		    var $selected = $('li.active a', this.$element);
		    var label = $selected.data('content') || '{noun}';
		    var noun = $selected.data('noun') || $selected.html();
		    var value = $selected.data('value');
		    var label = label.map({
			    noun: noun
		    });

		    $('.button-label', this.$button).html(this.content.map({
			    label: label
		    }));
		    this.$input.val(value);
		    if (this.onupdate) {
			    this.onupdate.call(this, value);
		    }
	    }
	});
	$.fn[plugin_name] = function(options) {
		var element_apply = function() {
			var $this = $(this);
			$this.data(plugin_name, new Bootstrap_Dropdown($this, options));
		};
		$(this).each(function(index, item) {
			element_apply.call(item);
		});
	};
	$[plugin_name] = function(element, options) {
		var murl = new Bootstrap_Dropdown(element, options);
		murl.click();
	};
	$.bootstrap_dropdown_ready = document_ready;
	zesk.add_hook('document::ready', document_ready);
})(window, window.jQuery);
