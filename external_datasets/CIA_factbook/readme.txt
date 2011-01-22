

==========================================================================

	README file
	
	CIA factbook: Convert rdf/xml version into MySql-database
	
==========================================================================


The following steps are necessary to convert the html version of the CIA factbook
into a MySql-database:


1. Download the latest version of the CIA factbook.

2. Run Ben Humphrey's Perl-script (see his readme) to convert the html to rdf/xml.
	(http://www.aktors.org/interns/2005/cia/CIA%20World%20Factbook%20to%20RDF.htm)
	
3. Run create_db.php to create the database

4. Run factbook_extract_data to extract the data and fill the database.


Necessary files:

element_names.php
-------------------------------------

Includes the element names, used for the table "countries". This table contains only values, which occurr at most once per country. I selected about 180 items, though you are free to add or drop items in this list.


parsing.php
-------------------------------------

Includes the parsing and data-conversion functions. It is needed by "extract_data.php" and "create_db.php".


create_db.php
-------------------------------------

Creates the MySql-database. Don't forget to edit the database hostname, user and password.


extract_data.php
-------------------------------------

Extracts the data and copies it into the database. Don't forget to edit the database hostname, user and password.


test.rdf
-------------------------------------

Sample rdf-file, needed to generate the database. Don't edit this file.


folder rdf
-------------------------------------

Subfolder rdf containing the .rdf files.