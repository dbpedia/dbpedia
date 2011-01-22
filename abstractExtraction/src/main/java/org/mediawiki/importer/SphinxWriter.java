/*
 * MediaWiki import/export processing tools
 * Copyright 2006 by Brion Vibber
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
 * $Id: XmlDumpWriter.java 11268 2005-10-10 06:57:30Z vibber $
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

/**
 * Generates XML stream suitable for the Sphinx search engine's xmlpipe input.
 */
public class SphinxWriter implements DumpWriter {
	protected OutputStream stream;
	protected XmlWriter writer;
	protected Page _page;
	protected Revision _rev;
	
	public SphinxWriter(OutputStream output) throws IOException {
		stream = output;
		writer = new XmlWriter(stream);
	}
	
	public void close() throws IOException {
		writer.close();
	}
	
	public void writeStartWiki() throws IOException {
		writer.openXml();
		// No containing element to open
	}
	
	public void writeEndWiki() throws IOException {
		// No containing element to close
		writer.closeXml();
	}
	
	public void writeSiteinfo(Siteinfo info) throws IOException {
		// Nothing!
	}
	
	public void writeStartPage(Page page) throws IOException {
		_page = page;
	}
	
	/**
	 * @fixme What's the "group" number here do?
	 * @fixme preprocess the text to strip some formatting?
	 */
	public void writeEndPage() throws IOException {
		writer.openElement("document");
		writer.textElement("id", Integer.toString(_page.Id));
		writer.textElement("group", "0");
		writer.textElement("timestamp", formatTimestamp(_rev.Timestamp));
		writer.textElement("title", _page.Title.toString());
		writer.textElement("body", _rev.Text);
		writer.closeElement();
		_rev = null;
		_page = null;
	}
	
	public void writeRevision(Revision rev) throws IOException {
		_rev = rev;
	}
	
	/**
	 * @fixme double-check that it wants Unix timestamp
	 */
	static String formatTimestamp(Calendar ts) {
		return Long.toString(ts.getTimeInMillis() / 1000L);
	}
}
