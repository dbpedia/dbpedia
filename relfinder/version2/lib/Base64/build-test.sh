#!/bin/sh
mxmlc -source-path+=./src -library-path+=./testrunner/lib ./testrunner/Base64TestRunner/Base64TestRunner.mxml
if [ $? = 0 ]; then
	open ./testrunner/Base64TestRunner/Base64TestRunner.swf
fi