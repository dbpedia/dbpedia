package oaiReader;

import java.io.File;

import mywikiparser.ast.IWikiNode;

import org.apache.commons.collections15.BidiMap;
import org.apache.commons.collections15.bidimap.DualHashBidiMap;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.junit.Before;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

import util.InitUtil;

public class TBoxTripleGeneratorTest
{
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

	@Test
	public void testExtract()
		throws Exception
	{
		String text = "{{DBpedia ObjectProperty\n" + "| rdfs:label =\n"
				+ "| rdfs:label@de =\n"
				+ "| rdfs:label@en =\n"
				+ "| rdfs:label@fr =\n"
				+
				// "| rdfs:comment = Relates an animal to the place where it was born.\n"
				// +
				"| owl:equivalentProperty =\n" + "| rdfs:seeAlso =\n"
				+ "| rdfs:subPropertyOf =\n"
				+ "| rdfs:domain = Person OR Animal\n" +
				// "| rdfs:subClassOf = SuperClass\n" +
				"| rdfs:range =\n" + "| rdf:type = \n" + "}}\n";
		BidiMap<Integer, String> namespaces = new DualHashBidiMap<Integer, String>();
		namespaces.put(0, "");
		namespaces.put(1, "Media");
		namespaces.put(2, "Special");
		namespaces.put(3, "Talk");
		namespaces.put(4, "User");
		namespaces.put(5, "User_talk");
		namespaces.put(6, "Wikipedia");
		namespaces.put(7, "Wikipedia_talk");
		namespaces.put(8, "Mediawiki");
		namespaces.put(9, "Mediawiki_talk");
		namespaces.put(10, "Template");
		namespaces.put(11, "Template_talk");
		namespaces.put(12, "Help");
		namespaces.put(13, "Help_talk");
		namespaces.put(14, "Category");
		namespaces.put(15, "Category_talk");
		namespaces.put(100, "Portal");
		namespaces.put(101, "Portal_talk");
		namespaces.put(108, "Book");
		namespaces.put(109, "Book_talk");

		
		IWikiNode node = WikiParserHelper.parse(text);
		//String t = "MediaWiki_talk:David_Bowie";
		String t = "DBpedia/ontology/birthplace";
		
		MediawikiTitle title = MediawikiHelper.parseTitle(t, namespaces);

		RecordMetadata metadata = new RecordMetadata("en", title, "oai:12345",
				IRI.create("http://wiki.org"), "12345", "x", "000.000.000.000",
				"");

		/*
		DefaultDbpediaMetadataFilter filter = new DefaultDbpediaMetadataFilter();
		boolean isAccepted = filter.evaluate(metadata);
		System.out.println("isAccepted(" +  title.getFullTitle() + ")? " + isAccepted);
		*/
		
		RecordContent content = new RecordContent(text, "revision", null);
		Record record = new Record(metadata, content);
		record.getContent().getRepresentations().add(node);

		TBoxTripleGenerator generator = new TBoxTripleGenerator(prefixResolver);
		generator.setExprPrefix("http://test.org/innerNode/");

		// MultiMap<URI, Set<RDFTriple>> triples =
		// generator.extract(URI.create("http://test.org/root"), node);
		// System.out.println(triples);

		TBoxExtractor ex = new TBoxExtractor("http://index.org/", null,
				"http://dbpedia.org/", "http://dbpedia.org/meta",
				"http://dbpedia.org/meta/axiom", "http://dbpedia.org/ontology/",
				"http://dbpedia.org/ontology/", prefixResolver);

		ex.handle(record);

		/*
		 * triples.put(null, new HashSet<RDFTriple>());
		 * System.out.println(triples.get(null));
		 * System.out.println(triples.get(URI.create("http://x.org")));
		 */
		/*
		 * for(int i = 0; i < 100; ++i) { StopWatch sw = new StopWatch();
		 * sw.start();
		 * 
		 * MultiMap<URI, Set<RDFTriple>> triples =
		 * generator.extract(URI.create("http://test.org/root"), node);
		 * sw.stop(); System.out.println("Time = " + sw.getTime());
		 * 
		 * System.out.println(triples); }
		 */
	}

}
