package oaiReader;

import helpers.EqualsUtil;

import java.io.File;
import java.util.HashMap;
import java.util.Map;

import junit.framework.Assert;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.junit.Before;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

import util.InitUtil;


public class IPrefixResolverTest
{
	private Logger logger = Logger.getLogger(IPrefixResolverTest.class);
	private IPrefixResolver prefixResolver;


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
	public void testResolve()
	{
		Map<String, String> testPair = new HashMap<String, String>();
		testPair.put("foaf:Person", "http://xmlns.com/foaf/0.1/Person");
		testPair.put("cyc:birthPlace", "http://sw.opencyc.org/concept/birthPlace");
		testPair.put("owl:FunctionalProperty", "http://www.w3.org/2002/07/owl#FunctionalProperty");
		testPair.put("http://someabsolute.uri", null);
	
		
		for(Map.Entry<String, String> entry : testPair.entrySet()) {
			IRI uri = prefixResolver.transform(entry.getKey());
			
			String got = uri == null ? null : uri.toString();
			
			boolean success = EqualsUtil.equals(got, entry.getValue());
			
			logger.info(
					"success: " + success + ", " +
					"resolve: '" + entry.getKey() + "', " +
					"expected: '" + entry.getValue() + "', " +
					"got: '" + got + "'"
			);
			
			Assert.assertEquals(entry.getValue(), got);
		}
	}
}
