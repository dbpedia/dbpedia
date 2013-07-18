package sparql;

import java.util.Collections;
import java.util.List;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;

import com.hp.hpl.jena.query.QuerySolution;

public abstract class AbstractSparqlStatisticExecutorWrapper
	implements ISparqlExecutor
{
	protected Logger	logger	= Logger
										.getLogger(AbstractSparqlStatisticExecutorWrapper.class);

	protected abstract ISparqlExecutor getDelegate();

	@Override
	public boolean executeAsk(String query)
		throws Exception
	{
		boolean result = false;

		logger.trace("Query =\n" + query);
		StopWatch sw = new StopWatch();
		sw.start();

		result = getDelegate().executeAsk(query);

		sw.stop();
		logger.trace("Query took: " + sw.getTime() + "ms, Answer was '"
				+ result + "'.");

		return result;
	}

	@Override
	public List<QuerySolution> executeSelect(String query)
		throws Exception
	{
		logger.trace("Query =\n" + query);

		StopWatch sw = new StopWatch();
		sw.start();

		List<QuerySolution> result = Collections.emptyList();
		// try {
		result = getDelegate().executeSelect(query);
		// } catch(Exception e) {
		// logger.error(MyCommonHelper.exceptionToString(e));
		// }

		sw.stop();
		logger.trace("Query took: " + sw.getTime() + "ms, Got " + result.size()
				+ " results.");

		return result;
	}

	@Override
	public String getGraphName()
	{
		return getDelegate().getGraphName();
	}
}