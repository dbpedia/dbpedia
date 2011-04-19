/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.Evaluator;

/**
 *
 * @author Paul
 */
public class ObjectSwitch  implements Switch
{

    public LiteralSwitch literals = new LiteralSwitch();
    public ResourceSwitch resources = new ResourceSwitch();

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(literals.getTriples());
        m.add(resources.getTriples());
        return m;
    }

    public void add(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isLiteral())
        {
            literals.add(stmt, datastore);
        }
        else if (object.isResource())
        {
            resources.add(stmt, datastore);
        }
        else
        {
            Evaluator.printStatement(stmt, "ObjectSwitch - unable to add an unknown object:");
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isLiteral())
        {
            literals.remove(stmt, datastore);
        }
        else if (object.isResource())
        {
            resources.remove(stmt, datastore);
        }
        else
        {
            Evaluator.printStatement(stmt, "ObjectSwitch - unable to remove an unknown object:");
        }
    }
}
