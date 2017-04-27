<?php
/**
 * 
 */
namespace zesk;

$image = $this->object;
if (!$image instanceof Content_Image) {
	$id = $this->id;
	if (is_numeric($id) && !empty($id)) {
		try {
			$image = $this->application->object_factory('zesk\\Content_Image', $id)->fetch();
		} catch (Exception_Object_NotFound $e) {
			$image = null;
		}
	}
}

if ($image instanceof Content_Image) {
	if (true) {
		$src = Controller_Content_Cache::url_content_image_scaled($image, $this->width, $this->height);
		if ($this->has('image_src_query_append')) {
			$src = URL::query_append($src, $this->image_src_query_append);
		}
		// 	echo HTML::tag('img', )
		$attributes = $this->geta('attributes') + array(
			'src' => $src
		) + $this->variables;
		echo HTML::div('.content-image', HTML::tag('img', arr::filter($attributes, 'id;class;src;width;height;alt;title')));
	} else {
		$image->sync();
		$unique_id = $image->id . '-' . $image->data;
		echo HTML::div('.content-image', $this->theme('image', array(
			'id' => $unique_id,
			'src' => $image->path(),
			'width' => $this->get('width', $image->width),
			'height' => $this->get('height', $image->height)
		) + $this->variables));
	}
} else if ($this->image_missing) {
	echo HTML::div('.content-image missing', $this->theme('image', array(
		'src' => $this->image_missing,
		'width' => $this->width,
		'height' => $this->height
	) + $this->variables));
}
