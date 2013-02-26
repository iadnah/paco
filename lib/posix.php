<?php

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

?>