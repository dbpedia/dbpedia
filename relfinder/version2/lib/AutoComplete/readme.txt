AutoComplete Component

Copyright (C) 2003-2006 Adobe Macromedia Software LLC and its licensors.
All Rights Reserved.

Contents :

	/bin/ 		- component SWC file
	/docs/		- Documentation
	/samples/	- Sample applications with source
	/src/		- component Source files 
	buildSWC.bat	- to build component swc file
	buildSamples.bat- to build sample applications
	manifest.xml	- maps component namespace to class names. It defines the package name that the component used before being compiled into a SWC file.


Description :

The AutoComplete control is an enhanced TextInput control which pops up a list of suggestions to the user based on the characters entered. 

These suggestions are to be provided by setting the dataProvider property of the control. 

Install Instructions :

Extract contents of zip file to any folder. To use compiled swc file directly in your applications use following steps:

1. If using Flex Builder select Project-> Properties add AutoComplete.swc file under "Flex Build Path"->"Library path" 
2. If using command line compiler copy AutoComplete.swc file to "\frameworks\libs" folder of your Flex SDK installation or use command line compilers "library-path" configuration parameter


To build all samples directly run "buildSamples.bat" file alternatively you can build individual samples by using AutoComplete.swc as library file and individual sample's mxml file as source file.

Known Issues:

1. Setting “backgroundAlpha” style to 0 results in combobox button to appear
2. In an autocomplete where rowcount property is set to higher number than dataproviders length, dropdown shown is equal to rowcount for first time 
3. lookahead works incorrect if filter function is changed to “contains"
4. In RestaurantFinder sample typing cuisine names very fast doesn't show correct list of suggestions
