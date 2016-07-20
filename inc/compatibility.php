<?php
namespace HM\Attachments;

function getAttachments( $post_id, $orderby = 'order' ) {
	if( !class_exists( '\HMAttachments' ) ) {
		return;
	}

	return \HMAttachments::getAttachments( $post_id, $orderby );
}
