<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

$latitude = $this->latitude;
$longitude = $this->longitude;
$zoom = $this->geti('zoom', 14);
$message = $this->message;

$id = "openlayers-map-" . $response->id_counter();

$width = $this->geti("width", null);
$height = $this->geti("height", null);

ob_start();
?>
<script>
$(document).ready(function () {
	var map = null,
	zoom = parseInt('<?php echo $zoom ?>', 10),
	latitude = parseFloat('<?php echo $latitude ?>'),
	longitude = parseFloat('<?php echo $longitude ?>'),
	message = <?php echo JSON::encode($message); ?>,
	size = <?php echo $width ? "new ol.Size($width, $height)" : "null" ?>,
	$popup = $('#<?php echo $id; ?>-popup'),
	position =  ol.proj.transform([longitude, latitude], 'EPSG:4326', 'EPSG:3857'),
	overlay,
	map;

	/**
	 * Create an overlay to anchor the popup to the map.
	 */
	overlay = new ol.Overlay({
		element: $popup[0]
	});
	overlay.setPosition(position);

	/**
	 * Create the map.
	 */
	map = new ol.Map({
		layers: [
			new ol.layer.Tile({
				source: new ol.source.OSM()
			})
		],
		overlays: [overlay],
		target: '<?php echo $id; ?>',
		view: new ol.View({
			center: position,
			zoom: 17
		})
	});
	map.renderSync();
	$popup.popover({
		html: true,
		content: <?php echo JSON::encode($this->message); ?>,
		viewport: overlay.element,
		placement: 'bottom'
	}).popover('show');
});
</script>
<?php

$response->javascript_inline(HTML::extract_tag_contents("script", ob_get_clean()));

echo HTML::tag("div", "#$id .openlayers-map .openlayers-map-marker-popup", "");
echo HTML::tag('div', array(
	'id' => "$id-popup",
	'class' => 'openlayers-map-marker-member',
	'style' => "",
	'data-title' => $this->title
), "");

