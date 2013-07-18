package de.fuberlin.wiwiss.pubby.vocab;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.Resource;

public class CONF {

	public static final String NS = "http://richard.cyganiak.de/2007/pubby/config.rdf#";
	
	private static final Model m = ModelFactory.createDefaultModel();
	
	public static final Resource Configuration = m.createResource(NS + "Configuration"); 

	public static final Property projectName = m.createProperty(NS + "projectName"); 
	public static final Property projectHomepage = m.createProperty(NS + "projectHomepage");
	public static final Property webBase = m.createProperty(NS + "webBase"); 
	public static final Property usePrefixesFrom = m.createProperty(NS + "usePrefixesFrom");
	public static final Property labelProperty = m.createProperty(NS + "labelProperty");
	public static final Property commentProperty = m.createProperty(NS + "commentProperty");
	public static final Property imageProperty = m.createProperty(NS + "imageProperty");
	public static final Property defaultLanguage = m.createProperty(NS + "defaultLanguage");
	public static final Property indexResource = m.createProperty(NS + "indexResource");
	public static final Property dataset = m.createProperty(NS + "dataset");

	public static final Property datasetBase = m.createProperty(NS + "datasetBase");
	public static final Property datasetURIPattern = m.createProperty(NS + "datasetURIPattern");
	public static final Property webResourcePrefix = m.createProperty(NS + "webResourcePrefix"); 
	public static final Property fixUnescapedCharacters = m.createProperty(NS + "fixUnescapedCharacters");
	public static final Property addSameAsStatements = m.createProperty(NS + "addSameAsStatements");
	public static final Property sparqlEndpoint = m.createProperty(NS + "sparqlEndpoint"); 
	public static final Property sparqlDefaultGraph = m.createProperty(NS + "sparqlDefaultGraph");
	public static final Property loadRDF = m.createProperty(NS + "loadRDF");
	public static final Property redirectRDFRequestsToEndpoint = m.createProperty(NS + "redirectRDFRequestsToEndpoint"); 
	public static final Property rdfDocumentMetadata = m.createProperty(NS + "rdfDocumentMetadata");
	public static final Property metadataTemplate = m.createProperty(NS + "metadataTemplate");

}
