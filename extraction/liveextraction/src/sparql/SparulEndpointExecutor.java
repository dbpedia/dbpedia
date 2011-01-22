package sparql;

import java.util.Collection;

import org.coode.owlapi.rdf.model.RDFTriple;

import com.hp.hpl.jena.sparql.engine.http.QueryEngineHTTP;

public class SparulEndpointExecutor
	extends SparqlEndpointExecutor
	implements ISparulExecutor
{
	public SparulEndpointExecutor(String service, String graph)
	{
		super(service, graph);
	}

	@Override
	public void executeUpdate(String query)
	{
		if (query == null)
			return;

		QueryEngineHTTP queryExecution = new QueryEngineHTTP(service, query);
		queryExecution.addDefaultGraph(graph);

		queryExecution.execSelect();
	}

	@Override
	public Object getConnection()
	{
		return null;
	}

	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
	{
		throw new RuntimeException("Not implemented yet");
	}

	@Override
	public boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		throw new RuntimeException("Not implemented yet");
	}
}