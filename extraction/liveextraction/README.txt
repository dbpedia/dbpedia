Normally fully working java source  would be included here,
but some needed packages require a GPL License.

For the OAIReader also a password from Wikipedia is required, which is not publicly available
and was given to the DBpedia project only.

The process is very simple though.
The OAI harvester reads the changed articles from the stream and writes 
a file to the oairecords directory

live_extract.php reads the oldest file from this directory and
starts the extraction job

The files have a pretty simple format, i.e. 
There is a metadata header, a separator and then the wiki source.

If you have any question mail me: Sebastian Hellmann <hellmann@@informatik.uni-leipzig.de>
or Claus Stadler <RavenArkadon@@googlemail.com>


Hibernate configuration files:
-------------------------------------------------------------------------------
The framework requires database connections to be configured.
As hibernate is used, database configuration is done in the *.cfg.xml files.

Currently only TemplateDb.virtuoso.cfg.xml needs to be configured.

This configuration file contains the line
<property name="hibernate.hbm2ddl.auto">validate</property>

On the first run "validate" needs to be replaced by "create" so that the
schema will be created.





 




Here is a list of required libraries:
-- This list is outdated -- claus
commons-lang-2.4.jar
harvester2.jar
hibernate/
Jena-2.6.0/
log4j-1.2.12.jar
xalan.jar
xercesImpl.jar
xml-apis.jar
