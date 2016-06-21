<?php
if (false) {
	/* @var $this Template */
	
	$application = $this->application;
	/* @var $application Application */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$object = $this->object;
	/* @var $object Object */
}

$offset = $object->offset;
$limit = $object->limit;
$total = $object->total;
$last_offset = $object->last_offset;

if (!$this->option_bool("always_show", false)) {
	if ($total <= $limit)
		return "";
}

$last_index = ($limit < 0) ? $total : min($offset + $limit, $total);
$uu = url::query_format($request->uri(), array(
	"limit" => $limit
));

$suffix = "";

if ($offset > 0) {
	$p0 = $this->arrowLink($uu, 0, "pager-start", "First page");
	$p1 = $this->arrowLink($uu, max($offset - $limit, 0), "pager-prev", "Previous page");
} else {
	$p0 = html::tag('a', html::cdn_img("/share/images/pager/pager-start-off.gif", __("Already at first page")));
	$p1 = html::tag('a', html::cdn_img("/share/images/pager/pager-prev-off.gif", __("Already at first page")));
}

if ($last_index < $total) {
	$p2 = $this->arrowLink($uu, $offset + $limit, "pager-next", "Next page");
	$p3 = $this->arrowLink($uu, $last_offset, "pager-end", "Last page");
} else {
	$p2 = html::tag('a', html::cdn_img("/share/images/pager/pager-next-off.gif", __("Already at last page")));
	$p3 = html::tag('a', html::cdn_img("/share/images/pager/pager-end-off.gif", __("Already at last page")));
}

if ($this->has_option("suffix")) {
	$suffix = html::tag("td", ".spacer", "") . html::tag("td", "align='right'", $this->option("suffix", ""));
}

$attrs = $_GET;
$hidden = "";
unset($attrs['limit']);
foreach ($attrs as $k => $v) {
	$hidden .= html::hidden($k, $v);
}
$showing = __('Control_Pager:=Showing {0} - {1} of {2}', ($offset + 1), $last_index, $total);
$show = __('Control_Pager:=Show:');
$uri = url::current_uri();
if ($this->option_bool("pager_use_table", zesk::getb("Control_Pager::pager_use_table", true))) {
	$small_width = array(
		"width" => "1%"
	);
	$result = html::tag("form", array(
		"method" => 'get',
		'action' => $uri
	), html::tag("table", array(
		"border" => 0,
		"cellspacing" => 0,
		'cellpadding' => 5,
		'class' => 'pager'
	), html::tag("tr", html::tag("td", $small_width, $p0) . html::tag("td", $small_width, $p1) . html::tag("td", $small_width, $p2) . html::tag("td", $small_width, $p3) . html::tag("td", ".spacer", "") . html::tag("td", array(
		"nowrap" => 'nowrap',
		'align' => 'center'
	), $showing) . html::tag("td", array(
		'width' => '99%'
	), "") . html::tag("td", "$show:") . html::tag("td", array(
		"align" => 'center'
	), $this->outputLimits($limit, $total)) . $suffix)) . $hidden);
} else {
	html::cdn_css("/share/zesk/widgets/pager/pager.css");
	$result = html::tag("form", array(
		"method" => 'get',
		'action' => $uri
	), html::tag("div", array(
		"class" => 'pager'
	), html::tag("div", array(
		"class" => "pager-arrow"
	), $p0) . html::tag("div", array(
		"class" => "pager-arrow"
	), $p1) . html::tag("div", array(
		"class" => "pager-arrow"
	), $p2) . html::tag("div", array(
		"class" => "pager-arrow"
	), $p3) . html::tag("div", array(
		"class" => "pager-state"
	), $showing) . html::tag("div", array(
		"class" => 'pager-limits'
	), html::tag("label", false, "$show:") . $this->outputLimits($limit, $total)) . $suffix) . $hidden);
}
