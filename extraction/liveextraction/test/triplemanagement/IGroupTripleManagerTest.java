package triplemanagement;

import java.io.File;
import java.sql.Connection;
import java.sql.DriverManager;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import junit.framework.Assert;
import oaiReader.ComplexGroupTripleManager;

import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.junit.Before;
import org.junit.Test;
import org.semanticweb.owlapi.model.IRI;

import sparql.ISparulExecutor;
import sparql.VirtuosoJdbcSparulExecutor;
import test.TripleManagerTest;
import util.InitUtil;

import com.hp.hpl.jena.query.QuerySolution;


public class IGroupTripleManagerTest
{
	private Logger				logger	= Logger
												.getLogger(TripleManagerTest.class);

	private ISparulExecutor		dataExecutor;
	private ISparulExecutor		metaExecutor;
	private IGroupTripleManager	tripleManager;

	private long getTripleCount(ISparulExecutor executor)
		throws Exception
	{
		List<QuerySolution> q = executor
				.executeSelect("Select count(*) {?s ?p ?o}");

		return q.get(0).getLiteral("callret-0").getLong();
	}

	@Before
	public void setUp()
		throws Exception
	{
		Class.forName("virtuoso.jdbc4.Driver");
		
		Ini ini = new Ini(new File("config/test/config.ini"));
		InitUtil.initLoggers(ini);
		
		Section backendSection = ini.get("BACKEND_VIRTUOSO");
		String graphNameData = backendSection.get("graphNameData");
		String graphNameMeta = backendSection.get("graphNameMeta");
		String uri = backendSection.get("uri");
		String username = backendSection.get("username");
		String password = backendSection.get("password");

		Connection connection = DriverManager.getConnection(uri, username,
				password);

		dataExecutor = new VirtuosoJdbcSparulExecutor(connection, graphNameData);
		metaExecutor = new VirtuosoJdbcSparulExecutor(connection, graphNameMeta);

		tripleManager = new ComplexGroupTripleManager(dataExecutor,
				metaExecutor);
	}

	/**
	 * First saves the counts of the data and meta graphs. (currently need to be
	 * distinct). Then inserts a single data triple, which is shared among a set
	 * of sources where a source is defined by a target and a extractor. After
	 * that triples are counted again, then removed, and then counted again.
	 * 
	 * Since only a single data triple is inserted, the number of data triples
	 * may only increase by 1. The number of triples after the test should be
	 * the same as before the test.
	 * 
	 * @param dataExecutor
	 * @param metaExecutor
	 * @throws Exception
	 */
	@Test
	public void testGroups()
		throws Exception
	{
		int groupCount = 3;
		int targetCount = 5;

		long beforeDataCount = getTripleCount(dataExecutor);
		long beforeMetaCount = getTripleCount(metaExecutor);

		/*
		tripleManager = new ComplexGroupTripleManager(
				dataExecutor, metaExecutor);
		*/
		List<TripleSetGroup> groups = new ArrayList<TripleSetGroup>();

		// Common triples
		RDFTriple triple = new RDFTriple(new RDFResourceNode(IRI
				.create("http://test.org/s")), new RDFResourceNode(IRI
				.create("http://test.org/p")), new RDFResourceNode(IRI
				.create("http://test.org/o")));

		for (int i = 0; i < targetCount; ++i) {

			for (int j = 0; j < groupCount; ++j) {
				DBpediaGroupDef g = new DBpediaGroupDef(IRI
						.create("http://test.org/extractor/" + j), IRI
						.create("http://test.org/target/" + i));

				TripleSetGroup tsg = new TripleSetGroup(g);
				tsg.setTriples(Collections.singleton(triple));
				groups.add(tsg);

				tripleManager.update(tsg);
			}
		}

		long middleDataCount = getTripleCount(dataExecutor);
		long middleMetaCount = getTripleCount(metaExecutor);

		// Clear all groups again
		for (TripleSetGroup g : groups) {
			g.setTriples(null);

			tripleManager.update(g);
		}

		long afterDataCount = getTripleCount(dataExecutor);
		long afterMetaCount = getTripleCount(metaExecutor);

		logger.info("Data: Before = " + beforeDataCount + ", After = "
				+ afterDataCount + ", Middle = " + middleDataCount);
		logger.info("Meta: Before = " + beforeMetaCount + ", After = "
				+ afterMetaCount + ", Middle = " + middleMetaCount);

		boolean success = beforeDataCount == afterDataCount
				&& beforeMetaCount == afterMetaCount
				&& middleDataCount == beforeDataCount + 1;

		Assert.assertEquals("Data graph unmodified", beforeDataCount,
				afterDataCount);

		Assert.assertEquals("Mata graph unmodified", beforeMetaCount,
				afterMetaCount);

		Assert.assertEquals("Exactly 1 extra data triple", middleDataCount,
				beforeDataCount + 1);

		logger.info("Success: " + success);
	}
}
