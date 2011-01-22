#!/bin/bash

set -e

LISTFILE=wikipedias_csv.php
LISTURL=http://s23.org/wikistats/$LISTFILE

DATADIR=$1
DUMPS=$2
LISTMIN=$3
LISTMAX=$4

if [[ $# -lt 4 || $# -gt 4 || ! -d "$DATADIR" || ! "$DUMPS" =~ ^[1-9][0-9]*$ || ! "$LISTMIN" =~ ^[1-9][0-9]*$ || ! "$LISTMAX" =~ ^[1-9][0-9]*$ || "$LISTMIN" -gt "$LISTMAX" ]]
then
	echo "Usage: $0 <data dir> <dumps> <min> <max>"
	echo "Download wikipedia data for commons and languages with at least min and at most max articles."
	echo "Article counts are taken from $LISTURL, which is downloaded to data dir."
	echo
	echo "  data dir    Must be an existing directory. A sub-directory will be created for each language."
	echo "  dumps       Number of recent dumps to download for each language."
	echo "  min         Minimum number of articles."
	echo "  max         Maximum number of articles."
	exit 1
fi

wget --quiet --output-document="$DATADIR/$LISTFILE" $LISTURL

WPLANGS="commons"

IFS=, && while read -a COLUMNS
do
	if [[ "${COLUMNS[5]}" -ge "$LISTMIN" && "${COLUMNS[5]}" -le "$LISTMAX" ]]
	then
		WPLANGS="$WPLANGS ${COLUMNS[2]//-/_}"
	fi
done <"$DATADIR/$LISTFILE"

./download.sh "$DATADIR" $DUMPS "$WPLANGS"