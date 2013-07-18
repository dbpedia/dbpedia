package helpers;

import java.io.ByteArrayInputStream;
import java.util.Collection;
import java.util.HashSet;
import java.util.Random;
import java.util.Set;

import oaiReader.JenaToOwlApi;
import oaiReader.SparqlHelper;

import org.apache.commons.collections15.Transformer;
import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.coode.owlapi.rdf.model.RDFTriple;
import org.semanticweb.owlapi.model.IRI;

import com.hp.hpl.jena.n3.turtle.parser.ParseException;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;


class TripleToCanonicalStringTransformer
	implements Transformer<RDFTriple, String>
{
	@Override
	public String transform(RDFTriple triple)
	{
		return TripleUtil.toCanonicalString(triple);
	}
}

/**
 * The canonicalX methods can be used to create hashes on sets of triples 
 *  
 * @author raven
 *
 */
public class TripleUtil
{
	public static String toCanonicalString(Collection<RDFTriple> triples)
	{
		return toCanonicalString(new HashSet<RDFTriple>(triples));
	}
	
	public static String toCanonicalString(Set<RDFTriple> triples)
	{
		StringBuilder builder = new StringBuilder();
		for(RDFTriple item : triples) {
			builder.append(toCanonicalString(item));
			builder.append(". ");
		}
		
		return builder.toString();
		/*
		return StringUtil.implode(". ", new TransformIterator<RDFTriple, String>(
						triples.iterator(),
						new TripleToCanonicalStringTransformer()));
		*/
	}
	
	public static String toCanonicalString(RDFTriple triple)
	{
		return SparqlHelper.toSparqlString(triple);
		/*
		return
			triple.getSubject().toString() + " " +
			triple.getProperty().toString() + " " +
			triple.getObject().toString();
		*/
	}
	
	private static final Random random = new Random();

	public static RDFResourceNode randomResource()
	{
		return new RDFResourceNode(
				IRI.create("http://ex.org/random/" + random.nextInt()));		
	}
	
	public static RDFTriple randomTriple()
	{
		return new RDFTriple(randomResource(), randomResource(), randomResource());
	}
	
	public static Set<RDFTriple> randomTripleSet(int size)
	{
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		while(result.size() < size)
			result.add(randomTriple());
		
		return result;
	}

	public static String md5(Set<RDFTriple> triples)
	{
		return MD5Util.generateMD5(toCanonicalString(triples));
	}
	
	public static byte[] zip(Set<RDFTriple> triples)
	{
		return StringUtil.zip(toCanonicalString(triples));
	}
	
	public static Set<RDFTriple> unzip(byte[] bytes)
	{
		return deserialize(StringUtil.unzip(bytes));
	}
	
	
	/**
	 * deserialize using jena
	 * 
	 * @param triples
	 * @return
	 * @throws ParseException 
	 */
	public static Set<RDFTriple> deserialize(String str)
	{
		Model model = ModelFactory.createDefaultModel();
		//model.read(new ByteArrayInputStream(str.getBytes()), "http://base.org/");
		model.read(new ByteArrayInputStream(str.getBytes()), "", "N3");

		return JenaToOwlApi.toTriples(model);

		/*
		StmtIterator it = model.listStatements();
		while(it.hasNext()) {
			Statement stmt = it.next();
			Triple triple = stmt.asTriple();
			
			
			//RDFTriple triple = new 
			triple.getSubject();
			triple.getPredicate();
			triple.getObject();
			
		}
		
		return null;
		/*
		Set<RDFTriple> result = new HashSet<RDFTriple>();
		while(it.hasNext()) {
			result.add((RDFTriple)it.next());
		}*/
	}
}
