<?php declare(strict_types=1);
class Module_Masonry extends zesk\Module_JSLib {
	protected $javascript_paths = [
		'/share/masonry/masonry.pkgd.js',
	];

	protected $jquery_ready = [
		'if (!$.fn.masonry) { $.fn.masonry = function (args) { $(this).each(function() { $(this).data("masonry", new Masonry(this, args)); }) }; }',
	];
}
