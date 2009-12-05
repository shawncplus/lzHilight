#!/bin/bash

echo "Installing man page..."
mv ./docs/lzhighlight.1.gz /usr/share/man/man1/
echo "Configuring highlight script..."
sed -i "s#<INSTALL_RESC_DIR>#`pwd`/#" highlight
echo "Creating symlink"
mv ./highlight /usr/bin/lzhighlight

echo "Install finished, would you like to view the man page? [Y/n]";
read answer
case "$answer" in
[nN]) exit;;
*)    man lzhighlight;;
esac
