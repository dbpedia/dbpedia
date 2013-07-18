package de.fuberlin.wiwiss.pubby;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;

/**
 * A data source backed by a Jena model.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class ModelDataSource implements DataSource {
	private Model model;

	public ModelDataSource(Model model) {
		this.model = model;
	}

	public String getEndpointURL() {
		return null;
	}
	
	public String getResourceDescriptionURL(String resourceURI) {
		return null;
	}

	public Model getResourceDescription(String resourceURI) {
		Model result = ModelFactory.createDefaultModel();
		addResourceDescription(model.getResource(resourceURI), result);
		return result;
	}
	
	public Model getAnonymousPropertyValues(String resourceURI,
			Property property, boolean isInverse) {
		Resource r = model.getResource(resourceURI);
		Model result = ModelFactory.createDefaultModel();
		StmtIterator it = isInverse
				? model.listStatements(null, property, r)
				: r.listProperties(property);
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			RDFNode node = isInverse ? stmt.getSubject() : stmt.getObject();
			if (!node.isAnon()) continue;
			addResourceDescription((Resource) node.as(Resource.class), result); 
		}
		return result;
	}
	
	private void addResourceDescription(Resource resource, Model targetModel) {
		targetModel.add(model.listStatements(resource, null, (RDFNode) null));
		targetModel.add(model.listStatements(null, null, resource));
	}
}
