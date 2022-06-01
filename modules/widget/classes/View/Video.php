<?php declare(strict_types=1);
/**
 * Adding support for http://www.jeroenwijering.com/extras/readme.html
 *
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Video extends View {
	public static function html_param_tag($name, $value) {
		if (is_bool($value)) {
			$value = StringTools::fromBool($value);
		}
		return HTML::tag('param', [
			'name' => $name,
			'value' => $value,
		], null);
	}

	private function fpOption($key, $default) {
		if (is_bool($default)) {
			$var = $this->optionBool($key, $default);
			$var = StringTools::fromBool($var);
		} else {
			$var = $this->option($key, $default);
			$var = strval($var);
		}
		return $var;
	}

	private function href($url) {
		return HTML::href($this->application, $url);
	}

	private function flash_player_html($path) {
		static $player_id = 1;

		$width = $this->optionInt('width');
		$height = $this->optionInt('height');
		$this_id = $player_id++;
		$html = '<p id="player' . $this_id . '"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</p>';
		$html .= "<script type=\"text/javascript\">\n";
		$html .= "var fp$this_id = new SWFObject(\"" . $this->href('/share/zesk/widgets/video/flvplayer.swf') . "\",\"single\",\"$width\",\"$height\",\"7\");\n";

		$params = [
			'allowfullscreen' => true,
			'wmode' => 'opaque',
		];
		$vars = [
			'file' => $this->href(urlencode($path)),
			'autostart' => false,
			'image' => '',
			'displayheight' => $height,
			'backcolor' => '0x000000',
			'frontcolor' => '0xFFFFFF',
			'lightcolor' => '0x557722',
			'volume' => 80,
		];
		foreach ($params as $key => $default) {
			$var = $this->fpOption($key, $default);
			if ($var !== '') {
				$html .= "fp$this_id.addParam(\"$key\", \"" . addslashes($var) . "\");\n";
			}
		}
		foreach ($vars as $key => $default) {
			$var = $this->fpOption($key, $default);
			if ($var !== '') {
				$html .= "fp$this_id.addVariable(\"$key\", \"" . addslashes($var) . "\");\n";
			}
		}
		$html .= "fp$this_id.write('player$this_id');\n";
		$html .= "</script>\n";
		return $html;
	}

	public function media_player_html($path) {
		$width = $this->optionInt('width');
		$height = $this->optionInt('height');

		$autostart = $this->optionBool('autostart', true);
		$standby_string = htmlspecialchars($this->option('standby_string', 'Loading Microsoft Windows� Media Player components...'));
		;
		$showcontrols = $this->optionBool('showcontrols', true);
		$volume = $this->optionInt('volume', -20);
		$AutoSize = $this->optionBool('AutoSize', false);
		$ShowDisplay = $this->optionBool('ShowDisplay', false);

		$win_embed = '<embed type="application/x-mplayer2" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/"
src="' . $this->href($path) . '"
name="MediaPlayer1"
width="' . $width . '" height="' . $height . '" autostart="' . intval($autostart) . '" showcontrols="' . intval($showcontrols) . '" volume="' . $volume . '">';
		$embed = HTML::tag('embed', [
			'src' => $this->href($path),
			'width' => $width,
			'height' => $height,
			'ShowControls' => intval($showcontrols),
			'ShowDisplay' => intval($ShowDisplay),
		], '');
		$result = '<object id="MediaPlayer1"
classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95"
codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701"
standby="' . $standby_string . '" type="application/x-oleobject" width="' . $width . '" height="' . $height . '">' . self::html_param_tag('fileName', $path) . self::html_param_tag('animationatStart', 'true') . self::html_param_tag('transparentatStart', 'true') . self::html_param_tag('AutoSize', intval($AutoSize)) . self::html_param_tag('ShowDisplay', intval($ShowDisplay)) . self::html_param_tag('autoStart', StringTools::fromBool($autostart)) . self::html_param_tag('ShowControls', intval($showcontrols)) . self::html_param_tag('Volume', $volume) . $embed . '</object>';

		return $result;
	}

	public function quicktime_player_html($path) {
		$width = $this->optionInt('width');
		$height = $this->optionInt('height');
		$attrs = $this->options_include('name;id;tabindex;hspace;vspace;border;align;class;title;accesskey;noexternaldata');

		$attrs['classid'] = 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B';
		$attrs['width'] = $width;
		$attrs['height'] = $height;
		$attrs['codebase'] = 'http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0';

		$oparams['src'] = $this->option('preview_src');
		$oparams['href'] = $this->href($path, false);
		foreach ([
			'autoplay' => false,
		] as $k => $v) {
			$oparams[$k] = $this->option($k, $v);
		}
		$oparams_content = '';
		foreach ($oparams as $k => $v) {
			$oparams_content .= self::html_param_tag($k, $v);
		}
		$embed_attrs = $this->options_include('name;align;tabindex;autoplay');
		$embed_attrs['src'] = $this->href($path, false);
		$embed_attrs['width'] = $width;
		$embed_attrs['height'] = $height;
		$embed_attrs['pluginspage'] = 'http://www.apple.com/quicktime/download/';

		$content = $oparams_content . HTML::tag('embed', $embed_attrs, '');

		$result = HTML::tag('object', $attrs, $content);

		return $result;
	}

	public function video_html($path) {
		$ext = strtolower(File::extension($path));
		if (in_array($ext, $this->optionIterable('flash_player_extensions', 'flv'))) {
			return $this->flash_player_html($path);
		}
		if (in_array($ext, $this->optionIterable('quicktime_extensions', 'mov;sdp;dv;mpeg;mpg;mp4;m4v'))) {
			return $this->quicktime_player_html($path);
		}
		if (in_array($ext, $this->optionIterable('mplayer_extensions', ''))) {
			return $this->media_player_html($path);
		}
		switch ($this->option('unknown_use')) {
			case 'quicktime':
				return $this->quicktime_player_html($path);
			case 'windows-media-player':
				return $this->media_player_html($path);
			default:
				return $this->quicktime_player_html($path);
		}
	}

	/**
	 * Returns the representation of model as an <img /> tag.
	 *
	 * @return string
	 */
	public function render(): string {
		$path = $this->object->applyMap($this->option('src', '{' . $this->column() . '}'));
		return $this->video_html($path);
	}
}
