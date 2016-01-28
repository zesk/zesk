<?php
/* @var $request Request */
$request = $this->request;
?>
<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container" style="width: auto; padding: 0 20px;">
			<?php
			echo html::tag('a', array(
				'class' => 'brand',
				'href' => $this->get('title_href', '/')
			), $this->title);
			?>
			<ul class="nav">
				<?php
				foreach ((array) $this->menu as $link => $item) {
					$active = $request->path() == $link;
					echo html::tag('li', array(
						"class" => $active ? "active" : ""
					), html::tag('a', array(
						"href" => $link
					), $item));
				}
				?>
			</ul>
		</div>
	</div>
</div>
