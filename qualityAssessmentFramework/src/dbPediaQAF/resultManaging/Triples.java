/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.Evaluator;
import java.util.Iterator;

/**
 *
 * @author Paul
 */
public class Triples
{

    private Model triples = ModelFactory.createDefaultModel();

    public Model getTriples()
    {
        return triples;
    }

    public void add(Statement stmt, DataSet datastore)
    {
        triples.add(stmt);
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        if (triples.contains(stmt))
        {
            triples.remove(stmt);
        }
        else
        {
            //Evaluator.printStatement(stmt, null);
        }
    }

    public void printStmts()
    {
        for (Iterator iterator = triples.listStatements(); iterator.hasNext();)
        {
            Statement statement = (Statement) iterator.next();
            Evaluator.printStatement(statement, "");
        }
    }
}
