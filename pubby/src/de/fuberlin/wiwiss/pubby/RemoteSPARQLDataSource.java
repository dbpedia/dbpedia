package de.fuberlin.wiwiss.pubby;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.util.Collections;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.sparql.engine.http.QueryEngineHTTP;

/**
 * A data source backed by a SPARQL endpoint accessed through
 * the SPARQL protocol.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class RemoteSPARQLDataSource implements DataSource {
	private String endpointURL;
	private String defaultGraphName;
	private String previousDescribeQuery;
	
	public RemoteSPARQLDataSource(String endpointURL, String defaultGraphName) {
		this.endpointURL = endpointURL;
		this.defaultGraphName = defaultGraphName;
	}
	
	public String getEndpointURL() {
		return endpointURL;
	}
	
	public String getResourceDescriptionURL(String resourceURI) {
		try {
			StringBuffer result = new StringBuffer();
			result.append(endpointURL);
			result.append("?");
			if (defaultGraphName != null) {
				result.append("default-graph-uri=");
				result.append(URLEncoder.encode(defaultGraphName, "utf-8"));
				result.append("&");
			}
			result.append("query=");
			result.append(URLEncoder.encode("DESCRIBE <" + resourceURI + ">", "utf-8"));
			return result.toString();
		} catch (UnsupportedEncodingException ex) {
			// can't happen, utf-8 is always supported
			throw new RuntimeException(ex);
		}
	}
	
	public Model getResourceDescription(String resourceURI) {
		return execDescribeQuery("DESCRIBE <" + resourceURI + ">");
	}
	
	public Model getAnonymousPropertyValues(String resourceURI, Property property, boolean isInverse) {
		String query = "DESCRIBE ?x WHERE { "
			+ (isInverse 
					? "?x <" + property.getURI() + "> <" + resourceURI + "> . "
					: "<" + resourceURI + "> <" + property.getURI() + "> ?x . ")
			+ "FILTER (isBlank(?x)) }";
		return execDescribeQuery(query);
	}
	
	public String getPreviousDescribeQuery() {
		return previousDescribeQuery;
	}
	
	private Model execDescribeQuery(String query) {
		previousDescribeQuery = query;
		QueryEngineHTTP endpoint = new QueryEngineHTTP(endpointURL, query);
		if (defaultGraphName != null) {
			endpoint.setDefaultGraphURIs(Collections.singletonList(defaultGraphName));
		}
		return endpoint.execDescribe();
	}
}
