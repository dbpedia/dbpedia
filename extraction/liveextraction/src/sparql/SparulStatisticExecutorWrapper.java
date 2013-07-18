package sparql;

import java.util.Collection;

import org.apache.commons.lang.time.StopWatch;
import org.coode.owlapi.rdf.model.RDFTriple;

public class SparulStatisticExecutorWrapper
	extends AbstractSparqlStatisticExecutorWrapper
	implements ISparulExecutor
{
	private ISparulExecutor	delegate;

	public SparulStatisticExecutorWrapper(ISparulExecutor delegate)
	{
		this.delegate = delegate;
	}

	@Override
	protected ISparulExecutor getDelegate()
	{
		return delegate;
	}

	@Override
	public void executeUpdate(String query)
		throws Exception
	{
		if (query == null)
			return;

		logger.trace("Update =\n" + query);
		StopWatch sw = new StopWatch();
		sw.start();

		//boolean result =
		getDelegate().executeUpdate(query);

		sw.stop();
		logger.trace("Update took: " + sw.getTime() + "ms.");
		
		//return result;
	}

	@Override
	public Object getConnection()
	{
		return delegate.getConnection();
	}

	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		logger.trace("Insert " + triples.size() + " triples");
		StopWatch sw = new StopWatch();
		sw.start();

		boolean result = getDelegate().insert(triples, graphName);

		sw.stop();
		logger.trace("Insert took: " + sw.getTime() + "ms.");
	
		return result;
	}

	@Override
	public boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		throw new RuntimeException("Not implemented yet");
	}

}
