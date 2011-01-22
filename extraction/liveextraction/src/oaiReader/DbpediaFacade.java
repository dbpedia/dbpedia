package oaiReader;

import java.net.URLEncoder;
import java.util.ArrayDeque;
import java.util.HashSet;
import java.util.List;
import java.util.Queue;
import java.util.Set;

import org.apache.log4j.Logger;

import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.query.ResultSetFormatter;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.sparql.engine.http.QueryEngineHTTP;


/**
 * TODO Clearify use of this class
 * This class was intended to do remove operations, which
 * might be no longer needed.
 * 
 * 
 * 
 * @author raven
 *
 */
public class DbpediaFacade
{
	private Logger logger = Logger.getLogger(DbpediaFacade.class);

	private String service;       //"dbp:sparql";
	private String defaultGraph;  //"dbp";
	
	// Don't forget trailing slash
	private String prefix;        //"dbp:resource";

	public DbpediaFacade()
	{
	}
	
	public DbpediaFacade(String service, String defaultGraph, String prefix)
	{
		this.service = service;
		this.defaultGraph = defaultGraph;
		this.prefix = prefix;
	}
	
	public String getService()
	{
		return service;
	}
	
	public String getDefaultGraph()
	{
		return defaultGraph;
	}
	
	public String getPrefix()
	{
		return prefix;
	}

	
	public Set<String> findBlankNodesByOaiId(String oaiId)
		throws Exception
	{
		Set<String> result = new HashSet<String>();

		Set<String> actualResources = findResourceByOaiId(oaiId);
		
		// Safety test for now: There may only be a single resource
		if(actualResources.size() > 1)
			throw new RuntimeException("Safety test failed: Multiple subjects found for same oai identifier");
		
		for(String item : actualResources) {
			String title = item.replaceAll("^" + prefix, "");
			//result.add(title);
			
			Set<String> nodes = findBlankNodes(title);
			result.addAll(nodes);
		}
		
		return result;
	}
	
	
	public Set<String> findResourceByOaiId(String oaiId)
	{
		Set<String> result = new HashSet<String>();
		
		String query =
			"Select ?s Where {?s ?p <" + oaiId + "> . }";

		QueryEngineHTTP queryExecution = new QueryEngineHTTP(service, query);
		queryExecution.addDefaultGraph(defaultGraph);

		ResultSet rs = queryExecution.execSelect();
		List<QuerySolution> l = ResultSetFormatter.toList(rs);
		for (QuerySolution resultBinding : l) {
			RDFNode node = resultBinding.get("s");
			
			if(!node.isResource())
				continue;
			
			result.add(node.toString());
		}
		return result;
	}

	public String createDeleteStatement(String s)
	{
		return "Delete From <" + defaultGraph + "> {<" + s + "> ?p ?o}";
	}

	private Set<String> findBlankNodes(String title)
		throws Exception
	{
		return recursiveFindBlankNodes(title);
	}

	private Set<String> recursiveFindBlankNodes(String title)
		throws Exception
	{
		Queue<String> todo = new ArrayDeque<String>();
		todo.add(title);
		
		Set<String> done = new HashSet<String>();

		while(!todo.isEmpty()) {
			String current = todo.remove();
			done.add(current);
			
			Set<String> found = queryBlankNodes(current, title);
			logger.info("Queried: " + current + ", result count = " + found.size());
			
			for(String item : found)
				if(!done.contains(item))
					todo.add(item);
		}
		
		return done;
	}
	
	/**
	 * Queries all blank nodes related to a given title.
	 * 
	 * A subject has the form: prefix/title (e.g. dbp:resource/Berlin)
	 * A blank node has the form: subject/... 
	 * 
	 * Returns the uris for blank nodes
	 * 
	 * @param title
	 * @throws Exception
	 */
	private Set<String> queryBlankNodes(String title, String baseTitle)
		throws Exception
	{
		Set<String> result = new HashSet<String>();

		String encodedBaseTitle = URLEncoder.encode(baseTitle, "UTF-8");

		String subject = prefix + title;
		String pattern1 = "^" + prefix + baseTitle + "/.+";
		String pattern2 = "^" + prefix + encodedBaseTitle + "/.+";
		
		// Find all blank nodes related to a given subject
		String query =
			"Select ?o Where {<" + subject + "> ?p ?o . " +
			"Filter( Regex(?o, '" + pattern1 + "') || Regex(?o, '" + pattern2 + "')" +
			") . }";


		QueryEngineHTTP queryExecution = new QueryEngineHTTP(service, query);
		queryExecution.addDefaultGraph(defaultGraph);

		ResultSet rs = queryExecution.execSelect();
		List<QuerySolution> l = ResultSetFormatter.toList(rs);
		for (QuerySolution resultBinding : l) {
			RDFNode node = resultBinding.get("o");
			
			if(!node.isResource())
				continue;
			
			// get the result and remove the prefix
			String o = node.toString();
			String blankTitle = o.replaceAll("^" + prefix, "");
			
			result.add(blankTitle);
		}
		
		return result;
	}
}
