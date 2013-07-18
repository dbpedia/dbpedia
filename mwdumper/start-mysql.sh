#!/bin/bash

set -e

MYDIR=$1

if [[ -z "$MYDIR" ]]
then
	echo "usage: $0 <mysql dir>"
	echo "Start MySQL server using databases in <mysql dir>/data, listening at default port and socket <mysql dir>/mysql.sock, logging to <mysql dir>/mysql.log."
	echo
	echo "  mysql dir   Must be an existing directory."
	echo
	echo "Example:"
	echo "$0 ~/data/mysql"
	exit 1
fi

mysqld_safe --default-character-set=UTF8 --socket=$MYDIR/mysql.sock --datadir=$MYDIR/data --max_allowed_packet=1G --key_buffer_size=1G >>$MYDIR/mysql.log 2>&1 &

