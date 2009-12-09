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

cp docs/lzhighlight.1.gz ${lmanpath}lzhighlight.1.gz

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

if [ -e $installpath ]
then
	rm $installpath
fi
ln -s `pwd`/highlight $installpath

echo "Installing and configuring highlight script..."

echo "Pick your default theme:"
for dir in themes/*
do
	echo -n "	"
	basename $dir
done
echo -n ":"
read theme

if [ ! -d themes/$theme ]
then
	echo -e "\e[31mWarning: You picked a non-existant theme as your default. You will have to manually symlink later...\e[0m"
fi

if [ -h syntax ]
then
	rm syntax
fi
ln -s themes/$theme syntax

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
