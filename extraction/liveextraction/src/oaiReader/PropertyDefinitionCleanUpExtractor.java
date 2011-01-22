package oaiReader;

import static oaiReader.MyVocabulary.OWL_ANNOTATED_PROPERTY;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_SOURCE;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_TARGET;
import helpers.ExceptionUtil;

import org.apache.log4j.Logger;

import sparql.ISparulExecutor;


public class PropertyDefinitionCleanUpExtractor
	implements IHandler<IRecord>, IRecordVisitor<Void>
{
	private Logger logger = Logger.getLogger(TBoxExtractor.class);

	private String rootPrefix;
	
	private String dataGraphName;
	private String metaGraphName;
	private ISparulExecutor sparulExecutor;

	
	//private RemoveNonMemberSubjectsQueryGenerator removeNonMemberSubjects =
	//	new RemoveNonMemberSubjectsQueryGenerator();
	
	public PropertyDefinitionCleanUpExtractor(
			String rootPrefix,
			String dataGraphName,
			String metaGraphName,
			ISparulExecutor sparulExecutor)
	{
		if(sparulExecutor.getGraphName() != null)
			throw new RuntimeException("CleanUpExtractor requires a sparul-executor with default graph set to null");
		
		this.rootPrefix = rootPrefix;
		this.dataGraphName = dataGraphName;
		this.metaGraphName = metaGraphName;

		this.sparulExecutor = sparulExecutor;
	}


	@Override
	public void handle(IRecord item)
	{
		item.accept(this);
	}



	@Override
	public Void visit(Record item)
	{	
		//String name = item.getMetadata().getTitle().getFullTitle();
		
		//String rootName = WikiParserHelper.extractSubPageName(name);
		String rootName = TBoxExtractor.getRootName(item.getMetadata().getTitle());
		
		String subject = rootPrefix + rootName;

		logger.debug("Running cleanup: Removing triples with subject " +
				subject +
				//item.getMetadata().getTitle().getFullTitle() +
				" from data-graph");

		/*
				from data-graph) query for " +
				item.getMetadata().getRevision() + 
				" of subject " +
		*/
		
		try {
			/*
			sparulExecutor.executeUpdate(
					removeNonMemberSubjects.generate(
							subject,
							dataGraphName,
							metaGraphName));
			*/
			String query =
					"Delete From <" + dataGraphName + "> {" + 
						"<" + subject + "> ?p ?o }\n" +
					"From <" + dataGraphName + ">  {\n" +
						"<" + subject + "> ?p ?o " +
					"}";
			
			sparulExecutor.executeUpdate(query);
		}
		catch(Exception e)
		{
			logger.error(ExceptionUtil.toString(e));
		}
		
		return null;
	}
	
	
	@Override
	public Void visit(DeletionRecord item)
	{
		return null;
	}
}



/**
 * Removes all triples with a certain subject unless it belongs to a group
 * 
 * @author raven
 *
 */
/*
class RemoveNonMemberSubjectsQueryGenerator
{
	public String generate(String subject, String graph, String metaGraph)
		throws Exception
	{		
		String fromPart = graph == null
		? ""
		: "From <" + graph + "> \n";
		
		String s = "<" + subject.toString() + ">";
		
		return
			"Delete " +
				fromPart +
			"{\n" +
				s + " ?p ?o .\n" +
			"}\n" +
			"{\n" +
				"Graph <" + graph + "> {\n" +
					s + " ?p ?o .\n" +
				"} .\n" +
				"Optional {\n" + 
					"Graph <" + metaGraph + "> {\n" +
						//"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
						"?b <" + OWL_ANNOTATED_SOURCE + "> " + s + ".\n" +
						"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
						"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +
						//"_:b <" + DBM_MEMBER_OF + "> ?g .\n" +
					"} .\n" +
				"} .\n" +
				"Filter(!Bound(?b)) .\n" +
			"}";
	}	
}
*/


