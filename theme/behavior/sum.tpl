<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $response Response */
/* @var $widget Widget */
/* @var $object Model */
$sum_widgets = [];
foreach ($this->widgets as $widget_name) {
	$sum_widgets[$widget_name] = $widget->top()->addChild($widget_name)->jquery_target_expression();
}

$id = $widget->id();

$map['total_widget'] = $widget->jquery_target_expression();
$map['sum_widgets'] = implode(', ', $sum_widgets);

ob_start();
?>
<script>
(function () {
	var
	$total_widget = {total_widget},
	$sum_widgets = [ {sum_widgets} ],
	$first_widget = $sum_widgets[0],
	$last_widget = $sum_widgets[$sum_widgets.length - 1],
	input_float_val = function (x) {
		var f = parseFloat($(x).val());
		if (isNaN(f)) {
			return null;
		}
		return f;
	},
	format_float_val = function (f) {
		if (f === 0 || f === 0.0) {
			return "";
		}
		return Number(f).toFixed(2);
	},
	adjust_total = function () {
		var total = 0;
		$.each($sum_widgets, function() {
			total += input_float_val(this);
		});
		if (total != 0) {
			$total_widget.val(format_float_val(total));
		}
	},
	find_adjust_target = function ($not_target) {
		$adjust_target = null;
		$.each($sum_widgets, function() {
			var $this = $(this);
			if ($adjust_target === null && $this.get(0) !== $not_target.get(0)) {
				$adjust_target = $this;
			}
		});
		return $adjust_target;
	},
	adjust_sum_widgets = function (total, $target, not_target) {
		var
		$adjust_target = not_target ? find_adjust_target($target) : $target;
		$target.val(format_float_val(Math.min(input_float_val($target) || 0, total)));
		total -= input_float_val($target) || 0;
		$.each($sum_widgets, function() {
			var
			$this = $(this),
			val = input_float_val($this);
			if ($this.get(0) === $target.get(0)) {
				return;
			}
			if (val > total) {
				val = total;
				$this.val(format_float_val(val));
			}
			total -= val;
		});
		if (total > 0) {
			$adjust_target.val(format_float_val(input_float_val($adjust_target) + total));
		}
	};
	$total_widget.off("blur.sum").on("blur.sum", function () {
		var
		$this = $(this),
		val = input_float_val($this),
		total;
		if (val === null) {
			adjust_total();
		} else {
			$this.val(format_float_val(val));
			adjust_sum_widgets(val, $first_widget);
		}
	});
	$.each($sum_widgets, function () {
		var $this = $(this);
		$this.off("blur.sum").on("blur.sum", function () {
			var
			total = input_float_val($total_widget),
			val = input_float_val($this);
			if (total === null) {
				$this.val(format_float_val(val));
				adjust_total();
			} else {
				if (val === null) {
					adjust_sum_widgets(total, $this, true);
				} else {
					$this.val(format_float_val(Math.min(val, total)));
					adjust_sum_widgets(total, $this, true);
				}
			}
		});
	});
}());
</script>
<?php
$response->html()->jquery(map(HTML::extract_tag_contents('script', ob_get_clean()), $map));
