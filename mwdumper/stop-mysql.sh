#!/bin/bash

set -e

MYDIR=$1

if [[ -z "$MYDIR" ]]
then
	echo "usage: $0 <mysql dir>"
	echo "Stop MySQL server listening at socket <mysql dir>/mysql.sock."
	echo
	echo "  mysql dir   Must be an existing directory."
	echo
	echo "Example:"
	echo "$0 ~/data/mysql"
	exit 1
fi

mysqladmin --default-character-set=utf8 --socket=$MYDIR/mysql.sock shutdown
