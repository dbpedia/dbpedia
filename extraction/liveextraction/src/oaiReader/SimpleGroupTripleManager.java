package oaiReader;

import static oaiReader.MyVocabulary.DBM_MEMBER_OF;
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



class RemoveTriplesQueryGeneratorX
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{
		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "_:g";
	
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
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +

				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +

				group.generateReference("?g") +
			"}\n";
	
		return Collections.singletonList(result);
	}
}

class FullGroupMemberRemoveQueryGeneratorX
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{
		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "_:g";
	
		String fromPart = graph == null
			? ""
			: "From <" + graph + "> \n";
	
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?b ?c ?d .\n" + 
				"?g ?h ?i .\n" + 
			"}\n" +
			"{\n" +
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
				"?b ?c ?d .\n" + 
				"?g ?h ?i .\n" + 
				group.generateReference("?g") +
			"}\n";
	
		return Collections.singletonList(result);
	}
}


class FullInsertQueryGeneratorX
	implements IFullInsertQueryGenerator 
{
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();

		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "?g";
		
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
				
				//x + " <" + DBP_MEMBER_OF + "> " + b + " .\n";
				b + " <" + DBM_MEMBER_OF + "> " + g + " .\n";
		}
		result += "}\n";
		
		if(group.getIdentity() == null) {
			result +=
				"{\n" +
					group.generateReference(g) +
				"}\n";
		}
		
		return Collections.singletonList(result);		
	}
}


/**
 * A simple triple manager:
 * Each triple is associated with a group.
 * It is assumed that no triple can belong to multiple groups 
 * 
 * Thus no expensive group membership checks of triples are made.
 * 
 * @author raven
 *
 */
public class SimpleGroupTripleManager
	implements IGroupTripleManager
{
	private static Logger logger = Logger.getLogger(SimpleGroupTripleManager.class); 

	private ISparulExecutor sparqlExecutor;
	
	private CreateGroupIfNotExistsQueryGenerator createGroup =
		new CreateGroupIfNotExistsQueryGenerator();
	
	private ITripleQueryGenerator fullInsertQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullInsertQueryGeneratorX(), 100);

	private RemoveTriplesQueryGeneratorX removeTriplesQueryGenerator =
		new RemoveTriplesQueryGeneratorX();
	
	private FullGroupMemberRemoveQueryGeneratorX fullRemoveQueryGenerator =
		new FullGroupMemberRemoveQueryGeneratorX();
	
	public SimpleGroupTripleManager(ISparulExecutor sparqlExecutor)
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
		executeUpdate(
				createGroup.generate(
						tsg.getGroup(),
						sparqlExecutor.getGraphName()));
		
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
