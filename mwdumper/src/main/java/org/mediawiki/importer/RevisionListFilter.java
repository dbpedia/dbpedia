/*
 * MediaWiki import/export processing tools
 * Copyright 2006 by Tim Starling
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
 * $Id: RevisionListFilter.java 13834 2006-04-24 03:44:19Z tstarling $
 */

package org.mediawiki.importer;

import java.lang.Integer;
import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.TreeSet;

public class RevisionListFilter implements DumpWriter {
	DumpWriter sink;
	protected TreeSet revIds;
	protected Page currentPage;
	protected boolean pageWritten;
	
	public RevisionListFilter(DumpWriter sink, String sourceFileName) throws IOException {
		this.sink = sink;
		revIds = new TreeSet();
		BufferedReader input = new BufferedReader(new InputStreamReader(
			new FileInputStream(sourceFileName), "utf-8"));
		String line = input.readLine();
		while (line != null) {
			line = line.trim();
			if (line.length() > 0 && !line.startsWith("#")) {
				revIds.add(new Integer(line));
			}
			line = input.readLine();
		}
		input.close();
	}
	
	public void close() throws IOException {
		sink.close();
	}
	
	public void writeStartWiki() throws IOException {
		sink.writeStartWiki();
	}
	
	public void writeEndWiki() throws IOException {
		sink.writeEndWiki();
	}
	
	public void writeSiteinfo(Siteinfo info) throws IOException {
		sink.writeSiteinfo(info);
	}
	
	public void writeStartPage(Page page) throws IOException {
		currentPage = page;
		pageWritten = false;
	}
	
	public void writeEndPage() throws IOException {
		if (pageWritten) {
			sink.writeEndPage();
		}
	}
	
	public void writeRevision(Revision revision) throws IOException {
		if (revIds.contains(new Integer(revision.Id))) {
			if (!pageWritten) {
				sink.writeStartPage(currentPage);
				pageWritten = true;
			}
			sink.writeRevision(revision);
		}
	}
}
