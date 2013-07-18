package oaiReader;

import helpers.RDFUtil;

import java.io.File;
import java.net.URI;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;

import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.junit.Before;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

import util.InitUtil;

public class RdfHelperTest
{
	private Logger			logger	= Logger
											.getLogger(IPrefixResolverTest.class);
	private IPrefixResolver	prefixResolver;

	@Before
	public void setUp()
		throws Exception
	{
		Ini ini = new Ini(new File("config/test/config.ini"));
		InitUtil.initLoggers(ini);

		Section section = ini.get("NAMESPACE_MAPPING");
		String filename = section.get("filename");

		prefixResolver = new PrefixResolver(new File(filename));
	}

	// @Test(expected=IndexOutOfBoundsException.class)
	@Test
	public void testInterpretMos()
		throws Exception
	{
		Map<String, RDFExpression> testPair = new HashMap<String, RDFExpression>();
		
		/*
		testPair.put(
				"Person",
				new RdfExpression(
						new RDFResourceNode(URI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
	    */
		/*
		testPair.put(
				"Fungus Or Animal Or Plant",
				new RdfExpression(
						new RDFResourceNode(URI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
		*/
		
		testPair.put(
				"cyc:birthPlace",
				new RDFExpression(
						new RDFResourceNode(IRI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));

		testPair.put(
				"xsd:integer",
				new RDFExpression(
						new RDFResourceNode(IRI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
/*
		testPair.put(
				"cyc:birthPlace AND owl:blubb",
				new RDFExpression(
						new RDFResourceNode(IRI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
		
		testPair.put(
				"blubb:a AND blubb:b",
				new RDFExpression(
						new RDFResourceNode(IRI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
		
		testPair.put(
				"blubb:a",
				new RDFExpression(
						new RDFResourceNode(IRI.create("http://expr/Person")),
						new HashSet<RDFTriple>()));
*/		
		// TODO Figure out how to use some rdf-parser in jena so we can 
		// more easily define the expected result.
		//<http://dbpedia.org/ontology/Person> -> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> -> <http://www.w3.org/2002/07/owl#Class>
		for(Map.Entry<String, RDFExpression> entry : testPair.entrySet()) {
			System.out.println("Input: " + entry.getKey());
			try {

			RDFExpression result = RDFUtil.interpretMos(entry.getKey(),
				prefixResolver, "http://expr", false);
		
			System.out.println("    Result:" + result);
			}
			catch(Exception e) {
				System.out.println(" Throw Exception");
			}
			
			//Assert.assertEquals(result, entry.getValue());
		}		
	}
}
