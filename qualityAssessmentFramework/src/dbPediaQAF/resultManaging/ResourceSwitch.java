/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Statement;

/**
 *
 * @author Paul
 */
public class ResourceSwitch implements Switch
{

    public Triples unknowns = new Triples();
    public Triples entities = new Triples();
    public Triples urls = new Triples();
    public Triples intermediateNodes = new Triples();

    public void add(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isResource())
        {
            // TODO: DONE! intermediate node check is to simple. See Astro_Boy and Astro_Boy__Anime. Also check for predicate!
            String dbPediaNameSpace = "http://dbpedia.org/resource/";
            String subjectName = stmt.getSubject().getURI().replace(dbPediaNameSpace, "");
            String predicateName = stmt.getPredicate().getURI().replace("http://dbpedia.org/ontology/", "");
            String objectName = object.asResource().getURI().replace(dbPediaNameSpace, "");

            if (!object.asResource().getURI().contains(dbPediaNameSpace))
            {
                urls.add(stmt, datastore);
            }
            else if (objectName.contains(subjectName + "__" + predicateName))
            {
                intermediateNodes.add(stmt, datastore);
            }
            else if (object.asResource().getURI().contains(dbPediaNameSpace))
            {
                entities.add(stmt, datastore);
            }
            else
            {
                unknowns.add(stmt, datastore);
            }
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isResource())
        {
            String dbPediaNameSpace = "http://dbpedia.org/resource/";
            String subjectName = stmt.getSubject().getURI().replace(dbPediaNameSpace, "");
            String objectName = object.asResource().getURI().replace(dbPediaNameSpace, "");

            if (!object.asResource().getURI().contains(dbPediaNameSpace))
            {
                urls.remove(stmt, datastore);
            }
            else if (objectName.contains(subjectName))
            {
                intermediateNodes.remove(stmt, datastore);
            }
            else if (object.asResource().getURI().contains(dbPediaNameSpace))
            {
                entities.remove(stmt, datastore);
            }
            else
            {
                unknowns.remove(stmt, datastore);
            }
        }
    }

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(unknowns.getTriples());
        m.add(entities.getTriples());
        m.add(urls.getTriples());
        m.add(intermediateNodes.getTriples());
        return m;
    }

    public int getNumberOfResources()
    {
        int num = (int) unknowns.getTriples().size()
                + (int) entities.getTriples().size()
                + (int) urls.getTriples().size()
                + (int) intermediateNodes.getTriples().size();
        return num;
    }

    public void printStats()
    {
        System.out.println("Resource Results:");
        System.out.println("Overall triples: " + getNumberOfResources());
        System.out.println("Number of entities: " + entities.getTriples().size());
        System.out.println("Number of urls: " + urls.getTriples().size());
        System.out.println("Number of intermediateNodes: " + intermediateNodes.getTriples().size());
        System.out.println("Number of unknowns: " + unknowns.getTriples().size());

    }
}
