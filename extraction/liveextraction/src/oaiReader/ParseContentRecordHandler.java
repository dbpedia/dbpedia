package oaiReader;

import helpers.ExceptionUtil;
import mywikiparser.SimpleTemplateInfo;
import mywikiparser.ast.RootWikiNode;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;


public class ParseContentRecordHandler
	implements IHandler<IRecord>, IProducer<SimpleTemplateInfo>, IRecordVisitor<Void>
{
	private Logger logger = Logger.getLogger(ParseContentRecordHandler.class);
	
	private IHandler<SimpleTemplateInfo> handler;
	
	public IHandler<SimpleTemplateInfo> getHandler()
	{
		return handler;
	}

	public void setHandler(IHandler<SimpleTemplateInfo> handler)
	{
		this.handler = handler;
	}
	
	
	// Is it a ok to create a transaction per document?
	// Or should this be batched?
	// (We could make this class require a session object, and
	// create a wrapper which does a transaction every e.g. 1000 records)
	// but then we run into the problem of how to determine if the task is finished
	// - so outside of the wrapper it needs to be taken care that a possible
	// open transaction is commited
	@Override
	public void handle(IRecord item)
	{
		item.accept(this);
	}


	@Override
	public Void visit(Record item)
	{		
		try {
			RootWikiNode root = WikiParserHelper.parse(item.getContent().getText()); 
	    	item.getContent().getRepresentations().add(root);
		} catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
		}

		return null;
	}

	@Override
	public Void visit(DeletionRecord record)
	{
		return null;
	}
}
