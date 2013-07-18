package oaiReader;

import helpers.ExceptionUtil;
import helpers.RDFUtil;
import helpers.StringUtil;

import java.util.Collection;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

import mywikiparser.SimpleTemplateParser;
import mywikiparser.ast.IWikiNode;
import mywikiparser.ast.TemplateWikiNode;

import org.apache.commons.collections15.MultiMap;
import org.apache.commons.collections15.multimap.MultiHashMap;
import org.apache.log4j.Logger;
import org.coode.owlapi.rdf.model.RDFLiteralNode;
import org.coode.owlapi.rdf.model.RDFNode;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.vocab.OWLRDFVocabulary;

import collections.IMultiMap;

/**
 * Interface for objects which generate triples
 * 
 * @author raven
 * 
 */
interface ITripleGenerator
{
	Set<RDFTriple> generate(IRI subject, IRI property, String value, String lang)
		throws Exception;

	// public void setExprPrefix(String prefix);
}

class TripleGeneratorDecorator
	implements ITripleGenerator
{
	protected ITripleGenerator	tripleGenerator;

	public TripleGeneratorDecorator(ITripleGenerator tripleGenerator)
	{
		this.tripleGenerator = tripleGenerator;
	}

	/*
	 * @Override public void setExprPrefix(String prefix) {
	 * tripleGenerator.setExprPrefix(prefix); }
	 */

	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
		throws Exception
	{
		return tripleGenerator.generate(subject, property, value, lang);
	}

}

/**
 * Splits the value by a given separator and passes each fragment to the given
 * generator and collects the partial results
 * 
 * @author raven
 * 
 */
class ListTripleGenerator
	// implements ITripleGenerator
	extends TripleGeneratorDecorator
{
	private String	separator;
	// private ITripleGenerator generator;

	private Logger	logger	= Logger.getLogger(ListTripleGenerator.class);

	public ListTripleGenerator(String separator,
			ITripleGenerator tripleGenerator)
	{
		super(tripleGenerator);
		this.separator = separator;
		// this.tripleGenerator = tripleGenerator;
	}

	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
		throws Exception
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();

		for (String part : value.split(separator)) {
			try {
				Set<RDFTriple> triples = tripleGenerator.generate(subject,
						property, part, lang);

				result.addAll(triples);
			}
			catch (Exception e) {
				logger.warn(ExceptionUtil.toString(e));
			}
		}

		return result;
	}
}

class LiteralTripleGenerator
	implements ITripleGenerator
{
	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
	{
		// Ignore empty triples
		value = value.trim();
		if (value.isEmpty())
			return Collections.emptySet();

		RDFResourceNode s = new RDFResourceNode(subject);
		RDFResourceNode p = new RDFResourceNode(property);
		RDFLiteralNode o = new RDFLiteralNode(value, lang);

		return Collections.singleton(new RDFTriple(s, p, o));
	}
}

class StringReference
{
	private String	value;

	public String getValue()
	{
		return value;
	}

	public void setValue(String value)
	{
		this.value = value;
	}
}

/*
 * class PrefixedStringTransformer implements Transformer<String, String> {
 * private String prefix;
 * 
 * public String getPrefix() { return prefix; }
 * 
 * public void setPrefix(String prefix) { this.prefix = prefix; }
 * 
 * @Override public String transform(String value) { return prefix + value; } }
 */

class MosTripleGenerator
	implements ITripleGenerator
{
	private static Logger	logger	= Logger
											.getLogger(MosTripleGenerator.class);

	private StringReference	exprPrefixRef;
	private IPrefixResolver	prefixResolver;

	public MosTripleGenerator(StringReference exprPrefixRef,
			IPrefixResolver prefixResolver)
	{
		this.exprPrefixRef = exprPrefixRef;
		this.prefixResolver = prefixResolver;
	}

	/*
	 * public void setExprPrefix(String exprPrefix) { this.exprPrefix =
	 * exprPrefix; }
	 */

	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
		throws Exception
	{
		value = value.trim();
		if (value.isEmpty())
			return Collections.emptySet();

		RDFResourceNode s = new RDFResourceNode(subject);

		Set<RDFTriple> result;
		try {
			RDFExpression expr = RDFUtil.interpretMos(value, prefixResolver,
					exprPrefixRef.getValue(), false);

			// if for some reason (is there one?) the rootSuject is null
			// return no triple
			if (expr.getRootSubject() == null)
				return Collections.emptySet();

			RDFResourceNode p = new RDFResourceNode(property);
			RDFResourceNode o = expr.getRootSubject();

			result = expr.getTriples();

			result.add(new RDFTriple(s, p, o));
		}
		catch (Exception e) {
			String msg = "Errornous expression for predicate '"
					+ property.toString() + "': "
					+ StringUtil.cropString(value.trim(), 50, 20);

			RDFResourceNode p = new RDFResourceNode(MyVocabulary.DBM_ERROR
					.getIRI());
			RDFLiteralNode o = new RDFLiteralNode(msg);

			result = new HashSet<RDFTriple>();
			result.add(new RDFTriple(s, p, o));

			logger.warn(ExceptionUtil.toString(e));
		}

		return result;
	}
}

/**
 * Predicate and object are 'static'. Only the supplied subject is used for
 * triple generation.
 * 
 * @author raven
 * 
 */
class StaticTripleGenerator
	implements ITripleGenerator
{
	private RDFResourceNode	predicate;
	private RDFNode			object;

	public StaticTripleGenerator(RDFResourceNode predicate, RDFNode object)
	{
		this.predicate = predicate;
		this.object = object;
	}

	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
		throws Exception
	{
		return Collections.singleton(new RDFTriple(
				new RDFResourceNode(subject), predicate, object));
	}
}

/**
 * A triple generator that first delegates generation request to the
 * mainGenerator. If no triples are generated by it, the request is delegated to
 * the alternativeGenerator.
 * 
 * @author raven
 * 
 */
class AlternativeTripleGenerator
	implements ITripleGenerator
{
	private ITripleGenerator	mainGenerator;
	private ITripleGenerator	alternativeGenerator;

	public AlternativeTripleGenerator(ITripleGenerator firstGenerator,
			ITripleGenerator alternativeGenerator)
	{
		this.mainGenerator = firstGenerator;
		this.alternativeGenerator = alternativeGenerator;
	}

	@Override
	public Set<RDFTriple> generate(IRI subject, IRI property, String value,
			String lang)
		throws Exception
	{
		Set<RDFTriple> triples = mainGenerator.generate(subject, property,
				value, lang);

		if (triples.isEmpty())
			triples = alternativeGenerator.generate(subject, property, value,
					lang);

		return triples;
	}
}

/**
 * This is the actual extractor (PropDefExtractor is just the handler)
 * 
 * @author raven
 * 
 */
class TBoxTripleGenerator
{
	// TODO Make that configurable
	// private static final String DEFAULT_LANGUAGE = "en";

	/*
	private static final String				OBJECT_PROPERTY		= "DBpedia_ObjectProperty";
	private static final String				DATATYPE_PROPERTY	= "DBpedia_DatatypeProperty";
	private static final String				CLASS				= "DBpedia_Class";
	 */
	private static final String				OBJECT_PROPERTY		= "ObjectProperty";
	private static final String				DATATYPE_PROPERTY	= "DatatypeProperty";
	private static final String				CLASS				= "Class";
	
	
	private Logger							logger				= Logger
																		.getLogger(TBoxTripleGenerator.class);

	private StringReference					exprPrefixRef		= new StringReference();
	private IPrefixResolver					prefixResolver;

	// NOTE: This extractor uri is NOT THIS CLASS' name!!!
	/*
	 * private static final IRI extractorUri = IRI .create(DBM +
	 * PropertyDefinitionExtractor.class .getSimpleName());
	 */

	// mapping for class definition attributes to triple generators
	private Map<String, ITripleGenerator>	classToGenerator	= new HashMap<String, ITripleGenerator>();

	// Default values for class (will be used if the parameter is not present)
	private Map<String, ITripleGenerator>	classDefaults		= new HashMap<String, ITripleGenerator>();

	private Map<String, ITripleGenerator>	propertyToGenerator	= new HashMap<String, ITripleGenerator>();

	private Map<String, ITripleGenerator>	objectDefaults		= new HashMap<String, ITripleGenerator>();

	private Map<String, ITripleGenerator>	dataToGenerator		= new HashMap<String, ITripleGenerator>();

	private Map<String, ITripleGenerator>	dataDefaults		= new HashMap<String, ITripleGenerator>();

	public void setExprPrefix(String value)
	{
		exprPrefixRef.setValue(value);
	}

	public TBoxTripleGenerator(
	// StringReference exprPrefixRef,
			IPrefixResolver prefixResolver)
	{
		// this.exprPrefix = exprPrefix;
		this.prefixResolver = prefixResolver;

		ITripleGenerator literalTripleGenerator = new LiteralTripleGenerator();

		ITripleGenerator mosListTripleGenerator = new ListTripleGenerator(",",
				new MosTripleGenerator(exprPrefixRef, prefixResolver));

		ITripleGenerator fallbackSubClassGenerator = new StaticTripleGenerator(
				new RDFResourceNode(OWLRDFVocabulary.RDFS_SUBCLASS_OF.getIRI()),
				new RDFResourceNode(OWLRDFVocabulary.OWL_THING.getIRI()));

		/*
		 * ITripleGenerator subClassTripleGenerator = new
		 * AlternativeTripleGenerator( mosListTripleGenerator,
		 * fallbackSubClassGenerator);
		 */

		classToGenerator.put("rdfs:label", literalTripleGenerator);
		classToGenerator.put("rdfs:comment", literalTripleGenerator);
		classToGenerator.put("owl:equivalentClass", mosListTripleGenerator);
		classToGenerator.put("owl:disjointWith", mosListTripleGenerator);
		classToGenerator.put("rdfs:seeAlso", mosListTripleGenerator);
		classToGenerator.put("rdfs:subClassOf", mosListTripleGenerator);// )subClassTripleGenerator);

		classDefaults.put("rdfs:subClassOf", fallbackSubClassGenerator);

		propertyToGenerator.put("rdfs:label", literalTripleGenerator);
		propertyToGenerator.put("rdfs:comment", literalTripleGenerator);
		propertyToGenerator.put("owl:equivalentProperty",
				mosListTripleGenerator);
		propertyToGenerator.put("rdfs:seeAlso", mosListTripleGenerator);
		propertyToGenerator.put("rdfs:subPropertyOf", mosListTripleGenerator);
		propertyToGenerator.put("rdfs:domain", mosListTripleGenerator);
		propertyToGenerator.put("rdfs:range", mosListTripleGenerator);
		propertyToGenerator.put("rdf:type", mosListTripleGenerator);

		dataToGenerator.put("rdfs:label", literalTripleGenerator);
		dataToGenerator.put("rdfs:comment", literalTripleGenerator);
		dataToGenerator.put("owl:equivalentProperty", mosListTripleGenerator);
		dataToGenerator.put("rdfs:seeAlso", mosListTripleGenerator);
		dataToGenerator.put("rdfs:subPropertyOf", mosListTripleGenerator);
		dataToGenerator.put("rdfs:domain", mosListTripleGenerator);
		dataToGenerator.put("rdfs:range", mosListTripleGenerator);
		dataToGenerator.put("rdf:type", mosListTripleGenerator);
	}

	// Takes a wiki node and returns a triple set group
	public MultiMap<IRI, Set<RDFTriple>> extract(IRI rootUri, IWikiNode root)
	{
		// TripleSetGroup result =
		// new TripleSetGroup(new DBpediaGroupDef(extractorUri, targetUri));
		// result.setTriples(new HashSet<RDFTriple>());

		IMultiMap<String, TemplateWikiNode> nameToTemplate = WikiParserHelper
				.indexTemplates(root.getChildren());

		// process the various types of templates
		MultiMap<IRI, Set<RDFTriple>> result = new MultiHashMap<IRI, Set<RDFTriple>>();

		result.putAll(processObjectProperty(nameToTemplate
				.safeGet(OBJECT_PROPERTY), rootUri));

		result.putAll(processDataProperty(nameToTemplate
				.safeGet(DATATYPE_PROPERTY), rootUri));

		result.putAll(processClass(nameToTemplate.safeGet(CLASS), rootUri));

		return result;
	}

	/*
	 * {{ DBpedia Class | rdfs:label = person | rdfs:label@de = Person |
	 * rdfs:label@fr = personne | rdfs:comment = A person in DBpedia is defined
	 * as an individual human being. | owl:equivalentClass = foaf:Person
	 * umbel-sc:Person yago:Person100007846 | rdfs:seeAlso = opencyc:Person |
	 * rdfs:subClassOf = LivingThing }}
	 */
	private MultiMap<IRI, Set<RDFTriple>> processClass(
			Collection<TemplateWikiNode> nodes, IRI subject)
	{
		if (nodes.size() == 0)
			return new MultiHashMap<IRI, Set<RDFTriple>>();

		MultiMap<IRI, Set<RDFTriple>> result = processTemplate(nodes, subject,
				classToGenerator, classDefaults);

		result.put(OWLRDFVocabulary.RDF_TYPE.getIRI(),
				Collections
						.singleton(new RDFTriple(new RDFResourceNode(subject),
								new RDFResourceNode(OWLRDFVocabulary.RDF_TYPE
										.getIRI()), new RDFResourceNode(
										OWLRDFVocabulary.OWL_CLASS.getIRI()))));

		return result;
	}

	/*
	 * | rdfs:label = birth place | rdfs:label@de = Geburtsort | rdfs:label@fr =
	 * lieu de naissance | rdfs:comment = Relates a living thing to the place
	 * where it was born. | owl:equivalentProperty = | rdfs:seeAlso =
	 * cyc:birthPlace | rdfs:subPropertyOf = | rdfs:domain = LivingThing |
	 * rdfs:range = xsd:dateO | rdf:type = owl:FunctionalProperty
	 */
	private MultiMap<IRI, Set<RDFTriple>> processObjectProperty(
			Collection<TemplateWikiNode> nodes, IRI subject)
	{
		if (nodes.size() == 0)
			return new MultiHashMap<IRI, Set<RDFTriple>>();

		MultiMap<IRI, Set<RDFTriple>> result = processTemplate(nodes, subject,
				propertyToGenerator, objectDefaults);

		result.put(OWLRDFVocabulary.RDF_TYPE.getIRI(),
				Collections
						.singleton(new RDFTriple(new RDFResourceNode(subject),
								new RDFResourceNode(OWLRDFVocabulary.RDF_TYPE
										.getIRI()), new RDFResourceNode(
										OWLRDFVocabulary.OWL_OBJECT_PROPERTY
												.getIRI()))));

		return result;
	}

	private MultiMap<IRI, Set<RDFTriple>> processDataProperty(
			Collection<TemplateWikiNode> nodes, IRI subject)
	{
		if (nodes.size() == 0)
			return new MultiHashMap<IRI, Set<RDFTriple>>();

		MultiMap<IRI, Set<RDFTriple>> result = processTemplate(nodes, subject,
				dataToGenerator, dataDefaults);

		result.put(OWLRDFVocabulary.RDF_TYPE.getIRI(), Collections
				.singleton(new RDFTriple(

				new RDFResourceNode(subject), new RDFResourceNode(
						OWLRDFVocabulary.RDF_TYPE.getIRI()),
						new RDFResourceNode(OWLRDFVocabulary.OWL_DATA_PROPERTY
								.getIRI()))));

		return result;
	}

	/**
	 * Returns a multimap - for each property a list of triples that were
	 * generated for it.
	 * 
	 * 
	 * @param nodes
	 * @param subject
	 * @param map
	 * @param defaults
	 * @return
	 */
	private MultiMap<IRI, Set<RDFTriple>> processTemplate(
			Collection<TemplateWikiNode> nodes, IRI subject,
			// TripleSetGroup result,
			Map<String, ITripleGenerator> map,
			Map<String, ITripleGenerator> defaults)
	{
		MultiMap<IRI, Set<RDFTriple>> result = new MultiHashMap<IRI, Set<RDFTriple>>();

		if (nodes == null || nodes.size() == 0)
			return result;

		if (nodes.size() > 1) {
			logger.warn("Multiple annotation on the same site - Skipping");
			return result;
		}

		TemplateWikiNode node = nodes.iterator().next();

		IMultiMap<String, IWikiNode> keyToValue = WikiParserHelper
				.indexArguments(node);

		// Those parameters for which triples have been generated
		Set<String> seenParameters = new HashSet<String>();

		for (Map.Entry<String, Collection<IWikiNode>> entry : keyToValue
				.entrySet()) {
			KeyInfo keyInfo = parseKey(entry.getKey());

			ITripleGenerator g = map.get(keyInfo.getName());
			if (g == null)
				continue;

			IRI property = prefixResolver.transform(keyInfo.getName());
			if (property == null)
				continue;

			for (IWikiNode v : entry.getValue()) {
				String value = SimpleTemplateParser.nodeToText(v);

				try {
					Set<RDFTriple> triples = g.generate(subject, property,
							value, keyInfo.getLanguageTag());

					if (triples.size() > 0)
						seenParameters.add(entry.getKey());

					result.put(property, triples);
				}
				catch (Exception e) {
					logger.warn(ExceptionUtil.toString(e));
				}

			}
		}
		// System.out.println("Seen = " + seenParameters);
		// System.out.println("Defaults = " + defaults);

		// Process default values for parameters which have not been seen
		for (Map.Entry<String, ITripleGenerator> item : defaults.entrySet()) {
			if (seenParameters.contains(item.getKey()))
				continue;

			KeyInfo keyInfo = parseKey(item.getKey());

			IRI property = prefixResolver.transform(keyInfo.getName());
			// if (property == null)
			// continue;

			try {
				Set<RDFTriple> triples = item.getValue().generate(subject,
						null, null, null);

				logger.trace("Generated default triples = " + triples);
				result.put(property, triples);
				logger.trace(result);
			}
			catch (Exception e) {
				logger.warn(ExceptionUtil.toString(e));
			}
		}

		return result;
	}

	/**
	 * Parses an argument key - returns the name and the language prefix
	 * 
	 * language prefix is turned upper case
	 * 
	 * @param key
	 */
	private KeyInfo parseKey(String key)
	{
		int i = key.lastIndexOf('@');

		return i < 0 ? new KeyInfo(key, null) : new KeyInfo(
				key.substring(0, i), key.substring(i + 1).toLowerCase());
	}

}
