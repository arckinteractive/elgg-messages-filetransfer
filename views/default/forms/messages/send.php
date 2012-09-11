<?php
/**
 * Compose message form
 *
 * @package ElggMessages
 * @uses $vars['friends']
 */

$recipient_guid = elgg_extract('recipient_guid', $vars, 0);
$subject = elgg_extract('subject', $vars, '');
$body = elgg_extract('body', $vars, '');

$recipients_options = array();
foreach ($vars['friends'] as $friend) {
	$recipients_options[$friend->guid] = $friend->name;
}

if (!array_key_exists($recipient_guid, $recipients_options)) {
	$recipient = get_entity($recipient_guid);
	if (elgg_instanceof($recipient, 'user')) {
		$recipients_options[$recipient_guid] = $recipient->name;
	}
}

$recipient_drop_down = elgg_view('input/dropdown', array(
	'name' => 'recipient_guid',
	'value' => $recipient_guid,
	'options_values' => $recipients_options,
));

?>
<div>
	<label><?php echo elgg_echo("messages:to"); ?>: </label>
	<?php echo $recipient_drop_down; ?>
</div>
<div>
	<label><?php echo elgg_echo("messages:title"); ?>: <br /></label>
	<?php echo elgg_view('input/text', array(
		'name' => 'subject',
		'value' => $subject,
	));
	?>
</div>
<div>
	<label><?php echo elgg_echo("messages:message"); ?>:</label>
	<?php echo elgg_view("input/longtext", array(
		'name' => 'body',
		'value' => $body,
	));
	?>
</div>

<?php
  // begin files dropdown
  $dbprefix = elgg_get_config('dbprefix');

  $files = elgg_get_entities(array(
      'type_subtype_pairs' => array('object' => 'file'),
      'owner_guids' => array(elgg_get_page_owner_guid()),
      'container_guids' => array(elgg_get_page_owner_guid()),
      'limit' => 0,
      'joins' => array("JOIN {$dbprefix}objects_entity o ON e.guid = o.guid"),
      'order_by' => 'o.title asc'
  ));
 
  if (!$files) {
    $files = array();
  }
  
  $options_values = array();
  foreach ($files as $file) {
    $text = $file->title . ' (' . date('Y/m/d', $file->time_created) . ')';
    $label = elgg_view('output/url', array('href' => $file->getURL(), 'text' => $text, 'target' => '_blank'));
    $options_values[$label] = $file->guid;
  }
  
  if (count($options_values)):
?>
<div>
  <label><?php echo elgg_echo('messages_filetransfer:label'); ?>:</label>
  <div class="messages-filetransfer-selectwrapper">
    <?php echo elgg_view('input/checkboxes', array(
        'name' => 'filetransfer',
        'options' => $options_values,
        'default' => false,
        'value' => $_SESSION['messages_ft_files']
    ));
    unset($_SESSION['messages_ft_files']);
    ?>
  </div>
</div>

<?php
  endif;
  // end files dropdown
?>
<div class="elgg-foot">
	<?php echo elgg_view('input/submit', array('value' => elgg_echo('messages:send'))); ?>
</div>
