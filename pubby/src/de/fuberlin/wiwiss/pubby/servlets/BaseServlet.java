package de.fuberlin.wiwiss.pubby.servlets;
import java.io.File;
import java.io.IOException;
import java.net.MalformedURLException;

import javax.servlet.ServletException;
import javax.servlet.UnavailableException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.velocity.context.Context;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.util.FileManager;

import de.fuberlin.wiwiss.pubby.Configuration;
import de.fuberlin.wiwiss.pubby.MappedResource;
import de.fuberlin.wiwiss.pubby.ModelTranslator;

/**
 * An abstract base servlet for servlets that manage a namespace of resources.
 * This class handles preprocessing of the request to extract the
 * resource URI relative to the namespace root, and manages the
 * {@link Configuration} instance shared by all servlets.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public abstract class BaseServlet extends HttpServlet {
	private final static String SERVER_CONFIGURATION =
		BaseServlet.class.getName() + ".serverConfiguration";
	
	private Configuration config;

	public void init() throws ServletException {
		synchronized (getServletContext()) {
			if (getServletContext().getAttribute(SERVER_CONFIGURATION) == null) {
				getServletContext().setAttribute(SERVER_CONFIGURATION, createServerConfiguration());
			}
		}
		config = 
			(Configuration) getServletContext().getAttribute(SERVER_CONFIGURATION);
	}

	private Configuration createServerConfiguration() throws UnavailableException {
		String param = getServletContext().getInitParameter("config-file");
		if (param == null) {
			throw new UnavailableException("Missing context parameter 'config-file'");
		}
		File configFile = new File(param);
		if (!configFile.isAbsolute()) {
			configFile = new File(getServletContext().getRealPath("/") + "/WEB-INF/" + param);
		}
		try {
			return new Configuration(
					FileManager.get().loadModel(
							configFile.getAbsoluteFile().toURL().toExternalForm()));
		} catch (MalformedURLException ex) {
			throw new RuntimeException(ex);
		}
	}
	
	protected Model getResourceDescription(MappedResource resource) {
		return new ModelTranslator(
				resource.getDataset().getDataSource().getResourceDescription(
						resource.getDatasetURI()),
				config).getTranslated();
	}
	
	protected Model getAnonymousPropertyValues(MappedResource resource, 
			Property property, boolean isInverse) {
		return new ModelTranslator(
				resource.getDataset().getDataSource().getAnonymousPropertyValues(
						resource.getDatasetURI(), property, isInverse),
				config).getTranslated();
	}
	
	protected abstract boolean doGet(
			String relativeURI,
			HttpServletRequest request,
			HttpServletResponse response,
			Configuration config) throws IOException, ServletException;
	
	public void doGet(HttpServletRequest request,
			HttpServletResponse response) throws IOException, ServletException {
		String relativeURI = request.getRequestURI().substring(
				request.getContextPath().length() + request.getServletPath().length());
		// Some servlet containers keep the leading slash, some don't
		if (!"".equals(relativeURI) && "/".equals(relativeURI.substring(0, 1))) {
			relativeURI = relativeURI.substring(1);
		}
		if (!doGet(relativeURI, request, response, config)) {
			send404(response, null, null);
		}
	}
	
	protected void send404(HttpServletResponse response, MappedResource resource) throws IOException {
		send404(response, resource.getWebURI(), 
				resource.getDataset().getDataSource().getEndpointURL());
	}

	protected void send404(HttpServletResponse response, String resourceURI, String endpointURL) throws IOException {
		response.setStatus(404);
		VelocityHelper template = new VelocityHelper(getServletContext(), response);
		Context context = template.getVelocityContext();
		context.put("project_name", config.getProjectName());
		context.put("project_link", config.getProjectLink());
		context.put("server_base", config.getWebApplicationBaseURI());
		context.put("sparql_endpoint", endpointURL);
		context.put("title", "404 Not Found");
		if (resourceURI != null) {
			context.put("uri", resourceURI);
		}
		template.renderXHTML("404.vm");
	}
	
	protected String addQueryString(String dataURL, HttpServletRequest request) {
		if (request.getParameter("output") == null) {
			return dataURL;
		}
		return dataURL + "?output=" + request.getParameter("output");
	}
}