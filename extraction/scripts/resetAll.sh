#!/bin/sh
cd ..
rm -r liveextraction/oairecords
mkdir -v liveextraction/oairecords
rm -v liveextraction/config/en/lastResponseDate.txt
rm -v liveextraction/config/meta/lastResponseDate.txt
rm -v liveextraction/log/enExtraction.log
rm -v liveextraction/log/metaExtraction.log
rm -v files/statistic/index.html
rm -v files/harvester_througput.dat
rm -v log/main.log


