package helpers;

import java.util.ArrayList;
import java.util.List;

import oaiReader.SparqlHelper;

import org.coode.owlapi.rdf.model.RDFNode;

public class SparulUtil
{
	/**
	 * Returns a set of queries which rename a resource.
	 * Returns empty set if no renaming is required (e.g. rename(A, A) -> e)
	 * 
	 * @param src
	 * @param dest
	 * @param graphName
	 * @return
	 */
	public static List<String> generateRenameQuery(RDFNode src, RDFNode dest, String graphName)
	{
		List<String> result = new ArrayList<String>();
		
		if(src.equals(dest))
			return result;
		
		for(int i = 0; i < 3; ++i) {
			String tmp = generateRenameQuery(src, dest, graphName, i);
			if(tmp != null)
				result.add(tmp);
		}
		
		return result;
	}

	public static String generateRenameQuery(RDFNode src, RDFNode dest, String graphName, int pos)
	{
		// FIXME Validation. E.g. its an error to rename a subject resource 
		// into a literal
		return generateRenameQuery(
				SparqlHelper.toSparqlString(src),
				SparqlHelper.toSparqlString(dest),
				graphName,
				pos);
	}

	public static String generateRenameQuery(String src, String dest, String graphName, int pos)
	{
		String graphPart = graphName == null ? "" : "<" + graphName + ">";

		List<String> spoSrc  = CollectionUtil.newList("?s", "?p", "?o");
		List<String> spoDest = CollectionUtil.newList("?s", "?p", "?o");
		
		spoSrc.set(pos, src);
		spoDest.set(pos, dest);
		
		String s = StringUtil.implode(" ", spoSrc);
		String d = StringUtil.implode(" ", spoDest);
	
		return
			"MODIFY " + graphPart + "\n" +
			"DELETE {" + s + ". }\n" +
			"INSERT {" + d + ". }\n" +
			"WHERE  {" + s + ". }\n";
	}
}
