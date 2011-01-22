package de.fuberlin.wiwiss.pubby.servlets;

import java.io.OutputStreamWriter;

import javax.servlet.ServletContext;
import javax.servlet.http.HttpServletResponse;

import org.apache.velocity.VelocityContext;
import org.apache.velocity.app.VelocityEngine;
import org.apache.velocity.context.Context;

/**
 * A façade class that simplifies using a custom Velocity
 * engine from a servlet. It encapsulates creation of the
 * VelocityEngine instance, its storage in the servlet
 * context, and the rendering of templates into the
 * servlet response output stream.
 * 
 * @author Richard Cyganiak (richard@cyganiak.de)
 * @version $Id$
 */
public class VelocityHelper {
	private final static String VELOCITY_ENGINE = 
		VelocityHelper.class.getName() + ".VELOCITY_ENGINE";
	
	private final ServletContext servletContext;
	private final HttpServletResponse response;
	private final Context velocityContext;
	
	public VelocityHelper(ServletContext servletContext, HttpServletResponse response) {
		this.servletContext = servletContext;
		this.response = response;
		this.velocityContext = new VelocityContext();
	}
	
	/**
	 * @return A receptacle for template variables
	 */
	public Context getVelocityContext() {
		return velocityContext;
	}

	/**
	 * Renders a template using the template variables put into the velocity context.
	 */
	public void renderXHTML(String templateName) {
		response.addHeader("Content-Type", "text/html; charset=utf-8");
		response.addHeader("Cache-Control", "no-cache");
		response.addHeader("Pragma", "no-cache");
		try {
			OutputStreamWriter writer = new OutputStreamWriter(response.getOutputStream(), "utf-8");
			getVelocityEngine().mergeTemplate(templateName, velocityContext, 
					writer);
			writer.close();
		} catch (Exception ex) {
			throw new RuntimeException(ex);
		}
	}
	
	private VelocityEngine getVelocityEngine() {
		synchronized (servletContext) {
			if (servletContext.getAttribute(VELOCITY_ENGINE) == null) {
				servletContext.setAttribute(VELOCITY_ENGINE, createVelocityEngine());
			}
			return (VelocityEngine) servletContext.getAttribute(VELOCITY_ENGINE);
		}
	}
	
	private VelocityEngine createVelocityEngine() {
		try {
			VelocityEngine result = new VelocityEngine();
			result.setProperty("output.encoding", "utf-8");
			result.setProperty("file.resource.loader.path", 
					servletContext.getRealPath("/") + "/WEB-INF/templates/");
			
			// Turn off Velocity logging
			result.setProperty("runtime.log.logsystem.class", 
					"org.apache.velocity.runtime.log.NullLogSystem");
			
			result.init();
			return result;
		} catch (Exception ex) {
			throw new RuntimeException(ex);
		}
	}
}
