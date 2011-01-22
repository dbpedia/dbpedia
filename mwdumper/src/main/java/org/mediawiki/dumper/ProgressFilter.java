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
 * $Id: ProgressFilter.java 11275 2005-10-11 00:55:02Z vibber $
 */

package org.mediawiki.dumper;

import java.io.IOException;
import java.text.MessageFormat;

import org.mediawiki.importer.DumpWriter;
import org.mediawiki.importer.Page;
import org.mediawiki.importer.PageFilter;
import org.mediawiki.importer.Revision;

public class ProgressFilter extends PageFilter {
	int pages = 0;
	int revisions = 0;
	int interval = 1000;
	MessageFormat format = new MessageFormat("{0} pages ({1}/sec), {2} revs ({3}/sec)");
	long start = System.currentTimeMillis();
	
	public ProgressFilter(DumpWriter sink, int interval) {
		super(sink);
		this.interval = interval;
		if (interval <= 0)
			throw new IllegalArgumentException("Reporting interval must be positive.");
	}
	
	public void writeStartPage(Page page) throws IOException {
		super.writeStartPage(page);
		pages++;
	}
	
	public void writeRevision(Revision rev) throws IOException {
		super.writeRevision(rev);
		revisions++;
		reportProgress();
	}
	
	/**
	 * If we didn't just show a progress report on the last revision,
	 * show the final results.
	 * @throws IOException 
	 */
	public void writeEndWiki() throws IOException {
		super.writeEndWiki();
		if (revisions % interval != 0)
			showProgress();
	}

	private void reportProgress() {
		if (revisions % interval == 0)
			showProgress();
	}
	
	private void showProgress() {
		long delta = System.currentTimeMillis() - start;
		sendOutput(format.format(new Object[] {
			new Integer(pages),
			rate(delta, pages),
			new Integer(revisions),
			rate(delta, revisions)}));
	}
	
	protected void sendOutput(String text) {
		System.err.println(text);		
	}

	private static Object rate(long delta, int count) {
		return (delta > 0.001)
			? (Object)new Double(1000.0 * (double)count / (double)delta)
			: (Object)"-";
	}
}
