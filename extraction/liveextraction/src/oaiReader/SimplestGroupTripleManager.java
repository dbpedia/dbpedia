package oaiReader;

import static oaiReader.MyVocabulary.OWL_ANNOTATED_PROPERTY;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_SOURCE;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_TARGET;
import static oaiReader.MyVocabulary.OWL_AXIOM;
import static org.semanticweb.owlapi.vocab.OWLRDFVocabulary.RDF_TYPE;

import java.util.Collections;
import java.util.List;
import java.util.Set;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFTriple;

import sparql.ISparulExecutor;
import triplemanagement.GroupDef;
import triplemanagement.IGroupTripleManager;
import triplemanagement.TripleSetGroup;


class RemoveTriplesQueryGeneratorY
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{
		String fromPart = graph == null
			? ""
			: "From <" + graph + "> \n";
	
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?s ?p ?o .\n" +
			"}\n" +
			"{\n" +

				"_:b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"_:b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"_:b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +

				group.generateReference("_:b") +
			"}\n";
	
		return Collections.singletonList(result);
	}
}

class FullGroupMemberRemoveQueryGeneratorY
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{	
		String fromPart = graph == null
			? ""
			: "From <" + graph + "> \n";
	
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?b ?c ?d .\n" + 
			"}\n" +
			"{\n" +
				"?b ?c ?d .\n" + 
				group.generateReference("?b") +
			"}\n";
	
		return Collections.singletonList(result);
	}
}


class FullInsertQueryGeneratorY
	implements IFullInsertQueryGenerator 
{
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();

		String intoPart = graph == null
			? ""
			: "Into <" + graph + "> \n";
		
		String result =
			"Insert\n" +
				intoPart +
			"{\n"; 

		// generate insert pattern
		int i = 0;
		for(RDFTriple triple : triples) {
			
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			String b = "_:b" + (++i);
			
			result +=
				s + " " + p + " " + o + " .\n" +
				
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				b + " <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
				b + " <" + OWL_ANNOTATED_PROPERTY +  "> " + p + " .\n" +
				b + " <" + OWL_ANNOTATED_TARGET +  "> " + o + " .\n" +

				group.generateReference(b);
		}
		result += "}\n";
		
		return Collections.singletonList(result);		
	}
}


/**
 * Almost the simplest Triple Manager:
 * All group properties are attached directly to the reifying node.
 * 
 * @author raven
 *
 */
public class SimplestGroupTripleManager
	implements IGroupTripleManager
{
	private static Logger logger = Logger.getLogger(SimplestGroupTripleManager.class); 

	private ISparulExecutor sparqlExecutor;
	
	/*
	private CreateGroupIfNotExistsQueryGenerator createGroup =
		new CreateGroupIfNotExistsQueryGenerator();
	*/
	
	private ITripleQueryGenerator fullInsertQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullInsertQueryGeneratorY(), 100);

	private RemoveTriplesQueryGeneratorY removeTriplesQueryGenerator =
		new RemoveTriplesQueryGeneratorY();

	private FullGroupMemberRemoveQueryGeneratorY fullRemoveQueryGenerator =
		new FullGroupMemberRemoveQueryGeneratorY();
	
	public SimplestGroupTripleManager(ISparulExecutor sparqlExecutor)
	{
		this.sparqlExecutor = sparqlExecutor;
	}
	
	public void update(TripleSetGroup tsg)
		throws Exception
	{
		// Remove triples
		executeUpdate(
				removeTriplesQueryGenerator.generate(
						tsg.getGroup(),
						sparqlExecutor.getGraphName()));
		
		// Remove group
		executeUpdate(
				fullRemoveQueryGenerator.generate(
						tsg.getGroup(),
						sparqlExecutor.getGraphName()));

		// create group
		/*
		executeUpdate(
				createGroup.generate(
						tsg.getGroup(),
						sparqlExecutor.getGraphName()));
		*/

		// Insert all 
		executeUpdate(
				fullInsertQueryGenerator.generate(
						tsg.getTriples(),
						tsg.getGroup(),
						sparqlExecutor.getGraphName()));
	}
	
	private void executeUpdate(List<String> queries)
		throws Exception
	{
		StopWatch stopWatch = new StopWatch();
		stopWatch.start();
		
		logger.trace("Queries = \n" + queries);
		
		for(String query : queries) 
			sparqlExecutor.executeUpdate(query);
		
		stopWatch.stop();
		logger.debug("Query block consisting of " + queries.size() + " queries took " + stopWatch.getTime() + "ms");
	}
}
