<?php
/**
 * $URL$
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2007, Market Acumen, Inc.
 */
html::cdn_javascript("/share/zesk/js/date.js");
$this->select->set_option("onchange", "ControlDateRange_Update(this,'" . $this->name . "')");
$current = $model->get($name);
?><table class="daterange">
	<tr>
		<td>
			<?php echo $this->SelectWidget->output($model)?>
		</td>
		<td>
			<table id="custom_range_<?php echo $this->name ?>" style="display: <?php echo $current == "custom" ? "block" : "none" ?>">
				<tr>
					<td class="daterange-label">From:</td>
					<td nowrap="nowrap"><?php echo $start->render($model) ?></td>
					<td class="daterange-label">To:</td>
					<td nowrap="nowrap"><?php echo $end->render($model) ?></td>
				</tr>
			</table>
			<table id="month_range_<?php echo $this->name ?>" style="display: <?php echo $current == "month" ? "block" : "none" ?>">
				<tr>
					<td class="daterange-label">Month:</td>
					<td nowrap="nowrap"><?php echo $this->month->render($model) ?></td>
				</tr>
			</table>
			<table id="week_range_<?php echo $this->name ?>" style="display: <?php echo $current == "week" ? "block" : "none" ?>">
				<tr>
					<td class="daterange-label">Week:</td>
					<td nowrap="nowrap"><?php echo '0'/*$this->week->output($model)*/ ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
