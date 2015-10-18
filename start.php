<?php

namespace Arck\MessagesFileTransfer;

const PLUGIN_ID = 'messages_filetransfer';

require_once __DIR__ . '/lib/hooks.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

function init() {

	// Extend system CSS with our own styles
	elgg_extend_view('css/elgg', 'css/messages_filetransfer');
	
	elgg_extend_view('forms/messages/send', 'messages_filetransfer/send');

	// note - need to register for 'action', 'all' so it runs *after* the tokeninput callback
	elgg_register_plugin_hook_handler('action', 'all', __NAMESPACE__ . '\\file_transfer', 1000);
	elgg_register_plugin_hook_handler('permissions_check', 'all', __NAMESPACE__ . '\\permissions_check');
}

/**
 * token input callback
 * 
 * @param type $query
 * @param type $option
 */
function file_search($query, $option = array()) {
	$owner_guid = get_input('owner', elgg_get_logged_in_user_guid());
	
	$query = sanitize_string($query);
	
	$dbprefix = elgg_get_config('dbprefix');
	$files = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'file',
		'owner_guid' => $owner_guid,
		'joins' => array(
			"JOIN {$dbprefix}objects_entity oe ON oe.guid = e.guid"
		),
		'wheres' => array(
			"oe.title LIKE '$query%'"
		),
		'limit' => 10
	));
			
	return $files;
}