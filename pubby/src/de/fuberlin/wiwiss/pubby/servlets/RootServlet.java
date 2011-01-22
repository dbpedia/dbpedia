package de.fuberlin.wiwiss.pubby.servlets;

import java.io.IOException;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import de.fuberlin.wiwiss.pubby.Configuration;

/**
 * A catch-all servlet managing the URI space of the web application.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class RootServlet extends BaseServlet {

	protected boolean doGet(String relativeURI,
			HttpServletRequest request, HttpServletResponse response,
			Configuration config)
			throws IOException, ServletException {
		
		// static/ directory handled by default servlet
		if (relativeURI.startsWith("static/")) {
			getServletContext().getNamedDispatcher("default").forward(request, response);
			return true;
		}
		
		// If index resource is defined, redirect requests for the index page to it
		if ("".equals(relativeURI) && config.getIndexResource() != null) {
			response.sendRedirect(config.getIndexResource().getWebURI());
			return true;
		}
		
		// Assume it's a resource URI -- will produce 404 if not
		getServletContext().getNamedDispatcher("WebURIServlet").forward(request, response);
		return true;
	}
}
