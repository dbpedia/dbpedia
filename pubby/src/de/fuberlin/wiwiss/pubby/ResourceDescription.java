package de.fuberlin.wiwiss.pubby;

import java.util.ArrayList;
import java.util.Collection;
import java.util.Collections;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

import com.hp.hpl.jena.graph.Node;
import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import com.hp.hpl.jena.shared.PrefixMapping;

import de.fuberlin.wiwiss.pubby.Configuration;

/**
 * A convenient interface to an RDF description of a resource.
 * Provides access to its label, a textual comment, detailed
 * representations of its properties, and so on.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class ResourceDescription {
	private final MappedResource mappedResource;
	private final Model model;
	private final Resource resource;
	private final Configuration config;
	private List properties = null;
	
	public ResourceDescription(MappedResource mappedResource, Model model, 
			Configuration config) {
		this.mappedResource = mappedResource;
		this.model = model;
		this.resource = model.getResource(mappedResource.getWebURI());
		this.config = config;
	}

	public ResourceDescription(Resource resource, Model model, Configuration config) {
		this.mappedResource = null;
		this.model = model;
		this.resource = resource;
		this.config = config;
	}
	
	public String getURI() {
		if (mappedResource == null) {
			return null;
		}
		return mappedResource.getWebURI();
	}
	
	public String getLabel() {
		Collection candidates = getValuesFromMultipleProperties(config.getLabelProperties());
		String label = getBestLanguageMatch(
				candidates, config.getDefaultLanguage());
		if (label == null) {
			return resource.getLocalName();
		}
		return label;
	}
	
	public String getComment() {
		Collection candidates = getValuesFromMultipleProperties(config.getCommentProperties());
		return getBestLanguageMatch(candidates, config.getDefaultLanguage());
	}
	
	public String getImageURL() {
		Collection candidates = getValuesFromMultipleProperties(config.getImageProperties());
		Iterator it = candidates.iterator();
		while (it.hasNext()) {
			RDFNode candidate = (RDFNode) it.next();
			if (candidate.isURIResource()) {
				return ((Resource) candidate.as(Resource.class)).getURI();
			}
		}
		return null;
	}
	
	public List getProperties() {
		if (properties == null) {
			properties = buildProperties();
		}
		return properties;
	}
	
	private List buildProperties() {
		Map propertyBuilders = new HashMap();
		StmtIterator it = resource.listProperties();
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			Property predicate = stmt.getPredicate();
			String key = "=>" + predicate;
			if (!propertyBuilders.containsKey(key)) {
				propertyBuilders.put(key, new PropertyBuilder(predicate, false));
			}
			((PropertyBuilder) propertyBuilders.get(key)).addValue(stmt.getObject());
		}
		it = model.listStatements(null, null, resource);
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			Property predicate = stmt.getPredicate();
			String key = "<=" + predicate;
			if (!propertyBuilders.containsKey(key)) {
				propertyBuilders.put(key, new PropertyBuilder(predicate, true));
			}
			((PropertyBuilder) propertyBuilders.get(key)).addValue(stmt.getSubject());
		}
		List results = new ArrayList();
		Iterator it2 = propertyBuilders.values().iterator();
		while (it2.hasNext()) {
			PropertyBuilder propertyBuilder = (PropertyBuilder) it2.next();
			results.add(propertyBuilder.toProperty());
		}
		Collections.sort(results);
		return results;
	}

	private PrefixMapping getPrefixes() {
		return model;
	}

	private Collection getValuesFromMultipleProperties(Collection properties) {
		Collection results = new ArrayList();
		Iterator it = properties.iterator();
		while (it.hasNext()) {
			com.hp.hpl.jena.rdf.model.Property property = (com.hp.hpl.jena.rdf.model.Property) it.next();
			StmtIterator labelIt = resource.listProperties(property);
			while (labelIt.hasNext()) {
				RDFNode label = labelIt.nextStatement().getObject();
				results.add(label);
			}
		}
		return results;
	}
	
	private String getBestLanguageMatch(Collection nodes, String lang) {
		Iterator it = nodes.iterator();
		String aLiteral = null;
		while (it.hasNext()) {
			RDFNode candidate = (RDFNode) it.next();
			if (!candidate.isLiteral()) continue;
			Literal literal = (Literal) candidate.as(Literal.class);
			if (lang == null
					|| lang.equals(literal.getLanguage())) {
				return literal.getString();
			}
			aLiteral = literal.getString();
		}
		return aLiteral;
	}
	
	public class ResourceProperty implements Comparable {
		private final Property predicate;
		private final URIPrefixer predicatePrefixer;
		private final boolean isInverse;
		private final List values;
		private final int blankNodeCount;
		public ResourceProperty(Property predicate, boolean isInverse, List values,
				int blankNodeCount) {
			this.predicate = predicate;
			this.predicatePrefixer = new URIPrefixer(predicate, getPrefixes());
			this.isInverse = isInverse;
			this.values = values;
			this.blankNodeCount = blankNodeCount;
		}
		public boolean isInverse() {
			return isInverse;
		}
		public String getURI() {
			return predicate.getURI();
		}
		public boolean hasPrefix() {
			return predicatePrefixer.hasPrefix();
		}
		public String getPrefix() {
			return predicatePrefixer.getPrefix();
		}
		public String getLocalName() {
			return predicatePrefixer.getLocalName();
		}
		public List getValues() {
			return values;
		}
		public int getBlankNodeCount() {
			return blankNodeCount;
		}
		public String getPathPageURL() {
			if (mappedResource == null) {
				return null;
			}
			return isInverse 
					? mappedResource.getInversePathPageURL(predicate) 
					: mappedResource.getPathPageURL(predicate);
		}
		public int compareTo(Object other) {
			if (!(other instanceof ResourceProperty)) {
				return 0;
			}
			ResourceProperty otherProperty = (ResourceProperty) other;
			String propertyLocalName = getLocalName();
			String otherLocalName = otherProperty.getLocalName();
			if (propertyLocalName.compareTo(otherLocalName) != 0) {
				return propertyLocalName.compareTo(otherLocalName);
			}
			if (this.isInverse() != otherProperty.isInverse()) {
				return (this.isInverse()) ? -1 : 1;
			}
			return 0;
		}
	}
	
	private class PropertyBuilder {
		private final Property predicate;
		private final boolean isInverse;
		private final List values = new ArrayList();
		private int blankNodeCount = 0;
		PropertyBuilder(Property predicate, boolean isInverse) {
			this.predicate = predicate;
			this.isInverse = isInverse;
		}
		void addValue(RDFNode valueNode) {
			if (valueNode.isAnon()) {
				blankNodeCount++;
				return;
			}
			values.add(new Value(valueNode));
		}
		ResourceProperty toProperty() {
			Collections.sort(values);
			return new ResourceProperty(predicate, isInverse, values, blankNodeCount);
		}
	}
	
	public class Value implements Comparable {
		private final RDFNode node;
		private URIPrefixer prefixer;
		public Value(RDFNode valueNode) {
			this.node = valueNode;
			if (valueNode.isURIResource()) {
				prefixer = new URIPrefixer((Resource) valueNode.as(Resource.class), getPrefixes());
			}
		}
		public Node getNode() {
			return node.asNode();
		}
		public boolean hasPrefix() {
			return prefixer != null && prefixer.hasPrefix();
		}
		public String getPrefix() {
			if (prefixer == null) {
				return null;
			}
			return prefixer.getPrefix();
		}
		public String getLocalName() {
			if (prefixer == null) {
				return null;
			}
			return prefixer.getLocalName();
		}
		public String getDatatypeLabel() {
			if (!node.isLiteral()) return null;
			String uri = ((Literal) node.as(Literal.class)).getDatatypeURI();
			if (uri == null) return null;
			URIPrefixer datatypePrefixer = new URIPrefixer(uri, getPrefixes());
			return datatypePrefixer.toN3();
		}
		public int compareTo(Object other) {
			if (!(other instanceof Value)) {
				return 0;
			}
			Value otherValue = (Value) other;
			if (getNode().isURI() && otherValue.getNode().isURI()) {
				return getNode().getURI().compareTo(otherValue.getNode().getURI());
			}
			if (getNode().isURI()) {
				return 1;
			}
			if (otherValue.getNode().isURI()) {
				return -1;
			}
			if (getNode().isBlank() && otherValue.getNode().isBlank()) {
				return getNode().getBlankNodeLabel().compareTo(otherValue.getNode().getBlankNodeLabel());
			}
			if (getNode().isBlank()) {
				return 1;
			}
			if (otherValue.getNode().isBlank()) {
				return -1;
			}
			// TODO Typed literals, language literals
			return getNode().getLiteralLexicalForm().compareTo(otherValue.getNode().getLiteralLexicalForm());
		}
	}
}
