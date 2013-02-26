<?php
function mk_cnotebook() {
	global $cmd_notebook, $tooltips;
	$hbox = new GtkHbox(false);
	$cmd_notebook = new GtkNotebook();
	$bbox = new GtkVBox(false);

	$close = new GtkButton();
	$close->set_image(GtkImage::new_from_file(DATADIR. '/components/close_tab.png'));
	$close->connect_simple('enter-notify-event', 'tool_tip', 'cnote_close');
	$close->connect_simple('leave-notify-event', 'rm_tool_tip');	
	$close->connect_simple('clicked', 'cnote_drop_page');
	$tooltips['cnote_close'] = 'Close this tab.';
	$tooltips['cnote_detach'] = 'Detach this tab and make it it\'s own window.';
	
	$detach = new GtkButton();
	$detach->set_image(GtkImage::new_from_file(DATADIR. '/components/detach2.png'));	
	$detach->connect_simple('enter-notify-event', 'tool_tip', 'cnote_detach');
	$detach->connect_simple('leave-notify-event', 'rm_tool_tip');	
	$detach->connect_simple('clicked', 'cnote_detach_page');	

	$bbox->pack_start($close); $bbox->pack_start($detach);	

	$bbox_align = new GtkAlignment(0.0, 0.0, 0.0, 0.0);	
	$bbox_align->add($bbox);
	$hbox->pack_start($cmd_notebook, true, true);
	$hbox->pack_start($bbox_align, false, false);

	return $hbox;
}

function cnote_drop_page() {
	global $cmd_notebook;
	$page = $cmd_notebook->get_current_page();
	$cmd_notebook->remove_page($page);
}

function cnote_detach_page() {
	global $cmd_notebook;
	$page = $cmd_notebook->get_current_page();
	$child = $cmd_notebook->get_nth_page($page);
	$title = $cmd_notebook->get_menu_label_text($child);	
	
	cnote_drop_page();
	$w = new GtkWindow();
	$w->set_title($title);
	$w->set_resizable(true); $w->set_default_size(200, 100);
	$w->add($child);
	$w->show_all();
	
}
function cnote_add_page($widget, $label) {
	global $cmd_notebook;
	$cmd_notebook->append_page($widget, $label);


	
	
	$cmd_notebook->show_all();
}
?>
