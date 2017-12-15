<?php
namespace zesk;

/* @var $this Template */
$where = $this->get('where');
if (!in_array($where, array(
	'left',
	'right',
	'top',
	'bottom',
	'top-right',
	'top-left',
	'bottom-right',
	'bottom-left'
))) {
	$where = 'bottom-left';
}
$id = 'zesk-overlay-frame-' . $where;
$this->frame = null;
$this->response->content_type('text/javascript');

header("Content-Type: text/javascript");

$qs = array();
$qs['width'] = $this->geti('width', 300);
$qs['height'] = $this->geti('height', 100);
$qs['where'] = $where;
$qs['thickness'] = $this->geti('thickness', 20);

$timeout = $this->geti('timeout', 30);

ob_start();
?>
<script>
(function(exports) {
	function ZeskOverlay() {
		this.e = [];
		this.t = null;
		this.i = null;
		this.te = null;
		this.init();

		this.timeout = parseInt('{timeout}', 10);
		this.over_stop = false;
	}
	ZeskOverlay.prototype = {
		close: function() {
			while (this.e.length > 0) {
				document.body.removeChild(this.e.pop());
			}
		},
		start: function () {
			this.t = parseInt(new Date().getTime() / 1000) + this.timeout;
			this.i = setTimeout("zesk_overlay.timer()", 1000);
		},
		stop: function () {
			if (this.i) {
				clearTimeout(this.i);
				this.i = null;
				this.te.innerHTML = "&nbsp;";
			}
		},
		timer: function() {
			if (this.i === null) {
				return;
			}
			var dt = this.t - parseInt(new Date().getTime() / 1000);
			if (dt <= 0) {
				this.close();
			} else {
				if (dt < 10) { dt = "0" + dt; }
				this.te.innerHTML = "0:" + dt;
				setTimeout("zesk_overlay.timer()", 1000);
			}
		},
		add: function (e) {
			var id = e.id;
			var d = document;
			if(d.getElementById(id)){
				var f = d.getElementById(id);
				d.body.removeChild(f);
			}
			d.body.appendChild(e);
			this.e.push(e);
			return e;
		},
		init: function () {
			var d = document;
			var t = this;
			var e = t.e;
			function head_add(e) {
				try {
					d.getElementsByTagName('head')[0].appendChild(e);
				} catch(z) {
					d.body.appendChild(e);
				}
				return e;
			}
			function iframe_add(u, over_stop) {
				var e = d.createElement('iframe');
				e.setAttribute('frameBorder','0');
				e.setAttribute('src',u);
				e.setAttribute('scrolling','no');
				e.setAttribute('allowTransparency','allowtransparency');
				if (over_stop) {
					e.setAttribute('onmouseover','zesk_overlay.stop()');
				}
				e.id = '{id}';
				return e;
			}
			function css_add(u) {
				var e = d.createElement('link');
				e.setAttribute('type', 'text/css');
				e.setAttribute('href', u);
				e.setAttribute('rel', 'stylesheet');
				e.setAttribute('media', 'screen');
				return e;
			}
			function div_add(id,content) {
				var e = d.createElement('div');
				e.id = id;
				e.innerHTML = content;
				return e;
			}
			t.add(css_add('{css_url}'));
			t.add(iframe_add('{iframe_url_prefix}'+escape(d.URL)+'&title='+escape(d.title)), this.over_stop);
			t.add(div_add('{id}-close', '<a href="javascript:zesk_overlay.close()"></a>'));
			t.te = t.add(div_add('{id}-timer', '&nbsp;'));
			t.start();
		}
	};
	exports.zesk_overlay = new ZeskOverlay();
}(window));
</script>
<?php
$map['css_url'] = URL::query_format($this->css_url, $qs);
$map['iframe_url_prefix'] = $this->iframe_url_prefix;
$map['timeout'] = $timeout;
$map['id'] = $id;

echo HTML::extract_tag_contents("script", map(ob_get_clean(), $map));
