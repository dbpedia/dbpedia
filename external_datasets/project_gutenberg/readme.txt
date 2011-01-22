

==========================================================================

	README file
	
	Project Gutenberg: Convert rdf/xml version into MySql-database
	
==========================================================================


The following steps are necessary to convert the rdf/xml dump of the Project gutenberg
into a MySql-database:


1. Download the latest version of the dump and the zip-file containing the author-names.

2. Run create_db.php to create the database

3. Run parse_gutenberg.php to extract the data and fill the database.

4. Run gb_authors.php to extract the authornames and corresponding wikipedia-links


Necessary files:

create_db.php
-------------------------------------

Creates the MySql-database. Don't forget to edit the database hostname, user and password.


parse_gutenberg.php
-------------------------------------

Extracts the data and copies it into the database. Don't forget to edit the database hostname, user and password.


catalog.rdf
-------------------------------------

Project Gutenberg rdf-dump. (You have to delete one item manually: pgterms:file with textId:).


gb_authors.php
-------------------------------------

Extracts author-names and wikipedia links. Put the .html files containing the authornames into subfolder "authors".
