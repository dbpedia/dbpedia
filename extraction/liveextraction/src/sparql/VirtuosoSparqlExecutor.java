package sparql;

import java.util.List;

import virtuoso.jena.driver.VirtGraph;
import virtuoso.jena.driver.VirtuosoQueryExecution;
import virtuoso.jena.driver.VirtuosoQueryExecutionFactory;

import com.hp.hpl.jena.query.Query;
import com.hp.hpl.jena.query.QueryFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.query.ResultSetFormatter;

public class VirtuosoSparqlExecutor
	implements ISparqlExecutor
{
	protected VirtGraph	graph;

	public VirtuosoSparqlExecutor(VirtGraph graph)
	{
		this.graph = graph;
	}

	@Override
	public List<QuerySolution> executeSelect(String query)
		throws Exception
	{
		Query sparql = QueryFactory.create(query);

		VirtuosoQueryExecution vqe = VirtuosoQueryExecutionFactory.create(
				sparql, graph);
		ResultSet rs = vqe.execSelect();
		List<QuerySolution> result = ResultSetFormatter.toList(rs);

		return result;
	}

	@Override
	public boolean executeAsk(String query)
		throws Exception
	{
		Query sparql = QueryFactory.create(query);

		VirtuosoQueryExecution vqe = VirtuosoQueryExecutionFactory.create(
				sparql, graph);
		return vqe.execAsk();
	}

	@Override
	public String getGraphName()
	{
		return graph.getGraphName();
	}
}
