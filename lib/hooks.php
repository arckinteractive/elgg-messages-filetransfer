<?php

namespace Arck\MessagesFileTransfer;

/**
 * Note that this happens before the action, so we need to make sure the message
 * will go through first before transferring files
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 */
function file_transfer($hook, $type, $return, $params) {
	if ($type != 'messages/send') {
		return $return;
	}
	
	$subject = strip_tags(get_input('subject'));
	$body = get_input('body');
	$recipient = get_user_by_username(get_input('recipient_username'));
	$files = $_SESSION['messages_ft_files'] = get_input('filetransfer', array());

	$error = false;

	if (!$recipient) {
		$error = true;
	}

	// Make sure the message field, send to field and title are not blank
	if (!$body || !$subject) {
		$error = true;
	}

	if (!$error && is_array($files) && count($files) > 0) {

		// need to overwrite permissions temporarily to create content in another persons id
		$context = elgg_get_context();
		elgg_set_context('messages_filetransfer_permissions');
		$orig_access = elgg_set_ignore_access();

		$prefix = "file/";
		$newfiles = array();
		foreach ($files as $guid) {
			$file = get_entity($guid);
			$filestorename = elgg_strtolower(time() . $file->originalfilename);

			$newfile = new \FilePluginFile();
			$newfile->subtype = "file";
			$newfile->title = $file->title;
			$newfile->description = $file->description;
			$newfile->access_id = ACCESS_PRIVATE;
			$newfile->owner_guid = $recipient->guid;
			$newfile->container_guid = $recipient->guid;
			$newfile->setFilename($prefix . $filestorename);
			$newfile->setMimeType($file->getMimeType());
			$newfile->originalfilename = $file->originalfilename;
			$newfile->simpletype = file_get_simple_type($file->getMimeType());

			// Open the file to guarantee the directory exists
			$newfile->open("write");
			$newfile->close();

			if (!copy($file->getFilenameOnFilestore(), $newfile->getFilenameOnFilestore())) {
				$error = true;
			}

			if (!$newfile->save()) {
				$error = true;
			}

			// if image, we need to create thumbnails (this should be moved into a function)
			if ($newfile->simpletype == "image") {
				$newfile->icontime = time();

				$thumbnail = get_resized_image_from_existing_file($newfile->getFilenameOnFilestore(), 60, 60, true);
				if ($thumbnail) {
					$thumb = new \ElggFile();
					$thumb->owner_guid = $recipient->guid;
					$thumb->container_guid = $recipient->guid;
					$thumb->setMimeType($newfile->getMimeType());
					$thumb->setFilename($prefix . "thumb" . $filestorename);
					$thumb->open("write");
					$thumb->write($thumbnail);
					$thumb->close();
					$newfile->thumbnail = $prefix . "thumb" . $filestorename;
					unset($thumbnail);
				}

				$thumbsmall = get_resized_image_from_existing_file($newfile->getFilenameOnFilestore(), 153, 153, true);
				if ($thumbsmall) {
					$thumb->setFilename($prefix . "smallthumb" . $filestorename);
					$thumb->open("write");
					$thumb->write($thumbsmall);
					$thumb->close();
					$newfile->smallthumb = $prefix . "smallthumb" . $filestorename;
					unset($thumbsmall);
				}

				$thumblarge = get_resized_image_from_existing_file($newfile->getFilenameOnFilestore(), 600, 600, false);
				if ($thumblarge) {
					$thumb->setFilename($prefix . "largethumb" . $filestorename);
					$thumb->open("write");
					$thumb->write($thumblarge);
					$thumb->close();
					$newfile->largethumb = $prefix . "largethumb" . $filestorename;
					unset($thumblarge);
				}
			}

			$newfiles[] = $newfile;
		}

		if ($error) {
			foreach ($newfiles as $newfile) {
				if ($newfile) {
					$newfile->delete();
				}
			}

			register_error(elgg_echo('messages_filetransfer:error:filesave'));
			return false;
		}

		// everything has gone according to plan
		$body .= '<br><br>';
		$body .= elgg_echo('messages_filetransfer:attachments') . ':<br>';
		$body .= '<ol>';
		foreach ($newfiles as $newfile) {
			$body .= '<li><a href="' . $newfile->getURL() . '">' . $newfile->title . '</a></li>';
		}
		$body .= "</ol>";

		set_input('body', $body);

		// restore permissions
		elgg_set_context($context);
		elgg_set_ignore_access($orig_access);
	}

	unset($_SESSION['messages_ft_files']);
}

/**
 * allow context based permissions override
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return boolean
 */
function permissions_check($hook, $type, $return, $params) {
	if (elgg_get_context() == 'messages_filetransfer_permissions') {
		return true;
	}
	
	return $return;
}
