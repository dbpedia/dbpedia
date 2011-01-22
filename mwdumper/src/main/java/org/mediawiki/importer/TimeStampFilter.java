/*
 * MediaWiki import/export processing tools
 * Copyright 2006 by Aurimas Fischer
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
 */

package org.mediawiki.importer;

import java.io.IOException;
import java.util.Calendar;
import java.text.SimpleDateFormat;
import java.text.ParseException;

public class TimeStampFilter implements DumpWriter {
	DumpWriter sink;
	protected Calendar filterTimeStamp;
	protected Page currentPage;
	protected boolean pageWritten;

	public TimeStampFilter(DumpWriter sink, String timeStamp) throws ParseException {
		this.sink = sink;
		filterTimeStamp = Calendar.getInstance();
		filterTimeStamp.setTime(new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'").parse(timeStamp));
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
		if (!pageWritten) {
			sink.writeStartPage(currentPage);
			pageWritten = true;
		}
		sink.writeRevision(revision);
	}
}
