package de.fuberlin.wiwiss.pubby;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;

/**
 * A source of RDF data intended for publication through
 * the server.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public interface DataSource {

	String getEndpointURL();
	
	String getResourceDescriptionURL(String resourceURI);
	
	Model getResourceDescription(String resourceURI);
	
	Model getAnonymousPropertyValues(String resourceURI, Property property, boolean isInverse);
}
