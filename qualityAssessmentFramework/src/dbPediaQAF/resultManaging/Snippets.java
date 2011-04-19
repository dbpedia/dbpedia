/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import dbPediaQAF.xmlQuery.Snippet;
import java.util.LinkedList;
import java.util.List;

/**
 *
 * @author Paul
 */
public class Snippets
{

    private List<Snippet> snippets = new LinkedList<Snippet>();

    public List<Snippet> getSnippets()
    {
        return snippets;
    }

    public void add(Snippet snpt)
    {
        snippets.add(snpt);
    }
}
