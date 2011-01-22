package triplemanagement;

import oaiReader.MyVocabulary;

import org.coode.owlapi.rdf.model.RDFResourceNode;
import org.semanticweb.owlapi.model.IRI;


public class DBpediaGroupDef
	extends GroupDef
{
	public DBpediaGroupDef(IRI extractor, IRI target)
	{
		super(
				null,
				new GroupDefSchema(
						MyVocabulary.DBM_EXTRACTED_BY.getUri(),
						MyVocabulary.DBM_TARGET.getUri()),
				new RDFResourceNode(extractor),
				new RDFResourceNode(target));
	}
}
