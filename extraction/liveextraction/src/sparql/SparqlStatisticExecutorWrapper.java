package sparql;


public class SparqlStatisticExecutorWrapper
	extends AbstractSparqlStatisticExecutorWrapper
{
	private ISparqlExecutor	delegate;

	public SparqlStatisticExecutorWrapper(ISparqlExecutor delegate)
	{
		this.delegate = delegate;
	}

	@Override
	protected ISparqlExecutor getDelegate()
	{
		return delegate;
	}
}