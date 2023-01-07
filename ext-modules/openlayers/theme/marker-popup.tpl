<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$latitude = $this->latitude;
$longitude = $this->longitude;
$zoom = $this->getInt('zoom', 14);
$message = $this->message;

$id = $this->get('id', 'openlayers-map');

$width = $this->getInt('width', null);
$height = $this->getInt('height', null);

ob_start();
?>
<script>
$(document).ready(function () {
	var map = null,
	zoom = parseInt('<?php echo $zoom ?>', 10),
	latitude = parseFloat('<?php echo $latitude ?>'),
	longitude = parseFloat('<?php echo $longitude ?>'),
	message = <?php echo JSON::encode($message); ?>,
	size = <?php echo $width ? "new ol.Size($width, $height)" : 'null' ?>,
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
		placement: 'bottom',
		delay: 200,
	}).popover('show');
});
</script>
<?php

$response->inlineJavaScript(HTML::extract_tag_contents('script', ob_get_clean()));

echo HTML::tag('div', "#$id .openlayers-map .openlayers-map-marker-popup", '');
echo HTML::tag('div', [
	'id' => "$id-popup",
	'class' => 'openlayers-map-marker-member',
	'style' => '',
	'data-title' => $this->title,
], '');
