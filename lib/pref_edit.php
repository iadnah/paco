<?php
function pref_edit() {
	$w = new GtkWindow();
	$w->set_title('pEdit alpha');
	$w->connect_simple('destroy', array('gtk', 'main_quit'));
	$w->set_resizable(true);
	$w->set_icon_from_file(DATADIR. '/components/paco.png');
	$w->set_position(Gtk::WIN_POS_MOUSE);
	$w->set_default_size(600, 400);	
	$w->set_modal(TRUE);

	$tabs = new GtkNotebook();
	$tabs->set_tab_pos(Gtk::POS_LEFT);
	
	$tb_box = new GtkTable(4, 4);
	$tb_box->attach(new GtkLabel('Text Buffer Settings'), 0, 4, 0, 1);
	
	$fg_label = new GtkLabel('Font color:');
	
	
	$tabs->append_page($tb_box, new GtkLabel('Text Buffer Settings'));

	$w->add($tabs);
	$w->show_all();


}
?>