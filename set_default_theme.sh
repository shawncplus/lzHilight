#!/bin/bash
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
