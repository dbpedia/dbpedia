#!/bin/bash

set -e

TIMEFORMAT=%lR

function bunzip
{
	for FILE in "$@"
	do
		bunzip2 --keep --verbose $DATADIR/$FILE
	done
}

DATADIR=$1
PROCS=$2
LOGF=$3

if [[ $# -lt 3 || $# -gt 3 || ! -d "$DATADIR" || ! "$PROCS" =~ ^[1-9][0-9]*$ || -z "$LOGF" ]]
then
	echo "Usage: $0 <data dir> <procs> <log pattern>"
	echo "bunzip2 wikipedia data for all languages in data dir in multiple processes."
	echo "Write a separate log file for each process."
	echo
	echo "  data dir     Must be an existing directory that contains a sub-directory for each language."
	echo "  procs        Number of processes to run in parallel."
	echo "  log pattern  Name pattern for log files. One-based process number will be passed to printf."
	exit 1
fi

# make path handling easier
cd "$DATADIR"

FILES=
for i in */*/downloaded
do
	DIR=${i%/downloaded}
	if [[ ! -f $DIR/bunzipped ]]
	then
		FILES="$FILES $DIR/pages-articles.xml.bz2"
	fi
done

cd - &>/dev/null

# convert to array
FILES=($FILES)
COUNT=${#FILES[@]}
for PROC in $(seq 1 $PROCS)
do
	LO=$(bc <<< "scale = 6; $COUNT / $PROCS * ( $PROC - 1 )")
	HI=$(bc <<< "scale = 6; $COUNT / $PROCS * $PROC - 1")
	[ $PROC == $PROCS ] && HI=$(bc <<< "$COUNT - 1")
	LO=${LO%.*}
	HI=${HI%.*}
	LEN=$(bc <<< "$HI - $LO + 1")
	LOGFILE=$(printf $LOGF $PROC)
	bunzip ${FILES[@]:$LO:$LEN} 1>$LOGFILE 2>&1 & 
done