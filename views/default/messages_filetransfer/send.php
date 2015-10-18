<?php

namespace Arck\MessagesFileTransfer;

?>

<div class="messages-filetransfer">
  <label><?php echo elgg_echo('messages_filetransfer:label'); ?>:</label>
  <?php
	echo elgg_view('input/tokeninput', array(
		'name' => 'filetransfer',
		'value' => $_SESSION['messages_ft_files'],
		'multiple' => true,
		'query' => array('owner' => elgg_get_page_owner_guid()),
		'callback' => __NAMESPACE__ . '\\file_search'
	));
  ?>
  
</div>

<?php

elgg_require_js('messages_filetransfer');