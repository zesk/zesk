<?php

/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/bootstrap/classes/Module/Bootstrap.php $
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Bootstrap extends Module implements Interface_Module_Foot, Interface_Module_Head {
	
	/**
	 */
	public function initialize() {
		$this->application->hooks->add('zesk\\Control_List::row_widget', array(
			$this,
			'_hook_list_row_widget'
		));
	}
	
	/**
	 *
	 * @param Control $self
	 * @param Control_Row $row
	 */
	public function _hook_list_row_widget(Control $self, Control_Row $row) {
		$n_columns = $self->option('list_column_count', 12);
		$children = $row->children();
		$child_divisor = count($children) === 0 ? 1 : count($children);
		$n_per = max(1, intval($n_columns / $child_divisor));
		$autos = array();
		$total = $n_columns;
		foreach ($row->children() as $child) {
			$list_column_width = $child->option('list_column_width');
			if ($list_column_width === null) {
				$child->set_option('list_column_width', $n_per);
				$total -= $n_per;
			} else if ($list_column_width === 'auto') {
				$autos[] = $child;
			} else if (is_numeric($list_column_width)) {
				$total -= $list_column_width;
			}
		}
		if (count($autos) > 0) {
			$n_per = intval($total / count($autos));
			foreach ($autos as $child) {
				$child->set_option('list_column_width', $n_per);
			}
		}
	}
	
	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Template $template
	 */
	public function hook_head(Request $request, Response $response, Template $template) {
		// Lazy eval
		if ($this->option_bool('enabled')) {
			$html = $response->html();
			if (!$this->source_location()) {
				$response->css("/share/bootstrap/css/bootstrap.css", array(
					'share' => true
				));
			}
			if ($this->option_bool("responsive", true)) {
				$html->meta("viewport", "width=device-width, initial-scale=1.0");
			}
			$html->meta(array(
				'http-equiv' => 'X-UA-Compatible',
				'content' => "IE=edge"
			));
			$html->meta(array(
				'charset' => 'utf-8'
			));
			
			$html->jquery();
			$html->javascript(array(
				$this->application->development() ? "/share/bootstrap/js/bootstrap.js" : "/share/bootstrap/js/bootstrap.min.js"
			), array(
				'share' => true
			));
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Module_Foot::hook_foot()
	 */
	public function hook_foot(Request $request, Response $response, Template $template) {
		if ($this->option_bool('responsive_size_enabled', $this->application->development())) {
			echo $this->application->theme("bootstrap/responsive-size");
		}
	}
	/**
	 * Setting the source location for bootstrap means you want to build your own less files from
	 * their source, very powerful.
	 *
	 * Set in your application configuration file, and upon update the files will be automatically
	 * copied into your source tree, where they should be checked in
	 * to the source as well.
	 *
	 * @param string $set
	 * @return string
	 */
	public function source_location($set = null) {
		return $set !== null ? $this->set_option("source_location", $set) : $this->option("source_location");
	}
	
	/**
	 * map bootstrap file
	 */
	private function map_variables_file($source_location) {
		$newfilename = $this->option('variables_less_override_name', '../variables.less');
		$tr = array(
			"@import \"variables.less\";\n@import \"mixins.less\";" => "@import \"variables.less\";\n@import \"$newfilename\";\n@import \"mixins.less\";"
		);
		$bootstrap_main = path($source_location, "bootstrap.less");
		if (!is_file($bootstrap_main)) {
			$this->application->logger->error("Module_Bootstrap expected file to exist for modification: {file} - no action taken", array(
				"file" => $bootstrap_main
			));
			return false;
		}
		$parent = dirname($source_location);
		if (is_file($import_file_path = path($source_location, $newfilename))) {
			$old = file_get_contents($bootstrap_main);
			$new = strtr($old, $tr);
			if ($old === $new) {
				$this->application->logger->warning("Module_Bootstrap tried to modify {file} for custom variables.less, but didn't change it?", array(
					"file" => $bootstrap_main
				));
				return false;
			}
			file_put_contents($bootstrap_main, $new);
			$this->application->logger->notice("Module_Bootstrap modified {file} for custom variables.less", array(
				"file" => $bootstrap_main
			));
			return true;
		} else {
			$this->application->logger->warning("Module_Bootstrap - can not find import file {import_file_path}", compact('import_file_path'));
		}
		return false;
	}
	
	/**
	 * After this module is updated
	 */
	public function hook_updated() {
		$source_location = $this->option('source_location');
		if (!$source_location) {
			$this->application->logger->notice("{method}: No {class}::source_location configuration option specified, using default bootstrap CSS/LESS files", array(
				"method" => __METHOD__,
				"class" => __CLASS__
			));
			return;
		}
		if (!!Directory::is_absolute($source_location)) {
			$source_location = $this->application->path($source_location);
		}
		$this->application->logger->debug("{class}::hook_updated: source_location={source_location}", array(
			'class' => get_class($this),
			"source_location" => $source_location
		));
		if (is_dir($source_location) || is_dir(dirname($source_location))) {
			$master = path($this->application->path(), dirname($this->option("share_path")), "less");
			if (!is_dir($master)) {
				$this->application->logger->error("Bootstrap master directory moved from \"{master}\" to {source_location} - update Module_Bootstrap code", compact("master", "source_location"));
				return;
			}
			Directory::copy($master, $source_location, true);
			$this->map_variables_file($source_location);
		}
	}
}
