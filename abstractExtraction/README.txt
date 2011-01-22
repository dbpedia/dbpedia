===================================
    DBPEDIA ABSTRACT EXTRACTION
===================================
Author: Max Jakob <max.jakob@fu-berlin.de>
Date: 2010-12-28

In order to generate clean abstracts from Wikipedia articles one needs to
render wiki templates as they would be rendered in the original Wikipedia 
instance.
So in order for the DBpedia AbstractExtractor to work, a running MediaWiki 
instance with Wikipedia data in a database is necessary.


1) DOWNLOAD SQL DUMPS
=====================

* Run trunk/extraction/dump/Download.scala for the desired languges.


2) INSTALL A SQL SERVER
=======================

* For example http://dev.mysql.com/downloads/mysql/


3) CLEAN SQL DUMPS
==================

* Run clean.sh.
  It might be necessary to make adjustments.
  

4) IMPORT WIKIPEDIA DUMPS INTO THE DATABASE
===========================================

* Run import.sh.
  Maven is required for the MWdumper. It compiles the MWdumper source files
  that are located in the src directory and then runs the MWdumper to 
  insert templates etc. into the database. Afterwards, the SQL dumps are
  imported.
  Remember the database prefix that you set (for example, choose "dbpedia_").


5) SET UP DATABASE AND LANGUAGE SETTINGS
========================================

* Decompress mw-modified.tar.gz

* Edit mw-modified/LocalSettings.php by adjusting the following variables:
    $wgDBserver         example: "localhost";
    $wgDBname           example: "dbpedia_en";
    $wgDBuser
    $wgDBpassword


6) INSTALL AN HTTP SERVER
=========================

* For example http://httpd.apache.org/download.cgi

* PHP has to be installed
  - Make sure that PHP runs with a large stack size (4 MB seems to work).
    Otherwise, a recursive Regex in DBpediaFunctions.php crashes.
    In Apache, this means:
    - add the line 'ThreadStackSize 4194304' in the appropriate section
      (in 'IfModule mpm_winnt_module' section when you're running on Windows)
    - make sure that the section is used (e.g. in XAMPP, comment in the line
      that includes httpd-mpm.conf in httpd.conf)

* Copy the directory mw-modified into the HTML documents directory (htdocs).


7) TEST
=======

* Test MediaWiki instance at
  WEBSERVER_URL/mw-modified/api.php?uselang=LANGUAGE&action=parse&text=TEXT

  For example: http://127.0.0.1:88/mw-modified/api.php?uselang=en&action=parse&text=[[This]] is a [[Test_text|text for testing]].
  
  This XML should be returned:
    <api>
      <parse>
        <text xml:space="preserve">This is a text for testing.</text>
      </parse>
    </api>


8) RUN ABSTRACT EXTRACTOR
=========================

* Use trunk/extraction/core/AbstractExtrator.scala to extract
  abstracts using either the dump module or the server module.

