package sparql;

import java.util.Collections;
import java.util.List;

import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.query.ResultSetFormatter;
import com.hp.hpl.jena.sparql.engine.http.QueryEngineHTTP;

public class SparqlEndpointExecutor
	implements ISparqlExecutor
{
	protected String	service;
	protected String	graph;

	public SparqlEndpointExecutor(String service, String graph)
	{
		this.service = service;
		this.graph = graph;
	}

	@Override
	public List<QuerySolution> executeSelect(String query)
	{
		if (query == null)
			return Collections.emptyList();

		QueryEngineHTTP queryExecution = new QueryEngineHTTP(service, query);
		queryExecution.addDefaultGraph(graph);
		ResultSet rs = queryExecution.execSelect();

		List<QuerySolution> result = ResultSetFormatter.toList(rs);

		return result;
	}

	@Override
	public boolean executeAsk(String query)
		throws Exception
	{
		QueryEngineHTTP queryExecution = new QueryEngineHTTP(service, query);
		queryExecution.addDefaultGraph(graph);
		return queryExecution.execAsk();
	}

	@Override
	public String getGraphName()
	{
		return graph;
	}
}
