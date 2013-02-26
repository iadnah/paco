<?php
$status_bars = array();
$status_bars['left'] = new GtkStatusbar;
$status_bars['middle'] = new GtkStatusBar;
$status_bars['right'] = new GtkStatusBar;
$status_bars['left']->set_has_resize_grip(0);
$status_bars['middle']->set_has_resize_grip(0);
$status_bars['right']->set_has_resize_grip(1);

$status_table = new GtkTable(1, 5);
$status_table->attach($status_bars['left'], 0, 3, 0, 1);
$status_table->attach($status_bars['middle'], 3, 4, 0, 1);
$status_table->attach($status_bars['right'], 4, 5, 0, 1);
$s_align = new GtkAlignment(0.0, 0.0, 1.0, 0.0);	$s_align->add($status_table);

?>