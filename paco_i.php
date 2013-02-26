/*
status_bar context id's:

0 = unimportant status message
1 = error or something important

*/


function posix_uinfo() {
	/*
	Returns an array containing info about the current user.
	[0] = user's login name
	[1] = x (passwd password mark)
	[2] = uid
	[3] = gid
	[4] = GECOS
	[5] = home directory
	[6] = default shell
	*/
	$uid = posix_getuid();	$passwd = file('/etc/passwd');	$user_line = preg_grep("/^[a-z]+:x:$uid:/", $passwd);
	foreach ($user_line as $line) { $x = explode(':', $line); $username = $x[0]; $homedir = $x[5]; return $x; }
}

function load_user_config() {
	$dir = posix_uinfo();
	$dir = $dir[5]. '/.paco';
	if (!is_dir($dir)) {
		echo "Creating config directory $dir\t";
		if (mkdir($dir, 0700)) {	echo "OK\n"; }
		else { echo "FAILED\n"; exit; }
	}
	if (!is_file($dir. "/pacorc")) {
		touch($dir. '/pacorc');
	}
}

function __create_buffer($contents, $title) {
	global $notebook, $text_views, $text_buffers, $status_bars, $w;
	$full_title = $title;
	if (preg_match('/\//', $title)) {
		$title = preg_replace('/^.+\//', '', $title);
	}

	$pages = $notebook->get_n_pages();
	$page = $pages++;
	$text_views[$page] = new GtkSourceView();
	$text_views[$page]->set_show_line_numbers(TRUE);
	$text_buffers[$page] = new GtkSourceBuffer();
	$text_buffers[$page]->set_text($contents);

	$text_buffers[$page]->place_cursor($text_buffers[$page]->get_start_iter());
	$lines = $text_buffers[$page]->get_line_count();
	$status_bars['left']->push(0, "Loaded $lines lines from $title successfully.");

	$text_buffers[$page]->connect('changed', 'update_line_nums');
	$text_views[$page]->set_buffer($text_buffers[$page]);
	$text_views[$page]->set_editable(true);
	$text_views[$page]->set_cursor_visible(true);
	$text_views[$page]->set_wrap_mode(Gtk::WRAP_WORD);
	$scrolled_window = new GtkScrolledWindow();
	$scrolled_window->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
	$scrolled_window->set_shadow_type(Gtk::SHADOW_IN);
	$scrolled_window->add($text_views[$page]);	
	$swindow_align = new GtkAlignment(0.0, 0.0, 1.0, 1.0);
	$swindow_align->add($scrolled_window);
	$swindow_align->set_padding(0, 0, 3, 3);
	if ($title == NULL) {
		$title = 'untitled';
		$GLOBALS['open_files'][$page]['title'] = $title;
	}
	else {
		$GLOBALS['open_files'][$page]['title'] = $title;
		$GLOBALS['open_files'][$page]['disk_location'] = $full_title;	
	}	
	$ltitle = new GtkLabel($title);
	$notebook->append_page($swindow_align, $ltitle);	
	$notebook->set_current_page($page);
		
	$text_buffers[$page]->set_highlight(true);	
	
	$w->show_all();
}

function __create_main_window() {
	global $w, $text_buffers, $text_views, $status_bars, $notebook, $cmd_notebook;
	$w = new GtkWindow();
	$w->set_title('paco 0.0.2');
	$w->connect_simple('destroy', array('gtk', 'main_quit'));
	$w->set_resizable(true);
	$w->set_icon_from_file(DATADIR. '/components/paco.png');
	$w->set_position(Gtk::WIN_POS_MOUSE);
	$w->set_default_size(400, 200);
	$w->maximize();
	$accel = new GtkAccelGroup();
	$w->add_accel_group($accel);

	require_once DATADIR. '/lib/menus_0.0.2.php';
	require_once DATADIR. '/lib/toolbar_0.0.2.php';
	require_once DATADIR. '/lib/statusbars_0.0.2.php';
	require_once DATADIR. '/lib/cmd_notebook_0.0.2.php';	

	$cn_box = mk_cnotebook();

	$notebook = new GtkNotebook();	
	$vpaned = new GtkVPaned();
	$vpaned->pack1($notebook, true, false);	
	$vpaned->pack2($cn_box, false, true);
	
	$vbox = new GtkVBox(); $vbox->set_border_width(2);
	$vbox->pack_start($menubar, false, false);
	$vbox->pack_start($toolbar, false, false);
	$vbox->pack_start($vpaned, true, true);
	$vbox->pack_start($s_align, false, false);
	$w->add($vbox);
	$w->show_all();
}

function jump_to_line() {
	global $text_views, $text_buffers, $status_bars, $notebook;
	
	$page=$notebook->get_current_page();
	
	$dialog = new GtkDialog('Jump to line');
	$dialog->add_button('Jump', Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_NO, Gtk::BUTTONS_CANCEL);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_modal(true);
	$dialog->set_default_response(Gtk::BUTTONS_OK);
//	$dialog->connect(Gdk::KEY_PRESS, 'handle_keypress');
	
	$entry = new GtkEntry();
	$entry->set_activates_default(true);
	$dialog->vbox->pack_start($entry);
	$entry->show_all();
	$res = $dialog->run();

	if ($res === 1) {
		$tline = $entry->get_text();
		if (is_numeric($tline)) {
			$lines = $text_buffers[$page]->get_line_count();
			$tline--;
			if ($tline <= $lines) {
				$text_buffers[$page]->place_cursor($text_buffers[$page]->get_iter_at_line($tline));
			}
		}
		else {
			$status_bars['left']->push(1, 'Entering an actual NUMBER might be a good idea...');
		}
	}
	
	$dialog->destroy();
	$entry->destroy();
}

function undo_change() { global $text_buffers; $text_buffers[current_page()]->undo(); }
function redo_change() { global $text_buffers; $text_buffers[current_page()]->redo(); }

function find_next() {
	global $text_buffers, $status_bars;
	$page = current_page();
	if (isset($GLOBALS['open_files'][$page]['last_search_term'])) {
		$contents = get_contents();
		$start_offset = $GLOBALS['open_files'][$page]['last_search_offset'];
		
		if ($start_offset >= strlen($contents)) { $start_offset = 0; }		
		
		if ($GLOBALS['open_files'][$page]['last_search_mode'] == 'i') {
			$offset = stripos($contents, $GLOBALS['open_files'][$page]['last_search_term'], $start_offset);
		}
		else {
			$offset = strpos($contents, $GLOBALS['open_files'][$page]['last_search_term'], $start_offset);
		}
		if ($offset === FALSE) {
			$status_bars['left']->push(0, 'String not found. Press Ctrl+G again to loop the search.');
			$GLOBALS['open_files'][$page]['last_search_offset'] = 0;
		}
		else {
			$position = $text_buffers[$page]->get_iter_at_offset($offset);
			$text_buffers[$page]->place_cursor($position);
			$GLOBALS['open_files'][$page]['last_search_offset'] = $offset + strlen($GLOBALS['open_files'][$page]['last_search_term']);			
		}
	}
}

function find_string() {
	global $text_buffers;
	$dialog = new GtkDialog('Find string in text buffer');
	$dialog->add_button(Gtk::STOCK_FIND, Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::BUTTONS_CANCEL);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_default_response(Gtk::BUTTONS_OK);
	
	$label = new GtkLabel('String:');
	$search_term = new GtkEntry();
	$in_check = new GtkCheckButton('Case insensitive match'); $in_check->set_active(TRUE);
	$hbox = new GtkHBox(false);
	$hbox->pack_start($label, false, false);
	$hbox->pack_start($search_term);
	$dialog->vbox->pack_start($hbox);
	$dialog->vbox->pack_start($in_check);
	$hbox->show_all(); $in_check->show_all();
	$ret = $dialog->run();
	$dialog->destroy();
	
	if ($ret == 1) {
		$in = $in_check->get_active();
		$page = current_page();
		$contents = get_contents();
		$search = $search_term->get_text();
		if ($in) {
			$offset = stripos($contents, $search);
		}
		else {
			$offset = strpos($contents, $search);
		}
		if ($offset === FALSE) { error_box('Not found', 'The string '. $search. ' was not found.'); }
		else {
			$position = $text_buffers[$page]->get_iter_at_offset($offset);
			$text_buffers[$page]->place_cursor($position);

			if ($in) {
				$GLOBALS['open_files'][$page]['last_search_mode'] = 'i';
			}
			else {
				$GLOBALS['open_files'][$page]['last_search_mode'] = '';
			}

			$GLOBALS['open_files'][$page]['last_search_term'] = $search;
			$GLOBALS['open_files'][$page]['last_search_offset'] = $offset + strlen($search);
		}		
	}
}

function string_replace() {
	global $text_buffers, $notebook, $status_bars;
	$dialog = new GtkDialog('String search and replace');
	$dialog->add_button(Gtk::STOCK_FIND_AND_REPLACE, Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::BUTTONS_CANCEL);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_default_response(Gtk::BUTTONS_OK);	
	
	$ls = new GtkLabel('Search term:'); $lr = new GtkLabel('Replacement:');	
	$search_term = new GtkEntry();
	$replace_term = new GtkEntry();	
	
	//case insensitive search
	$in_check = new GtkCheckButton('Case insensitive match'); $in_check->set_active(TRUE);
	
	$table = new GtkTable(2, 3);
	$table->attach($ls, 0, 1, 0, 1);
	$table->attach($lr, 0, 1, 1, 2);
	$table->attach($search_term, 1, 2, 0, 1);
	$table->attach($replace_term, 1, 2, 1, 2);
	$table->attach($in_check, 0, 2, 2, 3);
	
	$dialog->vbox->pack_start($table);
	$table->show_all();
	$ret = $dialog->run();
	$dialog->destroy();
	
	if ($ret === 1) {
		$target = $search_term->get_text();
		$replace = $replace_term->get_text();
		$in = $in_check->get_active();

		$page = current_page();
		$contents = get_contents();
		if ($in) {
			$contents = str_ireplace("$target", "$replace", $contents, $count);
		}
		else {
			$contents = str_replace("$target", "$replace", $contents, $count);
		}
		$text_buffers[$page]->set_text($contents);
		$status_bars['left']->push(0, "Replaced $count occurances of $target");
	}
}

function tabs_to_spaces() {
	global $text_buffers, $notebook;
	$dialog = new GtkDialog('Convert Tabs to Spaces');
	$dialog->add_button('Convert', Gtk::BUTTONS_OK);
	$dialog->add_button('Cancel', Gtk::BUTTONS_CANCEL);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_default_response(Gtk::BUTTONS_OK);
	$label = new GtkLabel('Number of spaces:');
	$entry = new GtkEntry('4', 2);
	$entry->set_width_chars(2);
	$entry->set_activates_default(true);
	
	$hbox = new GtkHBox(false, 2);
	$hbox->pack_start($label, false, false);
	$hbox->pack_start($entry);	
	
	$dialog->vbox->pack_start($hbox);
	$hbox->show_all();
	$ret = $dialog->run();
	$dialog->destroy();
	
	if ($ret === 1) {		
		$spaces = $entry->get_text();
		if (is_numeric($spaces)) {
			$page = current_page();
			$contents = get_contents();
			$spaces = str_repeat(' ', $spaces);
			$contents = preg_replace("/\t/", "$spaces", $contents);
			$text_buffers[$page]->set_text($contents);
		}
		else {
			error_box('You must enter a number', 'You must specify how many spaces each tab should be converted to.');
		}
	}
}

function execute_php_expression() {
	global $text_buffers;
	$dialog = new GtkDialog('Execute PHP expression on buffer');
	$dialog->add_button(Gtk::STOCK_EXECUTE, Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::BUTTONS_CANCEL);
	$dialog->add_button(Gtk::STOCK_HELP, 8);
	$dialog->set_position(Gtk::WIN_POS_CENTER);
	$dialog->set_modal(true);
	$dialog->set_default_response(Gtk::BUTTONS_OK);
	$dialog->set_default_size(640, 480);

	$sw = new GtkScrolledWindow();
	$tw = new GtkSourceView();
	$tb = new GtkSourceBuffer();
	$tw->set_buffer($tb);
	$sw->add($tw);

	$hw = new GtkScrolledWindow(); $hv = new GtkTextView(); $hb = new GtkTextBuffer(); $hv->set_buffer($hb); $hw->add($hv);
	$hv->set_wrap_mode(Gtk::WRAP_WORD);

$help = 'You can put just about any PHP code you like in the buffer above. The contents of the buffer are stored as a string in $contents. If you want to have changes you make to $contents actually appear in the editor window set the variable $ALTER to true.
Store any other output of your expression in the variable $OUTPUT.';


	$hw->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
	$hw->set_shadow_type(Gtk::SHADOW_IN);
	$sw->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
	$sw->set_shadow_type(Gtk::SHADOW_IN);
	$hb->set_text($help);

	$vpaned = new GtkVPaned();
	$vpaned->pack1($sw, true, false);	
	$vpaned->pack2($hw, false, true);

	
	$dialog->vbox->pack_start($vpaned);
	$vpaned->show_all();
	$ret = $dialog->run();
	$dialog->destroy();
	
	if ($ret === 1) {
		$expression = $tb->get_text($tb->get_start_iter(), $tb->get_end_iter(), TRUE);
		$contents = get_contents();
		$ALTER=FALSE; $OUTPUT = '';
		eval($expression);		
		if ($ALTER === TRUE) {
			$text_buffers[current_page()]->set_text($contents);
		}
		
		if (strlen($OUTPUT) > 0) {
			$scrolled = new GtkScrolledWindow();
			$scrolled->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
			$scrolled->set_shadow_type(Gtk::SHADOW_IN);
			
			$tview = new GtkTextView(); $tview->set_editable(false);
			$tview->ensure_style();
	
			$tview->modify_bg(Gtk::STATE_NORMAL, new GdkColor(255, 0, 0));	
			
			$tbuff = new GtkTextBuffer(); $tbuff->set_text($OUTPUT); $tview->set_buffer($tbuff);
			$scrolled->add($tview);
			cnote_add_page($scrolled, new GtkLabel('Output from PHP Expression'));
		
		}
	}
	
}

function execute_external() {
	$dialog = new GtkDialog('Execute external command on buffer');
	$dialog->add_button(Gtk::STOCK_EXECUTE, Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::BUTTONS_CANCEL);
	$dialog->add_button(Gtk::STOCK_HELP, 8);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_modal(true);
	$dialog->set_default_response(Gtk::BUTTONS_OK);

	$label = new GtkLabel('External Command:');
	$entry = new GtkEntry();
	$entry->set_activates_default(true);
	$hbox = new GtkHBox(false);
	$hbox->pack_start($label, false, false);
	$hbox->pack_start($entry);
	$dialog->vbox->pack_start($hbox);
	
	$hbox->show_all(); //$help->show_all();
	$ret = $dialog->run();
	$dialog->destroy();
	if ($ret == 1) {
		$command_chain = $entry->get_text();
		$temp = tempnam('/tmp', $GLOBALS['open_files'][current_page()]['title']);
		$handle = fopen($temp, 'w');
		fwrite($handle, get_contents());
		fclose($handle);
		exec("cat $temp | $command_chain", $output, $return_value);
		$buffer = '';
		$y = count($output) * 12; if ($y > 480) { $y = 480; }
		
		foreach ($output as $line) { $buffer .= "$line\n"; }
		unset($output); unlink($temp); unset($temp);

		$scrolled = new GtkScrolledWindow();
		$scrolled->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);
		$scrolled->set_shadow_type(Gtk::SHADOW_IN);
		
		$tview = new GtkTextView(); $tview->set_editable(false);
		$tview->ensure_style();

		$tview->modify_bg(Gtk::STATE_NORMAL, new GdkColor(255, 0, 0));	
		
		$tbuff = new GtkTextBuffer(); $tbuff->set_text($buffer); $tview->set_buffer($tbuff);
		$scrolled->add($tview);
		cnote_add_page($scrolled, new GtkLabel('Results of '. $command_chain));

	}
	elseif ($ret = 8) {
		show_help('execute_external');
	}
}

function regex_replace() {
	global $text_buffers, $notebook;
	$page = current_page();

	$dialog = new GtkDialog('Regex replace');
	$dialog->add_button('Replace', Gtk::BUTTONS_OK);
	$dialog->add_button(Gtk::STOCK_NO, Gtk::BUTTONS_CANCEL);
	$dialog->set_position(Gtk::WIN_POS_MOUSE);
	$dialog->set_modal(true);
	$dialog->set_default_response(Gtk::BUTTONS_OK);

	$help = new GtkLabel('Enter a perl regular expression. The search term will be replaced with the replacement.');
	$table = new GtkTable(2, 2);
	$search = new GtkEntry();
	$replace = new GtkEntry();
	$replace->set_activates_default(true);	
	$search->set_activates_default(true);
	$searchl = new GtkLabel('Search Term: ');
	$replacel = new GtkLabel('Replacement: ');
	
	$table->attach($searchl, 0, 1, 0, 1);
	$table->attach($search, 1, 2, 0, 1);
	$table->attach($replacel, 0, 1, 1, 2);
	$table->attach($replace, 1, 2, 1, 2);

	$dialog->vbox->pack_start($help);
	$dialog->vbox->pack_start($table);
	$help->show_all();
	$table->show_all();
	$ret = $dialog->run();	
	$dialog->destroy();
	if ($ret === 1) {
		$sterm = $search->get_text();
		$sterm = preg_replace('/\//', '\/', $sterm);
		$replacement = $replace->get_text();	
		
		$contents = get_contents();
		$contents = preg_replace("/$sterm/", "$replacement", $contents);
		if ($contents === FALSE) {
			$error = error_get_last();
			error_box('Invalid expression', "The expression you entered caused the following error:\n". $error['message']);
		}
		else {
			$text_buffers[$page]->set_text($contents);
		}
	}
}

function toggle_double_view() {
	global $text_views, $text_buffers, $notebook, $w;
	$page = current_page();
	
	if ($GLOBALS['open_files'][$page]['dview'] === TRUE) {
		$notebook->remove_page($page);
		$edit_window = new GtkScrolledWindow();
		$edit_text_window = new GtkSourceView();
		$edit_text_window->set_buffer($text_buffers[$page]);
		$edit_text_window->set_editable(TRUE);
		$edit_text_window->set_show_line_numbers(TRUE);
		$edit_window->add($edit_text_window);
		$label = new GtkLabel($GLOBALS['open_files'][$page]['title']);		
		$notebook->insert_page($edit_window, $label, $page);
		$GLOBALS['open_files'][$page]['dview'] = FALSE;
		$w->show_all();
	
	}
	else {	
	$hbox = new GtkHBox();
	$view_window = new GtkScrolledWindow();
	$edit_window = new GtkScrolledWindow();
	$hbox->pack_start($view_window);
	$hbox->pack_start($edit_window);
	
	$view_text_window = new GtkSourceView();
	$view_text_buffer = $text_buffers[$page];
	$view_text_window->set_buffer($view_text_buffer);
	$view_text_window->set_editable(FALSE);
	$view_text_window->set_show_line_numbers(TRUE);	
	
	$edit_text_window = new GtkSourceView();
	$edit_text_window->set_buffer($text_buffers[$page]);
	$edit_text_window->set_editable(TRUE);
	$edit_text_window->set_show_line_numbers(TRUE);	
	
	$view_window->add($view_text_window);
	$edit_window->add($edit_text_window);
	
	$label = new GtkLabel($GLOBALS['open_files'][$page]['title']);
	$GLOBALS['open_files'][$page]['dview'] = TRUE;
	$notebook->remove_page($page);
	$notebook->insert_page($hbox, $label, $page);
	$w->show_all();
	}
	
}

function toggle_word_wrap() {
	global $text_views, $status_bars, $notebook;
	$page=$notebook->get_current_page();
	$title = $GLOBALS['open_files'][$page]['title'];
	
	if ($text_views[$page]->get_wrap_mode() == Gtk::WRAP_WORD) {
		$text_views[$page]->set_wrap_mode(Gtk::WRAP_NONE);
		$status_bars['left']->push(0, 'Word wrap disabled for '. $title);
	}
	else {
		$text_views[$page]->set_wrap_mode(Gtk::WRAP_WORD);
		$status_bars['left']->push(0, 'Word wrap enabled for '. $title);
	}	
}

function toggle_autoi() {
	global $text_views, $status_bars, $notebook;
	$page=$notebook->get_current_page();
	$title = $GLOBALS['open_files'][$page]['title'];
	
	if ($text_views[$page]->get_auto_indent() == TRUE) {
		$text_views[$page]->set_auto_indent(FALSE);
		$status_bars['left']->push(0, 'Auto-indent disabled for '. $title);
	}
	else {
		$text_views[$page]->set_auto_indent(TRUE);
		$status_bars['left']->push(0, 'Auto-indent enabled for '. $title);
	}	
}

function toggle_line_nums() {
	global $notebook, $text_views;
	$page = current_page();

	if ( ($text_views[$page]->get_show_line_numbers()) == FALSE) {
		$text_views[$page]->set_show_line_numbers(TRUE);
	}
	else {
			$text_views[$page]->set_show_line_numbers(FALSE);
	}
}

function toggle_hl() {
	global $text_views, $notebook;
	$pages = $notebook->get_n_pages();	
	if ($GLOBALS['line_highlighting'] == TRUE) {
		$toggle = FALSE; $GLOBALS['line_highlighting'] = FALSE;
	}
	else {
		$toggle = TRUE; $GLOBALS['line_highlighting'] = TRUE;		
	}
	for($page = 0; $page < $pages; $page++) {
		$text_views[$page]->set_highlight_current_line($toggle);
	}
}

function ask_file_location($type = 'save') {
	$dialog = new GtkDialog();
	$dialog->set_resizable(TRUE); $dialog->set_position(Gtk::WIN_POS_MOUSE);
	if ($type == 'save') { 
		$action = Gtk::FILE_CHOOSER_ACTION_SAVE; 
		$dialog->add_button(Gtk::STOCK_SAVE, Gtk::BUTTONS_OK);
		$dialog->set_title('Save File');
		$dialog->set_default_size(400, 100); 
	}
	else { 
		$action = Gtk::FILE_CHOOSER_ACTION_OPEN;
		$dialog->add_button(Gtk::STOCK_OPEN, Gtk::BUTTONS_OK); 
		$dialog->set_title('Open File');
		$dialog->set_default_size(600, 400); 
	}
	$dialog->add_button(Gtk::STOCK_CANCEL, Gtk::BUTTONS_CANCEL);
	$dialog->set_default_response(Gtk::BUTTONS_OK);
	$chooser = new GtkFileChooserWidget($action);
	$chooser->set_select_multiple(false);
	$dialog->vbox->pack_start($chooser);
	$chooser->show_all(); 
	$dialog->run();
	$file = $chooser->get_filename();
	while (is_dir($file)) {
		$chooser->set_current_folder($file);
		$chooser->show_all(); 
		$dialog->run();
		$file = $chooser->get_filename();
		$dialog->destroy();
	}
	$dialog->destroy();	
	return $file;
}

function error_box($title, $message) {
		$dialog = new GtkMessageDialog(null, 0, Gtk::MESSAGE_WARNING, Gtk::BUTTONS_OK, "$message");
		$dialog->image->set_from_file(DATADIR. '/components/idiot.png');
		$dialog->set_title("$title");
		$dialog ->run();
		$dialog->destroy();
}

function new_file() {
	global $text_buffers, $text_view, $status_bars, $w, $notebook;
	__create_buffer(NULL, NULL);
}

function open_file() {
	global $text_buffers, $text_views, $status_bars, $w, $notebook, $argv, $argc;
	
	if (isset($argv[1])) {	
		if (is_file($argv[1])) {
			$file = $argv[1];	
		}
		else {
			$file = ask_file_location('open');
		}			
	}
	else {
		$file = ask_file_location('open');
	}	

	if ($file === FALSE) {
		return FALSE;
	}
	$status_bars['left']->push(0, "Opening $file...");
	if (is_file($file)) {
		if ($contents = file_get_contents($file)) {
			__create_buffer($contents, $file);
			return TRUE;
		}
		else {
			if (filesize($file) === 0 ) {
				__create_buffer($contents, $file);
				$status_bars['left']->push(0, "$file opened successfully.");
				return TRUE;
			}
			else {
				error_box('Error', "Cannot open $file. Make sure permissions are correct.");
				$status_bars['left']->push(1, "Error opening $file. Check file permissions.");
				return FALSE;
			}
		}
	}
	else {
		error_box('Error', "$file doesn't seem to actually be a file...");
		$status_bars['left']->push(1, "Error opening $file. It doesn't seem to actually be a file.");
		return FALSE;
	}
}

function update_line_nums() {
	global $text_buffers, $status_bars;
	$x = $text_buffers[current_page()]->get_line_count();
	$status_bars['right']->push(0, $x. ' lines');
	$GLOBALS['open_files'][current_page()]['saved'] = FALSE;
}


function current_page() {
	global $notebook;
	$current_page=$notebook->get_current_page();
	return $current_page;
}

function get_contents() {
	global $text_buffers;
	$page = current_page();
	$contents = $text_buffers[$page]->get_text($text_buffers[$page]->get_start_iter(), $text_buffers[$page]->get_end_iter(), TRUE);
	return $contents;
}


function paco_close() {
	if ($GLOBALS['open_files'][$page]['saved'] != TRUE) {
		$dialog = new GtkMessageDialog(null, 0, Gtk::MESSAGE_QUESTION, Gtk::BUTTONS_YES_NO, "You haven't saved your changes yet. Do you wany to save before closing?");
		$dialog->set_modal(TRUE);
		$dialog->set_position(Gtk::WIN_POS_MOUSE);
		$answer = $dialog->run();
		$dialog->destroy();
		if ($answer == Gtk::RESPONSE_YES) { 
			save_file();
		} 

		global $notebook;
		$notebook->remove_page(current_page());

	}
}

function save_file() {
	global $text_buffers, $text_views, $status_bars, $notebook;
	$page = current_page();

	//if this buffer is not already associated with a file, ask where to save it.
	if (!isset($GLOBALS['open_files'][$page]['disk_location'])) { $file = ask_file_location(); }
	else { $file = $GLOBALS['open_files'][$page]['disk_location']; }

	//get the contents of the buffer
	$contents = get_contents();
	
	if ( (is_file($file)) && ($file !=  $GLOBALS['open_file'][$page]['disk_location']) ) {
		if (confirm_overwrite($file)) {
			if (!$handle = fopen($file, 'w')) {
				error_box('Error', "Cannot open $file for writing.");
				return FALSE;
			}
			if (fwrite($handle, $contents)) {
				$status_bars['left']->push(0, "$file saved successfully.");
				$GLOBALS['open_files'][$page]['disk_location'] = $file;
				$GLOBALS['open_files'][$page]['saved'] = TRUE;

				if (preg_match('/\//', $file)) {
					$title = preg_replace('/^.+\//', '', $file);
				}
				else { $title = $file; }
				$GLOBALS['open_files'][$page]['title'] = $title;
				$pw = $notebook->get_nth_page($page);
				$notebook->set_tab_label_text($pw, "$title");
			}
			fclose($handle);	
		}
	}
	elseif (is_dir($file)) {
		$dialog = new GtkMessageDialog(null, 0, Gtk::MESSAGE_WARNING, Gtk::BUTTONS_OK, "$file is a directory.... ");
		$dialog->image->set_from_file(DATADIR. '/components/idiot.png');
		$dialog->set_title("$file is a directory....");
		$dialog ->run();
		$dialog->destroy();
	}
	else {
		if (touch($file)) {
			$handle = fopen($file, 'w');
			if (fwrite($handle, $contents)) {
				$status_bars['left']->push(0, "$file saved successfully.");
				$GLOBALS['open_files'][$page]['disk_location'] = $file;
				if (preg_match('/\//', $file)) {
					$title = preg_replace('/^.+\//', '', $file);
				}
				else { $title = $file; }
				$GLOBALS['open_files'][$page]['title'] = $title;
				$pw = $notebook->get_nth_page($page);
				$notebook->set_tab_label_text($pw, "$title");		
				$GLOBALS['open_files'][$page]['saved'] = TRUE;		
			}
			fclose($handle);

		}
		else {
			error_box('Cannot create file', "Unable to create file $file. Check permissions.");
			return FALSE;
		}
	}
}

function confirm_overwrite($file) {		
		$dialog = new GtkMessageDialog(null, 0, Gtk::MESSAGE_QUESTION, Gtk::BUTTONS_YES_NO, "$file. Overwrite?");
		$dialog->set_modal(TRUE);
		$dialog->set_position(Gtk::WIN_POS_MOUSE);
		$answer = $dialog->run();
		$dialog->destroy();
		if ($answer == Gtk::RESPONSE_YES) { return TRUE; } 
		else if ($answer == Gtk::RESPONSE_NO) { return FALSE; } else { return FALSE; }
}

load_user_config();
__create_main_window();
if ($argc == 2) {
	if (is_file($argv[1])) {
		open_file($argv[1]);
	}
}
else {
	new_file();
}

require_once DATADIR. '/lib/pref_edit.php';
require_once DATADIR. '/lib/tooltips.php';
Gtk::main();

?>
