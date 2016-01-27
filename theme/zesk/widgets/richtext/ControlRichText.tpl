<?php
ControlRichText::page_register();
html::jquery("RichText_Widget('$this->name', " . str::from_bool($this->read_only) . ")");
?><textarea name="storage_<?php echo $this->name?>"
	id="storage_<?php echo $this->name?>" style="display: none"><?php echo $this->content?></textarea>

<table class="btnBack" cellpadding=2 cellspacing=0
	id="Buttons1_<?php echo $this->name?>" width="' + tablewidth + '">
	<tr>
		<td><select id="formatblock_<?php echo $this->name?>"
			onchange="RichText_Select('<?php echo $this->name?>', this.id)">
			<option value="">[Style]</option>
			<option value="<p>">Normal</option>
			<option value="<h1>">Heading 1</option>
			<option value="<h2>">Heading 2</option>
			<option value="<h3>">Heading 3</option>
			<option value="<h4>">Heading 4</option>
			<option value="<pre>">Formatted</option>
		</select></td>
<?php/*
	Font? No.
		<td>
			<select id="fontname_<?php echo $this->name ?>" onchange="RichText_Select('<?php echo $this->name ?>', this.id)">
				<option value="Font" selected>[Font]</option>
				<option value="Arial, Helvetica, sans-serif">Arial</option>
				<option value="Courier New, Courier, mono">Courier New</option>
				<option value="Times New Roman, Times, serif">Times New Roman</option>
				<option value="Verdana, Arial, Helvetica, sans-serif">Verdana</option>
			</select>
		</td>
		<td>
			<select unselectable="on" id="fontsize_<?php echo $this->name ?>" onchange="RichText_Select('<?php echo $this->name ?>', this.id)">
				<option value="Size">[Size]</option>
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="3">3</option>
				<option value="4">4</option>
				<option value="5">5</option>
				<option value="6">6</option>
				<option value="7">7</option>
			</select>
		</td>
		<td width="100%">
		</td>
	</tr>
</table>
<table class="btnBack" cellpadding="0" cellspacing="0" id="Buttons2_<?php echo $this->name ?>" width="' + tablewidth + '">
	<tr>
*/?>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/bold.gif") ?>"
			width="25" height="24" alt="Bold" title="Bold"
			onClick="RichText_Format('<?php echo $this->name?>', 'bold', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/italic.gif") ?>"
			width="25" height="24" alt="Italic" title="Italic"
			onClick="RichText_Format('<?php echo $this->name?>', 'italic', '')"></td>
		<?php
		/*
		 * <td><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/underline.gif") ?>" width="25" height="24" alt="Underline" title="Underline" onClick="RichText_Format('<?php echo $this->name ? >', 'underline', '')"></td>
		 */
		?>
		<td><span class="vertSep"></span></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/left_just.gif") ?>"
			width="25" height="24" alt="Align Left" title="Align Left"
			onClick="RichText_Format('<?php echo $this->name?>', 'justifyleft', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/centre.gif") ?>"
			width="25" height="24" alt="Center" title="Center"
			onClick="RichText_Format('<?php echo $this->name?>', 'justifycenter', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/right_just.gif") ?>"
			width="25" height="24" alt="Align Right" title="Align Right"
			onClick="RichText_Format('<?php echo $this->name?>', 'justifyright', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/justifyfull.gif") ?>"
			width="25" height="24" alt="Justify Full" title="Justify Full"
			onclick="RichText_Format('<?php echo $this->name?>', 'justifyfull', '')"></td>
		<td><span class="vertSep"></span></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/hr.gif") ?>"
			width="25" height="24" alt="Horizontal Rule" title="Horizontal Rule"
			onClick="RichText_Format('<?php echo $this->name?>', 'inserthorizontalrule', '')"></td>
		<td><span class="vertSep"></span></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/numbered_list.gif") ?>"
			width="25" height="24" alt="Ordered List" title="Ordered List"
			onClick="RichText_Format('<?php echo $this->name?>', 'insertorderedlist', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/list.gif") ?>"
			width="25" height="24" alt="Unordered List" title="Unordered List"
			onClick="RichText_Format('<?php echo $this->name?>', 'insertunorderedlist', '')"></td>
		<td><span class="vertSep"></span></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/outdent.gif") ?>"
			width="25" height="24" alt="Outdent" title="Outdent"
			onClick="RichText_Format('<?php echo $this->name?>', 'outdent', '')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/indent.gif") ?>"
			width="25" height="24" alt="Indent" title="Indent"
			onClick="RichText_Format('<?php echo $this->name?>', 'indent', '')"></td>
		<?php
		/*
		No funky colors, either
		<td><div id="forecolor_<?php echo $this->name ?>"><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/textcolor.gif") ?>" width="25" height="24" alt="Text Color" title="Text Color" onClick="RichText_Format('<?php echo $this->name ?>', 'forecolor', '')"></div></td>
		<td><div id="hilitecolor_<?php echo $this->name ?>"><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/bgcolor.gif") ?>" width="25" height="24" alt="Background Color" title="Background Color" onClick="RichText_Format('<?php echo $this->name ?>', 'hilitecolor', '')"></div></td>
		*/
		?>
		<td><span class="vertSep"></span></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/hyperlink.gif") ?>"
			width="25" height="24" alt="Insert Link" title="Insert Link"
			onClick="RichText_Format('<?php echo $this->name?>', 'createlink')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/image.gif") ?>"
			width="25" height="24" alt="Add Image" title="Add Image"
			onClick="RichText_AddImage('<?php echo $this->name?>')"></td>
		<?php
		/*
		if (isIE) data += '		<td><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/spellcheck.gif") ?>" width="25" height="24" alt="Spell Check" title="Spell Check" onClick="RichEdit_SpellCheck()"></td>

		//		<td><span class="vertSep"></span></td>
		//		<td><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/cut.gif") ?>" width="25" height="24" alt="Cut" title="Cut" onClick="RichText_Format('<?php echo $this->name ? >', 'cut')"></td>
		//		<td><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/copy.gif") ?>" width="25" height="24" alt="Copy" title="Copy" onClick="RichText_Format('<?php echo $this->name ? >', 'copy')"></td>
		//		<td><img class="btnImage" src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/paste.gif") ?>" width="25" height="24" alt="Paste" title="Paste" onClick="RichText_Format('<?php echo $this->name ? >', 'paste')"></td>
		//		<td><span class="vertSep"></span></td>*/
		?>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/undo.gif") ?>"
			width="25" height="24" alt="Undo" title="Undo"
			onClick="RichText_Format('<?php echo $this->name?>', 'undo')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/redo.gif") ?>"
			width="25" height="24" alt="Redo" title="Redo"
			onClick="RichText_Format('<?php echo $this->name?>', 'redo')"></td>
		<td><img class="btnImage"
			src="<?php echo cdn::url("/share/zesk/widgets/richtext/images/removeformat.gif") ?>"
			width="25" height="24" alt="Remove Formatting"
			title="Remove Formatting"
			onClick="RichText_Format('<?php echo $this->name?>', 'removeformat')"></td>
		<td width="100%"></td>
	</tr>
</table>
<iframe id="iframe_<?php echo $this->name?>" name="iframe_<?php echo $this->name?>"
	width="<?php echo $this->width?>px" height="<?php echo $this->height?>px"></iframe>
<?php
//	if (!readOnly) data += '<br /><input type="checkbox" id="chkSrc<?php echo $this->name ? >" onclick="RichText_ToggleHTMLSource('<?php echo $this->name ? >';" />&nbsp;View Source
?>

<iframe width="254" height="174" id="cp<?php echo $this->name?>"
	src="<?php echo cdn::url("/share/zesk/widgets/richtext/palette.html") ?>"
	marginwidth="0" marginheight="0" scrolling="no"
	style="visibility: hidden; display: none; position: absolute;"></iframe>

<input type="hidden" id="<?php echo $this->name?>" name="<?php echo $this->name?>"
	value="<?php echohtmlspecialchars($this->content)?>" />

<noscript><textarea name="<?php echo $this->name?>"><?php echohtmlspecialchars($this->noscript_content)?></textarea></noscript>
