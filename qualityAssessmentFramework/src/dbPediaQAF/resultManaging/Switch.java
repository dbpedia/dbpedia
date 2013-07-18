/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Statement;

/**
 *
 * @author Paul
 */
public interface Switch
{
    Model getTriples();

    void add(Statement stmt, DataSet datastore);

    void remove(Statement stmt, DataSet datastore);
}
