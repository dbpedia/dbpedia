package de.fuberlin.wiwiss.pubby;

import java.util.ArrayList;
import java.util.Collection;
import java.util.Iterator;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import com.hp.hpl.jena.shared.PrefixMapping;
import com.hp.hpl.jena.shared.impl.PrefixMappingImpl;
import com.hp.hpl.jena.util.FileManager;
import com.hp.hpl.jena.vocabulary.DC;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

import de.fuberlin.wiwiss.pubby.vocab.CONF;

/**
 * The server's configuration.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class Configuration {
	private final Model model;
	private final Resource config;
	private final PrefixMapping prefixes;
	private final Collection labelProperties;
	private final Collection commentProperties;
	private final Collection imageProperties;
	private final Collection datasets;
	
	public Configuration(Model configurationModel) {
		model = configurationModel;
		StmtIterator it = model.listStatements(null, RDF.type, CONF.Configuration);
		if (!it.hasNext()) {
			throw new IllegalArgumentException(
					"No conf:Configuration found in configuration model");
		}
		config = it.nextStatement().getSubject();

		datasets = new ArrayList();
		it = model.listStatements(config, CONF.dataset, (RDFNode) null);
		while (it.hasNext()) {
			datasets.add(new Dataset(it.nextStatement().getResource()));
		}
		labelProperties = new ArrayList();
		it = model.listStatements(config, CONF.labelProperty, (RDFNode) null);
		while (it.hasNext()) {
			labelProperties.add(it.nextStatement().getObject().as(Property.class));
		}
		if (labelProperties.isEmpty()) {
			labelProperties.add(RDFS.label);
			labelProperties.add(DC.title);
			labelProperties.add(model.createProperty("http://xmlns.com/foaf/0.1/name"));
		}
		commentProperties = new ArrayList();
		it = model.listStatements(config, CONF.commentProperty, (RDFNode) null);
		while (it.hasNext()) {
			commentProperties.add(it.nextStatement().getObject().as(Property.class));
		}
		if (commentProperties.isEmpty()) {
			commentProperties.add(RDFS.comment);
			commentProperties.add(DC.description);
		}
		imageProperties = new ArrayList();
		it = model.listStatements(config, CONF.imageProperty, (RDFNode) null);
		while (it.hasNext()) {
			imageProperties.add(it.nextStatement().getObject().as(Property.class));
		}
		if (imageProperties.isEmpty()) {
			imageProperties.add(model.createProperty("http://xmlns.com/foaf/0.1/depiction"));
		}
		
		prefixes = new PrefixMappingImpl();		
		if (config.hasProperty(CONF.usePrefixesFrom)) {
			it = config.listProperties(CONF.usePrefixesFrom);
			while (it.hasNext()) {
				Statement stmt = it.nextStatement();
				prefixes.setNsPrefixes(FileManager.get().loadModel(
						stmt.getResource().getURI()));
			}
		} else {
			prefixes.setNsPrefixes(model);
		}
		if (prefixes.getNsURIPrefix(CONF.NS) != null) {
			prefixes.removeNsPrefix(prefixes.getNsURIPrefix(CONF.NS));
		}
	}

	public MappedResource getMappedResourceFromDatasetURI(String datasetURI) {
		Iterator it = datasets.iterator();
		while (it.hasNext()) {
			Dataset dataset = (Dataset) it.next();
			if (dataset.isDatasetURI(datasetURI)) {
				return dataset.getMappedResourceFromDatasetURI(datasetURI, this);
			}
		}
		return null;
	}

	public MappedResource getMappedResourceFromRelativeWebURI(String relativeWebURI, boolean isResourceURI) {
		Iterator it = datasets.iterator();
		while (it.hasNext()) {
			Dataset dataset = (Dataset) it.next();
			MappedResource resource = dataset.getMappedResourceFromRelativeWebURI(
					relativeWebURI, isResourceURI, this);
			if (resource != null) {
				return resource;
			}
		}
		return null;
	}
	
	public PrefixMapping getPrefixes() {
		return prefixes;
	}

	public Collection getLabelProperties() {
		return labelProperties;
	}
	
	public Collection getCommentProperties() {
		return commentProperties;
	}
	
	public Collection getImageProperties() {
		return imageProperties;
	}
	
	public String getDefaultLanguage() {
		if (!config.hasProperty(CONF.defaultLanguage)) {
			return null;
		}
		return config.getProperty(CONF.defaultLanguage).getString();
	}
	
	public MappedResource getIndexResource() {
		if (!config.hasProperty(CONF.indexResource)) {
			return null;
		}
		return getMappedResourceFromDatasetURI(
				config.getProperty(CONF.indexResource).getResource().getURI());
	}
	
	public String getProjectLink() {
		return config.getProperty(CONF.projectHomepage).getResource().getURI();
	}

	public String getProjectName() {
		return config.getProperty(CONF.projectName).getString();
	}

	public String getWebApplicationBaseURI() {
		return config.getProperty(CONF.webBase).getResource().getURI();
	}
}
