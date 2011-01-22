package test;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Random;
import java.util.Set;

import oaiReader.IPrefixResolver;
import oaiReader.TBoxExtractor;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.semanticweb.owlapi.model.IRI;

import sparql.ISparulExecutor;
import triplemanagement.DBpediaGroupDef;
import triplemanagement.IGroupTripleManager;
import triplemanagement.TripleSetGroup;


public class TripleManagerTest
{
	private Logger logger = Logger.getLogger(TripleManagerTest.class);
		
	private ISparulExecutor dataExecutor;
	private ISparulExecutor metaExecutor;
	private IGroupTripleManager tripleManager;
	
	private IPrefixResolver prefixResolver;
    
	
	/**
	 * test the mos parser 
	 * 
	 */
	private void testPropertyDefinitionExtractor(Ini ini)
	{		
		Section extractorSection = ini.get("PROPERTY_DEFINITION_EXTRACTOR");
		String expressionPrefix = extractorSection.get("expressionPrefix");
		String propertyPrefix = extractorSection.get("propertyPrefix");

		Section namespaceMappingSection = ini.get("NAMESPACE_MAPPING");
		String filename = namespaceMappingSection.get("filename");
		
		Section harvesterSection = ini.get("HARVESTER");
		String technicalBaseUri = harvesterSection.get("technicalWikiUri");
		
		
		TBoxExtractor x = null;
		/*
			new TBoxExtractor(
					technicalBaseUri,
					tripleManager,
					propertyPrefix,
					expressionPrefix,
					prefixResolver
					);
		*/
		//x.
		
		Map<String, Set<RDFTriple>> testPair = new HashMap<String, Set<RDFTriple>>();
		//testSet.put("A", "
		//testSet.
		
		
		
	}

    /**
     * Generates random triples for random 'targets' by the same extractor
     * and measures times 
     * @throws Exception 
     * 
     */
    //@Test
    public void stressTest() throws Exception
    {
    	int maxNumTargets = 1000;
    	int maxNumResources = 10000;
    	
    	int maxNumTriples = 80;
    	
    	String targetPrefix = "http://dbpedia.org/target/";
    	String resourcePrefix = "http://dbpedia.org/target/";
    	IRI extractor = IRI.create("http://dbpedia.org/extractors/TestExtractor");
    		
   
    	Random rand = new Random();

    	StopWatch totalSw = new StopWatch();
    	totalSw.start();
    	
    	long totalNumTriples = 0;
    	for(;;) {
    		IRI target = IRI.create(targetPrefix + rand.nextInt(maxNumTargets));

    		DBpediaGroupDef group = new DBpediaGroupDef(extractor, target);
    		TripleSetGroup tsg = new TripleSetGroup(group);
    		tsg.setTriples(new HashSet<RDFTriple>());
    		
    		int n = rand.nextInt(maxNumTriples);
    		for(int i = 0; i < n; ++i) {
    			IRI s = IRI.create(resourcePrefix + rand.nextInt(maxNumResources));
    			IRI p = IRI.create(resourcePrefix + rand.nextInt(maxNumResources));
    			IRI o = IRI.create(resourcePrefix + rand.nextInt(maxNumResources));

    			RDFTriple triple = new RDFTriple(
        				new RDFResourceNode(s),
        				new RDFResourceNode(p),
        				new RDFResourceNode(o));
    			
    			tsg.getTriples().add(triple);
    		}
    		logger.debug("Generated " + n + " triples for extractor " + extractor + " for target " + target);

    		StopWatch sw = new StopWatch();
    		sw.start();
    		tripleManager.update(tsg);
    		sw.stop();
    		totalNumTriples += n;
    		
    		logger.info("Processed " + n + " triples in " + sw.getTime() + "ms - avg = " + (n / (double)(sw.getTime() / 1000.0f)));    		
    		logger.info("Processed a total of " + totalNumTriples + " triples in " + totalSw.getTime() + "ms - avg = " + (totalNumTriples / (double)(totalSw.getTime() / 1000.0f)));    		
    	}
    }
    
    
    /**
     * Inserts a set of triples and checks if they are really there
     * 
     * 
     */
    //@Test
    /*
    public void testInsert()
    {
    	RDFResourceNode r[] = {
    			new RDFResourceNode(IRI.create("http://www.ex.org/a")),
    			new RDFResourceNode(IRI.create("http://www.ex.org/b")),
    			new RDFResourceNode(IRI.create("http://www.ex.org/c")),
    			new RDFResourceNode(IRI.create("http://www.ex.org/d")),
    			new RDFResourceNode(IRI.create("http://www.ex.org/e")),
    			new RDFResourceNode(IRI.create("http://www.ex.org/f"))
    	};
    	
    	Set<RDFTriple> tripleSetA = new HashSet<RDFTriple>();
    	tripleSetA.add(new RDFTriple(r[0], r[1], r[2]));
    	tripleSetA.add(new RDFTriple(r[1], r[2], r[3]));
    	tripleSetA.add(new RDFTriple(r[2], r[3], r[4]));
    	tripleSetA.add(new RDFTriple(r[3], r[4], r[5]));
    	tripleSetA.add(new RDFTriple(r[4], r[5], r[6]));
    	tripleSetA.add(new RDFTriple(r[5], r[6], r[7]));
    	
    	TripleSetGroup tsgA = new TripleSetGroup(IRI.create("http://extractor.org"), IRI.create("http://target.org"));
		tsgA.setTriples(tripleSetA);
		
		sys.update(tsgA);
    }
*/
}
