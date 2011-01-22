package helpers;

import java.net.URI;

import org.apache.commons.collections15.Transformer;
import org.coode.owlapi.manchesterowlsyntax.ManchesterOWLSyntaxEditorParser;
import org.semanticweb.owlapi.apibinding.OWLManager;
import org.semanticweb.owlapi.expression.OWLEntityChecker;
import org.semanticweb.owlapi.model.IRI;
import org.semanticweb.owlapi.model.OWLAnnotationProperty;
import org.semanticweb.owlapi.model.OWLClass;
import org.semanticweb.owlapi.model.OWLClassExpression;
import org.semanticweb.owlapi.model.OWLDataFactory;
import org.semanticweb.owlapi.model.OWLDataProperty;
import org.semanticweb.owlapi.model.OWLDatatype;
import org.semanticweb.owlapi.model.OWLNamedIndividual;
import org.semanticweb.owlapi.model.OWLObjectProperty;
import org.semanticweb.owlapi.model.OWLOntologyManager;

public class ManchesterParse {
    //public static final String NS = "http://smi-protege.stanford.edu/ontologies/Parse.owl";
        
    public static OWLClassExpression parse(String text, String base, Transformer<String, IRI> prefixResolver)
    {
        try {
/*
        	String text = null;
        	
        	text =
"Pizza that not (hasTopping some (MeatTopping or FishTopping))";
        	//text = "Class1 SubClassOf: not (has_part some Class2)";
*/
            OWLOntologyManager manager = OWLManager.createOWLOntologyManager();
            OWLDataFactory dataFactory = manager.getOWLDataFactory();
            OWLEntityChecker checker = new StupidEntityChecker(manager.getOWLDataFactory(), base, prefixResolver);
            
            ManchesterOWLSyntaxEditorParser parser = new ManchesterOWLSyntaxEditorParser(dataFactory, text);
            parser.setBase(base);
            parser.setOWLEntityChecker(checker);
            return parser.parseClassExpression();
            //OWLAxiom axiom =  parser.parseAxiom();
            //System.out.println("Axiom = " + axiom);
        }
        catch (Throwable t) {
            t.printStackTrace();
        }    	
        
        return null;
    }
    

    private static class StupidEntityChecker implements OWLEntityChecker {
        private OWLDataFactory factory;
        private Transformer<String, IRI> resolver;
        
        public StupidEntityChecker(OWLDataFactory factory, String base, Transformer<String, IRI> resolver) {
            this.factory = factory;
            this.resolver = resolver;
        }
        


        public OWLClass getOWLClass(String name) {
        	String prefix = "";
        	String className = name;
        	String parts[] = name.split(":", 2);
        	if(parts.length == 2) {
        		prefix = parts[0].trim();
        		className = parts[1].trim();
        	}
        	
            if (prefix.equals("xsd") || Character.isUpperCase(className.toCharArray()[0])) {
            	IRI iri = resolver.transform(name);
            	if(iri != null) {
                	return factory.getOWLClass(iri);
            	}
            }
            
            return null;
        }
        
        public OWLObjectProperty getOWLObjectProperty(String name) {
        	String prefix = "";
        	String className = name;
        	String parts[] = name.split(":", 2);
        	if(parts.length == 2) {
        		prefix = parts[0].trim();
        		className = parts[1].trim();
        	}
        	
            if (!prefix.equals("xsd") && !Character.isUpperCase(className.toCharArray()[0])) {
            	IRI iri = resolver.transform(name);
            	if(iri != null) {
                	return factory.getOWLObjectProperty(iri);
            	}
            }
            
            return null;
        }
        
        public OWLAnnotationProperty getOWLAnnotationProperty(String name) {
            return null;
        }

        public OWLDataProperty getOWLDataProperty(String name) {
            return null;
        }

        public OWLDatatype getOWLDatatype(String name) {
            return null;
        }

        public OWLNamedIndividual getOWLIndividual(String name) {
            return null;
        }
        
    }
}