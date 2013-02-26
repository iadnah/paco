<?php

$menubar = new GtkMenuBar();
$file_men = new GtkMenuItem('_File');
$edit_men = new GtkMenuItem('_Edit');
$view_men = new GtkMenuItem('_View');
$tools_men = new GtkMenuItem('_Tools');
$help_men = new GtkMenuItem('_Help');

$mnuFile = __mkfmenu($accel);
$mnuView = __mkvmenu($accel);
$mnuEdit = __mkemenu($accel);
$mnuTools = __mktmenu($accel);

$menubar->append($file_men);
$menubar->append($edit_men);
$menubar->append($view_men);
$menubar->append($tools_men);
$menubar->append($help_men);

$file_men->set_submenu($mnuFile);
$view_men->set_submenu($mnuView);
$edit_men->set_submenu($mnuEdit);
$tools_men->set_submenu($mnuTools);

function __mkfmenu(&$accel) {
$mnuFile = new GtkMenu();
$mnuFile_new = new GtkImageMenuItem('New');
$mnuFile_new_image = GtkImage::new_from_file(DATADIR. '/components/fnew.png');
$mnuFile_new->set_image($mnuFile_new_image);
$mnuFile_new->add_accelerator('activate', $accel, Gdk::KEY_N, Gdk::CONTROL_MASK, 1);
$mnuFile_new->connect("activate", 'new_file');

$mnuFile_open = new GtkImageMenuItem('Open');
$mnuFile_open_image = GtkImage::new_from_file(DATADIR. '/components/fopen.png');
$mnuFile_open->set_image($mnuFile_open_image);
$mnuFile_open->add_accelerator('activate', $accel, Gdk::KEY_O, Gdk::CONTROL_MASK, 1);
$mnuFile_open->connect("activate", 'open_file');

$mnuFile_save = new GtkImageMenuItem('Save');
$mnuFile_save_image = GtkImage::new_from_file(DATADIR. '/components/save.png');
$mnuFile_save->set_image($mnuFile_save_image);
$mnuFile_save->add_accelerator('activate', $accel, Gdk::KEY_S, Gdk::CONTROL_MASK, 1);
$mnuFile_save->connect("activate", 'save_file');

$mnuFile_close = new GtkImageMenuItem('Close');
$mnuFile_close_image = GtkImage::new_from_file(DATADIR. '/components/fclose.png');
$mnuFile_close->set_image($mnuFile_close_image);
$mnuFile_close->connect("activate", 'paco_close');

$mnuFile_exit = new GtkImageMenuItem('Exit');
$mnuFile_exit_image = GtkImage::new_from_file(DATADIR. '/components/exit.png');
$mnuFile_exit->set_image($mnuFile_exit_image);
$mnuFile_exit->add_accelerator('activate', $accel, Gdk::KEY_X, Gdk::CONTROL_MASK, 1);
$mnuFile_exit->connect("activate", 'paco_exit');

$mnuFile->append($mnuFile_new);
$mnuFile->append($mnuFile_open);
$mnuFile->append($mnuFile_save);
$mnuFile->append($mnuFile_close);
$mnuFile->append($mnuFile_exit);
return ($mnuFile);
}

function __mkvmenu(&$accel) {
global $notebook, $text_views, $mnuView_lines, $mnuView_wrap;
$mnuView = new GtkMenu();
$mnuView_wrap = new GtkMenuItem('Toggle Word Wrap');
$mnuView_wrap->add_accelerator('activate', $accel, Gdk::KEY_W, Gdk::CONTROL_MASK, 1);
$mnuView_wrap->connect("activate", 'toggle_word_wrap');
$mnuView->append($mnuView_wrap);

$mnuView_lines = new GtkMenuItem('Toggle Show Line Numbers');
$mnuView_lines->connect("activate", 'toggle_line_nums');
$mnuView->append($mnuView_lines);

$mnuView_hline = new GtkImageMenuItem('Highlight Current Line');
$mnuView_hline_image = GtkImage::new_from_file(DATADIR. '/components/hline.png');
$mnuView_hline->set_image($mnuView_hline_image);
$mnuView_hline->connect("activate", 'toggle_hl');
$mnuView->append($mnuView_hline);

$mnuView_autoi = new GtkImageMenuItem('Auto Indent');
$mnuView_autoi_image = GtkImage::new_from_stock(Gtk::STOCK_INDENT, Gtk::ICON_SIZE_LARGE_TOOLBAR);
$mnuView_autoi->set_image($mnuView_autoi_image);
$mnuView_autoi->connect("activate", 'toggle_autoi');
$mnuView->append($mnuView_autoi);

return ($mnuView);
}

function __mkemenu(&$accel) {

$mnuEdit = new GtkMenu();
$mnuEdit_undo = new GtkImageMenuItem('Undo');
$mnuEdit_undo_image = GtkImage::new_from_stock(Gtk::STOCK_UNDO, Gtk::ICON_SIZE_MENU);
$mnuEdit_undo->set_image($mnuEdit_undo_image);
$mnuEdit_undo->add_accelerator('activate', $accel, Gdk::KEY_Z, Gdk::CONTROL_MASK, 1);
$mnuEdit_undo->connect('activate', 'undo_change');	

$mnuEdit_redo = new GtkImageMenuItem('Redo');
$mnuEdit_redo_image = GtkImage::new_from_stock(Gtk::STOCK_REDO, Gtk::ICON_SIZE_MENU);
$mnuEdit_redo->set_image($mnuEdit_redo_image);
$mnuEdit_redo->add_accelerator('activate', $accel, Gdk::KEY_Z, 5, 1);
$mnuEdit_redo->connect('activate', 'redo_change');	

$mnuEdit_search = new GtkImageMenuItem('Find String');
$mnuEdit_search_image = GtkImage::new_from_stock(Gtk::STOCK_FIND, Gtk::ICON_SIZE_MENU);
$mnuEdit_search->set_image($mnuEdit_search_image);
$mnuEdit_search->connect('activate', 'find_string');
$mnuEdit_search->add_accelerator('activate', $accel, Gdk::KEY_F, Gdk::CONTROL_MASK, 1);

$mnuEdit_sagain = new GtkMenuItem('Find Next');
$mnuEdit_sagain->connect('activate', 'find_next');
$mnuEdit_sagain->add_accelerator('activate', $accel, Gdk::KEY_G, Gdk::CONTROL_MASK, 1);

$mnuEdit_replace = new GtkImageMenuItem('String Replace');
$mnuEdit_replace_image = GtkImage::new_from_stock(Gtk::STOCK_FIND_AND_REPLACE, Gtk::ICON_SIZE_MENU);
$mnuEdit_replace->set_image($mnuEdit_replace_image);
$mnuEdit_replace->connect('activate', 'string_replace');
$mnuEdit_replace->add_accelerator('activate', $accel, Gdk::KEY_R, Gdk::CONTROL_MASK, 1);


$mnuEdit_jump = new GtkImageMenuItem('Jump to line');
$mnuEdit_jump_image = GtkImage::new_from_stock(Gtk::STOCK_JUMP_TO, Gtk::ICON_SIZE_MENU);
$mnuEdit_jump->set_image($mnuEdit_jump_image);
$mnuEdit_jump->add_accelerator('activate', $accel, Gdk::KEY_L, Gdk::CONTROL_MASK, 1);
$mnuEdit_jump->connect('activate', 'jump_to_line');	

$mnuEdit_prefs = new GtkMenuItem('Preferences');
$mnuEdit_prefs->connect('activate', 'pref_edit');

$mnuEdit->append($mnuEdit_undo);
$mnuEdit->append($mnuEdit_redo);
$mnuEdit->append($mnuEdit_jump);
$mnuEdit->append(new GtkSeparatorMenuItem());
$mnuEdit->append($mnuEdit_search);
$mnuEdit->append($mnuEdit_sagain);
$mnuEdit->append($mnuEdit_replace);
$mnuEdit->append(new GtkSeparatorMenuItem());
$mnuEdit->append($mnuEdit_prefs);
return $mnuEdit;
}

function __mktmenu(&$accel) {
	$mnuTools = new GtkMenu();
	$regex_tools = new GtkMenu();
	$regex_tools_replace = new GtkMenuItem('Regex Replace');
	$regex_tools_replace->connect('activate', 'regex_replace');
	$regex_tools_search = new GtkMenuItem('Regex Search');
	$regex_tools_ts = new GtkMenuItem('Convert Tabs to Spaces');
	$regex_tools_ts->connect('activate', 'tabs_to_spaces');
	$regex_tools->append($regex_tools_replace);
	$regex_tools->append($regex_tools_search);
	$regex_tools->append($regex_tools_ts);

	$mnuTools_regex = new GtkMenuItem('Regex Tools');
	$mnuTools_regex->set_submenu($regex_tools);

	$mnuTools_dview = new GtkMenuItem('Double View');
	$mnuTools_dview->connect('activate', 'toggle_double_view');

	$mnuTools_exfunction = new GtkMenuItem('Execute PHP Expression');
	$mnuTools_exfunction->connect('activate', 'execute_php_expression');
	
	$mnuTools_external = new GtkImageMenuItem('Execute External Command');
	$mnuTools_external->set_image(GtkImage::new_from_stock(Gtk::STOCK_EXECUTE, Gtk::ICON_SIZE_MENU));
	$mnuTools_external->connect('activate', 'execute_external');

	$mnuTools->append($mnuTools_regex);
	$mnuTools->append($mnuTools_dview);
	$mnuTools->append($mnuTools_exfunction);
	$mnuTools->append($mnuTools_external);
	
	return $mnuTools;
}


?>