#!/bin/bash

set -e

# create database and tables
# uses global variables $DATADIR, $MYCMDARGS, $MYDB
function create-database
{
	mysql $MYCMDARGS -e "DROP DATABASE IF EXISTS $MYDB; CREATE DATABASE $MYDB CHARACTER SET utf8 COLLATE utf8_bin;"
	mysql $MYCMDARGS -e "CREATE TABLE table_info (table_name VARCHAR(255) BINARY NOT NULL PRIMARY KEY, table_date DATETIME);" $MYDB
	mysql $MYCMDARGS $MYDB <$DATADIR/tables-no-indexes.sql
}

# check that file $1 exists
# remember file date for tables $2, $3, ...
# uses global variables $WPLANGDIR, $MYCMDARGS, $MYDB
function check-data
{
	local DATAFILE="$1"
	local DATAPATH="$WPLANGDIR/$DATAFILE"
	
	shift 1
	
	if [[ ! -f "$DATAPATH" ]]
	then
		echo "missing $DATAPATH"
		exit 1
	fi
	
	local MYSQLDATE=$(date -r $DATAPATH +%Y%m%d%H%M%S)
	
	local SQL="INSERT INTO table_info (table_name, table_date) VALUES"
	local TABLE
	for TABLE in "$@"
	do
		SQL="$SQL ('$TABLE', $MYSQLDATE),"
	done
	# replace last comma by semicolon
	SQL=${SQL/%,/;}
	
	echo "remember date $MYSQLDATE of file $DATAFILE for tables: $@"
	mysql $MYCMDARGS -e "$SQL" $MYDB
}

TIMEFORMAT=%lR

DATADIR=$1
MYURL=$2
MYURLARGS=$3
MYCMDARGS=$4
MYDBPREFIX=$5
WPLANGS=$6

if [[ $# -lt 5 || $# -gt 6 || ! -d "$DATADIR" ]]
then
	echo "Usage: $0 <data dir> <mysql url> <mysql url args> <mysql cmd args> <database name prefix> <languages>"
	echo "Create a MySQL database for each language, download and insert wikipedia data."
	echo
	echo "  data dir          Must be an existing directory. A sub-directory will be created for each language."
	echo "  mysql url         The JDBC base URL of the MySQL server, e.g. 'example.com:3306'. If empty, localhost will be used."
	echo "  mysql url args    Arguments for the JDBC URL of the MySQL server, without the preceding  '?'. '&characterEncoding=utf8' will be appended."
	echo "  mysql cmd args    Arguments for the MySQL client, e.g. '--host=example.com --port=3306'. ' --default-character-set=utf8' will be appended."
	echo "  db name prefix    The name of each MySQL database has this prefix and the language name as suffix."
	echo "  languages         Names of wikipedia languages, e.g. 'en de fr'. If no languages are given, all directories in data dir are used."
	echo
	echo "Example for local MySQL server:"
	echo "$0 'data/wikipedia' '' 'user=...&password=...' '--socket=data/mysql/mysql.sock --user=... --password=...' 'wikipedia_' 'en de fr'"
	echo
	echo "Example for remote MySQL server:"
	echo "$0 'data/wikipedia' 'example.com:3306' 'user=...&password=...' '--host=example.com --port=3306 --user=... --password=...' 'wikipedia_' 'en de fr'"
	exit 1
fi

MYURLARGS="$MYURLARGS&characterEncoding=utf8"
MYCMDARGS="$MYCMDARGS --default-character-set=utf8"

if [[ -z "$WPLANGS" ]]
then
	WPLANGS=$(find "$DATADIR" -maxdepth 1 -mindepth 1 -type d -printf '%f ')
fi

echo -------------------------------------------------------------------------------
echo "importing wikipedia data into $MYURL/$MYDBPREFIX* for languages: $WPLANGS"
echo -------------------------------------------------------------------------------

echo "compile mwdumper..."
time mvn --quiet clean compile
echo

if [[ ! -f "$DATADIR/tables-no-indexes.sql" ]]
then
	echo "missing $DATADIR/tables-no-indexes.sql"
	exit 1
fi

if [[ ! -f "$DATADIR/tables-only-indexes.sql" ]]
then
	echo "missing $DATADIR/tables-only-indexes.sql"
	exit 1
fi

for WPLANG in $WPLANGS
do
	WPLANGDIR=$DATADIR/$WPLANG
	MYDB=$MYDBPREFIX$WPLANG

	echo -------------------------------------------------------------------------------
	echo "importing $MYDB"
	echo -------------------------------------------------------------------------------
	
	mkdir -p "$WPLANGDIR"
	
	echo "create database $MYDB and tables..."
	time create-database
	echo
	
	XMLFILE=${WPLANG}wiki-latest-pages-articles.xml
	
	check-data $XMLFILE page revision text
	
	# Note: Instead of using JDBC, mwdumper could generate SQL and pipe it to a mysql client, but then 
	# errors would be ignored. JDBC is also a bit faster (unless the mysql client uses a UNIX socket).
	echo "import page data into database $MYDB..."
	DUMPARGS="--progress=100000 --output=mysql://$MYURL/$MYDB?$MYURLARGS --format=mysql:1.5 --filter=truncate-comment $WPLANGDIR/$XMLFILE"
	time mvn -e exec:java -Dexec.mainClass=org.mediawiki.dumper.Dumper -Dexec.args="$DUMPARGS"
	echo
	
	for TABLE in categorylinks image imagelinks langlinks templatelinks
	do
		SQLFILE=${WPLANG}wiki-latest-$TABLE.sql

		check-data clean.$SQLFILE $TABLE
		
		echo "import clean.$SQLFILE into database $MYDB..."
		time mysql $MYCMDARGS $MYDB <$WPLANGDIR/clean.$SQLFILE
		echo
	done
	
	echo "create indexes in database $MYDB..."
	# use --force to keep going if an index can't be created - some languages contain duplicate titles
	time mysql --force $MYCMDARGS $MYDB <$DATADIR/tables-only-indexes.sql

	echo -------------------------------------------------------------------------------
	echo "imported $MYDB"
	echo -------------------------------------------------------------------------------
	
done

echo -------------------------------------------------------------------------------
echo "imported wikipedia data into $MYURL/$MYDBPREFIX* for languages: $WPLANGS"
echo -------------------------------------------------------------------------------
