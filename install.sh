#!/bin/sh
#Paco install script


function check_php_version() {
	found=0
	path=$(echo $PATH | sed -e "s/:/ /g");
	for each in $path; do
		if [ -x "$each/php" ]; then
			found=1
			echo -e "\tPHP found in $each"
			break;
		fi
	done
	
	if [ $found != 1 ]; then
		echo -e "\tCould not find the PHP binary on your system. Make sure it's called php and is in your PATH variable"
		exit
	fi
	
	vline=$("$each/php" -v | grep ' (built:')
	version=$(echo "$vline" | awk '{print $2}')
	major_version=$(echo "$vline" | sed -e "s/\./ /g" | awk '{print $2}')
	type=$(echo "$vline" | awk '{print $3}')
	
	if [ $major_version -ge 5 ]; then
		echo -e "\tPHP $version found!"
	else 
		echo -e "\tPaco requires PHP 5 or greater. $version was found in $each."
		exit
	fi
	
	echo -en "\tChecking if $each/php was built as php-cli:"
	if [ $type = '(cli)' ]; then
		echo -e "\tyes"
	else
		echo -e "\tno"
		echo -e "\tPaco requires that php be built with the --enable-cli option. Your installation was not built this way."
		exit
	fi
	
	PHP="$each/php"

}

function check_php_sanity() {
	touch /tmp/paco_installer_test_gtk.php

	echo "#!$PHP" > /tmp/paco_installer_test_gtk.php
	

	echo "<?php if (!class_exists('gtk')) {" >> /tmp/paco_installer_test_gtk.php
	echo "echo 'FALSE';" >> /tmp/paco_installer_test_gtk.php
	echo "}" >> /tmp/paco_installer_test_gtk.php
	echo "else { echo 'TRUE'; } ?>" >> /tmp/paco_installer_test_gtk.php

	chmod 0700 /tmp/paco_installer_test_gtk.php
	
	if [ "$(/tmp/paco_installer_test_gtk.php)" = 'TRUE' ]; then
		echo "PHP-GTK2 seems to be installed correctly."
	else 
		echo "PHP-GTK2 does not seem to be installed. Make sure that it is and is being loaded from php.ini."
		exit
	fi

	echo -en "\tChecking for GtkSourceView support:"

	echo "#!$PHP" > /tmp/paco_installer_test_gtk.php	
	echo "<?php if (!class_exists('GtkSourceView')) {" >> /tmp/paco_installer_test_gtk.php
	echo "echo 'FALSE';" >> /tmp/paco_installer_test_gtk.php
	echo "}" >> /tmp/paco_installer_test_gtk.php
	echo "else { echo 'TRUE'; } ?>" >> /tmp/paco_installer_test_gtk.php

	chmod 0700 /tmp/paco_installer_test_gtk.php
	
	if [ "$(/tmp/paco_installer_test_gtk.php)" = 'TRUE' ]; then
		echo -e "\tyes"
	else 
		echo -e "\tyes"
		echo -e "\tPaco requires that PHP-GTK be built with --enable-sourceview. Make sure you have gtksourceview 1.2.0 (available from gnome.org) or higher installed."
		exit
	fi

	rm -f /tmp/paco_installer_test_gtk.php
	
}

echo "Paco 0.0.2 installer"
echo
echo "Checking for PHP 5.0.0 or higher..."

check_php_version
check_php_sanity

if [ $UID = 0 ]; then
	read -p "Install Location: [/usr/local/paco]: " install_dir
else 
	read -p "Install Location: [$HOME/.paco]: " install_dir
fi

if [ "$install_dir" = '' ]; then
	if [ $UID = 0 ]; then
		install_dir='/usr/local/paco'
	else
		install_dir="$HOME/.paco"
	fi
fi

if [ ! $(mkdir -p $install_dir/) ]; then
	if [ ! -d "$install_dir/" ]; then
		echo "Error creating $install_dir/. Check file permissions."
		exit
	fi
fi
if [ ! $(mkdir -p $install_dir/components/) ]; then
	if [ ! -d "$install_dir/components/" ]; then
		echo "Error creating $install_dir/components/. Check file permissions."
		exit
	fi
fi
if [ ! $(mkdir -p $install_dir/lib/) ]; then
	if [ ! -d "$install_dir/lib/" ]; then
		echo "Error creating $install_dir/lib/. Check file permissions."
		exit
	fi
fi
if [ ! $(mkdir -p $install_dir/doc/) ]; then
	if [ ! -d "$install_dir/doc/" ]; then
		echo "Error creating $install_dir/doc/. Check file permissions."
		exit
	fi
fi

echo "Installing components: "

install -m 0755 components/* -t $install_dir/components/
install -m 0755 lib/* -t $install_dir/lib/
install -m 0755 doc/* -t $install_dir/doc/

echo "#!$PHP" > paco.php
echo "<?php" >> paco.php
echo "define('DATADIR', '$install_dir');" >> paco.php
cat paco_i.php >> paco.php

install -v -m 0755 paco.php -t $install_dir/

if [ $UID = 0 ]; then
	echo -en "Creating symlink /usr/local/bin/paco:"
	if [$(ln -s $install_dir/paco.php /usr/local/bin/paco) }; then
		echo -e "\tok"
	else
		echo -e "\tfailed"
	fi
else
	echo -en "Creating symlink $HOME/paco:"
	if [ $(ln -s $install_dir/paco.php $HOME/paco) ]; then
		echo -e "\tok"
	else
		echo -e "\tfailed"
	fi
fi

echo "OK, we're done."
