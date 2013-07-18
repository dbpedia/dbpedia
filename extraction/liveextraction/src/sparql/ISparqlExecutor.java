package sparql;

import java.util.List;

import com.hp.hpl.jena.query.QuerySolution;

/**
 * FIXME Make ISparqlExecutor a super class of ISparulExecutor
 * FIXME Make a new interfaces called BatchSpar(ql/ul)ExecutorWrapper
 * FIXME Move time statistics into a wrapper class
 * 
 * FIXME Should sparul be a subclass of sparql?! or rather seperate them?
 * 
 * @author raven
 *
 */
public interface ISparqlExecutor
{
	List<QuerySolution> executeSelect(String query) throws Exception;
	boolean executeAsk(String query) throws Exception;
	String getGraphName();
}