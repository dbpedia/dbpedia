#!/bin/bash

set -e

TIMEFORMAT=%lR

WPURL=http://download.wikimedia.org

# sed command to find all timestamped dump URLs
# on pages like http://download.wikimedia.org/enwiki/
DATESED='s~.*<a href="([0-9]+)/">\1</a>.*~\1~ p; d'

# awk command to extract lines containing name, date, and time for files 
# on pages like http://download.wikipedia.org/enwiki/20091103/index.html
# The timestamp for a file may occur in the same line as the file link 
# or in a previous line, so we remember each timestamp we find and print
# the nearest one when we find a file link.
DATEPAT='[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]'
FILEPAT='pages-articles\.xml\.bz2|categorylinks\.sql\.gz|image\.sql\.gz|imagelinks\.sql\.gz|langlinks\.sql\.gz|templatelinks\.sql\.gz'
FILESAWK="{
if (match(\$0, /^<li class='done'><span class='updates'>($DATEPAT)<\/span>/, groups)) datetime = groups[1];
if (match(\$0, /<a href=\".+\">($FILEPAT)<\/a>/, groups)) { print groups[1], datetime; } 
}"

DATADIR=$1
DUMPS=$2
WPLANGS=$3

if [[ $# -lt 3 || $# -gt 3 || ! -d "$DATADIR" || ! "$DUMPS" =~ ^[1-9][0-9]*$ || -z "$WPLANGS" ]]
then
	echo "Usage: $0 <data dir> <dumps> <languages>"
	echo "Download wikipedia data for each language."
	echo
	echo "  data dir    Must be an existing directory. A sub-directory will be created for each language."
	echo "  dumps       Number of recent dumps to download for each language."
	echo "  languages   Names of wikipedia languages, e.g. 'en de fr'."
	exit 1
fi

echo -------------------------------------------------------------------------------
echo "download table definitions"
echo -------------------------------------------------------------------------------
wget --no-verbose --timestamping --directory-prefix=$DATADIR http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/maintenance/tables.sql
echo

echo -------------------------------------------------------------------------------
echo "downloading wikipedia data to $DATADIR for languages: $WPLANGS"
echo -------------------------------------------------------------------------------

for WPLANG in $WPLANGS
do
	WPLANGURL=$WPURL/${WPLANG}wiki
	WPLANGDIR="$DATADIR/$WPLANG"
	
	COMPLETE=$DUMPS
	
	# find timestamped dump URLs and reverse them (newest first)
	WPDATES=$(wget --quiet --output-document=- $WPLANGURL/ | sed -r "$DATESED" | sort -r)
	
	for WPDATE in $WPDATES
	do
		WPDATEDIR=$WPLANGDIR/$WPDATE
		WPDATEURL=$WPLANGURL/$WPDATE
		
		wget --quiet --timestamping --directory-prefix="$WPDATEDIR" $WPDATEURL/index.html
		
		# find links to six required files, try next page if some are missing
		awk "$FILESAWK" <"$WPDATEDIR/index.html" >"$WPDATEDIR/index.txt"
		touch --reference="$WPDATEDIR/index.html" "$WPDATEDIR/index.txt"
		
		if [[ "$(wc -l <"$WPDATEDIR/index.txt")" == "6" ]]
		then
			
			if [[ -f $WPDATEDIR/downloaded ]]
			then
				echo -------------------------------------------------------------------------------
				echo "already downloaded data to $WPDATEDIR for language: $WPLANG"
				echo -------------------------------------------------------------------------------
			else
				echo -------------------------------------------------------------------------------
				echo "downloading wikipedia data to $WPDATEDIR for language: $WPLANG"
				echo -------------------------------------------------------------------------------
				
				while read LINE
				do
					# split line into array: name, date, time
					FILEDATA=($LINE)
					FILEURL=$WPDATEURL/${WPLANG}wiki-$WPDATE-${FILEDATA[0]}
					FILEPATH="$WPDATEDIR/${FILEDATA[0]}"
					FILETIME="${FILEDATA[1]} ${FILEDATA[2]}"
					time wget --progress=dot:giga --continue --output-document="$FILEPATH" $FILEURL
					touch --date="$FILETIME" "$FILEPATH"
					echo
				done < "$WPDATEDIR/index.txt"
				
				echo -------------------------------------------------------------------------------
				echo "downloaded wikipedia data to $WPDATEDIR for language: $WPLANG"
				echo -------------------------------------------------------------------------------
				
				touch --reference="$WPDATEDIR/index.html" $WPDATEDIR/downloaded
			fi
			
			(( COMPLETE-- ))
			if [[ $COMPLETE == 0 ]]
			then
				break
			fi
		fi
	done
	
	if [[ -z "$COMPLETE" ]]
	then
		echo -------------------------------------------------------------------------------
		echo "ERROR: found no completed dump on $WPLANGURL/ for language: $WPLANG"
		echo -------------------------------------------------------------------------------
		exit 1
	fi
	
done

echo -------------------------------------------------------------------------------
echo "downloaded wikipedia data to $DATADIR for languages: $WPLANGS"
echo -------------------------------------------------------------------------------
