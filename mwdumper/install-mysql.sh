#!/bin/bash

set -e

MYDIR=$1

if [[ -z "$MYDIR" ]]
then
	echo "usage: $0 <mysql dir>"
	echo "Install MySQL databases in <mysql dir>/data.
	echo "Start server listening at default port and socket <mysql dir>/mysql.sock, logging to <mysql dir>/mysql.log."
	echo "Grant all privileges to anonymous user."
	echo
	echo "  mysql dir   Will be created if it doesn't exist."
	echo
	echo "Example:"
	echo "$0 ~/data/mysql"
	exit 1
fi

mysql_install_db --default-character-set=utf8 --datadir=$MYDIR/data

mysqld_safe --default-character-set=utf8 --socket=$MYDIR/mysql.sock --datadir=$MYDIR/data --max_allowed_packet=1G --key_buffer_size=1G >>$MYDIR/mysql.log 2>&1 &

# wait for server to start
sleep 5

mysql --default-character-set=utf8 --socket=$MYDIR/mysql.sock -u root -e "GRANT ALL ON *.* TO ''@'localhost'" mysql
