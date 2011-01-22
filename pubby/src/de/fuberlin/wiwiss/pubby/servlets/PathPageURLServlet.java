package de.fuberlin.wiwiss.pubby.servlets;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.velocity.context.Context;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;

import de.fuberlin.wiwiss.pubby.Configuration;
import de.fuberlin.wiwiss.pubby.MappedResource;
import de.fuberlin.wiwiss.pubby.ResourceDescription;

/**
 * A servlet for rendering an HTML page describing the blank nodes
 * related to a given resource via a given property.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class PathPageURLServlet extends BasePathServlet {
		
	public boolean doGet(MappedResource resource, Property property, boolean isInverse, 
			HttpServletRequest request,
			HttpServletResponse response,
			Configuration config) throws IOException {		
		Model descriptions = getAnonymousPropertyValues(resource, property, isInverse);
		if (descriptions.size() == 0) {
			return false;
		}

		Resource r = descriptions.getResource(resource.getWebURI());
		List resourceDescriptions = new ArrayList();
		StmtIterator it = isInverse
				? descriptions.listStatements(null, property, r)
				: r.listProperties(property);
		while (it.hasNext()) {
			Statement stmt = it.nextStatement();
			RDFNode value = isInverse ? stmt.getSubject() : stmt.getObject();
			if (!value.isAnon()) continue;
			resourceDescriptions.add(new ResourceDescription(
					(Resource) value.as(Resource.class), descriptions, config));
		}
		
		Model description = getResourceDescription(resource);
		ResourceDescription resourceDescription = new ResourceDescription(
				resource, description, config);

		String title = resourceDescription.getLabel() + (isInverse ? " Ç " : " È ") +
				config.getPrefixes().getNsURIPrefix(property.getNameSpace()) + ":" + 
				property.getLocalName();
		VelocityHelper template = new VelocityHelper(getServletContext(), response);
		Context context = template.getVelocityContext();
		context.put("project_name", config.getProjectName());
		context.put("project_link", config.getProjectLink());
		context.put("title", title);
		context.put("server_base", config.getWebApplicationBaseURI());
		context.put("sparql_endpoint", resource.getDataset().getDataSource().getEndpointURL());
		context.put("back_uri", resource.getWebURI());
		context.put("back_label", resourceDescription.getLabel());
		context.put("rdf_link", isInverse ? resource.getInversePathDataURL(property) : resource.getPathDataURL(property));
		context.put("resources", resourceDescriptions);
		template.renderXHTML("pathpage.vm");
		return true;
	}
}