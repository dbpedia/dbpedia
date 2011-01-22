package oaiReader;

import static oaiReader.MyVocabulary.DBM_EXTRACTED_BY;
import static oaiReader.MyVocabulary.DBM_MEMBER_OF;
import static oaiReader.MyVocabulary.DBM_TARGET;
import static oaiReader.MyVocabulary.DC_CREATED;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_PROPERTY;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_SOURCE;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_TARGET;
import static oaiReader.MyVocabulary.OWL_AXIOM;
import static org.semanticweb.owlapi.vocab.OWLRDFVocabulary.RDF_TYPE;
import helpers.ExceptionUtil;

import java.text.Format;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Collections;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Set;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.vocab.OWLRDFVocabulary;

import sparql.ISparulExecutor;
import sparql.MultiSparulExecutor;
import triplemanagement.GroupDef;
import triplemanagement.GroupDefSchema;
import triplemanagement.IGroupTripleManager;
import triplemanagement.TripleSetGroup;
import collections.IMultiMap;
import collections.MultiMap;

import com.hp.hpl.jena.query.QuerySolution;



interface ResultBindingWrapper
	extends QuerySolution
{
	
}




//interface ISparulE


/***
 * Ok, i never thought that implementing basically a map on top of rdf could
 * be that freaking hard. Maybe i was doing things all wrong - maybe not ...
 * what counts is: it seems to work! (Hooray)
 * (What i mean is: its 1000+ lines of code... there has got to be a better
 * solution)
 * To be fair, there is also much unused chunk left in here.
 * 
 * Basically all this "TripleSourceSystem" does is mapping triples to sources
 * Map<RDFTriple, Set<Source>>
 * 
 * A source is currently hard coded to be a DBP_GROUP with an extractor
 * and a target (extractor could be e.g. infobox extractor, target could be
 * a wiki page - or in this case: extractor is the
 * PropertyDefinitionExtractor, target is some PropertyDefinitionPage)
 *
 * Purpose:
 * This Class serves to add triples to "groups".
 * A triple can be part of multiple groups, and if a triple is no longer
 * part of any group, it is removed.
 * So its a lifetime management for triples.
 * 
 * If a triple already exists in the target store (which is currently hard coded)
 * which does NOT contain a group member ship, it won't be added to a group -
 * so no life time management is done for existing triples without the proper
 * annotations.
 * 
 * @author raven
 *
 */

//import org.semanticweb.owl.vocab.OWLRDFVocabulary;

// Imports all owl/rdf constants
//import static org.semanticweb.owl.vocab.Namespaces

/*
class Group
{
	private IRI extractor;
	private IRI target;
	
	public Group(IRI extractor, IRI target)
	{
		this.extractor = extractor;
		this.target = target;
	}
	
	public IRI getExtractor()
	{
		return extractor;
	}
	
	public IRI getTarget()
	{
		return target;
	}

	@Override
	public String toString()
	{
		return "Group: (" + extractor + ", " + target + ")";
	}

	
	@Override
	public boolean equals(Object other)
	{
		if(!(other instanceof Group))
			return false;
		
		Group o = (Group)other;
		
		return
			extractor.equals(o.extractor) &&
			target.equals(o.target);
	}
	
	@Override
	public int hashCode()
	{
		return 13 * extractor.hashCode() * target.hashCode();
	}
}
*/
class Group
{
	private IRI x;
	
	public Group(IRI x)
	{
		this.x = x;
	}
	
	public IRI getX()
	{
		return x;
	}

	@Override
	public String toString()
	{
		return "Group: (" + x + ")";
	}

	
	@Override
	public boolean equals(Object other)
	{
		if(!(other instanceof Group))
			return false;
		
		Group o = (Group)other;
		
		return x.equals(o.x);
	}
	
	@Override
	public int hashCode()
	{
		return 13 * x.hashCode();
	}
}




class AnnotatedTriple
{
	private RDFTriple triple;
	private RDFResourceNode subject;
	private RDFResourceNode predicate;
	private RDFNode object;
	
	private RDFNode reification; // blank node of reification
	
	private RDFNode group;
	
	// The underlying triples of this object
	private Set<RDFTriple> triples;
}



interface IFetchGroupMemberQueryGenerator
{
	public List<String> generate(GroupDef group, String graph) throws Exception;
}



/**
 * This query only retrieves all members of a group - and nothing more
 * 
 */
class FetchGroupMembersOnlyQueryGenerator
implements IFetchGroupMemberQueryGenerator
{
	@Override
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{
		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "?g";
		
		
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result  =
			"Select ?s ?p ?o \n" +
				fromPart +
			"{\n" +
				"?b <" + MyVocabulary.DBM_MEMBER_OF + "> " + g + " .\n" +
				"?b <" + OWLRDFVocabulary.RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" + 
				"?b <" + MyVocabulary.OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + MyVocabulary.OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + MyVocabulary.OWL_ANNOTATED_TARGET + "> ?o .\n" +
				
				group.generateReference(g) +
			"}\n";
		
		return Collections.singletonList(result);
	}	
}


/**
 * This query retrieves for a given group all triples belonging to it and then
 * fetches for each triple all other groups they belong to.
 * 
 * This query archieved somewhat random query times:
 * Sometimes it returned within less than 200ms, sometimes it took more than
 * 200 seconds.
 * 
 * @author raven
 *
 */
class FetchGroupMemberQueryGenerator
	implements IFetchGroupMemberQueryGenerator
{
	@Override
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{
		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "?g";
		
		
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result  =
			"Select ?s ?p ?o ?x " + group.getSchema().generateProjection("?gx")  + "\n" +
				fromPart +
			"{\n" + 
				//x + " <" + DBP_MEMBER_OF + "> _:b .\n" + 
				"_:b <" + MyVocabulary.DBM_MEMBER_OF + "> " + g + " .\n" +
				"_:b <" + OWLRDFVocabulary.RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" + 
				"_:b <" + OWLRDFVocabulary.OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
				"_:b <" + OWLRDFVocabulary.OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"_:b <" + OWLRDFVocabulary.OWL_ANNOTATED_TARGET + "> ?o .\n" + 
				//"?x <" + DBP_MEMBER_OF + "> _:b .\n" + 
				"_:b <" + DBM_MEMBER_OF + "> ?x .\n" +
				
				group.getSchema().generateSelection("?x", "?gx") + 
				
				group.generateReference(g) +
			"}\n";
		
		return Collections.singletonList(result);
	}	
}



interface IFetchNonGroupMemberQueryGenerator
	extends ITripleQueryGenerator
{
	//List<String> generate(Set<RDFTriple> triples, String graph) throws Exception;
}


// With filter
//This query times out if too many filter conditions
/*
class FetchNonGroupMemberQueryGeneratorFilter
	implements IFetchNonGroupMemberQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
	{
		if(triples.size() == 0)
			return Collections.emptyList();
	
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Select ?s ?p ?o ?x\n" +
				fromPart +
			"{\n" +	
				"?s ?p ?o .\n" +
				
				// Optionally reified statement
				"Optional {\n" + 
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
					"?b <" + OWL_SUBJECT + "> ?s .\n" + 
					"?b <" + OWL_PREDICATE + "> ?p .\n" + 
					"?b <" + OWL_OBJECT + "> ?o .\n" + 				
					"?b <" + DBP_MEMBER_OF + "> ?x .\n" +				
				"}\n" +	
				"Filter(\n" +
					SparqlHelper.generateFilterExpression(triples) +
				"\n) .\n" +
			"}\n";

		return Collections.singletonList(result);
	}
}
*/

class FetchNonGroupMemberQueryGenerator
	implements IFetchNonGroupMemberQueryGenerator
{
	// group is not used
	@Override
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
	{
		if(triples.size() == 0)
			return Collections.emptyList();

		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Select ?s ?p ?o ?x " + group.getSchema().generateProjection("?gx") + "\n" +
				fromPart +
			"{\n";
		
		
		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String filterExpression =
					SparqlHelper.generateFilterExpression(Collections.singleton(triple));

			result +=
				"{\n" +
					//"?s ?p ?o .\n" +
					
					// Optionally reified statement
					// Without the optional, triples that exist but do not belong
				    // to a group will not be seen, and become assigned to a group
					//"Optional {\n" + 
						"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
						"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
						"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
						"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
					
						// Optionally group membership 
							//"?x <" + DBP_MEMBER_OF + "> ?b .\n" +
						"Optional {\n" + 
							"?b <" + DBM_MEMBER_OF + "> ?x .\n" +
							group.getSchema().generateSelection("?x", "?gx") +
						"}\n" +						
 
					//"}\n" +	
					"Filter(" + filterExpression + ") .\n" +
				"}\n";
			
			if(it.hasNext())
				result += "Union ";
		}
		result += "}\n";
		
		return Collections.singletonList(result);
	}
}


interface ITripleQueryGenerator
{
	List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception;
}


interface IFullInsertQueryGenerator
	extends ITripleQueryGenerator
{
}

interface IGroupInsertQueryGenerator
	extends ITripleQueryGenerator
{
}

interface IGroupRemoveQueryGenerator
	extends ITripleQueryGenerator
{
}

interface IFullRemoveQueryGenerator
	extends ITripleQueryGenerator
{
}


/*
class SingleTripleQueryGenerator
	implements ITripleQueryGenerator
{
	private ITripleQueryGenerator generator;
	
	public SingleTripleQueryGenerator(ITripleQueryGenerator generator)
	{
		this.generator = generator;
	}
	
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
		List<String> result = new ArrayList<String>();
		for(RDFTriple triple : triples)
			result.addAll(generator.generate(
					Collections.singleton(triple), group, graph));

		return result;
	}
}
*/


class DeleteGroupQueryGenerator
{
	public List<String> generator(GroupDef group, String graph)
		throws Exception
	{
		String fromPart = graph == null
		? ""
		: "From <" + graph + "> \n";
	
		return Collections.singletonList(
			"Delete\n" +
				fromPart +
			"{\n" +
				//group.generateReference("?g") +
				"?g ?h ?i .\n" + 
			"}\n" +
			"{\n" + 
				group.generateReference("?g") +
				"?g ?h ?i.\n" +
				"Optional {\n" + 
					"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
				"}\n" +
				"Filter(!Bound(?b)) .\n" +		
			"}\n");
	}
}


/**
 * Due to a bug in automatic namespace assignment in virtuoso this
 * query currently doesn't work
 * 
 * @author raven
 *
 */
class CreateGroupIfNotExistsQueryGenerator 
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{		
		String intoPart = graph == null
		? ""
		: "Into <" + graph + "> \n";
		
		return Collections.singletonList(
			"Insert " +
				intoPart +
			"{\n" +
				group.generateReference("_:g") +
				group.generateProperties("_:g") +
			"}\n" +
			"{\n" + 
				"Optional {\n" + 
					group.generateReference("?b") +
				"}\n" +
				"Filter(!Bound(?b)) .\n" +		
			"}\n");
	}
}


class AskIfGroupExistsQueryGenerator
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{		
		String intoPart = graph == null
		? ""
		: "From <" + graph + "> \n";
		
		return Collections.singletonList(
			"Ask " +
				intoPart +
			"{\n" +
				group.generateReference("?x") +
			"}\n");
	}
}


class CreateGroupQueryGenerator
{
	public List<String> generate(GroupDef group, String graph)
		throws Exception
	{		
		String intoPart = graph == null
		? ""
		: "Into <" + graph + "> \n";
		
		return Collections.singletonList(
			"Insert " +
				intoPart +
			"{\n" +
				group.generateReference("_:g") +
				group.generateProperties("_:g") +
			"}\n");
	}
}



class FullDataInsertQueryGenerator
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
		for(RDFTriple triple : triples) {
			
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			result +=
				s + " " + p + " " + o + " .\n";
		}
		result += "}\n";
		
		return Collections.singletonList(result);		
	}
}


class FullDataRemoveQueryGenerator
	implements IFullRemoveQueryGenerator
{
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();
		
		String fromPart = graph == null
			? ""
			: "From <" + graph + "> \n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n"; 
		
		// generate delete pattern
		for(RDFTriple triple : triples) {
			
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			result +=
				s + " " + p + " " + o + " .\n";
		}
		result += "}\n";
		
		return Collections.singletonList(result);		
	}
}



class FullInsertQueryGenerator
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

		Date date = new Date();
		Format formatter = new SimpleDateFormat("yyyy.MM.dd'T'HH:mm:ss");
		String dateString = formatter.format(date);
		
		// generate insert pattern
		int i = 0;
		for(RDFTriple triple : triples) {
			
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			String b = "_:b" + (++i);
			
			result +=
				//s + " " + p + " " + o + " .\n" +
				
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				b + " <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
				b + " <" + OWL_ANNOTATED_PROPERTY +  "> " + p + " .\n" +
				b + " <" + OWL_ANNOTATED_TARGET +  "> " + o + " .\n" +
				
				b + " <" + DBM_MEMBER_OF + "> " + g + " .\n" +
			
				b + " <" + DC_CREATED + "> \"" + dateString + "\"^^xsd:dateTime .\n";
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


class GroupInsertQueryGenerator
	implements IGroupInsertQueryGenerator
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
		
		String intoPart = graph == null ? "" : "Into <" + graph + ">\n";
		
		String result =
			"Insert\n" +
				intoPart +
			"{\n" +
				//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> " + g + " .\n" +
			"}\n" +
			"{\n" +					
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +
				
				group.generateReference(g) +
				
				"Filter(\n" +
					SparqlHelper.generateFilterExpression(triples) +
				"\n) .\n" +
			"}\n";
		
		return Collections.singletonList(result);
	}
}

/*
class GroupInsertQueryGeneratorUnion
	implements IGroupInsertQueryGenerator
{
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();
		
		String g = "<" + group + ">";
		
		String intoPart = graph == null ? "" : "Into <" + graph + ">\n";
		
		String result =
			"Insert\n" +
				intoPart +
			"{\n" +
				//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBP_MEMBER_OF + "> " + g + " .\n" +
			"}\n" +
			"{\n";
			
		
		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
		
			result +=
				"{\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_SUBJECT + "> " + s + " .\n" +
					"?b <" + OWL_PREDICATE + "> " + p + " .\n" +
					"?b <" + OWL_OBJECT + "> " + o + " .\n" +
				"}\n";

			if(it.hasNext())
				result += "Union\n";
		}

		result += "}\n";
		
		return Collections.singletonList(result);
	}
}
*/


class GroupRemoveQueryGenerator
	implements IGroupRemoveQueryGenerator
{
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
	{
		if(triples.size() == 0)
			return Collections.emptyList();

		String g;
		if(group.getIdentity() != null)
			g = "<" + group.getIdentity() + ">";
		else
			g = "?g";
	
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Delete\n" +
				fromPart +
				"{\n" +
					//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
					"?b <" + DBM_MEMBER_OF + "> " + g + " .\n" +
				"}\n" +
				"{\n" + 
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
					"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
					"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
					"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +

					"?b <" + DBM_MEMBER_OF + "> " + g + " .\n" +
					
					group.generateReference(g) +
					
					"Filter(\n" +
						SparqlHelper.generateFilterExpression(triples) +
					"\n) .\n" +
				"}\n";
	
		return Collections.singletonList(result);
	}
}


/*
class GroupRemoveQueryGeneratorUnion
	implements IGroupRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
			throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();
		
		String g = "<" + group + ">";
		
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBP_MEMBER_OF + "> " + g + " .\n" +
			"}\n" +
			"{\n";
			
		
		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
		
			result +=
				"{\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +				
					"?b <" + OWL_SUBJECT + "> " + s + " .\n" +
					"?b <" + OWL_PREDICATE + "> " + p + " .\n" +
					"?b <" + OWL_OBJECT + "> " + o + " .\n" +
				"}\n";

			if(it.hasNext())
				result += "Union\n";
		}

		result += "}\n";
		
		return Collections.singletonList(result);		
	}
}
*/

/*
class FullRemoveQueryGeneratorBahObjectMayBeLiteral
	implements IFullRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
	
		if(triples.size() == 0)
			return Collections.emptyList();
	
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
	
		ArrayList<String> result = new ArrayList<String>();
		
		for(RDFTriple triple : triples) {
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			String query =
				"Delete\n" +
					fromPart +
				"{\n" +
					s + " " + p + " " + o + " .\n" +
					"_:b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"_:b <" + OWL_SUBJECT + "> " + s + " .\n" +
					"_:b <" + OWL_PREDICATE + "> " + p + " .\n" +
					"_:b <" + OWL_OBJECT + "> " + o + " .\n" +
			
					//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
					"_:b <" + DBP_MEMBER_OF + "> _:g .\n" +
				"}\n";
			
			result.add(query);	
				
		}	
		return result;	
	}
}
*/

/*
class FullRemoveQueryGeneratorSingleDelete
	implements IFullRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
	
		if(triples.size() == 0)
			return Collections.emptyList();
	
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
	
		ArrayList<String> result = new ArrayList<String>();
		
		for(RDFTriple triple : triples) {
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			//String o = SparqlHelper.toSparqlString(triple.getObject());
			
			
			String filterCondition =
				SparqlHelper.generateCondition("o", triple.getObject());
			
			String query =
				"Delete\n" +
					fromPart +
				"{\n" +
					s + " " + p + " ?o .\n" +
					"?b ?x ?y .\n" +
					/*
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_SUBJECT + "> " + s + " .\n" +
					"?b <" + OWL_PREDICATE + "> " + p + " .\n" +
					"?b <" + OWL_OBJECT + "> ?o .\n" +
			
					//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
					"?b <" + DBP_MEMBER_OF + "> ?g .\n" +
					* /
				"}\n" + 
				"{\n" + 
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_SUBJECT + "> " + s + " .\n" +
					"?b <" + OWL_PREDICATE + "> " + p + " .\n" +
					"?b <" + OWL_OBJECT + "> ?o .\n" +
			
					//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
					"?b ?x ?y .\n" +
					//"?b <" + DBP_MEMBER_OF + "> ?g .\n" +

					"Filter(" + filterCondition + ") .\n" +
				"}\n";
			
			result.add(query);	
				
		}	
		return result;	
	}
}
*/
class BulkWrapperTripleQueryGenerator
	implements ITripleQueryGenerator
{
	private ITripleQueryGenerator generator;
	private int maxPackSize;
	
	public BulkWrapperTripleQueryGenerator(ITripleQueryGenerator generator,
			int maxPackSize)
	{
		this.generator = generator;
		this.maxPackSize = maxPackSize;
	}
	
	@Override
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception
	{
		List<String> result = new ArrayList<String>();
		
		Iterator<RDFTriple> it = triples.iterator();
		
		Set<RDFTriple> pack = new HashSet<RDFTriple>();
		while(it.hasNext()) {
			RDFTriple triple = it.next();			
			pack.add(triple);

			if(pack.size() >= maxPackSize || !it.hasNext()) {
				result.addAll(generator.generate(pack, group, graph));
				pack.clear();
			}
		}
		
		return result;
	}
}


// Everything in filter condition
class FullRemoveQueryGenerator
	implements IFullRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, GroupDef group, String graph)
		throws Exception
	{

		if(triples.size() == 0)
			return Collections.emptyList();
	
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String filterExpression =
			"Filter(\n" +
				SparqlHelper.generateFilterExpression(triples) +
			"\n) .\n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				//"?s ?p ?o .\n" +
				"?b ?x ?y .\n" + 
				/*
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_SUBJECT + "> ?s .\n" +
				"?b <" + OWL_PREDICATE + "> ?p .\n" +
				"?b <" + OWL_OBJECT + "> ?o .\n" +
		
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBP_MEMBER_OF + "> ?g .\n" +
				*/
			"}\n" +
			"{\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +		
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
				"?b ?x ?y .\n" +		
		
				filterExpression +
			"}\n";
				
	
		return Collections.singletonList(result);	
	}
}

// With union
/*
class FullRemoveQueryGeneratorWithUnionAndSlow
	implements IFullRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
	
		if(triples.size() == 0)
			return Collections.emptyList();
		
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?s ?p ?o .\n" +
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_SUBJECT + "> ?s .\n" +
				"?b <" + OWL_PREDICATE + "> ?p .\n" +
				"?b <" + OWL_OBJECT + "> ?o .\n" +
		
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBP_MEMBER_OF + "> ?g .\n" +
			"}\n";
		
		Iterator<RDFTriple> it = triples.iterator();		
		result += "{\n";
		while(it.hasNext()) {
			RDFTriple triple = it.next();
		
			String filterExpression =
					SparqlHelper.generateFilterExpression(Collections.singleton(triple));
			
			//String result = "Select ?s ?p ?o\n";
			result +=
				"{\n" +
					"?s ?p ?o .\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_SUBJECT + "> ?s .\n" +
					"?b <" + OWL_PREDICATE + "> ?p .\n" +
					"?b <" + OWL_OBJECT + "> ?o .\n" +
		
					//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
					"?b <" + DBP_MEMBER_OF + "> ?g .\n" +
					
					"Filter(" + filterExpression + ") .\n" +	
				"}\n";
			
			if(it.hasNext())
				result += "Union ";
		}
		result += "}\n";
		
		return Collections.singletonList(result);
	}
}
*/
/*
class FullRemoveQueryGeneratorSingleClauseNotWorkin
	implements IFullRemoveQueryGenerator
{
	@Override
	public List<String> generate(Set<RDFTriple> triples, IRI group, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return Collections.emptyList();

		//String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
		String fromPart = graph == null ? "" : "From <" + graph + "> \n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n"; 

		// generate insert pattern
		int i = 0;
		for(RDFTriple triple : triples) {
			
			String s = SparqlHelper.toSparqlString(triple.getSubject());
			String p = SparqlHelper.toSparqlString(triple.getProperty());
			String o = SparqlHelper.toSparqlString(triple.getObject());
			
			String b = "_:b" + (++i);
			String x = "_:x" + (++i);
			
			result +=
				s + " " + p + " " + o + " .\n" +
				
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				b + " <" + OWL_SUBJECT + "> " + s + " .\n" +
				b + " <" + OWL_PREDICATE +  "> " + p + " .\n" +
				b + " <" + OWL_OBJECT +  "> " + o + " .\n" +
				
				//x + " <" + DBP_MEMBER_OF + "> " + b + " .\n";
				b + " <" + DBP_MEMBER_OF + "> " + x + " .\n";
		}
		result += "}\n";
		
		return Collections.singletonList(result);
	}

}
*/




/**
 * A class which takes care of adding, removing and diffing of triples.
 * 
 * INSERTED TRIPLES MUST NOT OVERLAP WITH GENERATED MANAGEMENT TRIPLES
 * updates works in the following way:
 * 
 * .) First all existing triples with their annotations
 *    and group member ships are retrieved
 * 
 * This results in the following cases:
 * 
 * 1) The triple does not yet exist
 *     Add the triple, generate full annotations
 * 2) The triple exists without reification
 *     Ignore (as the existing triple is taken as a all valid fact)
 * 3) The triple exists with reification but without group membership
 *     Ignore
 * 4) The triple exists with group membership, but not with current group
 *     Add group membership to the triple
 * 5) The triple exists with appropriate group membership
 *     Ignore (We could update dc_modified)
 * 
 * FOLLOWING STRUCTURE IS DEPRICATED.
 * extractor and target are combined into a single uri
 * 
 * Structure:
 * s             p            o
 *  \            |           /
 *   subject predicate object
 *          \    |    /
 *             blank - type - annotation
 *               |
 *             memberOf
 *               |
 *             blank - type - group
 *            /     \
 *   extractor       target
 *   
 *    
 * @param tripleSetGroup
 * @author raven
 *
 */
public class ComplexGroupTripleManager
	implements IGroupTripleManager
{	
	private Logger logger = Logger.getLogger(ComplexGroupTripleManager.class);

	//private ISparulExecutor metaSparqlExecutor;	
	//private ISparulExecutor dataSparqlExecutor;
	private MultiSparulExecutor metaSparqlExecutor;
	private MultiSparulExecutor dataSparqlExecutor;

	/**
	 * This might be considered hack since the sparql executors should
	 * remain encapsulated here, but i need them in the
	 * PropertyDefinitionExtractor to place some clean up queries. 
	 * 
	 * @return
	 */
	/*
	public ISparulExecutor getDataSparqlExecutor()
	{
		return dataSparqlExecutor;
	}
	*/
	
	private GroupDefSchema groupSchema;
	
	private FetchGroupMembersOnlyQueryGenerator groupMembersOnlyQueryGenerator =
		new FetchGroupMembersOnlyQueryGenerator();
	
	@Deprecated
	private IFetchGroupMemberQueryGenerator groupMemberQueryGenerator =
		new FetchGroupMemberQueryGenerator();


	private ITripleQueryGenerator nonGroupMemberQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FetchNonGroupMemberQueryGenerator(), 30);

	private ITripleQueryGenerator fullInsertQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullInsertQueryGenerator(), 30);

	private ITripleQueryGenerator fullDataInsertQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullDataInsertQueryGenerator(), 30);
	
	private ITripleQueryGenerator groupInsertQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new GroupInsertQueryGenerator(), 30);
	
	private ITripleQueryGenerator groupRemoveQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new GroupRemoveQueryGenerator(), 30);
	
	private ITripleQueryGenerator fullRemoveQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullRemoveQueryGenerator(), 30);

	private ITripleQueryGenerator fullDataRemoveQueryGenerator =
		new BulkWrapperTripleQueryGenerator(new FullDataRemoveQueryGenerator(), 30);
	
	public ComplexGroupTripleManager(
			ISparulExecutor dataSparqlExecutor,
			ISparulExecutor metaSparqlExecutor)
	{
		//this.dataSparqlExecutor = dataSparqlExecutor;
		//this.metaSparqlExecutor = metaSparqlExecutor;
		this.dataSparqlExecutor = new MultiSparulExecutor(dataSparqlExecutor);
		this.metaSparqlExecutor = new MultiSparulExecutor(metaSparqlExecutor);

		groupSchema = new GroupDefSchema();
		groupSchema.getIdentityPredicates().add(new RDFResourceNode(DBM_EXTRACTED_BY.getUri()));
		groupSchema.getIdentityPredicates().add(new RDFResourceNode(DBM_TARGET.getUri()));		
	}
	
	public void update(TripleSetGroup g)
	{
		StopWatch sw = new StopWatch();
		sw.start();

		try {
			myUpdate(g);
		} catch(Exception e) {
			logger.error(ExceptionUtil.toString(e));
		}

		sw.stop();
		logger.debug("Total Query Time: " + sw.getTime() + "ms");
	}
	
	private void myUpdate(TripleSetGroup g)
		throws Exception
	{	
		Set<RDFTriple> originalTriples = g.getTriples();
		if(originalTriples == null)
			originalTriples = Collections.emptySet();
		
		Set<RDFTriple> insertTriples = g.getTriples() == null
			? new HashSet<RDFTriple>()
			: new HashSet<RDFTriple>(g.getTriples());
		
		
		//IRI group = SparqlHelper.combineExtractorAndTarget(
		//		g.getExtractor(), g.getTarget());
			
			
		logger.debug("Fetching all group members");
		//IMultiMap<RDFTriple, GroupDef> mm = queryGroupMemberTriples(g.getGroup());				
		//Set<RDFTriple> members = mm.keySet();
		
		Set<RDFTriple> members = queryGroupMembersOnly(g.getGroup());


		// Those triples that already belong to the group do not need to be
		// inserted
		// Insert triples is: original triples without group triples
		insertTriples.removeAll(members);

		// remove triples is group triples without original Triples 
		Set<RDFTriple> removeTriples = new HashSet<RDFTriple>(members);
		removeTriples.removeAll(originalTriples);
		
		// affectedTriples is the union of insert and remove triples
		Set<RDFTriple> affectedTriples = new HashSet<RDFTriple>();
		affectedTriples.addAll(insertTriples);
		affectedTriples.addAll(removeTriples);

		
		// For the remaining triples determine their memberships - if any
		// (note: they can't belong to the insert group)

		// All triples that exist but do not belong to a group
		// remaing untouched
		//Model existingModel = queryExistingTriplesModel(insertTriples);
		
		//logger.debug("Fetching group memberships for non-members");
		
		// Changed: We now retrieve group memberships for all affected
		// triples
		logger.debug("Fetching group memberships (for members and non-members)");
		IMultiMap<RDFTriple, GroupDef> mm = queryNonGroupMemberTriples(affectedTriples);

		
		Set<RDFTriple> fullInsertTriples  = new HashSet<RDFTriple>();
		Set<RDFTriple> groupInsertTriples = new HashSet<RDFTriple>();

		// Do not insert triples which exist but are not part of a group
		// otherwise do an "add-group"
		// The remaining triples are "full-insert-triples"
		// Note: all insert triples cannot be members of the group already
		// thus it suffices to just check if any other group exists
		for(RDFTriple item : insertTriples) {
			Collection<GroupDef> memberships = mm.safeGet(item);
			if(memberships.size() == 0)
				fullInsertTriples.add(item);
			else
				groupInsertTriples.add(item);
		}

		Set<RDFTriple> groupRemoveTriples = new HashSet<RDFTriple>();
		Set<RDFTriple> fullRemoveTriples = new HashSet<RDFTriple>();
		// If a remove delete candidate is part of only a single group
		//  -> do a full remove
		// otherwise do a group remove
		// Remove candidates belong to at least 1 single group:
		// the group we are inserting into.
		for(RDFTriple item : removeTriples) {

			Collection<GroupDef> memberships = mm.get(item);
			// Note: the mapping must exist (since item is a member) and
			// size is at least 1, since it must be part of this group
			if(memberships.size() == 0)
				throw new Exception("I don't have a group for a triple which should actually have one");
			
			if(memberships.size() != 1)
				groupRemoveTriples.add(item);
			else
				fullRemoveTriples.add(item);
		}

		/*
		for(RDFTriple triple : insertTriples) {
			fullInsertTriples.add(triple);
		}
		*/
		
		logger.info("Diff Stats: [" +
				"Requested: " + originalTriples.size() + ", " +
				"fullInsert: " + fullInsertTriples.size() + ", " +
				"groupInsert: " + groupInsertTriples.size() + ", " +
				"fullRemove: " + fullRemoveTriples.size() + ", " +
				"groupRemove: " +  groupRemoveTriples.size() +
				"]");
		
		
		AskIfGroupExistsQueryGenerator askGroup =
			new AskIfGroupExistsQueryGenerator();
		
		if(g.getTriples() != null && (
				groupInsertTriples.size() > 0 ||
				fullInsertTriples.size() > 0)) {
			logger.debug("Checking if group exists");
			boolean isExisting =
				metaSparqlExecutor.executeAsk(
						askGroup.generate(
								g.getGroup(),
								metaSparqlExecutor.getGraphName()));
			
			if(!isExisting) {
				logger.debug("Group did not exist, creating.");
				CreateGroupQueryGenerator createGroup =
					new CreateGroupQueryGenerator();
				
				metaSparqlExecutor.executeUpdate(
						createGroup.generate(
								g.getGroup(),
								metaSparqlExecutor.getGraphName()));
			}
			else
				logger.debug("Group already exists");

			
		}
		
		
		DeleteGroupQueryGenerator deleteGroup =
			new DeleteGroupQueryGenerator();

		
		/*
		CreateGroupIfNotExistsQueryGenerator createGroup =
			new CreateGroupIfNotExistsQueryGenerator();
		// create the group
		if(g.getTriples() != null) {
			logger.info("Generating group");
			execUpdate(
					createGroup.generate(
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));
		}
		*/
		
		
		// do the full insert
		// Note insert meta data first - so if something goes wrong we
		// can delete it later
		if(fullInsertTriples.size() != 0) {
			logger.debug("Performing full insert");
			metaSparqlExecutor.executeUpdate(
					fullInsertQueryGenerator.generate(
							fullInsertTriples,
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));

			dataSparqlExecutor.executeUpdate(
					fullDataInsertQueryGenerator.generate(
							fullInsertTriples,
							g.getGroup(),
							dataSparqlExecutor.getGraphName()));
		}
		
		if(groupInsertTriples.size() != 0) {
			logger.debug("Performing group insert");
			metaSparqlExecutor.executeUpdate(
					groupInsertQueryGenerator.generate(
							groupInsertTriples,
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));
		}

		if(groupRemoveTriples.size() != 0) {
			logger.debug("Performing group remove");
			metaSparqlExecutor.executeUpdate(
					groupRemoveQueryGenerator.generate(
							groupRemoveTriples,
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));
		}
		
		// Same here: delete data first, so if this goes wrong we can still
		// reference it through the meta data.
		if(fullRemoveTriples.size() != 0) {
			logger.debug("Performing full remove");
			dataSparqlExecutor.executeUpdate(
					fullDataRemoveQueryGenerator.generate(
							fullRemoveTriples,
							g.getGroup(),
							dataSparqlExecutor.getGraphName()));

			metaSparqlExecutor.executeUpdate(
					fullRemoveQueryGenerator.generate(
							fullRemoveTriples,
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));

		}

		if(g.getTriples() == null) {
			logger.debug("Performing delete group");
			metaSparqlExecutor.executeUpdate(
					deleteGroup.generator(
							g.getGroup(),
							metaSparqlExecutor.getGraphName()));
		}

		// fullInsertTriples: triples which are created anew
		// groupAddTriples: triples which only need adding to a group
		
		// now we need to classify each triple wheter...
		// .) it does not exist -> add to insert-set
		// .) it exists and belongs to a group -> add the group annotation

		
		
	}

	
	/*
	public void clearGraph()
	{
		execUpdate("Clear Graph <" + metaSparqlExecutor.getGraphName() + ">");
	}
	 */
	
	private void processSpox(
			List<QuerySolution> qs, IMultiMap<RDFTriple, GroupDef> result)
	{
		Map<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode> bnMap
		 = new HashMap<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode>();
	
		for(QuerySolution item : qs) {
			RDFNode nS = JenaToOwlApi.toResource(item.get("s"), bnMap);
			RDFNode nP = JenaToOwlApi.toResource(item.get("p"), bnMap);
			
			RDFResourceNode s = (RDFResourceNode)nS;
			RDFResourceNode p = (RDFResourceNode)nP;
			RDFNode o = JenaToOwlApi.toResource(item.get("o"), bnMap);
			
			RDFTriple triple = new RDFTriple(s, p, o);
			
			
			//System.out.println("s = " + s);
			//System.out.println("p = " + p);
			//System.out.println("o = " + o);
	
			RDFNode nX = JenaToOwlApi.toResource(item.get("x"), bnMap);
			
			// Make the triple known in the map
			if(!result.keySet().contains(triple))
				result.put(triple, (GroupDef)null);
			
			if(nX == null)
				continue;

			List<RDFNode> groupValues = new ArrayList<RDFNode>();
			for(int i = 0; i < groupSchema.getIdentityPredicates().size(); ++i)
				groupValues.add(null);

			Iterator<String> it = item.varNames();
			while(it.hasNext()) {
				String groupVar = it.next();
				if(!groupVar.startsWith("gx"))
					continue;
				
				int index = Integer.parseInt(groupVar.substring(2));
				
				if(index >= groupSchema.getIdentityPredicates().size())
					continue;
								
				groupValues.set(
						index,
						JenaToOwlApi.toResource(item.get(groupVar), bnMap));
			}
			GroupDef group = new GroupDef(nX.getIRI(), groupSchema, groupValues);			
			
			result.put(triple, group);
		}
	}

	
	private Set<RDFTriple> queryGroupMembersOnly(GroupDef group)
		throws Exception
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		
		Map<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode> bnMap
		 = new HashMap<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode>();

		
		List<String> queries =
			groupMembersOnlyQueryGenerator.
				generate(group, metaSparqlExecutor.getGraphName());
	
		List<QuerySolution> qs = metaSparqlExecutor.executeSelect(queries);
		
		for(QuerySolution item : qs) {
			RDFNode nS = JenaToOwlApi.toResource(item.get("s"), bnMap);
			RDFNode nP = JenaToOwlApi.toResource(item.get("p"), bnMap);
			
			RDFResourceNode s = (RDFResourceNode)nS;
			RDFResourceNode p = (RDFResourceNode)nP;
			RDFNode o = JenaToOwlApi.toResource(item.get("o"), bnMap);
			
			RDFTriple triple = new RDFTriple(s, p, o);
			result.add(triple);
		}
		
		return result;
	}
	
	/**
	 * Generates queries, executes them and builds a map from each triple
	 * to the corresponding group
	 * 
	 * @param group
	 * @return
	 * @throws Exception
	 */
	private IMultiMap<RDFTriple, GroupDef> queryGroupMemberTriples(GroupDef group)
		throws Exception
	{	
		List<String> queries =
			groupMemberQueryGenerator.
				generate(group, metaSparqlExecutor.getGraphName());
		
		return execSelectAndSpox(queries);
	}
	
	
	private IMultiMap<RDFTriple, GroupDef> queryNonGroupMemberTriples(
			Set<RDFTriple> triples)
		throws Exception
	{
		GroupDef tmp = new GroupDef(null, groupSchema);
		
		List<String> queries =
			nonGroupMemberQueryGenerator.
				generate(triples, tmp, metaSparqlExecutor.getGraphName());
		
		return execSelectAndSpox(queries);
	}


	private IMultiMap<RDFTriple, GroupDef> execSelectAndSpox(List<String> queries)
		throws Exception
	{
		IMultiMap<RDFTriple, GroupDef> result = new MultiMap<RDFTriple, GroupDef>();
		
		for(String query : queries) {
			List<QuerySolution> rs = metaSparqlExecutor.getDelegate().executeSelect(query);
			
			processSpox(rs, result);
		}
		
		return result;		
	}
	
	/*	
	private List<QuerySolution> execSelect(List<String> queries)
		throws Exception
	{
		List<QuerySolution> result = new ArrayList<QuerySolution>();
		
		for(String query : queries)
			result.addAll(execSelect(query));

		return result;
	}
	

	private List<QuerySolution> queryGroupTriplesList(IRI extractor, IRI target)
		throws Exception
	{
		return execSelect(SparqlHelper.generateGroupQuery2(extractor, target));		
	}
	*/
	/*
	private Model queryGroupTriplesModel(IRI extractor, IRI target)
	{
		return execConstruct(SparqlHelper.generateGroupQuery(extractor, target));		
	}
	* /
	private List<QuerySolution> queryExistingTriples2(Set<RDFTriple> triples)
		throws Exception	
	{
		return execSelect(SparqlHelper.generateExistingTriplesQueryUnion(triples));		
	}

	/*
	private Set<RDFTriple> queryExistingTriples(Set<RDFTriple> triples)
	{
		return queryTriples(SparqlHelper.generateExistingTriplesQuery(triples));		
	}
*/
	/*
	private Set<RDFTriple> queryGroupTriples(IRI extractor, IRI target)
	{
		return queryTriples(SparqlHelper.generateGroupQuery(extractor, target));		
	}
	 */
	/*
	private void execUpdate(Collection<String> queries)
		throws Exception	
	{
		logger.trace("[UpdateBlock] queryCount = " + queries.size() + "\n");
		StopWatch sw = new StopWatch();
		sw.start();

		for(String query : queries)
			execUpdate(query);

		sw.stop();		
		logger.trace("[UpdateBlock] total time: " + sw.getTime() + "ms");		
	}
	
	private boolean execAsk(Collection<String> queries)
	{
		logger.trace("[UpdateBlock] queryCount = " + queries.size() + "\n");
		StopWatch sw = new StopWatch();
		sw.start();

		boolean result = true;
		
		for(String query : queries)
			result = result && execAsk(query);

		sw.stop();		
		logger.trace("[UpdateBlock] total time: " + sw.getTime() + "ms");
		
		return result;
	}
	
	private boolean execAsk(String query)
	{
		boolean result = false;

		if(query == null)
			return result;
		
		logger.trace("Query =\n" + query);
		StopWatch sw = new StopWatch();
		sw.start();

		try {
			result = metaSparqlExecutor.executeAsk(query);
		} catch(Exception e) {
			logger.error(MyCommonHelper.exceptionToString(e));
		}

		sw.stop();		
		logger.trace("Query took: " + sw.getTime() + "ms");
		
		return result;
	}
	
	private void execUpdate(String query)
		throws Exception
	{
		if(query == null)
			return;

		logger.trace("Query =\n" + query);
		StopWatch sw = new StopWatch();
		sw.start();

		//try {
			metaSparqlExecutor.executeUpdate(query);
		//} catch(Exception e) {
		//	logger.error(MyCommonHelper.exceptionToString(e));
		//}

		sw.stop();		
		logger.trace("Query took: " + sw.getTime() + "ms");		
	}

	private List<QuerySolution> execSelect(String query)
		throws Exception
	{
		if(query == null)
			return Collections.emptyList();

		logger.trace("Query =\n" + query);
		
		StopWatch sw = new StopWatch();
		sw.start();

		List<QuerySolution> result = Collections.emptyList();
		//try {
			result = metaSparqlExecutor.executeSelect(query);
		//} catch(Exception e) {
		//	logger.error(MyCommonHelper.exceptionToString(e));
		//}
		
		sw.stop();		
		logger.trace("Query took: " + sw.getTime() + "ms");

		return result;
	}
	*/
}


