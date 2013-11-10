package de.mannheim.uni.sparql;

import com.hp.hpl.jena.query.Query;
import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QueryFactory;
import com.hp.hpl.jena.query.ResultSet;

public class SPARQLEndpointQueryRunner {
	public final static String DBPEDIA_ENDPOINT = "http://dbpedia.org/sparql";
	public final static String LOCAL_DBPEDIA_ENDPOINT = "http://wifo5-38.informatik.uni-mannheim.de:8890/sparql";

	private String endpoint;

	private String alias;

	private int timeout;

	private int retries;

	private int pageSize;

	private boolean useCount;

	private boolean usePropertyPaths;

	public boolean isUseCount() {
		return useCount;
	}

	public void setUseCount(boolean useCount) {
		this.useCount = useCount;
	}

	public boolean isUsePropertyPaths() {
		return usePropertyPaths;
	}

	public void setUsePropertyPaths(boolean usePropertyPaths) {
		this.usePropertyPaths = usePropertyPaths;
	}

	public String getEndpoint() {
		return endpoint;
	}

	public void setEndpoint(String endpoint) {
		this.endpoint = endpoint;
	}

	public String getAlias() {
		return alias;
	}

	public void setAlias(String alias) {
		this.alias = alias;
	}

	public int getTimeout() {
		return timeout;
	}

	public void setTimeout(int timeout) {
		this.timeout = timeout;
	}

	public int getRetries() {
		return retries;
	}

	public void setRetries(int retries) {
		this.retries = retries;
	}

	public int getPageSize() {
		return pageSize;
	}

	public void setPageSize(int pageSize) {
		this.pageSize = pageSize;
	}

	public SPARQLEndpointQueryRunner(String endpoint, String alias,
			int timeout, int retries, int pageSize, boolean useCount,
			boolean usePropertyPaths) {
		super();
		this.endpoint = endpoint;
		this.alias = alias;
		this.timeout = timeout;
		this.retries = retries;
		this.pageSize = pageSize;
		this.useCount = useCount;
		this.usePropertyPaths = usePropertyPaths;
	}

	public SPARQLEndpointQueryRunner(String endpoint, int timeout, int retries) {
		super();
		this.endpoint = endpoint;
		this.timeout = timeout;
		this.retries = retries;
	}

	public SPARQLEndpointQueryRunner(String endpoint) {
		this.endpoint = endpoint;
		this.timeout = 60 * 1000;
		this.retries = 10;
		this.pageSize = 10000;
	}

	public static SPARQLEndpointQueryRunner getDBpeidaRunner() {
		SPARQLEndpointQueryRunner runner = new SPARQLEndpointQueryRunner(
				DBPEDIA_ENDPOINT);
		runner.setPageSize(10000);
		return runner;
	}

	public static SPARQLEndpointQueryRunner getLocalDBpeidaRunner() {
		SPARQLEndpointQueryRunner runner = new SPARQLEndpointQueryRunner(
				LOCAL_DBPEDIA_ENDPOINT);
		runner.setPageSize(10000);
		return runner;
	}

	public ResultSet runSelectQuery(String query) {
		Query q = QueryFactory.create(query);
		QueryExecution objectToExec = QueryExecutionFactory.sparqlService(
				endpoint, q.toString());
		objectToExec.setTimeout(timeout);
		// retry every 1000 millis if the endpoint goes down
		int localRetries = 0;
		ResultSet results = null;
		while (true) {
			try {
				results = objectToExec.execSelect();
				break;
			} catch (Exception ex) {
				ex.printStackTrace();
				localRetries++;
				// if (localRetries >= retries) {
				// ex.printStackTrace();
				// break;
				// }
				try {
					Thread.sleep(1000);
				} catch (InterruptedException e) {
					e.printStackTrace();
				}
			}
		}

		return results;
	}

	public static Query addOrderByToQuery(String queryStr) {
		Query querQ = QueryFactory.create(queryStr);
		for (String str : querQ.getResultVars()) {
			querQ.addOrderBy(str, 0);
		}
		// remove the prefixes from the subquery
		String prefixes = "";
		String noPrefixQuery = querQ.toString();
		if (querQ.toString().toLowerCase().contains("select")) {
			prefixes = querQ.toString().substring(0,
					querQ.toString().toLowerCase().indexOf("select"));
			if (prefixes.toLowerCase().contains("prefix")) {
				noPrefixQuery = querQ.toString().replace(prefixes, "");

			}
		}
		// add the subquery
		String outsideQuery = "SELECT";
		for (String str : querQ.getResultVars()) {
			outsideQuery += " ?" + str;
		}
		String finalQuery = outsideQuery + " WHERE { {" + noPrefixQuery + "} }";

		querQ = QueryFactory.create(prefixes + finalQuery);
		return querQ;
	}

	public static void main(String[] args) {
		// TODO Auto-generated method stub
		SPARQLEndpointQueryRunner qr = new SPARQLEndpointQueryRunner(
				"http://dbpedia.org/sparql");
		// qr.getSubClasses("http://dbpedia.org/class/yago/Object100002684",
		// new ArrayList<String>());
	}

}
