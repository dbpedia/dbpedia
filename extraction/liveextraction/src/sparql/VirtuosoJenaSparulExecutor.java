package sparql;

import java.util.Collection;

import org.coode.owlapi.rdf.model.RDFTriple;

import virtuoso.jena.driver.VirtGraph;
import virtuoso.jena.driver.VirtuosoUpdateFactory;
import virtuoso.jena.driver.VirtuosoUpdateRequest;

public class VirtuosoJenaSparulExecutor
	extends VirtuosoSparqlExecutor
	implements ISparulExecutor
{
	public VirtuosoJenaSparulExecutor(VirtGraph graph)
	{
		super(graph);
	}

	@Override
	public void executeUpdate(String query)
		throws Exception
	{
		query = "define input:default-graph-uri <" + getGraphName() + "> \n"
				+ query;

		VirtuosoUpdateRequest vur = VirtuosoUpdateFactory.create(query, graph);
		vur.exec();
	}

	@Override
	public Object getConnection()
	{
		// TODO Auto-generated method stub
		return null;
	}

	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
		throws Exception
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
