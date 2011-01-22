package de.fuberlin.wiwiss.pubby;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.regex.Pattern;

import javax.servlet.ServletContext;

import com.hp.hpl.jena.rdf.model.AnonId;
import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import com.hp.hpl.jena.util.FileManager;
import com.hp.hpl.jena.util.FileUtils;
import com.hp.hpl.jena.vocabulary.XSD;

import de.fuberlin.wiwiss.pubby.vocab.CONF;
import de.fuberlin.wiwiss.pubby.vocab.META;

/**
 * The server's configuration.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @author Hannes Mühleisen
 * @author Olaf Hartig
 * @version $Id$
 */
public class Dataset {
	private final Model model;
	private final Resource config;
	private final DataSource dataSource;
	private final Pattern datasetURIPattern;
	private final char[] fixUnescapeCharacters;
	private final Resource rdfDocumentMetadataTemplate;
	private final String metadataTemplate;
	private final static String metadataPlaceholderURIPrefix = "about:metadata:";
	private Calendar currentTime;
	
	public Dataset(Resource config) {
		model = config.getModel();
		this.config = config;
		if (config.hasProperty(CONF.datasetURIPattern)) {
			datasetURIPattern = Pattern.compile(
					config.getProperty(CONF.datasetURIPattern).getString());
		} else {
			datasetURIPattern = Pattern.compile(".*");
		}
		if (config.hasProperty(CONF.fixUnescapedCharacters)) {
			String chars = config.getProperty(CONF.fixUnescapedCharacters).getString();
			fixUnescapeCharacters = new char[chars.length()];
			for (int i = 0; i < chars.length(); i++) {
				fixUnescapeCharacters[i] = chars.charAt(i);
			}
		} else {
			fixUnescapeCharacters = new char[0];
		}
		if (config.hasProperty(CONF.rdfDocumentMetadata)) {
			rdfDocumentMetadataTemplate = config.getProperty(CONF.rdfDocumentMetadata).getResource();
		} else {
			rdfDocumentMetadataTemplate = null;
		}
		if (config.hasProperty(CONF.metadataTemplate)) {
			metadataTemplate = config.getProperty(CONF.metadataTemplate).getString();
		} else {
			metadataTemplate = null;
		}
		if (config.hasProperty(CONF.sparqlEndpoint)) {
			String endpointURL = config.getProperty(CONF.sparqlEndpoint).getResource().getURI();
			String defaultGraph = config.hasProperty(CONF.sparqlDefaultGraph)
					? config.getProperty(CONF.sparqlDefaultGraph).getResource().getURI()
					: null;
			dataSource = new RemoteSPARQLDataSource(endpointURL, defaultGraph);
		} else {
			Model data = ModelFactory.createDefaultModel();
			StmtIterator it = config.listProperties(CONF.loadRDF);
			while (it.hasNext()) {
				Statement stmt = it.nextStatement();
				FileManager.get().readModel(data, stmt.getResource().getURI());
			}
			dataSource = new ModelDataSource(data);
		}
	}

	public boolean isDatasetURI(String uri) {
		return uri.startsWith(getDatasetBase()) 
				&& datasetURIPattern.matcher(uri.substring(getDatasetBase().length())).matches();
	}
	
	public MappedResource getMappedResourceFromDatasetURI(String datasetURI, Configuration configuration) {
		return new MappedResource(
				escapeURIDelimiters(datasetURI.substring(getDatasetBase().length())),
				datasetURI,
				configuration,
				this);
	}

	public MappedResource getMappedResourceFromRelativeWebURI(String relativeWebURI, 
			boolean isResourceURI, Configuration configuration) {
		if (isResourceURI) {
			if (!"".equals(getWebResourcePrefix())) {
				if (!relativeWebURI.startsWith(getWebResourcePrefix())) {
					return null;
				}
				relativeWebURI = relativeWebURI.substring(getWebResourcePrefix().length());
			}
		}
		relativeWebURI = fixUnescapedCharacters(relativeWebURI);
		if (!datasetURIPattern.matcher(relativeWebURI).matches()) {
			return null;
		}
		return new MappedResource(
				relativeWebURI,
				getDatasetBase() + unescapeURIDelimiters(relativeWebURI),
				configuration,
				this);
	}
	
	public String getDatasetBase() {
		return config.getProperty(CONF.datasetBase).getResource().getURI();
	}
	
	public boolean getAddSameAsStatements() {
		return getBooleanConfigValue(CONF.addSameAsStatements, false);
	}
	
	public DataSource getDataSource() {
		return dataSource;
	}
	
	public boolean redirectRDFRequestsToEndpoint() {
		return getBooleanConfigValue(CONF.redirectRDFRequestsToEndpoint, false);
	}
	
	public String getWebResourcePrefix() {
		if (config.hasProperty(CONF.webResourcePrefix)) {
			return config.getProperty(CONF.webResourcePrefix).getString();
		}
		return "";
	}

	public void addDocumentMetadata(Model document, Resource documentResource) {
		if (rdfDocumentMetadataTemplate == null) {
			return;
		}
		StmtIterator it = rdfDocumentMetadataTemplate.listProperties();
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			document.add(documentResource, stmt.getPredicate(), stmt.getObject());
		}
		it = this.model.listStatements(null, null, rdfDocumentMetadataTemplate);
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			if (stmt.getPredicate().equals(CONF.rdfDocumentMetadata)) {
				continue;
			}
			document.add(stmt.getSubject(), stmt.getPredicate(), documentResource);
		}
	}
	
	public Model addMetadataFromTemplate(Model document, MappedResource documentResource, ServletContext context) {
		if (metadataTemplate == null) {
			return null;
		}
		
		currentTime = Calendar.getInstance();
		
		// add metadata from templates
		Model tplModel = ModelFactory.createDefaultModel();
		String tplPath = context.getRealPath("/") + "/WEB-INF/templates/" + metadataTemplate;
		FileManager.get().readModel( tplModel, tplPath, FileUtils.guessLang(tplPath,"N3") );

		// iterate over template statements to replace placeholders
		Model metadata = ModelFactory.createDefaultModel();
		StmtIterator it = tplModel.listStatements();
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			Resource subj = stmt.getSubject();
			Property pred = stmt.getPredicate();
			RDFNode  obj  = stmt.getObject();
			
			try {
				if (subj.toString().contains(metadataPlaceholderURIPrefix)){
					subj = (Resource) parsePlaceholder(subj, documentResource, context);
					if (subj == null) {
						// create a unique blank node with a fixed id.
						subj = model.createResource(new AnonId(String.valueOf(stmt.getSubject().hashCode())));
					}
				}
				
				if (obj.toString().contains(metadataPlaceholderURIPrefix)){
					obj = parsePlaceholder(obj, documentResource, context);
				}
				
				// only add statements with some objects
				if (obj != null) {
					stmt = metadata.createStatement(subj,pred,obj);
					metadata.add(stmt);
				}
			} catch (Exception e) {
				// something went wrong, oops - lets better remove the offending statement
				metadata.remove(stmt);
				e.printStackTrace();
			}
		}
		
		// remove blank nodes that don't have any properties
		boolean changes = true;
		while ( changes ) {
			changes = false;
			StmtIterator stmtIt = metadata.listStatements();
			List remList = new ArrayList();
			while (stmtIt.hasNext()) {
				Statement s = stmtIt.nextStatement();
				if (    s.getObject().isAnon()
				     && ! ((Resource) s.getObject().as(Resource.class)).listProperties().hasNext() ) {
					remList.add(s);
					changes = true;
				}
			}
			metadata.remove(remList);
		}

		if (document == null) {
			return metadata;
		} else {
			return document.add( metadata );
		}
	}
	
	private RDFNode parsePlaceholder(RDFNode phRes, MappedResource documentResource, ServletContext context) {
		String phURI = phRes.asNode().getURI();
		// get package name and placeholder name from placeholder URI
		phURI = phURI.replace(metadataPlaceholderURIPrefix, "");
		String phPackage = phURI.substring(0, phURI.indexOf(":")+1);
		String phName = phURI.replace(phPackage, "");
		phPackage = phPackage.replace(":", "");
		
		if (phPackage.equals("runtime")) {
			// <about:metadata:runtime:query> - the SPARQL Query used to get the RDF Graph
			if (phName.equals("query")) {
				RemoteSPARQLDataSource ds = (RemoteSPARQLDataSource) documentResource.getDataset().getDataSource();
				return model.createTypedLiteral(ds.getPreviousDescribeQuery());
			}
			// <about:metadata:runtime:time> - the current time
			if (phName.equals("time")) {
				return model.createTypedLiteral(currentTime);
			}
			// <about:metadata:runtime:graph> - URI of the graph
			if (phName.equals("graph")) {
				// Replaced the commented line by the following one because the
				// RDF graph we want to talk about is a specific representation
				// of the data identified by the getDataURL() URI.
				//                                       Olaf, May 28, 2010
				//return model.createResource(documentResource.getDataURL());
				return model.createResource("");
			}
			// <about:metadata:runtime:resource> - URI of the resource
			if (phName.equals("resource")) {
				return model.createResource(documentResource.getWebURI());
			}
		}
		
		// <about:metadata:config:*> - The configuration parameters
		if (phPackage.equals("config")) {
			// look for requested property in the dataset config
			Property p  = model.createProperty(CONF.NS + phName);
			if (config.hasProperty(p))
				return config.getProperty(p).getObject();
			
			// find pointer to the global configuration set...
			StmtIterator it = config.getModel().listStatements(null, CONF.dataset, config);
			Statement ptrStmt = it.nextStatement();
			if (ptrStmt == null) return null;
			
			// look in global config if nothing found so far
			Resource globalConfig = ptrStmt.getSubject();
			if (globalConfig.hasProperty(p))
				return globalConfig.getProperty(p).getObject();
		}
		
		// <about:metadata:metadata:*> - The metadata provided by users
		if (phPackage.equals("metadata")) {
			// look for requested property in the dataset config
			Property p  = model.createProperty(META.NS + phName);
			if (config.hasProperty(p))
				return config.getProperty(p).getObject();
			
			// find pointer to the global configuration set...
			StmtIterator it = config.getModel().listStatements(null, CONF.dataset, config);
			Statement ptrStmt = it.nextStatement();
			if (ptrStmt == null) return null;
			
			// look in global config if nothing found so far
			Resource globalConfig = ptrStmt.getSubject();
			if (globalConfig.hasProperty(p))
				return globalConfig.getProperty(p).getObject();
		}

		return model.createResource(new AnonId(String.valueOf(phRes.hashCode())));
	}
	
	private boolean getBooleanConfigValue(Property property, boolean defaultValue) {
		if (!config.hasProperty(property)) {
			return defaultValue;
		}
		Literal value = config.getProperty(property).getLiteral();
		if (XSD.xboolean.equals(value.getDatatype())) {
			return value.getBoolean();
		}
		return "true".equals(value.getString());
	}

	private String fixUnescapedCharacters(String uri) {
		if (fixUnescapeCharacters.length == 0) {
			return uri;
		}
		StringBuffer encoded = new StringBuffer(uri.length() + 4);
		for (int charIndex = 0; charIndex < uri.length(); charIndex++) {
			boolean encodeThis = false;
			if ((int) uri.charAt(charIndex) > 127) {
				encodeThis = true;
			}
			for (int i = 0; i < fixUnescapeCharacters.length; i++) {
				if (uri.charAt(charIndex) == fixUnescapeCharacters[i]) {
					encodeThis = true;
					break;
				}
			}
			if (encodeThis) {
				encoded.append('%');
				int b = (int) uri.charAt(charIndex);
				encoded.append(Integer.toString(b, 16).toUpperCase());
			} else {
				encoded.append(uri.charAt(charIndex));
			}
		}
		return encoded.toString();
	}

	private String escapeURIDelimiters(String uri) {
		return uri.replaceAll("#", "%23").replaceAll("\\?", "%3F");
	}
	
	private String unescapeURIDelimiters(String uri) {
		return uri.replaceAll("%23", "#").replaceAll("%3F", "?");
	}
}
