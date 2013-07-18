package oaiReader;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import org.coode.owlapi.rdf.model.RDFLiteralNode;
import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.model.IRI;

import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;



public class JenaToOwlApi
{
	/**
	 * Converts a Jena-Model to a set of owl-api triples
	 * Note that for each function call, blank node labelling restarts.
	 * 
	 * @param model
	 * @return
	 */
	public static Set<RDFTriple> toTriples(Model model)
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		
		Set<Statement> stmts = new HashSet<Statement>();
		StmtIterator it = model.listStatements();
		while(it.hasNext())
			stmts.add(it.next());
		
		Map<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode>
			blankNodeMap = new HashMap<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode>();
		
		for(Statement stmt : stmts) {
			Resource s   = stmt.getSubject();
			Property p   = stmt.getPredicate();
			com.hp.hpl.jena.rdf.model.RDFNode o = stmt.getObject();
			
			result.add(new RDFTriple(
					(RDFResourceNode)toResource(s, blankNodeMap),
					(RDFResourceNode)toResource(p, blankNodeMap),
					toResource(o, blankNodeMap)));
		}
		
		return result;
	}

	
	/**
	 * Helper function to map jena nodes to owlapi nodes
	 * 
	 * @param node
	 * @param blankNodeMap
	 * @return
	 */
	public static RDFNode toResource(com.hp.hpl.jena.rdf.model.RDFNode node,
			Map<com.hp.hpl.jena.rdf.model.RDFNode, RDFResourceNode> blankNodeMap)
	{
		if(node == null)
			return null;
		
		if(node.isAnon()) {
			RDFResourceNode resource = blankNodeMap.get(node); 
			if(resource != null)
				return resource;
			
			resource = new RDFResourceNode(blankNodeMap.size() + 1);
			blankNodeMap.put(node, resource);
			return resource;
		}

			
		if(node.isURIResource())
			return new RDFResourceNode(IRI.create(node.toString()));
		
		if(node.isLiteral()) {
			Literal literal = (Literal)node;
			
			String datatype = literal.getDatatypeURI(); 
			if(datatype != null)
				return new RDFLiteralNode(literal.getString(),
						IRI.create(datatype));

			if(literal.getLanguage() == null ||
					literal.getLanguage().isEmpty())				
				return new RDFLiteralNode(literal.getString());
				
			return new RDFLiteralNode(literal.getString(), literal.getLanguage());
		}
		
		throw new RuntimeException("Shouldn't happen");
	}
}

