<?php declare(strict_types=1);
$opts = $this->options;
$opts['language'] = $this->language;

$js_options = json_encode($opts);

$input_name = $this->input_name;

?>
var opts = <?php echo $js_options ?>;
opts['monthNames'] = Date.prototype.localeMonths(opts.language);
opts['dayNames'] = Date.prototype.localeWeekdays(opts.language);
opts['dayNamesMin'] = Date.prototype.localeWeekdaysMin(opts.language);
opts['weekHeader'] = Date.prototype.localeWeekHeader(opts.language);
opts['first_day_of_week'] = <?php echo $this->first_day_of_week ?>;
opts['input_name'] = '<?php echo $this->input_name ?>';
opts['start_column'] = '<?php echo $this->start_column ?>';
opts['end_column'] = '<?php echo $this->end_column ?>';
window['<?php echo $input_name ?>_datepicker_options'] = opts;
zesk_datepicker_configure(opts);
