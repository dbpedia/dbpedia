package test;

import helpers.RDFUtil;

import java.io.File;

import oaiReader.IPrefixResolver;
import oaiReader.PrefixResolver;
import oaiReader.RDFExpression;
import triplemanagement.IGroupTripleManager;


/*
 * TODO Write a test case for non-http uris. 
 * Construct
{
?b <http://dbpedia.org/meta/memberOf> ?g .
}
{
?b <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#Axiom> .
?b <http://www.w3.org/2002/07/owl#annotatedSource> ?s .
?b <http://www.w3.org/2002/07/owl#annotatedProperty> ?p .
?b <http://www.w3.org/2002/07/owl#annotatedTarget> ?o .
?b <http://dbpedia.org/meta/memberOf> ?g .
?g <http://dbpedia.org/meta/origin> <http://dbpedia.org/meta/PropertyDefinitionExtractor> .
?g <http://dbpedia.org/meta/oaiidentifier> <oai:localhost:DbpediaTestWiki:27> 
}

 */


public class Test
{
	private static IGroupTripleManager tripleManager;
	
	public static void test1()
		throws Exception
	{ 
		IPrefixResolver prefixResolver = new PrefixResolver(new File("config/meta/NamespaceMapping.ini"));
	 	RDFExpression expr = RDFUtil.interpretMos("Personö or ", prefixResolver, "http://expr", true);
	 	System.out.println(expr.getTriples());
	}

	
	public static void main(String[] args)
		throws Exception
	{
		test1();
	}
	
	
	/**
	 * A test case which adds random to triples to multiple groups.
	 * Then removes the triples from the groups again.
	 * Afterwards, the total count of triples before and
	 * after the test should be equal again.
	 * 
	 */
	public static void testGroups()
	{
		
	}
}
