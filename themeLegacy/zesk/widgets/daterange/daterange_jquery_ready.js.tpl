<?php declare(strict_types=1);
$opts = $this->options;
$opts['language'] = $this->language;

$js_options = json_encode($opts);

$input_name = $this->input_name;

?>
var opts = <?php echo $js_options ?>;
window['<?php echo $input_name ?>_datepicker_options'] = opts;
window['<?php echo $input_name ?>_datepicker_options']['monthNames'] = Date.prototype.localeMonths(opts.language);
window['<?php echo $input_name ?>_datepicker_options']['dayNames'] = Date.prototype.localeWeekdays(opts.language);
window['<?php echo $input_name ?>_datepicker_options']['dayNamesMin'] = Date.prototype.localeWeekdaysMin(opts.language);
window['<?php echo $input_name ?>_datepicker_options']['weekHeader'] = Date.prototype.localeWeekHeader(opts.language);
window['<?php echo $input_name ?>_datepicker_options']['first_day_of_week'] = <?php echo $this->first_day_of_week ?>;
zesk_datepicker_configure('<?php echo $input_name ?>', '<?php echo $this->start_column ?>', '<?php echo $this->end_column ?>');
