The DBpedia Test Framework

The Test Framework is based on the SimpleTest PHP unit tester.
That is a PHP unit test and web test framework. (http://simpletest.org)

# Folder Structure:
------------------------------------
 - expectedResults: text files with the expected results
 - pageSources: text files with the input for the tests
 - simpletest: the SimpleTest PHP unit tester Framework 
 - testData: xml files with the input and the expected results for the parser tests
 - tests: the test classes
 

# How to test a class?
------------------------------------
In /tests is the main test file 'allTests.php'. That file is the start file for the 
test framework. A new TestSuit will be created and you could add new TestCases. 
You can create a test portfolio by uncomment or comment the corresponding line.