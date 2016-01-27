<?php
	$class = $this->class ? " class=\"$this->class\"" : "";
?><a<?php echo $class ?> href="javascript:(function(){var%20_zE=document.createElement('script');_zE.setAttribute('language','javascript');_zE.setAttribute('src','<?php echo $this->src ?>');document.body.appendChild(_zE);void(0);}())"><?php echo $this->text ?></a>