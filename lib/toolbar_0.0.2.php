<?php

$toolbar = mk_toolbar();

function mk_toolbar() {
	global $tooltips;
	$handle = file(DATADIR. '/lib/toolbar.db');

	$bbox = new GtkHBox(false);

	foreach ($handle as $new_button) {
		$desc = explode(':', $new_button, 4);
		$hint = $desc[2];		
		$button = new GtkButton();
		$button->set_image(GtkImage::new_from_file(DATADIR. '/components/'. $desc[0]));
		$button->connect_simple('enter-notify-event', 'tool_tip', "$hint");
		$button->connect_simple('leave-notify-event', 'rm_tool_tip');
		$button->connect_simple('clicked', $desc[1]);
		$tooltips["$hint"] = rtrim($desc[3]);	
		$bbox->pack_start($button, false, false);
	}
		
	$bbox_align=new GtkAlignment(0.0, 0.0, 0.0, 0.0);
	$bbox_align->add($bbox);
	return $bbox_align;
}
?>