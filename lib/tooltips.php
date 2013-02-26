<?php

function rm_tool_tip() {
	global $status_bars;
	$status_bars['left']->pop(0);
}

function show_tip($message) {
	global $status_bars;
	$status_bars['left']->pop(0);
	$status_bars['left']->push(0, $message);
}

function tool_tip($message) {
	global $tooltips;
	show_tip($tooltips["$message"]);
}
?>