import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.LineNumberReader;
import java.io.StringReader;
import java.io.StringWriter;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.RDFReader;
import com.hp.hpl.jena.rdf.model.RDFWriter;
import com.hp.hpl.jena.shared.JenaException;

/**
 * Checks an N-Triples file for syntactic validity and makes
 * sure that it can be serialized as RDF/XML. Takes one filename
 * from the command line and outputs any problems on STDOUT.
 * 
 * There is a shell script and Windows batch file for running
 * the validator from the command line:
 * 
 *     dumpvaldiator filename.nt
 * 
 * The implementation reads a chunk of lines from the file,
 * tries to parse it as an N-Triples document, and writes
 * it back to RDF/XML. If an exception is thrown while parsing
 * or writing, then each line of the chunk is parsed and
 * written to RDF/XML seperately, to identify the exact
 * offending line or lines.
 * 
 * This needs all Jena jar files on the classpath and has been
 * tested with Jena 2.5.3.
 * 
 * @version $Id$
 * @author Georgi Kobilarov
 * @author Richard Cyganiak (richard@cyganiak.de)
 */
public class DumpValidator {

	private static int CHUNK_SIZE = 10000;

	public static void main(String[] args) throws Exception {

		// Display help if no command line argument given
		if (args.length != 1) {
			System.out.println("Usage: dumpvalidator dumpname.nt");
			System.exit(0);
		}

		// Open file
		String filename = args[0];
		
		new DumpValidator(filename).validate();
	}
	
	private final String filename;
	private final RDFReader reader;
	private final RDFWriter writer;
	private boolean done = false;
	
	private DumpValidator(String filename) {
		this.filename = filename;
		Model m = ModelFactory.createDefaultModel();
		this.reader = m.getReader("N-TRIPLES");
		this.writer = m.getWriter("RDF/XML");
	}
	
	private void validate() throws IOException {
		LineNumberReader input = new LineNumberReader(new BufferedReader(new InputStreamReader(
				new FileInputStream(new File(this.filename)))));
		System.out.println("Validating " + this.filename + " ...");
		while (!this.done) {
			processChunk(input);
			// Show progress
			System.out.println(input.getLineNumber() + " triples ...");
		}
		System.out.println("Done.");
	}
	
	private void processChunk(LineNumberReader input) throws IOException {
		int firstLineNumber = input.getLineNumber() + 1;
		String[] lines = new String[CHUNK_SIZE];
		StringBuffer chunk = new StringBuffer();
		for (int i = 0; i < CHUNK_SIZE; i++) {
			String line = input.readLine();
			if (line == null) {	// EOF?
				this.done = true;
				break;
			}
			chunk.append(line);
			lines[i] = line;
		}
		Model m = ModelFactory.createDefaultModel();
		try {
			this.reader.read(m, new StringReader(chunk.toString()), null);
			this.writer.write(m, new StringWriter(), null);
		} catch (JenaException ex) {
			System.out.println();
			processLines(lines, firstLineNumber);
		}
	}
	
	private void processLines(String[] lines, int firstLineNumber) {
		for (int i = 0; i < lines.length; i++) {
			if (lines[i] == null) continue;
			String line = lines[i];
			Model singleton = ModelFactory.createDefaultModel();
			try {
				this.reader.read(singleton, new StringReader(line), null);
				this.writer.write(singleton, new StringWriter(), null);
			} catch (JenaException ex) {
				System.out.println("Line " + (firstLineNumber + i) + ": " + ex.getClass());
				System.out.println("  Message: " + ex.getMessage());
				System.out.println("  Triple: " + line);
				System.out.println();
			}
		}
	}
}