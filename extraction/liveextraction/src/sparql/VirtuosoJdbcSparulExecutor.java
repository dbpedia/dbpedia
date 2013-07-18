package sparql;

import java.sql.Connection;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.Collection;
import java.util.List;

import oaiReader.SparqlHelper;

import org.coode.owlapi.rdf.model.RDFTriple;

import virtuoso.jdbc4.VirtuosoExtendedString;
import virtuoso.jdbc4.VirtuosoRdfBox;
import virtuoso.jdbc4.VirtuosoResultSet;

import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.QuerySolutionMap;
import com.hp.hpl.jena.rdf.model.AnonId;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;

/**
 * 
 * Implemented using:
 * http://docs.openlinksw.com/virtuoso/VirtuosoDriverJDBC.html
 * @author raven
 *
 */
public class VirtuosoJdbcSparulExecutor
	implements ISparulExecutor
{
	private Connection con;
	private String graphName;
	
	
	public VirtuosoJdbcSparulExecutor(String graphName)
	{
		this.graphName = graphName;
	}
	
	public VirtuosoJdbcSparulExecutor(Connection con, String graphName)
	{
		this.con = con;
		this.graphName = graphName;
	}

	
	public void setConnection(Connection connection)
	{
		this.con = connection;
	}
	
	public Connection getConnection()
	{
		return con;
	}
	
	
	@Override
	public void executeUpdate(String query)
		throws Exception
	{				
		query = processRawQuery(query);
		Statement stmt = con.createStatement();
		stmt.executeUpdate(query);
		//int result = stmt.executeUpdate(query);
		//System.out.println("Result was: " + result);
	}
	
	private String processRawQuery(String query)
	{
		// The order of the following two statements is important :)
		query = query.replace("\\", "\\\\");
		query = query.replace("'", "\\'");

		if(getGraphName() != null)
			query = 
				"define input:default-graph-uri <" + getGraphName() + "> \n" +
				query;
		
		query = "CALL DB.DBA.SPARQL_EVAL('" + query + "', NULL, 0)";

		return query;
	}

	@Override
	public boolean executeAsk(String query)
		throws Exception
	{
		query = processRawQuery(query);
	
		Statement stmt = con.createStatement();
		java.sql.ResultSet rs = stmt.executeQuery(query);
		
		boolean result = false;
		
		while(rs.next())
			result = rs.getBoolean(1);
		
		return result;
	}


	@Override
	public List<QuerySolution> executeSelect(String query)
		throws Exception
	{
		query = processRawQuery(query);

		List<QuerySolution> result = new ArrayList<QuerySolution>();
	
		Statement stmt = con.createStatement();
		VirtuosoResultSet rs = (VirtuosoResultSet)stmt.executeQuery(query);
		
		ResultSetMetaData meta = rs.getMetaData();
		
		Model model = ModelFactory.createDefaultModel();
		
		while(rs.next()) {
			QuerySolutionMap qs = new QuerySolutionMap();

			for(int i = 1; i <= meta.getColumnCount(); ++i) {
				String columnName = meta.getColumnName(i);
				
				Object o = rs.getObject(i);
				// String representing an IRI
				if(o instanceof VirtuosoExtendedString)
				{
					VirtuosoExtendedString vs = (VirtuosoExtendedString) o;
					if (vs.iriType == VirtuosoExtendedString.IRI)
						qs.add(columnName, model.createResource(vs.str));
					else if (vs.iriType == VirtuosoExtendedString.BNODE)
						qs.add(columnName, model.createResource(new AnonId(vs.str)));
				}
				else if(o instanceof VirtuosoRdfBox) // Typed literal
				{
					VirtuosoRdfBox rb = (VirtuosoRdfBox) o;
					if(rb.getType() == null || rb.getType().isEmpty())
						qs.add(columnName, model.createLiteral(rb.rb_box.toString(), rb.getLang()));
					else
						qs.add(columnName, model.createTypedLiteral(rb.rb_box));
				}
				else if(o == null) {
					qs.add(columnName, null);					
				}
				else { // Untyped literal
					//System.out.println("Got type: " + o.getClass());
					qs.add(columnName, model.createLiteral(o.toString()));
				}
			}

			result.add(qs);
		}

		/*
		System.out.println(result);
		for(QuerySolution qs : result) {
			Iterator<String> names = qs.varNames();
			while(names.hasNext()) {
				String name = names.next();
				Object value = qs.get(name);
				System.out.print(name + ": " + value + "(" + value.getClass().getSimpleName() + ")");
			}
			System.out.println("   ");
			System.out.println();
		}
		*/
		
		return result;
	}

	@Override
	public String getGraphName()
	{
		return graphName;
	}

	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
		throws SQLException
	{
		String ntriples = SparqlHelper.toNTriples(triples);

		String query =
			"ttlp('" + ntriples + "', '', '" + graphName + "')";
//		"DB.DBA.TTLP_MT ('" + ntriples + "', '" + graphName + "', '" + graphName + "', 255)";

		//con.
		System.out.println(query);
		Statement stmt = con.createStatement();
		boolean result = stmt.execute(query);
		stmt.close();
		return result;
	}

	@Override
	public boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		throw new RuntimeException("Not implemented yet");
	}
}
