package sparql;

import java.util.ArrayList;
import java.util.Collection;
import java.util.List;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;

import com.hp.hpl.jena.query.QuerySolution;

/**
 * TODO: It was a dumb idea to make this class non-static.
 * So eventually make this class a helper class with state methods only
 * 
 * @author raven
 *
 */
public class MultiSparulExecutor
// implements ISparulExecutor
{
	private static Logger			logger	= Logger
											.getLogger(MultiSparulExecutor.class);
	private ISparulExecutor	delegate;

	public ISparulExecutor getDelegate()
	{
		return delegate;
	}

	public MultiSparulExecutor(ISparulExecutor delegate)
	{
		this.delegate = delegate;
	}

	public List<QuerySolution> executeSelect(Collection<String> queries)
		throws Exception
	{
		List<QuerySolution> result = new ArrayList<QuerySolution>();

		for (String query : queries)
			result.addAll(delegate.executeSelect(query));

		return result;
	}

	public void executeUpdate(Collection<String> queries)
		throws Exception
	{
		logger.trace("[UpdateBlock] queryCount = " + queries.size() + "\n");
		StopWatch sw = new StopWatch();
		sw.start();

		for (String query : queries)
			delegate.executeUpdate(query);

		sw.stop();
		logger.debug("[UpdateBlock] total time: " + sw.getTime() + "ms");
	}

	public static void executeUpdate(ISparulExecutor executor, Collection<String> queries)
		throws Exception
	{
		logger.trace("[UpdateBlock] queryCount = " + queries.size() + "\n");
		StopWatch sw = new StopWatch();
		sw.start();
	
		for (String query : queries)
			executor.executeUpdate(query);
	
		sw.stop();
		logger.debug("[UpdateBlock] total time: " + sw.getTime() + "ms");
	}
	
	
	public boolean executeAsk(Collection<String> queries)
		throws Exception
	{
		logger.trace("[UpdateBlock] queryCount = " + queries.size() + "\n");
		StopWatch sw = new StopWatch();
		sw.start();

		boolean result = true;

		for (String query : queries)
			result = result && delegate.executeAsk(query);

		sw.stop();
		logger.debug("[UpdateBlock] total time: " + sw.getTime() + "ms");

		return result;
	}

	// @Override
	public String getGraphName()
	{
		return delegate.getGraphName();
	}

}
