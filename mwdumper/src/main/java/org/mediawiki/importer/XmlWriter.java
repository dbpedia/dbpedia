package org.mediawiki.importer;

import java.io.BufferedWriter;
import java.io.IOException;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.util.ArrayList;

/**
 * Quickie little class for sending properly encoded, prettily
 * indented XML output to a stream. There is no namespace support,
 * so prefixes and xmlns attributes must be managed manually.
 */
public class XmlWriter {
	OutputStream stream;
	String encoding;
	ArrayList stack;
	BufferedWriter writer;
	
	public XmlWriter(OutputStream stream) throws IOException {
		this.stream = stream;
		encoding = "utf-8";
		stack = new ArrayList();
		writer = new BufferedWriter(new OutputStreamWriter(stream, "UTF8"));
	}
	
	/**
	 * @throws IOException 
	 */
	public void close() throws IOException {
		writer.flush();
		writer.close();
	}
	
	
	/**
	 * Write the <?xml?> header.
	 * @throws IOException 
	 */
	public void openXml() throws IOException {
		writeRaw("<?xml version=\"1.0\" encoding=\"" + encoding + "\" ?>\n");
	}
	
	/**
	 * In theory we might close out open elements or such.
	 */
	public void closeXml() {
	}
	
	
	/**
	 * Write an empty element, such as <el/>, on a standalone line.
	 * Takes an optional dictionary of attributes.
	 * @throws IOException 
	 */
	public void emptyElement(String element) throws IOException {
		emptyElement(element, null);
	}
	
	public void emptyElement(String element, String[][] attributes) throws IOException {
		startElement(element, attributes, "/>\n");
		deindent();
	}
	
	/**
	 * Write an element open tag, such as <el>, on a standalone line.
	 * Takes an optional dictionary of attributes.
	 * @throws IOException 
	 */
	public void openElement(String element) throws IOException {
		openElement(element, null);
	}
	
	public void openElement(String element, String[][] attributes) throws IOException {
		startElement(element, attributes, ">\n");
	}
	
	/**
	 * Write an element close tag, such as </el>, on a standalone line.
	 * If indent=False is passed, indentation will not be added.
	 * @throws IOException 
	 */
	public void closeElement() throws IOException {
		closeElement(true);
	}
	
	public void closeElement(boolean indent) throws IOException {
		String[] bits = deindent();
		String element = bits[0];
		String space = bits[1];
		if (indent)
			writeRaw(space + "</" + element + ">\n");
		else
			writeRaw("</" + element + ">\n");
	}
	
	/**
	 * Write an element with a text node included, such as <el>foo</el>,
	 * on a standalone line. If the text is empty, an empty element will
	 * be output as <el/>. Takes an optional list of tuples with attribute
	 * names and values.
	 * @throws IOException 
	 */
	public void textElement(String element, String text) throws IOException {
		textElement(element, text, null);
	}
	
	public void textElement(String element, String text, String[][] attributes) throws IOException {
		if (text==null || text.length() == 0) {
			emptyElement(element, attributes);
		} else {
			startElement(element, attributes, ">");
			writeEscaped(text);
			closeElement(false);
		}
	}
	
	void startElement(String element, String[][] attributes, String terminator) throws IOException {
		writeRaw(indent(element));
		writeRaw('<');
		writeRaw(element);
		if (attributes != null) {
			for (int i = 0; i < attributes.length; i++) {
				writeRaw(' ');
				writeRaw(attributes[i][0]);
				writeRaw("=\"");
				writeEscaped(attributes[i][1]);
				writeRaw('"');
			}
		}
		writeRaw(terminator);
	}
	
	/**Send an encoded Unicode string to the output stream.
	 * @throws IOException */
	void writeRaw(String data) throws IOException {
		writer.write(data);
	}
	
	void writeRaw(char c) throws IOException {
		writer.write(c);
	}
	
	void writeEscaped(String data) throws IOException {
		int end = data.length();
		for (int i = 0; i < end; i++) {
			char c = data.charAt(i);
			switch (c) {
			case '&':
				writer.write("&amp;");
				break;
			case '<':
				writer.write("&lt;");
				break;
			case '>':
				writer.write("&gt;");
				break;
			case '"':
				writer.write("&quot;");
				break;
			default:
				writer.write(c);
			}
		}
	}
	
	private String indent(String element) {
		int level = stack.size();
		stack.add(element);
		return spaces(level);
	}
	
	private String[] deindent() {
		String element = (String)stack.remove(stack.size() - 1);
		String space = spaces(stack.size());
		return new String[] {element, space};
	}
	
	private String spaces(int level) {
		StringBuffer buffer = new StringBuffer();
		for (int i = 0; i < level * 2; i++)
			buffer.append(' ');
		return buffer.toString();
	}
}
