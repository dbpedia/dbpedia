/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.util.BaseCategory;
import dbPediaQAF.xmlQuery.Snippet;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.List;

/**
 *
 * @author Paul
 */
public class MappingSwitch implements Switch
{

    public TripleSnippetSwitch withMapping = new TripleSnippetSwitch();
    public TripleSnippetSwitch withoutMapping = new TripleSnippetSwitch();

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(withMapping.getTriples());
        m.add(withoutMapping.getTriples());
        return m;
    }

    public Model getTriples(BaseCategory bc)
    {
        Model m = ModelFactory.createDefaultModel();
        switch (bc)
        {

            case Resource:
                m.add(withMapping.triples.entityTriples.literals.strings.getTriples());
                m.add(withoutMapping.triples.entityTriples.literals.strings.getTriples());
                break;
            case IntermediateNode:

                break;
            case Url:

                break;
            case String:

                break;
            case Booelan:

                break;
            case Integer:

                break;
            case Double:

                break;
            case Float:

                break;
            case Date:

                break;
            case GYear:

                break;
            case GYearMonth:

                break;
            case GMonthDay:

                break;
            case Time:

                break;
        }


        return m;
    }

    public Model getMappedTriples()
    {
        return withMapping.getTriples();
    }

    public Model getNotMappedTriples()
    {
        return withoutMapping.getTriples();
    }

    public void add(Statement stmt, DataSet datastore)
    {
        if (datastore.hasMapping(stmt))
        {
            withMapping.add(stmt, datastore);
        }
        else
        {
            withoutMapping.add(stmt, datastore);
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        withMapping.remove(stmt, datastore);
        withoutMapping.remove(stmt, datastore);
//        if (datastore.hasMapping(stmt))
//        {
//            withMapping.remove(stmt, datastore);
//        }
//        else
//        {
//            withoutMapping.remove(stmt, datastore);
//        }
    }

    public void add(Snippet snpt, DataSet datastore)
    {
        boolean foundFlag = false;
        boolean notfoundFlag = false;
        Model snippetModel = (Model) datastore.getMapSnippetToGoldModel().get(snpt);
        Iterator stmtsItr = snippetModel.listStatements();
        while (stmtsItr.hasNext())
        {
            Statement stmt = (Statement) stmtsItr.next();
            if (datastore.getMapStatementHasMapping().get(stmt.hashCode()).equals(true))
            {
                foundFlag = true;
            }
            else
            {
                notfoundFlag = true;
            }
        }
        if (foundFlag && !notfoundFlag)
        {
            withMapping.add(snpt);
        }
        else
        {
            withoutMapping.add(snpt);
        }
    }

    public List<Snippet> getSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(withMapping.getSnippets());
        list.addAll(withoutMapping.getSnippets());
        return list;
    }

    public List<Snippet> getMappedSnippets()
    {
        return withMapping.getSnippets();
    }

    public List<Snippet> getNotMappedSnippets()
    {
        return withoutMapping.getSnippets();
    }
}
