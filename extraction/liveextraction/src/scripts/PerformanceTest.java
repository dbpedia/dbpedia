package scripts;

import helpers.CollectionUtil;
import helpers.ExceptionUtil;
import helpers.SQLUtil;
import helpers.StringUtil;
import helpers.TripleUtil;

import java.io.File;
import java.sql.Blob;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Random;
import java.util.Set;

import oaiReader.JdbcSimpleConnection;
import oaiReader.VirtuosoSimpleSparulExecutor;

import org.apache.commons.lang.time.StopWatch;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

import collections.SetDiff;

import com.hp.hpl.jena.query.QuerySolution;



public class PerformanceTest
{
	private static Logger logger = Logger.getLogger(PerformanceTest.class);
	
	private Connection connection;
	private static final String testTableName = "test";
	
	private static final String graphName = "http://test.org";
	private VirtuosoSimpleSparulExecutor executor = null;

	
	private void dropTable(String name)
		throws Exception
	{
		executor.executeUpdate("Clear Graph <" + graphName + ">");
		
		String query = "DROP TABLE " + testTableName;

		connection.createStatement().execute(query);
	}
	
	private void dropDataTable() throws Exception
	{
		dropTable(testTableName);
	}
	
	private void createDataTable() throws SQLException
	{
		String query =
			"CREATE TABLE " + testTableName + " (\n" +
			"\tid INT PRIMARY KEY NOT NULL,\n" +
			"\tdata LONG VARBINARY\n" +
			")";
		logger.info(query);
		connection.createStatement().execute(query);
	}
	
	
	public PerformanceTest(Connection connection)
		throws Exception
	{
		this.connection = connection;


		executor = new VirtuosoSimpleSparulExecutor(
				new JdbcSimpleConnection(connection), graphName);
		
		this.init();
	}
	
	private void init()
		throws Exception
	{
		logger.debug("Initializing");
		try {
			logger.debug("Dropping table");
			dropDataTable();
		} catch(Exception e) {
			logger.warn(ExceptionUtil.toString(e));
		}
		
		try {
			logger.debug("Creating table");
			createDataTable();
		} catch(Exception e) {
			logger.warn(ExceptionUtil.toString(e));
		}
		
		//runTest();
	}
	
	
	private Set<RDFTriple> getExisting(int pageId)
		throws Exception
	{
		// Fetch data from the database
		String query = "SELECT data FROM " + testTableName + " WHERE id = " + pageId;
		
		ResultSet rs = connection.createStatement().executeQuery(query);

		Object o = SQLUtil.single(rs, Object.class);
		byte data[];
		if(o instanceof Blob) {
			Blob blob = (Blob)o;
			data = blob == null ? null : blob.getBytes(1, (int)blob.length());
		}
		else
			data = (byte[])o;
			
		//System.out.println("" + o);
		//Blob blob = SQLUtil.single(rs, Blob.class);
		//byte[] data = SQLUtil.single(rs, byte[].class);
		//byte[] data = null;
		
		//System.out.println("Got = " +  (blob == null ? "null" : blob.getClass()));
		//System.out.println("Got = " +  (data == null ? "null" : data.getClass()));

		return data == null ? null : TripleUtil.unzip(data);
	}
	
	
	private void update(int pageId, Set<RDFTriple> existing, Set<RDFTriple> triples)
		throws Exception
	{
		String str = TripleUtil.toCanonicalString(triples);
		byte[] newData = StringUtil.zip(str);

		if(existing == null) {
			System.out.println("Insert: pageId = "  + pageId + " tripleCount = " + triples.size() + " unzippedLength = " + str.length() + " zippedLength = " +  newData.length);

			String sql = "INSERT INTO " + testTableName + " VALUES (:1, :2)";
			
			PreparedStatement stmt = connection.prepareStatement(sql);
			stmt.setInt(1, pageId);
			stmt.setBytes(2, newData);
			stmt.execute();

			executor.insert(triples, graphName);
		}
		else {
			
			//Set<RDFTriple> existing = Collections.emptySet();
			
			SetDiff<RDFTriple> diff = CollectionUtil.diff(existing, triples);
			
			executor.remove(diff.getRemoved(), graphName);
			executor.insert(diff.getAdded(), graphName);

			System.out.println("Update: pageId = "  + pageId + " addCount = " + diff.getAdded().size() + " removeCount = " + diff.getRemoved().size() + " retainedCount = " + diff.getRetained().size() + " unzippedLength = " + str.length() + " zippedLength = " +  newData.length);
			
			String sql = "UPDATE " + testTableName + " SET data = :2 WHERE id = :1";
			PreparedStatement stmt = connection.prepareStatement(sql);
			stmt.setInt(1, pageId);
			stmt.setBytes(2, newData);
			stmt.execute();
		}
	}
	
	
	private int getTripleCount()
		throws Exception
	{
		List<QuerySolution> qss = executor.executeSelect("Select COUNT(*) FROM <" + graphName + "> {?s ?p ?o}");
		
		return Integer.parseInt(qss.get(0).get("?callret-0").toString()); 
		//System.out.println("Triples in graph: " + );
	}


	private void update(int pageId, Set<RDFTriple> triples)
		throws Exception
	{		
		Set<RDFTriple> existing = getExisting(pageId); 
	
		update(pageId, existing, triples);
	}

	private void updateRandom(int pageId)
		throws Exception
	{
		Random rand = new Random();
		Set<RDFTriple> existing = getExisting(pageId);
		
		Set<RDFTriple> updates = null;
		
		if(existing != null)
			updates = new HashSet<RDFTriple>(existing);
		else
			updates = TripleUtil.randomTripleSet(rand.nextInt(2000)); 
		
		
		Iterator<RDFTriple> it = updates.iterator();
		while(it.hasNext()) {
			it.next();
			if(rand.nextInt(100) < 10)
				it.remove();
		}
		
		updates.addAll(TripleUtil.randomTripleSet(rand.nextInt(100)));

		update(pageId, existing, updates);
	}
	
	
	private void runTest()
		throws Exception
	{
		Random rand = new Random();
		StopWatch sw = new StopWatch();
		for(;;) {
			int pageId = rand.nextInt(1000);
			
			//Set<RDFTriple> tripleSet = TripleUtil.randomTripleSet(rand.nextInt(2000));

			sw.start();
			//update(pageId, tripleSet);
			
			System.out.println("Affected page id = " + pageId);
			updateRandom(pageId);
			
			sw.stop();
			System.out.println(sw.getTime() + "ms");
			sw.reset();
		}
	}
	
	
	public static void main(String[] args)
		throws Exception
	{
		Ini ini = new Ini(new File("src/scripts/config.ini"));
		Section section = ini.get("BENCHMARK");

		String connectionIRI = section.get("uri");
		String username = section.get("username");
		String password = section.get("password");
	
		/*
		String driverName = "virtuoso.jdbc4.Driver";
		Class.forName("com.jamonapi.proxy.JAMonDriver");
		String url = "jdbc:jamon:oracle:thin:@dbms:1521:DBjamonrealdriver=" + driverName;
		Connection connection = DriverManager.getConnection(connectionIRI, username, password);
		*/
		
		Class.forName("virtuoso.jdbc4.Driver").newInstance();
		Connection connection = DriverManager.getConnection(connectionIRI, username, password);
	
		//connection = MonProxyFactory.monitor(connection);

		logger.info("Benchmark started");
		
		PerformanceTest test = new PerformanceTest(connection);
		
		test.runTest();
	}
}
