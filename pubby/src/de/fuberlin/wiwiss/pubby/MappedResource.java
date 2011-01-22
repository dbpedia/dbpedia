package de.fuberlin.wiwiss.pubby;

import com.hp.hpl.jena.rdf.model.Property;

/**
 * A resource that is mapped between the SPARQL dataset and the Web server.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class MappedResource {
	private final String relativeWebURI;
	private final String datasetURI;
	private final Configuration serverConfig;
	private final Dataset datasetConfig;
	
	public MappedResource(String relativeWebURI, String datasetURI, 
			Configuration config, Dataset dataset) {
		this.relativeWebURI = relativeWebURI;
		this.datasetURI = datasetURI;
		this.serverConfig = config;
		this.datasetConfig = dataset;
	}

	/**
	 * @return The dataset which contains the description of this resource
	 */
	public Dataset getDataset() {
		return datasetConfig;
	}
	
	/**
	 * @return the resource's URI within the SPARQL dataset
	 */
	public String getDatasetURI() {
		return datasetURI;
	}
	
	/**
	 * @return the resource's URI on the public Web server
	 */
	public String getWebURI() {
		return serverConfig.getWebApplicationBaseURI() + 
				datasetConfig.getWebResourcePrefix() + relativeWebURI;
	}
	
	/**
	 * @return the HTML page describing the resource on the public Web server
	 */
	public String getPageURL() {
		return serverConfig.getWebApplicationBaseURI() + "page/" + relativeWebURI;
	}
	
	/**
	 * @return the RDF document describing the resource on the public Web server
	 */
	public String getDataURL() {
		return serverConfig.getWebApplicationBaseURI() + "data/" + relativeWebURI;
	}
		
	public String getPathPageURL(Property property) {
		return getPathURL("pathpage/", property);
	}
	
	public String getPathDataURL(Property property) {
		return getPathURL("pathdata/", property);
	}
	
	public String getInversePathPageURL(Property property) {
		return getPathURL("pathpage/-", property);
	}
	
	public String getInversePathDataURL(Property property) {
		return getPathURL("pathdata/-", property);
	}
	
	private String getPathURL(String urlPrefix, Property property) {
		if (serverConfig.getPrefixes().qnameFor(property.getURI()) == null) {
			return null;
		}
		return serverConfig.getWebApplicationBaseURI() + urlPrefix +
				serverConfig.getPrefixes().qnameFor(property.getURI()) + "/" +
				relativeWebURI;
	}
}
