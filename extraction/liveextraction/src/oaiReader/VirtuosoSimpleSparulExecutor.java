package oaiReader;

import helpers.SQLUtil;

import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Collection;
import java.util.HashSet;
import java.util.Iterator;
import java.util.List;
import java.util.Set;

import org.coode.owlapi.rdf.model.RDFTriple;

import sparql.ISparulExecutor;
import virtuoso.jdbc4.VirtuosoExtendedString;
import virtuoso.jdbc4.VirtuosoRdfBox;
import virtuoso.jdbc4.VirtuosoResultSet;

import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.QuerySolutionMap;
import com.hp.hpl.jena.rdf.model.AnonId;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;



public class VirtuosoSimpleSparulExecutor
	implements ISparulExecutor
{
	private ISimpleConnection con;
	private String graphName;


	public VirtuosoSimpleSparulExecutor(String graphName)
	{
		this.graphName = graphName;
	}

	public VirtuosoSimpleSparulExecutor(ISimpleConnection con, String graphName)
	{
		this.con = con;
		this.graphName = graphName;
	}

	public VirtuosoSimpleSparulExecutor(ISimpleConnection con)
	{
		this.con = con;
	}


	public void setConnection(ISimpleConnection connection)
	{
		this.con = connection;
	}

	public ISimpleConnection getConnection()
	{
		return con;
	}


	@Override
	public void executeUpdate(String query)
		throws Exception
	{				
		query = processRawQuery(query);		
		con.update(query);
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
		return SQLUtil.single(con.query(query), Boolean.class);
	}



	@Override
	public List<QuerySolution> executeSelect(String query)
		throws Exception
	{
		query = processRawQuery(query);
	
		VirtuosoResultSet rs = (VirtuosoResultSet)con.query(query);
		
		return virtuosoResultSetToJena(rs);
	}

	@Override
	public String getGraphName()
	{
		return graphName;
	}

	@Override
	public boolean insert(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		if(triples.size() == 0)
			return true;
		
		String ntriples = SparqlHelper.toNTriples(triples);
	
		String query =
			"DB.DBA.TTLP_MT ('" + ntriples + "', '" + graphName + "', '" + graphName + "', 255)";
	
		return con.update(query);
	}
	
	
	private boolean _remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{		
		String query =
			"Delete\n" +
				(graphName == null ? "" : "From <" + graphName + ">\n") +
			"{" +
				SparqlHelper.toNTriples(triples) + "\n" +
			"}";
	
		//System.out.println(query);
		executeUpdate(query);
		return true;
	}
	
	@Override
	public boolean remove(Collection<RDFTriple> triples, String graphName)
		throws Exception
	{
		if(triples.size() == 0)
			return true;

		Iterator<RDFTriple> it = triples.iterator();
		int counter = 0;
		Set<RDFTriple> pack = new HashSet<RDFTriple>();
		while(it.hasNext()) {
			++counter;
			RDFTriple triple = it.next();
			pack.add(triple);
			
			if(counter % 100 == 0 || it.hasNext() == false) {
				_remove(pack, graphName);
				counter = 0;
				pack.clear();
			}
		}
		
		return true;
	}
	
	private List<QuerySolution> virtuosoResultSetToJena(VirtuosoResultSet rs)
		throws SQLException
	{
		List<QuerySolution> result = new ArrayList<QuerySolution>();

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

		return result;
	}	
}