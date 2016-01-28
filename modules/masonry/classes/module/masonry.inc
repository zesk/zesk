<?php

class Module_Masonry extends Module_JSLib {
	protected $javascript_paths = array(
		'/share/masonry/masonry.pkgd.js'
	);
	protected $jquery_ready = array(
		'if (!$.fn.masonry) { $.fn.masonry = function (args) { $(this).each(function() { $(this).data("masonry", new Masonry(this, args)); }) }; }'
	);
}