package helpers;

import java.util.Set;

import org.coode.owlapi.rdf.model.RDFTriple;
import org.junit.Test;

public class TestTripleUtilTest
{

	@Test
	public void testDeserialize()
	{
		System.out.println("Here");
		Set<RDFTriple> triples = TripleUtil.deserialize("<http://ex.org/s> <http://ex.org/p> <http://ex.org/o> .");
		
		System.out.println(triples);
		
		//fail("Not yet implemented");
	}

}
