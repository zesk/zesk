<?php
namespace zesk;

$this->response->jquery();
$this->response->javascript('/share/zesk/jquery/jquery.autoresize.js');
$this->response->javascript('/share/markdown/markdown.js');
$this->response->css('/share/markdown/markdown.css');
?>
<table style="width: 100%">
	<tr>
		<td valign="top" width="50%"><textarea style="width: 100%"
				class="markdown" id="markdown"
				onkeyup="markdown_preview.call(this, '#markdown-preview')"></textarea></td>
		<td valign="top" id="markdown-preview" class="markdown" width="50%"></td>
	</tr>
</table>
