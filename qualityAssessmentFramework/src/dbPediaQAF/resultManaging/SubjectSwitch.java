/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Statement;

/**
 *
 * @author Paul
 */
public class SubjectSwitch implements Switch
{

    public ObjectSwitch entityTriples = new ObjectSwitch();
    public ObjectSwitch intermediateTriples = new ObjectSwitch();

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(entityTriples.getTriples());
        m.add(intermediateTriples.getTriples());
        return m;
    }

    public void add(Statement stmt, DataSet datastore)
    {
        if (datastore.isIntermediate(stmt))
        {
            intermediateTriples.add(stmt, datastore);
        }
        else
        {
            entityTriples.add(stmt, datastore);
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        if (datastore.isIntermediate(stmt))
        {
            intermediateTriples.remove(stmt, datastore);
        }
        else
        {
            entityTriples.remove(stmt, datastore);
        }
    }
}
