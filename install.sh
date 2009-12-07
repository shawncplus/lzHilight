#!/bin/bash

manpathdefault="/usr/share/man/man1/"
lmanpath="$manpathdefault"
echo "Install man page for all users? [Yn]"
read -s -n 1 answer
case "$answer" in
[nN])
	echo -n "Man page path: [$manpathdefault] "
	read lmanpath
	if [ -z "$lmanpath" ]
	then
		lmanpath="$manpathdefault"
	fi
;;
esac

cp ./docs/lzhighlight.1.gz $lmanpath

default="/usr/bin/lzhighlight"
installpath="$default"
echo "Install for all users? [Yn]"
read -s -n 1 answer
case "$answer" in
[nN])
	echo -n "Install path: [$default] "
	read installpath
	if [ -z "$installpath" ]
	then
		installpath="$installpathdefault"
	fi
;;
esac

cp ./highlight $installpath
echo "Installing and configuring highlight script..."
sed -i "s#<INSTALL_RESC_DIR>#`pwd`/#" $installpath

echo "Install finished, would you like to view the man page? [Yn]";
read -s -n 1 answer
case "$answer" in
[nN]) ;;
*)    man lzhighlight;;
esac

echo "Run test? [Yn]"
read -s -n 1 answer
case "$answer" in
[nN]) exit;;
*)    $installpath tests/fulltest.php;;
esac
