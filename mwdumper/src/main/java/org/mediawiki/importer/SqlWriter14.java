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
 * $Id: SqlWriter14.java 54087 2009-07-31 10:39:07Z daniel $
 */

package org.mediawiki.importer;

import java.io.IOException;


public class SqlWriter14 extends SqlWriter {
	private Page currentPage;
	private Revision lastRevision;
	
	public SqlWriter14(SqlWriter.Traits tr, SqlStream output) {
		super(tr, output);
	}
	
	public SqlWriter14(SqlWriter.Traits tr, SqlStream output, String prefix) {
		super(tr, output, prefix);
	}
	
	public void writeStartPage(Page page) {
		currentPage = page;
		lastRevision = null;
	}
	
	public void writeEndPage() throws IOException {
		if (lastRevision != null)
			writeCurRevision(currentPage, lastRevision);
		currentPage = null;
		lastRevision = null;
	}
	
	public void writeRevision(Revision revision) throws IOException {
		if (lastRevision != null)
			writeOldRevision(currentPage, lastRevision);
		lastRevision = revision;
	}
	
	private void writeOldRevision(Page page, Revision revision) throws IOException {
		bufferInsertRow("old", new Object[][] {
				{"old_id", new Integer(revision.Id)},
				{"old_namespace", page.Title.Namespace},
				{"old_title", titleFormat(page.Title.Text)},
				{"old_text", revision.Text == null ? "" : revision.Text},
				{"old_comment", revision.Comment == null ? "" : revision.Comment},
				{"old_user", revision.Contributor.Username == null ? ZERO : new Integer(revision.Contributor.Id)},
				{"old_user_text", revision.Contributor.Username == null ? "" : revision.Contributor.Username},
				{"old_timestamp", timestampFormat(revision.Timestamp)},
				{"old_minor_edit", revision.Minor ? ONE : ZERO},
				{"old_flags", "utf-8"},
				{"inverse_timestamp", inverseTimestamp(revision.Timestamp)}});
	}
	
	private void writeCurRevision(Page page, Revision revision) throws IOException {
		bufferInsertRow("cur", new Object[][] {
				{"cur_id", new Integer(page.Id)},
				{"cur_namespace", page.Title.Namespace},
				{"cur_title", titleFormat(page.Title.Text)},
				{"cur_text", revision.Text == null ? "" : revision.Text},
				{"cur_comment", revision.Comment == null ? "" : revision.Comment},
				{"cur_user", revision.Contributor.Username == null ? ZERO : new Integer(revision.Contributor.Id)},
				{"cur_user_text", revision.Contributor.Username == null ? "" : revision.Contributor.Username},
				{"cur_timestamp", timestampFormat(revision.Timestamp)},
				{"cur_restrictions", page.Restrictions},
				{"cur_counter", ZERO},
				{"cur_is_redirect", revision.isRedirect() ? ONE : ZERO},
				{"cur_minor_edit", revision.Minor ? ONE : ZERO},
				{"cur_random", traits.getRandom()},
				{"cur_touched", traits.getCurrentTime()},
				{"inverse_timestamp", inverseTimestamp(revision.Timestamp)}});
		checkpoint();
	}
}
