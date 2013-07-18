/*
 * MediaWiki import/export processing tools
 * Copyright 2005 by Brion Vibber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * $Id: XmlDumpWriter.java 54087 2009-07-31 10:39:07Z daniel $
 */

package org.mediawiki.importer;

import java.io.IOException;
import java.io.OutputStream;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Iterator;
import java.util.Map;
import java.util.TimeZone;

public class XmlDumpWriter implements DumpWriter {
	protected OutputStream stream;
	protected XmlWriter writer;
	
	protected static final String version = "0.3";
	protected static final String ns = "http://www.mediawiki.org/xml/export-" + version + "/";
	protected static final String schema = "http://www.mediawiki.org/xml/export-" + version + ".xsd";
	protected static final DateFormat dateFormat = new SimpleDateFormat("yyyy'-'MM'-'dd'T'HH':'mm':'ss'Z'");
	static {
		dateFormat.setTimeZone(TimeZone.getTimeZone("UTC"));
	}
	
	public XmlDumpWriter(OutputStream output) throws IOException {
		stream = output;
		writer = new XmlWriter(stream);
	}
	
	public void close() throws IOException {
		writer.close();
	}
	
	public void writeStartWiki() throws IOException {
		writer.openXml();
		writer.openElement("mediawiki", new String[][] {
			{"xmlns", ns},
			{"xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance"},
			{"xsi:schemaLocation", ns + " " + schema},
			{"version", version},
			{"xml:lang", "en"}});
		// TODO: store and keep the xml:lang
	}
	
	public void writeEndWiki() throws IOException {
		writer.closeElement();
		writer.closeXml();
	}
	
	public void writeSiteinfo(Siteinfo info) throws IOException {
		XmlWriter writer = this.writer;
		writer.openElement("siteinfo");
		writer.textElement("sitename", info.Sitename);
		writer.textElement("base", info.Base);
		writer.textElement("generator", info.Generator);
		writer.textElement("case", info.Case);
		
		writer.openElement("namespaces");
		for (Iterator i = info.Namespaces.orderedEntries(); i.hasNext();) {
			Map.Entry e = (Map.Entry)i.next();
			writer.textElement("namespace", e.getValue().toString(), new String[][] {
					{"key", e.getKey().toString()}});
		}
		writer.closeElement();
		
		writer.closeElement();
	}
	
	public void writeStartPage(Page page) throws IOException {
		XmlWriter writer = this.writer;
		writer.openElement("page");
		writer.textElement("title", page.Title.toString());
		if (page.Id != 0)
			writer.textElement("id", Integer.toString(page.Id));
		if (page.Restrictions != null && page.Restrictions.length() != 0)
			writer.textElement("restrictions", page.Restrictions);
	}
	
	public void writeEndPage() throws IOException {
		writer.closeElement();
	}
	
	public void writeRevision(Revision rev) throws IOException {
		XmlWriter writer = this.writer;
		writer.openElement("revision");
		if (rev.Id != 0)
			writer.textElement("id", Integer.toString(rev.Id));
		
		writer.textElement("timestamp", formatTimestamp(rev.Timestamp));
		
		writeContributor(rev.Contributor);
		
		if (rev.Minor) {
			writer.emptyElement("minor");
		}
		
		if (rev.Comment == null) {
			writer.emptyElement("comment", deletedAttrib);
		} 
		else if (rev.Comment.length() != 0) {
			writer.textElement("comment", rev.Comment);
		}
		
		writer.textElement("text", rev.Text, 
				rev.Text==null ? new String[][] {{"xml:space", "preserve"}, {"deleted", "deleted"}} 
												: new String[][] {{"xml:space", "preserve"}}
		);
		
		writer.closeElement();
	}

	static final String[][] deletedAttrib = new String[][] { {"deleted", "deleted"} };
	
	static String formatTimestamp(Calendar ts) {
		return dateFormat.format(ts.getTime());
	}
	
	void writeContributor(Contributor contrib) throws IOException {
		XmlWriter writer = this.writer;
		
		if (contrib.Username==null) {
			writer.emptyElement("contributor", deletedAttrib);
		}
		else {
			writer.openElement("contributor");
			if (contrib.isIP) {
				writer.textElement("ip", contrib.Username);
			} else {
				writer.textElement("username", contrib.Username);
				writer.textElement("id", Integer.toString(contrib.Id));
			}
			writer.closeElement();
		}
	}
}
