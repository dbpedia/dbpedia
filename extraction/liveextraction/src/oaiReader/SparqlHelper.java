package oaiReader;

import static oaiReader.MyVocabulary.DBM_EXTRACTED_BY;
import static oaiReader.MyVocabulary.DBM_GROUP;
import static oaiReader.MyVocabulary.DBM_MEMBER_OF;
import static oaiReader.MyVocabulary.DBM_TARGET;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_PROPERTY;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_SOURCE;
import static oaiReader.MyVocabulary.OWL_ANNOTATED_TARGET;
import static oaiReader.MyVocabulary.OWL_AXIOM;
import static org.semanticweb.owlapi.vocab.OWLRDFVocabulary.RDF_TYPE;

import java.util.Collection;
import java.util.Collections;
import java.util.Iterator;
import java.util.Set;

import org.coode.owlapi.rdf.model.RDFLiteralNode;
import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.model.IRI;

public class SparqlHelper
{
	// might be superseded by ...WithUnion
	public static String generateExistingTriplesQuery2(Set<RDFTriple> triples)
	{
		if(triples.size() == 0)
			return null;
		

		String filterExpression =
			"Filter(\n" +
				generateFilterExpression(triples) +		
			"\n) .\n";
		
		//String result = "Select ?s ?p ?o\n";
		String result =
			"Select ?s ?p ?o ?x\n" +
			"{\n" +
				"?s ?p ?o .\n" +
				
				// Optionally reified statement
				"Optional {\n" + 
					"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
					"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
					"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				
					// Optionally group membership
					"Optional {\n" + 
						//"?x <" + DBP_MEMBER_OF + "> ?b .\n" +
						"?b <" + DBM_MEMBER_OF + "> ?x .\n" +
					"}\n" + 
				
				"}\n" +	
				filterExpression +
			"}\n";
		
		return result;
	}	
	
	
	/**
	 * Merge the two uris into a single one
	 * 
	 * 
	 * @param extractor
	 * @param target
	 */
	/*
	public static IRI combineExtractorAndTarget(IRI extractor, IRI target)
		throws Exception
	{
		String prefix = "http://dbp.org/special/";
		String combined =
			URLEncoder.encode(extractor.toString() + "/" + target.toString(), "UTF-8");
		return IRI.create(prefix + combined);
	}
	*/

	public static String getGroup(IRI extractor, IRI target)
	{
		return "";
	}
	
	// new function 
	// should replace - other function times out, lets try this
	public static String generateExistingTriplesQueryUnion(Set<RDFTriple> triples)
	{
		if(triples.size() == 0)
			return null;

		String result =
			"Select ?s ?p ?o ?x\n";
		
		Iterator<RDFTriple> it = triples.iterator();
		//for(RDFTriple triple : triples)  {
		
		result += "{\n";
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String filterExpression =
					generateFilterExpression(Collections.singleton(triple));
			
			//String result = "Select ?s ?p ?o\n";
			result +=
				"{\n" +
					"?s ?p ?o .\n" +
					
					// Optionally reified statement
					"Optional {\n" + 
						"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
						"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
						"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
						"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
					
						// Optionally group membership
						"Optional {\n" + 
							//"?x <" + DBP_MEMBER_OF + "> ?b .\n" +
							"?b <" + DBM_MEMBER_OF + "> ?x .\n" +
						"}\n" + 
					
					"}\n" +	
					"Filter(" + filterExpression + ") .\n" +
				"}\n";
			
			if(it.hasNext())
				result += "Union ";
		}
		result += "}\n";
		
		return result;
	}	
	
	
	
	//NOT USED ANYMORE
	private static String generateExistingTriplesQuery(Set<RDFTriple> triples)
	{
		if(triples.size() == 0)
			return null;
		
		Iterator<RDFTriple> it = triples.iterator();		
		//String result = "Select ?s ?p ?o\n";
		String result =
			"Construct {\n" +
			"?s ?p ?o .\n" +
			"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
			"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
			"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
			"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
			"?b ?c ?d .\n" +
			"?g ?h ?i .\n" +
			"}\n";

		result += "{\n";
		
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
			
			result +=
				"{\n" +
				"?s ?p ?o .\n" +
				
				// Optionally reified statement
				"Optional {\n" + 
					"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
					"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" + 
					"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
					"?b ?c ?d .\n" +
				
					// Optionally group membership
					"Optional {\n" + 
						"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
						"?g ?h ?i .\n" +
					"}\n" + 
				
				"}\n" +

				
				"Filter(" + generateCondition("s", triple.getSubject()) + ") .\n" + 
				"Filter(" + generateCondition("p", triple.getProperty()) + ") .\n" + 
				"Filter(" + generateCondition("o", triple.getObject()) + ") .\n" + 
				
				//"Filter(?s = " + s + "). \n" +
				//"Filter(?p = " + p + "). \n" +
				//"Filter(?o = " + o + ") .\n" +
				"}\n";
			
			if(it.hasNext())
				result += "Union\n";
		}
		
		result += "}\n";

		
		return result;
	}

	
	// Not needed anymore with single uri
	public static String generateGroupIfNotExistsQuery(
			IRI extractor, IRI target, String graph)
	{
		String e = "<" + extractor.toString() + ">";
		String t = "<" + target.toString() + ">";

		//String  x = "<" + combineExtractorAndTarget(extractor, target) + "> ";
		
		String intoPart = graph == null
		? ""
		: "Into <" + graph + "> \n";
		
		return
			"Insert\n" +
				intoPart +
			"{\n" +
				"_:g <" + RDF_TYPE + "> <" + DBM_GROUP + "> .\n" + 
				"_:g <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				"_:g <" + DBM_TARGET + "> " + t + " .\n" +
			"}\n" +
			"{\n" + 
				"Optional {\n" + 
					"?b <" + RDF_TYPE + "> <" + DBM_GROUP + "> .\n" +
					"?b <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
					"?b <" + DBM_TARGET + "> " + t + " .\n" +
				"}\n" +
				"Filter(!Bound(?b)) .\n" +		
			"}\n";
	}
	
	/*
	public static String generateFullInsertQuery(
			Set<RDFTriple> triples, IRI extractor, IRI target, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return null;

		String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
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
			
			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
			
			String b = "_:b" + (++i);
			
			result +=
				s + " " + p + " " + o + " .\n" +
				
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				b + " <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
				b + " <" + OWL_ANNOTATED_PROPERTY +  "> " + p + " .\n" +
				b + " <" + OWL_ANNOTATED_TARGET +  "> " + o + " .\n" +
				
				//x + " <" + DBP_MEMBER_OF + "> " + b + " .\n";
				b + " <" + DBM_MEMBER_OF + "> " + x + " .\n";
		}
		result += "}\n";
		
		return result;
	}
*/


	
	public static String generateFilterExpression(Set<RDFTriple> triples)
	{
		String result = "";

		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			result += "(" +
				generateCondition("s", triple.getSubject()) + " && " + 
				generateCondition("p", triple.getProperty()) + " && " + 
				generateCondition("o", triple.getObject()) + ")"; 
			
			
			if(it.hasNext())
				result += " || \n";
		}
		
		return result;
	}
	
	

	public static String generateFullRemoveQuery(
			Set<RDFTriple> triples, String graph) //, IRI extractor, IRI target, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return null;

		//String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
		String intoPart = graph == null
			? ""
			: "From <" + graph + "> \n";
		
		String result =
			"Delete\n" +
				intoPart +
			"{\n"; 

		// generate insert pattern
		int i = 0;
		for(RDFTriple triple : triples) {
			
			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
			
			String b = "_:b" + (++i);
			String x = "_:x" + (++i);
			
			result +=
				s + " " + p + " " + o + " .\n" +
				
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				b + " <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
				b + " <" + OWL_ANNOTATED_PROPERTY +  "> " + p + " .\n" +
				b + " <" + OWL_ANNOTATED_TARGET +  "> " + o + " .\n" +
				
				//x + " <" + DBP_MEMBER_OF + "> " + b + " .\n";
				b + " <" + DBM_MEMBER_OF + "> " + x + " .\n";
		}
		result += "}\n";
		
		return result;
	}
	
	// Note used
	public static String generateFullRemoveQueryAgainOld(
			Set<RDFTriple> triples, String graph)
	{
		if(triples.size() == 0)
			return null;

		String fromPart = graph == null ? "" : "From <" + graph + ">\n";

		String filterExpression =
			"Filter(\n" +
				generateFilterExpression(triples) +
			"\n) .\n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?s ?p ?o .\n" +
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +
	
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
			"}\n" +
			"{\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +		
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +

				filterExpression +
			"}\n";
				
		
		return result;	
	}
	
	
	// should replace - other function times out, lets try this
	public static String generateFullRemoveQueryUnion(Set<RDFTriple> triples, String graph)
	{
		if(triples.size() == 0)
			return null;

		String fromPart = graph == null ? "" : "From <" + graph + ">\n";

		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				"?s ?p ?o .\n" +
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +
	
				//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
			"}\n";
		
		Iterator<RDFTriple> it = triples.iterator();		
		result += "{\n";
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String filterExpression =
					generateFilterExpression(Collections.singleton(triple));
			
			//String result = "Select ?s ?p ?o\n";
			result +=
				"{\n" +
					"?s ?p ?o .\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" +
					"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
					"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" +
		
					//"?g <" + DBP_MEMBER_OF + "> ?b .\n" +
					"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
					
					"Filter(" + filterExpression + ") .\n" +	
				"}\n";
			
			if(it.hasNext())
				result += "Union ";
		}
		result += "}\n";
		
		return result;
	}	
	
	
	/*
	public static String generateGroupRemoveQuery(
			Set<RDFTriple> triples, IRI extractor, IRI target, String graph)
		throws Exception
	{
		if(triples.size() == 0)
			return null;
		
		String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
		String fromPart = graph == null ? "" : "From <" + graph + ">\n";
		
		String result =
			"Delete\n" +
				fromPart +
			"{\n" +
				//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> " + x + " .\n" +
			"}\n" +
			"{\n";
			
		
		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
		
			result +=
				"{\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +				
					"?b <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
					"?b <" + OWL_ANNOTATED_PROPERTY + "> " + p + " .\n" +
					"?b <" + OWL_ANNOTATED_TARGET + "> " + o + " .\n" +
				"}\n";

			if(it.hasNext())
				result += "Union\n";
		}

		result += "}\n";
		
		return result;
	}	
	*/

	/*
	// the group is assumed to exist - so only the membership
	// triple needs to be generated
	public static String generateGroupInsertQuery(
			Set<RDFTriple> triples, IRI extractor, IRI target, String graph) throws Exception
	{
		if(triples.size() == 0)
			return null;
		
		String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
		String intoPart = graph == null ? "" : "Into <" + graph + ">\n";
		
		String result =
			"Insert\n" +
				intoPart +
			"{\n" +
				//x + " <" + DBP_MEMBER_OF + "> ?b .\n" +
				"?b <" + DBM_MEMBER_OF + "> " + x + " .\n" +
			"}\n" +
			"{\n";
			
		
		Iterator<RDFTriple> it = triples.iterator();
		while(it.hasNext()) {
			RDFTriple triple = it.next();

			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
		
			result +=
				"{\n" +
					"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
					"?b <" + OWL_ANNOTATED_SOURCE + "> " + s + " .\n" +
					"?b <" + OWL_ANNOTATED_PROPERTY + "> " + p + " .\n" +
					"?b <" + OWL_ANNOTATED_TARGET + "> " + o + " .\n" +
				"}\n";

			if(it.hasNext())
				result += "Union\n";
		}

		result += "}\n";
		
		return result;
	}	
	*/

	// I think this is wrong doing it without union
	// not needed anymore
	public static String generateGroupInsertQueryOld(
			Set<RDFTriple> triples, IRI extractor, IRI target, String graph)
	{
		if(triples.size() == 0)
			return null;
		
		String e = "<" + extractor.toString() + ">";
		String t = "<" + target.toString() + ">";

		String intoPart = graph == null
		? ""
		: "Into <" + graph + "> \n";
		
		String updatePart = "";
		String conditionPart = "";

		int i = 0;
		for(RDFTriple triple : triples) {
			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
			
			String g = "_:g" + i;
			
			updatePart +=
				g + " <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				g + " <" + DBM_TARGET + "> " + t + " .\n" +
				"\n";
			

			String b = "_:b" + i;
			
			conditionPart +=
				b + " <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" +
				b + " <" + OWL_ANNOTATED_SOURCE + " " + s + " .\n" +
				b + " <" + OWL_ANNOTATED_PROPERTY + "> " + p + " .\n" +
				b + " <" + OWL_ANNOTATED_TARGET + "> " + o + " .\n" +
				b + " <" + DBM_MEMBER_OF + "> " + g + " .\n" +

				g + " <" + RDF_TYPE + "> <" + DBM_GROUP + "> .\n" +
				"\n";
			
			++i;
		}

		return
			"Insert\n" + 
				intoPart +
			"{\n" + 
				updatePart + 
			"}\n" + 
			"{\n" +
				conditionPart +
			"}\n";
	}
	
/*	
		String conditionPart = "";
		int i = 0;
		for(RDFTriple triple : triples) {
			String s = toSparqlString(triple.getSubject());
			String p = toSparqlString(triple.getProperty());
			String o = toSparqlString(triple.getObject());
			
			String g = "_:g" + i;
			
			updatePart +=
				g + " " + DBP_EXTRACTED_BY + " " + e + " .\n" + 
				g + " " + DBP_TARGET + " " + t + " .\n";
			
			++i;
		}
		
		
		// generate condition pattern
		result +=
			"{\n" +
			"_:g " + RDF_TYPE + " " + DBP_GROUP + " .\n" + 
			"_:g " + DBP_EXTRACTED_BY + " " + extractor.toString() + " .\n" + 
			"_:g " + DBP_TARGET + " " + target.toString() + " .\n" +
			"}";
		
		return result;
	}
*/
	
	
	/*
	public static String generateGroupQuery2(IRI extractor, IRI target) throws Exception
	{
		//String e = "<" + extractor.toString() + ">";
		//String t = "<" + target.toString() + ">";
		String x = "<" + combineExtractorAndTarget(extractor, target) + ">";
		
		String result  =
			"Select ?s ?p ?o ?x\n" +
			"{\n" + 
				//x + " <" + DBP_MEMBER_OF + "> _:b .\n" + 
				"_:b <" + DBM_MEMBER_OF + "> " + x + " .\n" +
				"_:b <" + RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" + 
				"_:b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
				"_:b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"_:b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 
				//"?x <" + DBP_MEMBER_OF + "> _:b .\n" + 
				"_:b <" + DBM_MEMBER_OF + "> ?x .\n" +
			"}\n";
		
		return result;
	}
	*/

	/**
	 * Returns all triples belonging to a certain group
	 * and also returns all other groups which they belong to
	 * 
	 */
	public static String generateGroupQuery2WorkingButSlow(IRI extractor, IRI target)
	{
		String e = "<" + extractor.toString() + ">";
		String t = "<" + target.toString() + ">";
		
		String result  =
			"Select ?s ?p ?o ?e ?t\n" +
			"{\n" + 
				"?s ?p ?o .\n" + 

				"_:b <" + RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" + 
				"_:b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
				"_:b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"_:b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 

				"_:b <" + DBM_MEMBER_OF + "> _:g .\n" + 
 

				"_:g <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				"_:g <" + DBM_TARGET +"> " + t + " .\n" + 

				
				// hope this works - i want all groups for triples which are
				// members of some given group
				"_:b <" + DBM_MEMBER_OF + "> _:f .\n" + 
				"_:f <" + DBM_EXTRACTED_BY + "> ?e .\n" + 
				"_:f <" + DBM_TARGET +"> ?t .\n" +
			"}\n";
		
		return result;
	}

	/**
	 * NOT USED
	 * A query for retrieving all members of a group
	 * 
	 * TODO SIGH - SLOW QUERY
	 * 
	 * 
	 * @param extractor
	 * @param target
	 * @return
	 */
	public static String generateGroupQuery(IRI extractor, IRI target)
	{
		String e = "<" + extractor.toString() + ">";
		String t = "<" + target.toString() + ">";

		String result  =
			"Construct {\n" +
				"?s ?p ?o .\n" +

				"?b <" + RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" +
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 

				"?b <" + DBM_MEMBER_OF + "> ?g .\n" + 
				"?g ?c ?d .\n" + 
			"}\n" + 
			"{\n" + 
				"?s ?p ?o .\n" + 

				"?b <" + RDF_TYPE + "> " + " <" + OWL_AXIOM + "> .\n" + 
				"?b <" + OWL_ANNOTATED_SOURCE + "> ?s .\n" + 
				"?b <" + OWL_ANNOTATED_PROPERTY + "> ?p .\n" +
				"?b <" + OWL_ANNOTATED_TARGET + "> ?o .\n" + 

				"?b <" + DBM_MEMBER_OF + "> ?g .\n" + 
 
				"?g <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				"?g <" + DBM_TARGET +"> " + t + " .\n" + 
				"?g ?c ?d .\n" + 
			"}\n";
		
		return result;
	}
	
	/*
	public static String getExistingTriplesQuery(Set<RDFTriple> triples)
	{
		if(triples.size() == 0)
			return null;
		
		Iterator<RDFTriple> it = triples.iterator();		
		//String result = "Select ?s ?p ?o\n";
		String result =
			"Construct {\n" +
			"?s ?p ?o .\n" +
			"?b <" + OWL_SUBJECT + "> ?s .\n" + 
			"?b <" + OWL_PREDICATE + "> ?p .\n" + 
			"?b <" + OWL_OBJECT + "> ?o .\n" + 
			"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
			"?b ?c ?d .\n" +
			"}\n";

		result += "{\n";
		
		while(it.hasNext()) {
			RDFTriple triple = it.next();
			
			result +=
				"{\n" +
				"?s ?p ?o .\n" +
				
				"Optional {\n" + 
				"?b <" + OWL_SUBJECT + "> ?s .\n" + 
				"?b <" + OWL_PREDICATE + "> ?p .\n" + 
				"?b <" + OWL_OBJECT + "> ?o .\n" + 
				"?b <" + RDF_TYPE + "> <" + OWL_AXIOM + "> .\n" + 
				"?b ?c ?d .\n" +
				"}\n" +

				
				"Filter(?s = " + triple.getSubject() + "). \n" +
				"Filter(?p = " + triple.getProperty() + "). \n" +
				"Filter(?o = " + triple.getObject() + ") .\n" +
				"}\n";
			
			if(it.hasNext())
				result += "Union\n";
		}

		result += "}\n";
		
		return result;
	}
	*/

	/**
	 * 	 * Not needed anymore
	 * Delete a group if it has no members
	 */
	public static String generateDeleteGroupQuery(
			IRI extractor, IRI target, String graph)
	{
		String e = "<" + extractor.toString() + ">";
		String t = "<" + target.toString() + ">";
		
		String fromPart = graph == null
			? ""
			: "From <" + graph + "> \n";
		
		return
			"Delete\n" +
				fromPart +
			"{\n" +
				"?g <" + RDF_TYPE + "> <" + DBM_GROUP + "> .\n" + 
				"?g <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				"?g <" + DBM_TARGET + "> " + t + " .\n" +
			"}\n" +
			"{\n" + 
				"?g <" + RDF_TYPE + "> <" + DBM_GROUP + "> .\n" +
				"?g <" + DBM_EXTRACTED_BY + "> " + e + " .\n" + 
				"?g <" + DBM_TARGET + "> " + t + " .\n" +
				
				"Optional {\n" + 
					"?b <" + DBM_MEMBER_OF + "> ?g .\n" +
				"}\n" +
				"Filter(!Bound(?b)) .\n" +		
			"}\n";
	}
	
	public static String sparqlEscapeLiteral(String literal)
	{
		return literal.replace("\"", "\\\"");
	}
	
	
	public static String generateCondition(String var, RDFNode node)
	{
		if(node.isLiteral())
			return generateLiteralCondition(var, (RDFLiteralNode)node);

		if(node.isAnonymous())
			throw new RuntimeException("Anonymous node not expected here");
		
		RDFResourceNode n = (RDFResourceNode)node;
		
		return "?" + var + " = <" + n.getIRI() + ">";	
	}
	
	public static String generateLiteralCondition(String var, RDFLiteralNode node)
	{
		//if(node.getLang() == null || node.getLang().isEmpty())
		
		String literal = sparqlEscapeLiteral(node.getLiteral());
		String literalPart = ""; 

		
		String lang = node.getLang();
		if(lang != null && !lang.isEmpty()) {
			literalPart = "str(?" + var + ") = \"" + literal + "\"";

			
			lang = sparqlEscapeLiteral(lang.toLowerCase());

			String langPart = 
				"langMatches(lang(?" + var + "), \"" + lang + "\")";
			
			return "(" + literalPart + " && " + langPart + ")";
		}
		else
			literalPart = "?" + var + " = \"" + literal + "\"";
			
		
		IRI datatype = node.getDatatype();
		if(datatype != null && !datatype.toString().isEmpty()) {
			String datatypePart =
				"datatype(?" + var + ") = <" + datatype + ">";
			
			return "(" + literalPart + " && " + datatypePart + ")";
		}
		

		return literalPart;
	}

	public static String toNTriples(Collection<? extends RDFTriple> triples)
	{
		String result = "";
		for (RDFTriple triple : triples) {
			String tmp = SparqlHelper.toSparqlString(triple);
			tmp = tmp.replace("\\", "\\\\");
			tmp = tmp.replace("'", "\\'");

			result += tmp + " .\n";
		}

		return result;
	}
	
	public static String toSparqlString(RDFTriple triple)
	{
		return
			toSparqlString(triple.getSubject()) + " " +
			toSparqlString(triple.getProperty()) + " " +
			toSparqlString(triple.getObject());
	}
	
	public static String toSparqlString(RDFNode node)
	{
		if(node.isAnonymous())
			return "_:a" + node.toString();
		else if(node.isLiteral()) {
			RDFLiteralNode n = (RDFLiteralNode)node;
			
			String literal = sparqlEscapeLiteral(n.getLiteral());
			String result = "\"\"\"" + literal + "\"\"\""; 
						
			if(n.getLang() != null && !n.getLang().isEmpty())
				result += "@" + n.getLang().toLowerCase();
			
			if(n.getDatatype() != null)
				result += "^^" + "<" + n.getDatatype() + ">";
			
			return result;
		}
		else // resource
			return  node.toString();
	}	
	
}
