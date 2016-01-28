<?php
	
?><script type="text/x-zesk-template" id="template-confirmx">
<div class="modal fade">
	<% if (data['title']) { %>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3><%= data['title'] %></h3>
	</div>
	<% } %>
	<div class="modal-body">
		<%= data['message'] %>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn cancel"><%= data['cancelLabel'] || "No" %></a> <a href="#" class="btn ok btn-primary"><%= data['okLabel'] || "Yes" %></a>
	</div>
</div>
</script>
