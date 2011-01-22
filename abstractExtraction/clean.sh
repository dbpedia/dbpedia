#!/bin/bash

# Cleans the SQL dumps downloaded from Wikipedia for later import into a
# SQL database.
#
# This script works with directory structure produced by dump/Download.scala.

set -e
TIMEFORMAT=%lR

DATADIR=$1
WPLANGS=$2

if [[ $# -lt 1 || $# -gt 2 || ! -d "$DATADIR" ]]
then
	echo "Usage: $0 <data dir> <languages>"
	echo "Clean wikipedia dumps for later insertion in MySQL database."
	echo "IMPORTANT: Please run dump/org.dbpedia.extraction.dump.Download.scala beforehand to create the <data dir>."
	echo
	echo "  data dir          Must be an existing directory. A sub-directory will be created for each language."
	echo "  languages         Names of wikipedia languages, e.g. 'en de fr'. If no languages are given, all directories in data dir are used."
	echo
	echo "Example:"
	echo "$0 'data/wikipedia' 'en de fr'"
    echo
	exit 1
fi

if [[ -z "$WPLANGS" ]]
then
	WPLANGS=$(find "$DATADIR" -maxdepth 1 -mindepth 1 -type d -printf '%f ')
fi


echo --------------------------------------------------------------------
echo "cleaning wikipedia data in $DATADIR for languages: $WPLANGS"
echo --------------------------------------------------------------------

if [[ ! -f "$DATADIR/tables.sql" ]]
then
	echo "missing $DATADIR/tables.sql"
	exit 1
fi

for WPLANG in $(echo $WPLANGS | sed -e 's/-/_/g')
do
    LANGWITHDASH=$(echo $WPLANG | sed -e 's/_/-/g')
	RECENTDATE=$(ls $DATADIR/$LANGWITHDASH \
                    | grep "[0-9]*" --line-regexp \
                    | sort \
                    | tail -n1)  # get most recent date
	WPLANGDIR=$DATADIR/$LANGWITHDASH/$RECENTDATE        #e.g. /dumps/en/20100412

    if [[ ! -d "$WPLANGDIR" ]]
	then
		echo "invalid directory $WPLANGDIR"
		exit 1
	fi
	
	for TABLE in image imagelinks langlinks templatelinks categorylinks
	do
		SQLFILE=$WPLANGDIR/${WPLANG}wiki-$RECENTDATE-$TABLE.sql
		CLEANSQLFILE=$WPLANGDIR/clean.${WPLANG}wiki-$RECENTDATE-$TABLE.sql
        echo "cleaning $SQLFILE..."
        time sed -r '/^DROP TABLE IF EXISTS `'$TABLE'`;$/ d; /^CREATE TABLE `'$TABLE'` \($/,/^\) (ENGINE|TYPE)=InnoDB( DEFAULT CHARSET=binary)?;$/ d' "$SQLFILE" >"$CLEANSQLFILE"
	done

    echo --------------------------------------------------------------------
	echo "cleaned $WPLANG"
	echo --------------------------------------------------------------------
	
done


# ------------ new for DBpedia 3.6 ------------
# delete these three columns, because they are not in categorylinks.sql files
# (check in categorylinks how many columns get inserted!)
grep -P '(?:cl_sortkey_prefix|cl_collation|cl_type)' $DATADIR/tables.sql --invert-match \
     | sed -e 's/cl_timestamp timestamp NOT NULL,/cl_timestamp timestamp NOT NULL/' \
     >$DATADIR/clean.tables.sql

echo "splitting tables.sql into tables-no-indexes.sql and tables-only-indexes.sql..."
grep 'CREATE .*INDEX' $DATADIR/clean.tables.sql                >$DATADIR/tables-only-indexes.sql
grep 'CREATE .*INDEX' $DATADIR/clean.tables.sql --invert-match >$DATADIR/tables-no-indexes.sql


echo --------------------------------------------------------------------
echo "cleaned wikipedia data in $DATADIR for languages: $WPLANGS"
echo --------------------------------------------------------------------
