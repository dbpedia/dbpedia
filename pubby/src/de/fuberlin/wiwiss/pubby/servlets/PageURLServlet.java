package de.fuberlin.wiwiss.pubby.servlets;
import java.io.IOException;
import java.net.URLEncoder;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.velocity.app.Velocity;
import org.apache.velocity.context.Context;

import com.hp.hpl.jena.rdf.model.Model;

import de.fuberlin.wiwiss.pubby.Configuration;
import de.fuberlin.wiwiss.pubby.MappedResource;
import de.fuberlin.wiwiss.pubby.ResourceDescription;

/**
 * A servlet for serving the HTML page describing a resource.
 * Invokes a Velocity template.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class PageURLServlet extends BaseURLServlet {

	public boolean doGet(MappedResource resource, 
			HttpServletRequest request,
			HttpServletResponse response,
			Configuration config) throws ServletException, IOException {

		Model description = getResourceDescription(resource);
		
		if (description.size() == 0) {
			return false;
		}
		
		Velocity.setProperty("velocimacro.context.localscope", Boolean.TRUE);
		
		ResourceDescription resourceDescription = new ResourceDescription(
				resource, description, config);
		String discoLink = "http://www4.wiwiss.fu-berlin.de/rdf_browser/?browse_uri=" +
				URLEncoder.encode(resource.getWebURI(), "utf-8");
		String tabulatorLink = "http://dig.csail.mit.edu/2005/ajar/ajaw/tab.html?uri=" +
				URLEncoder.encode(resource.getWebURI(), "utf-8");
		String openLinkLink = "http://demo.openlinksw.com/rdfbrowser/?uri=" +
				URLEncoder.encode(resource.getWebURI(), "utf-8");
		VelocityHelper template = new VelocityHelper(getServletContext(), response);
		Context context = template.getVelocityContext();
		context.put("project_name", config.getProjectName());
		context.put("project_link", config.getProjectLink());
		context.put("uri", resourceDescription.getURI());
		context.put("server_base", config.getWebApplicationBaseURI());
		context.put("rdf_link", resource.getDataURL());
		context.put("disco_link", discoLink);
		context.put("tabulator_link", tabulatorLink);
		context.put("openlink_link", openLinkLink);
		context.put("sparql_endpoint", resource.getDataset().getDataSource().getEndpointURL());
		context.put("title", resourceDescription.getLabel());
		context.put("comment", resourceDescription.getComment());
		context.put("image", resourceDescription.getImageURL());
		context.put("properties", resourceDescription.getProperties());
		
		try {
			Model metadata = resource.getDataset().addMetadataFromTemplate(null, resource, getServletContext());
			// Replaced the commented line by the following one because the
			// RDF graph we want to talk about is a specific representation
			// of the data identified by the getDataURL() URI.
			//                                       Olaf, May 28, 2010
			// context.put("metadata", metadata.getResource(resource.getDataURL()).listProperties().toList());
			context.put("metadata", metadata.getResource("").listProperties().toList());
			Map nsSet = metadata.getNsPrefixMap();
			nsSet.putAll(description.getNsPrefixMap());
			context.put("prefixes", nsSet.entrySet());
			context.put("blankNodesMap", new HashMap());
		}
		catch (Exception e) {
			context.put("metadata", Boolean.FALSE);
		}
	
		template.renderXHTML("page.vm");
		return true;
	}
}
