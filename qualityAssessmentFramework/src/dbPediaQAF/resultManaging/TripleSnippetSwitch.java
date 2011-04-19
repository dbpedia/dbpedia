/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.xmlQuery.Snippet;
import java.util.List;

/**
 *
 * @author Paul
 */
public class TripleSnippetSwitch  implements Switch
{

    public SubjectSwitch triples = new SubjectSwitch();
    public Snippets snippets = new Snippets();

    public Model getTriples()
    {
        return triples.getTriples();
    }

    public List<Snippet> getSnippets()
    {
        return snippets.getSnippets();
    }

    public void add(Snippet snpt)
    {
        snippets.add(snpt);
    }

    public void add(Statement stmt, DataSet datastore)
    {
        triples.add(stmt, datastore);
    }

    public void remove(Statement stmt,DataSet datastore)
    {
        triples.remove(stmt, datastore);
    }
}
