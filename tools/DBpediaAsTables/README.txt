As some of the potential users of DBpedia might not be familiar with the RDF data model and the SPARQL query language, we provide some of the core DBpedia data also in the form of Comma-Separated-Values (CSV) files that can easily be processed using standard tools, such as spreadsheet applications, relational databases or data mining tools.

For each class in the DBpedia ontology (such as Person, Radio Station, Ice Hockey Player, or Band) we provide a single CSV file which contains all instances of this class. Each instance is described by its URI, a label and a short abstract, the mapping-based infobox data describing the instance, and geo-coordinates.

The full documentation can be found on the following link: http://wiki.dbpedia.org/DBpediaAsTables

To start the convertor, run the ClassToCSV class, where the first argument should contain the local DBpedia endpoint, otherwise the default http://dbpedia.org/sparql will be used.
