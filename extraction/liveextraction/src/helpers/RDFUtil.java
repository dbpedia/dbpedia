package helpers;

import java.io.UnsupportedEncodingException;
import java.lang.reflect.Field;
import java.net.URI;
import java.security.MessageDigest;
import java.util.Collection;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import oaiReader.RDFExpression;

import org.apache.commons.collections15.Transformer;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFGraph;
import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTranslator;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.apibinding.OWLManager;
import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.model.OWLClassExpression;
import org.semanticweb.owlapi.model.OWLOntology;
import org.semanticweb.owlapi.model.OWLOntologyManager;

public class RDFUtil
{
	private static Logger logger = Logger.getLogger(RDFUtil.class);

	
	private static MessageDigest md5 = null;

	static {
		try{
			md5 = MessageDigest.getInstance("MD5");
		}catch(Exception e) {
			logger.fatal(ExceptionUtil.toString(e));
			throw new RuntimeException(e);
		}
	}

	public static URI generateMD5HashUri(String prefix, RDFTriple triple)
	{
		return URI.create(prefix + generateMD5(triple));
	}

	public static String generateMD5(RDFTriple triple)
	{
		String str = triple.getSubject().toString() + " "
				+ triple.getProperty().toString() + " "
				+ triple.getObject().toString();

		return generateMD5(str);
	}

	public static String generateMD5(String str)
	{
		md5.reset();
		md5.update(str.getBytes());
		byte[] result = md5.digest();

		StringBuffer hexString = new StringBuffer();
		for (int i = 0; i < result.length; i++) {
			hexString.append(Integer.toHexString(0xFF & result[i]));
		}
		return hexString.toString();
	}	
	
	private static Set<RDFTriple> stripTypeClass(Set<RDFTriple> triples)
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		
		for(RDFTriple item : triples) {
			RDFNode node = item.getObject();
			if(!(node.isAnonymous() || node.isLiteral())) {
				if(node.getIRI().toString().equals("http://www.w3.org/2002/07/owl#Class"))
					continue;
			}
			
			result.add(item);
			//if(item.
		}
		
		return result;
	}
	
	/**
	 * 
	 * @param text
	 * @param leafPrefix
	 *            prefix for leaf-resources of the expression tree
	 * @param innerPrefix
	 *            prefix for inner nodes of the expression tree
	 * @return
	 * @throws Exception
	 */
	public static RDFExpression interpretMos(String text,
			Transformer<String, IRI> prefixResolver, String innerPrefix, boolean generateTypeClass)
		throws Exception
	{
		RDFExpression e = parseManchesterOWLClassExpression(text, prefixResolver, generateTypeClass);
		
		// Replace anonymous nodes with resources generated from their hash
		Map<RDFNode, RDFResourceNode> map = relabelBlankNodes(e.getTriples(), innerPrefix);

		// Relabel nodes like cyc:...
		// An exception is thrown if relabelling fails
		// e.g. if myCostumPrefix:birthPlace cannot be resolved

		// Resolve the root subject
		resolve(e.getRootSubject(), prefixResolver, map);

		// And all triples
		map.putAll(resolve(e.getTriples(), prefixResolver));

		Set<RDFTriple> triples = relableTriples(e.getTriples(), map);

		RDFResourceNode rootNode = map.get(e.getRootSubject());
		if (rootNode == null)
			rootNode = e.getRootSubject();

		

		// Return if there is no root
		RDFExpression result = rootNode == null
			? null
			: new RDFExpression(rootNode, triples);
		

		String msg = "Triples generated for MOS-Expression '" + text + "':\n";
		
		if(result == null)
			logger.trace(msg + "None");
		else {
			msg += "\tRoot subject = " + rootNode + "\n";
			for(RDFTriple item : triples)
				msg += "\t" + item.toString() + "\n";
			
			logger.trace(msg);
		}
		
		return result;
	}

	private static Map<RDFNode, RDFResourceNode> resolve(
			Set<RDFTriple> triples, Transformer<String, IRI> resolver)
		throws UnsupportedEncodingException
	{
		Map<RDFNode, RDFResourceNode> result = new HashMap<RDFNode, RDFResourceNode>();

		for (RDFTriple triple : triples)
			resolve(triple, resolver, result);

		return result;
	}

	private static void resolve(RDFTriple triple, Transformer<String, IRI> resolver,
			Map<RDFNode, RDFResourceNode> map)
		throws UnsupportedEncodingException
	{
		resolve(triple.getSubject(), resolver, map);
		resolve(triple.getProperty(), resolver, map);
		resolve(triple.getObject(), resolver, map);
	}

	private static void resolve(RDFNode node, Transformer<String, IRI> resolver,
			Map<RDFNode, RDFResourceNode> map)
		throws UnsupportedEncodingException
	{
		if (node.isAnonymous() || node.isLiteral())
			return;

		RDFResourceNode n = (RDFResourceNode) node;

		String value = n.getIRI().toString();

		if (!value.startsWith("http://relabel.me/"))
			return;

		value = value.substring("http://relabel.me/".length());
		// value = URLEncoder.encode(value, "UTF-8");

		IRI uri = resolver.transform(value);

		if (uri == null)
			throw new RuntimeException("'" + value
					+ "' didn't resolve to an uri.");

		RDFResourceNode mapped = map.get(node);
		if (mapped == null)
			map.put(node, new RDFResourceNode(uri));
	}


	private static Set<RDFTriple> resolve2(Set<RDFTriple> triples,
			Transformer<String, IRI> resolver)
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();

		for (RDFTriple triple : triples)
			result.add(resolve2(triple, resolver));

		return result;
	}

	private static RDFTriple resolve2(RDFTriple triple, Transformer<String, IRI> resolver)
	{
		RDFResourceNode s = (RDFResourceNode) resolve2(triple.getSubject(),
				resolver);

		RDFResourceNode p = (RDFResourceNode) resolve2(triple.getProperty(),
				resolver);

		RDFNode o = resolve2(triple.getObject(), resolver);

		if (triple.getSubject() == s && triple.getProperty() == p
				&& triple.getObject() == o)
			return triple;

		return new RDFTriple(s, p, o);
	}

	private static RDFNode resolve2(RDFNode node, Transformer<String, IRI> resolver)
	{
		if (node.isAnonymous() || node.isLiteral())
			return node;

		RDFResourceNode n = (RDFResourceNode) node;

		String value = n.getIRI().toString();

		if (!value.startsWith("http://relabel.me/"))
			return node;

		value = value.substring("http://relabel.me/".length());
		IRI uri = resolver.transform(value);

		if (uri == null)
			throw new NullPointerException();

		return new RDFResourceNode(uri);
	}

	private static RDFExpression parseManchesterOWLClassExpression(String text, Transformer<String, IRI> prefixResolver, boolean generateTypeClass)
		throws Exception
	{
		String base = "http://relabel.me/";

		OWLOntologyManager m = OWLManager.createOWLOntologyManager();
		OWLOntology o = m.createOntology(IRI
				//.create("http://relabel.me"));
				.create("http://this_should_not_show_up_anywhere.org"));
		/*

		//OWLOntologyIRIMapperImpl iriMapper = new OWLOntologyIRIMapperImpl();
		//iriMapper.
		
		//m.addIRIMapper(arg0)
		//m.add
		
		OWLDataFactory df = m.getOWLDataFactory();
		
		
		
		OWLClass pizza = df.getOWLClass(IRI.create(prefix + "Pizza"));
		OWLClass topping = df.getOWLClass(IRI.create(prefix + "Topping"));
		OWLObjectProperty hasTopping = df.getOWLObjectProperty(IRI.create(prefix + "hasTopping"));

		
		OWLAxiom[] xs = {
			df.getOWLEquivalentObjectPropertiesAxiom(CollectionUtil.set(hasTopping)),
			df.getOWLEquivalentClassesAxiom(CollectionUtil.set(pizza)),
			df.getOWLEquivalentClassesAxiom(CollectionUtil.set(topping)),
		};
			
		for(OWLAxiom x : xs) {
			AddAxiom addX = new AddAxiom(o, x); 
			m.applyChange(addX);
		}
		
		//o.
		//df.getOWLObjectPropertyA
		//OWLObjectPropertyAxiom 
		
		//m.applyChange(hasFather);
		System.out.println("DataProperties = " + o.getDataPropertiesInSignature());
		System.out.println("ObjectProperties = " + o.getObjectPropertiesInSignature());
		System.out.println("Classes = " + o.getClassesInSignature());
		
		//http://owlapi.svn.sourceforge.net/viewvc/owlapi/v3/trunk/examples/src/main/java/org/coode/owlapi/examples/Example4.java?view=markup
		
		
		
		ManchesterOWLSyntaxEditorParser parser = new ManchesterOWLSyntaxEditorParser(
				m.getOWLDataFactory(), text);

		// we temporarely add a prefix
		// String removeMePrefix = "http://removeMe.org";
		parser.setBase(prefix);
		// parser.se


		OWLAxiom d = parser.parseAxiom();
*/
		OWLClassExpression classExpression = ManchesterParse.parse(text, base, prefixResolver);
		
		RDFTranslator t = new RDFTranslator(m, o, false);
		classExpression.accept(t);

		RDFGraph rdfGraph = t.getGraph();
		// System.out.println(rdfGraph.getRootAnonymousNodes().size());
		Set<RDFResourceNode> rootNodes = rdfGraph.getRootAnonymousNodes();

		RDFResourceNode rootSubject = null;
		if (rootNodes.size() == 0) {
			//IRI uri = IRI.create(prefix
			//		+ URLEncoder.encode(text.trim(), "UTF-8"));
			
			IRI uri = IRI.create(base + text.trim());
			
			rootSubject = new RDFResourceNode(uri);
			//System.out.println("dfsad = " +  rootSubject);
		}
		else if (rootNodes.size() == 1)
			rootSubject = rootNodes.iterator().next();
		else
			throw new RuntimeException("Unexpected multiple root nodes");

		Set<RDFTriple> triples = extractTriples(rdfGraph);
		
		if(!generateTypeClass)
			triples = stripTypeClass(triples);
		
		return new RDFExpression(rootSubject, triples);
	}

	// public void replaceResource(RDFResourceNode orig, RDFResourceNode

	/**
	 * This is a hack which retrieves triples from an RDFGraph using reflection
	 * of private fields.
	 * 
	 * 
	 * @param graph
	 * @return
	 */
	@SuppressWarnings("unchecked")
	public static Set<RDFTriple> extractTriples(RDFGraph graph)
	{
		try {
			Field field = graph.getClass().getDeclaredField("triples");

			field.setAccessible(true);
			Set<RDFTriple> triples = (Set<RDFTriple>) field.get(graph);
			field.setAccessible(false);

			return new HashSet<RDFTriple>(triples);
		}
		catch (Exception e) {
			e.printStackTrace();
			return Collections.emptySet();
		}
	}

	public static Set<RDFTriple> relableTriples(Collection<RDFTriple> triples,
			Map<RDFNode, RDFResourceNode> map)
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		for (RDFTriple triple : triples)
			result.add(relableTriple(triple, map));

		return result;
	}

	public static RDFTriple relableTriple(RDFTriple triple,
			Map<RDFNode, RDFResourceNode> map)
	{
		boolean changed = false;

		RDFResourceNode s = map.get(triple.getSubject());
		if (s == null) {
			s = triple.getSubject();
			changed = true;
		}

		RDFResourceNode p = map.get(triple.getProperty());
		if (p == null) {
			p = triple.getProperty();
			changed = true;
		}

		RDFNode o = map.get(triple.getObject());
		if (o == null) {
			o = triple.getObject();
			changed = true;
		}

		if (changed)
			return new RDFTriple(s, p, o);

		return triple;
	}

	/**
	 * relabels all blank nodes with unique ids. Does nothing if prefix is null.
	 * 
	 * @param triples
	 * @param idGenerator
	 * @return
	 */
	public static Map<RDFNode, RDFResourceNode> relabelBlankNodes(
			Set<RDFTriple> triples, String prefix)
	{
		Map<RDFNode, RDFResourceNode> result = new HashMap<RDFNode, RDFResourceNode>();

		if (prefix == null)
			return result;

		for (RDFTriple triple : triples) {
			relableBlankNode(triple.getSubject(), prefix, result);
			relableBlankNode(triple.getProperty(), prefix, result);
			relableBlankNode(triple.getObject(), prefix, result);
		}

		return result;
	}

	// Turns blank nodes into resources
	public static RDFResourceNode relableBlankNode(RDFNode node, String prefix,
			Map<RDFNode, RDFResourceNode> oldToNew)
	{
		if (!node.isAnonymous())
			return null;

		String id = node.toString();
		RDFResourceNode newId = oldToNew.get(id);

		if (newId == null) {
			newId = new RDFResourceNode(IRI.create(prefix + id));
			oldToNew.put(node, newId);
		}

		return newId;
	}

}
