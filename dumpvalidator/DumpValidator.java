import java.io.BufferedReader;
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
 * Checks an N-Triples file for syntactic validity and ensures
 * it can be serialized as RDF/XML. Takes one filename from
 * the command line and outputs any problems on STDOUT.
 * 
 * Usage: java DumpValidator filename.nt
 * 
 * This implementation reads a chunk of lines from the file,
 * tries to parse it as an N-Triples document, and writes
 * it back to RDF/XML. If an exception is thrown while parsing
 * or writing, each line of the chunk is parsed and written
 * to RDF/XML separately to identify the exact offending line(s).
 * 
 * This program requires all Jena jar files on the classpath
 * and has been tested with Jena 2.5.3.
 * 
 * @version $Id$
 * @author Georgi Kobilarov
 * @author Richard Cyganiak (richard@cyganiak.de)
 */
public class DumpValidator {

    private static final int CHUNK_SIZE = 10000;

    public static void main(String[] args) {
        if (args.length != 1) {
            System.out.println("Usage: java DumpValidator filename.nt");
            System.exit(1);
        }

        String filename = args[0];
        new DumpValidator(filename).validate();
    }

    private final String filename;
    private final RDFReader reader;
    private final RDFWriter writer;

    private DumpValidator(String filename) {
        this.filename = filename;
        Model model = ModelFactory.createDefaultModel();
        this.reader = model.getReader("N-TRIPLES");
        this.writer = model.getWriter("RDF/XML");
    }

    private void validate() {
        try (LineNumberReader input = new LineNumberReader(
                new BufferedReader(new InputStreamReader(new FileInputStream(new File(this.filename)))))) {
            System.out.println("Validating " + this.filename + " ...");
            while (true) {
                processChunk(input);
                System.out.println(input.getLineNumber() + " triples ...");
                if (input.ready()) {
                    break;
                }
            }
            System.out.println("Done.");
        } catch (IOException e) {
            System.err.println("Error reading file: " + e.getMessage());
        }
    }

    private void processChunk(LineNumberReader input) {
        int firstLineNumber = input.getLineNumber() + 1;
        String[] lines = new String[CHUNK_SIZE];
        StringBuilder chunk = new StringBuilder();
        try {
            for (int i = 0; i < CHUNK_SIZE; i++) {
                String line = input.readLine();
                if (line == null) { // EOF?
                    break;
                }
                chunk.append(line);
                lines[i] = line;
            }
            Model model = ModelFactory.createDefaultModel();
            this.reader.read(model, new StringReader(chunk.toString()), null);
            this.writer.write(model, new StringWriter(), null);
        } catch (IOException e) {
            e.printStackTrace();
        } catch (JenaException e) {
            System.out.println();
            processLines(lines, firstLineNumber);
        }
    }

    private void processLines(String[] lines, int firstLineNumber) {
        for (int i = 0; i < lines.length; i++) {
            if (lines[i] == null)
                continue;
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
