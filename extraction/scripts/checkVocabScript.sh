#!/bin/sh
cd ..
echo 'dbpedia.org in liveextraction'
find liveextraction | grep -v '\.svn' | grep -v 'oairecords' |grep -v 'ini$' | grep -v 'log$' | xargs grep -l "dbpedia.org"
echo 'Category: in liveextraction'
find liveextraction | grep -v '\.svn' | grep -v 'oairecords'  | grep -v 'log$' | xargs grep -l "Category:"
echo 'dbpedia.org/extractor in liveextraction'
find liveextraction | grep -v '\.svn' | grep -v 'oairecords' | xargs grep -l 'dbpedia\.org\/extractor'

echo 'dbpedia.org in php code'
find . | grep -v '\.svn' | grep -v 'liveextraction' | grep -v '\/test\/'| grep -v '\/test_old\/'|grep -v '\/scripts\/'| grep -v 'ini$'| xargs grep -l "dbpedia.org"
echo 'Category in php code'
find . | grep -v '\.svn' | grep -v 'liveextraction' | grep -v '\/test\/'| grep -v '\/test_old\/'|grep -v '\/scripts\/'| grep -v 'ini$'| xargs grep -l "Category:"
echo 'dbpedia.org/extractor in php code'
find . | grep -v '\.svn' | grep -v 'liveextraction' | grep -v '\/test\/'| grep -v '\/test_old\/'|grep -v '\/scripts\/'| grep -v 'ini$'| xargs grep -l 'dbpedia\.org\/extractor'
echo '?> in php code'
find . | grep -v '\.svn' | grep -v 'liveextraction' | grep -v '\/test\/'| grep -v '\/test_old\/'| grep -v 'ini$'| xargs grep -l '\?\>'

