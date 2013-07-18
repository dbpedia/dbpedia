package oaiReader;

import helpers.ExceptionUtil;
import helpers.SparulUtil;

import java.util.List;

import org.apache.commons.collections15.Predicate;
import org.apache.commons.collections15.Transformer;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFNode;

import sparql.ISparulExecutor;
import sparql.MultiSparulExecutor;

public class RedirectRenameExtractor
	implements IHandler<IRecord>, IRecordVisitor<Void>
{
	private static Logger logger = Logger.getLogger(RedirectRenameExtractor.class);
	
	// Used to check the title of the target for validity
	private Predicate<String> targetTitleFilter; 
	private ISparulExecutor executor;
	private String graphName;
	private Transformer<String, RDFNode> nodeTransformer;
	
	public RedirectRenameExtractor(
			ISparulExecutor executor,
			String graphName,
			Predicate<String> targetTitleFilter,
			Transformer<String, RDFNode> nodeTransformer)
	{
		this.executor = executor;
		this.graphName = graphName;
		this.targetTitleFilter = targetTitleFilter;
		this.nodeTransformer = nodeTransformer;
	}
	
	@Override
	public void handle(IRecord item)
	{
		item.accept(this);
	}

	@Override
	public Void visit(Record item)
	{
		List<String> redirects =
			MediawikiHelper.getRedirects(item.getContent().getText());
		
		if(redirects.size() != 1)
			return null;

		String src = item.getMetadata().getTitle().getFullTitle(); 
		String dest = redirects.get(0);			
		
		if(targetTitleFilter.evaluate(dest)) {
			System.out.println("Accepted");
		}
		
		RDFNode srcResource = nodeTransformer.transform(src);
		RDFNode destResource = nodeTransformer.transform(dest);
		
		List<String> queries =
			SparulUtil.generateRenameQuery(srcResource, destResource, graphName);
		//String query = SparulUtil.generateRenameQuery(srcResource, destResource, graphName, 0);

		try {
			MultiSparulExecutor.executeUpdate(executor, queries);
			//executor.executeUpdate(query);
		}
		catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
		}

		return null;
	}

	@Override
	public Void visit(DeletionRecord record)
	{
		// TODO Auto-generated method stub
		return null;
	}

}
