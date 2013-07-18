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
 * $Id: SqlWriter.java 20338 2007-03-11 16:59:31Z river $
 */

package org.mediawiki.importer;

import java.io.IOException;
import java.text.MessageFormat;
import java.util.Calendar;
import java.util.GregorianCalendar;
import java.util.Map;
import java.util.HashMap;
import java.util.Iterator;
import java.util.TimeZone;


public abstract class SqlWriter implements DumpWriter {
	public static abstract class Traits {
		public abstract SqlLiteral getCurrentTime();
		public abstract SqlLiteral getRandom();
		public abstract String getTextTable();
		public abstract boolean supportsMultiRowInsert();
		public abstract MessageFormat getTimestampFormatter();
		public String getWikiPrologue() {
			return null;
		}
		public String getWikiEpilogue() {
			return null;
		}
	}

	public static class MySQLTraits extends Traits {
		// UTC_TIMESTAMP() is new in MySQL 4.1 or 5.0, so using this
		// godawful hack found in documentation comments:
		public SqlLiteral getCurrentTime() {
			return new SqlLiteral("DATE_ADD('1970-01-01', INTERVAL UNIX_TIMESTAMP() SECOND)+0");
		}
		public SqlLiteral getRandom() {
			return new SqlLiteral("RAND()");	
		}
		public boolean supportsMultiRowInsert() {
			return true;
		}
		public String getTextTable() {
			return "text";
		}
		private static final MessageFormat timestampFormatter = new MessageFormat(
				"{0,number,0000}{1,number,00}{2,number,00}{3,number,00}{4,number,00}{5,number,00}");
		public MessageFormat getTimestampFormatter() {
			return timestampFormatter;
		}
	}

	public static class PostgresTraits extends Traits {
		public SqlLiteral getCurrentTime() {
			return new SqlLiteral("current_timestamp AT TIME ZONE 'UTC'");
		}
		public SqlLiteral getRandom() {
			return new SqlLiteral("RANDOM()");
		}
		public boolean supportsMultiRowInsert() {
			return false;
		}
		public String getTextTable() {
			return "pagecontent";
		}
		private static final MessageFormat timestampFormatter = new MessageFormat(
				"{0,number,0000}-{1,number,00}-{2,number,00} {3,number,00}:{4,number,00}:{5,number,00}");
		public MessageFormat getTimestampFormatter() {
			return timestampFormatter;
		}
		public String getWikiPrologue() {
			return
"ALTER TABLE revision DISABLE TRIGGER ALL;" +
"ALTER TABLE page DISABLE TRIGGER ALL;";
		}
		public String getWikiEpilogue() {
			return
"ALTER TABLE revision ENABLE TRIGGER ALL;" +
"ALTER TABLE page ENABLE TRIGGER ALL;";
		}
	}

	private SqlStream stream;
	private String tablePrefix = "";
	
	protected static final Integer ONE = new Integer(1);
	protected static final Integer ZERO = new Integer(0);
	protected Traits traits;
	
	public SqlWriter(Traits tr, SqlStream output) {
		stream = output;
		traits = tr;
	}
	
	public SqlWriter(Traits tr, SqlStream output, String prefix) {
		stream = output;
		tablePrefix = prefix;
		traits = tr;
	}
	
	public void close() throws IOException {
		stream.close();
	}
	
	public void writeStartWiki() throws IOException {
		stream.writeComment("-- MediaWiki XML dump converted to SQL by mwdumper");
		stream.writeStatement("BEGIN");

		String prologue = traits.getWikiPrologue();
		if (prologue != null)
			stream.writeStatement(prologue);
	}
	
	public void writeEndWiki() throws IOException {
		flushInsertBuffers();

		String epilogue = traits.getWikiEpilogue();
		if (epilogue != null)
			stream.writeStatement(epilogue);
		stream.writeStatement("COMMIT");
		stream.writeComment("-- DONE");
	}
	
	public void writeSiteinfo(Siteinfo info) throws IOException {
		stream.writeComment("");
		stream.writeComment("-- Site: " + commentSafe(info.Sitename));
		stream.writeComment("-- URL: " + commentSafe(info.Base));
		stream.writeComment("-- Generator: " + commentSafe(info.Generator));
		stream.writeComment("-- Case: " + commentSafe(info.Case));
		stream.writeComment("--");
		stream.writeComment("-- Namespaces:");
		for (Iterator i = info.Namespaces.orderedEntries(); i.hasNext();) {
			Map.Entry e = (Map.Entry)i.next();
			stream.writeComment("-- " + e.getKey() + ": " + e.getValue());
		}
		stream.writeComment("");
	}
	
	public abstract void writeStartPage(Page page) throws IOException;
	
	public abstract void writeEndPage() throws IOException;
	
	public abstract void writeRevision(Revision revision) throws IOException;
	
	
	
	protected String commentSafe(String text) {
		// TODO
		return text;
	}
	
	private HashMap insertBuffers = new HashMap();
	private static final int blockSize = 1024 * 512; // default 512k inserts
	protected void bufferInsertRow(String table, Object[][] row) throws IOException {
		StringBuffer sql = (StringBuffer)insertBuffers.get(table);
		if (sql != null) {
			if (traits.supportsMultiRowInsert() && (sql.length() < blockSize)) {
				sql.append(',');
				appendInsertValues(sql, row);
				return;
			} else {
				flushInsertBuffer(table);
			}
		}
		sql = new StringBuffer(blockSize);
		synchronized (sql) { //only for StringBuffer
			appendInsertStatement(sql, table, row);
			insertBuffers.put(table, sql);
		}
	}
	
	protected void flushInsertBuffer(String table) throws IOException {
		stream.writeStatement((CharSequence)insertBuffers.get(table));
		insertBuffers.remove(table);
	}
	
	protected void flushInsertBuffers() throws IOException {
		Iterator iter = insertBuffers.values().iterator();
		while (iter.hasNext()) {
			stream.writeStatement((CharSequence)iter.next());
		}
		insertBuffers.clear();
	}
	
	protected void insertRow(String table, Object[][] row) throws IOException {
		StringBuffer sql = new StringBuffer(65536);
		appendInsertStatement(sql, table, row);		
		stream.writeStatement(sql);
	}
	
	private void appendInsertStatement(StringBuffer sql, String table, Object[][] row) {
		sql.append("INSERT INTO ");
		sql.append(tablePrefix);
		sql.append(table);
		sql.append(" (");
		
		for (int i = 0; i < row.length; i++) {
			String field = (String)row[i][0];
			if (i > 0)
				sql.append(',');
			sql.append(field);
		}
		sql.append(") VALUES ");
		appendInsertValues(sql, row);
	}
	
	private static void appendInsertValues(StringBuffer sql, Object[][] row) {
		sql.append('(');
		for (int i = 0; i < row.length; i++) {
			Object val = row[i][1];
			if (i > 0)
				sql.append(',');
			sql.append(sqlSafe(val));
		}
		sql.append(')');
	}
	
	protected void updateRow(String table, Object[][] row, String keyField, Object keyValue) throws IOException {
		StringBuffer sql = new StringBuffer(65536);
		synchronized (sql) { //only for StringBuffer
		sql.append("UPDATE ");
		sql.append(tablePrefix);
		sql.append(table);
		sql.append(" SET ");
		
		for (int i = 0; i < row.length; i++) {
			String field = (String)row[i][0];
			Object val = row[i][1];
			if (i > 0)
				sql.append(',');
			sql.append(field);
			sql.append('=');
			sql.append(sqlSafe(val));
		}
		
		sql.append(" WHERE ");
		sql.append(keyField);
		sql.append('=');
		sql.append(sqlSafe(keyValue));
		
		stream.writeStatement(sql);
		}
	}
	
	protected static String sqlSafe(Object val) {
		if (val == null)
			return "NULL";
		
		String str = val.toString();
		if (val instanceof String) {
			return sqlEscape(str);
		} else if (val instanceof Integer) {
			return str;
		} else if (val instanceof Double) {
			return str;
		} else if (val instanceof SqlLiteral) {
			return str;
		} else {
			throw new IllegalArgumentException("Unknown type in SQL");
		}
	}
	
	protected static String sqlEscape(String str) {
		if (str.length() == 0)
			return "''"; //TODO "NULL",too ?
		final int len = str.length();
		StringBuffer sql = new StringBuffer(len * 2);
		synchronized (sql) { //only for StringBuffer
		sql.append('\'');
		for (int i = 0; i < len; i++) {
			char c = str.charAt(i);
			switch (c) {
			case '\u0000':
				sql.append('\\').append('0');
				break;
			case '\n':
				sql.append('\\').append('n');
				break;
			case '\r':
				sql.append('\\').append('r');
				break;
			case '\u001a':
				sql.append('\\').append('Z');
				break;
			case '"':
			case '\'':
			case '\\':
				sql.append('\\');
				// fall through
			default:
				sql.append(c);
				break;
			}
		}
		sql.append('\'');
		return sql.toString();
		}
	}
	
	protected static String titleFormat(String title) {
		return title.replace(' ', '_');
	}
	
	protected String timestampFormat(Calendar time) {
		return traits.getTimestampFormatter().format(new Object[] {
				new Integer(time.get(Calendar.YEAR)),
				new Integer(time.get(Calendar.MONTH) + 1),
				new Integer(time.get(Calendar.DAY_OF_MONTH)),
				new Integer(time.get(Calendar.HOUR_OF_DAY)),
				new Integer(time.get(Calendar.MINUTE)),
				new Integer(time.get(Calendar.SECOND))});
	}
	
	protected String inverseTimestamp(Calendar time) {
		return traits.getTimestampFormatter().format(new Object[] {
				new Integer(9999 - time.get(Calendar.YEAR)),
				new Integer(99 - time.get(Calendar.MONTH) - 1),
				new Integer(99 - time.get(Calendar.DAY_OF_MONTH)),
				new Integer(99 - time.get(Calendar.HOUR_OF_DAY)),
				new Integer(99 - time.get(Calendar.MINUTE)),
				new Integer(99 - time.get(Calendar.SECOND))});
	}

	private static final TimeZone utc = TimeZone.getTimeZone("UTC");
	protected static GregorianCalendar now() {
		return new GregorianCalendar(utc);
	}

	int commitInterval = 1000; // Commit a transaction every n pages
	int pageCount = 0;
	protected void checkpoint() throws IOException {
		pageCount++;
		if (pageCount % commitInterval == 0) {
			flushInsertBuffers();
			stream.writeStatement("COMMIT");
			stream.writeStatement("BEGIN");
		}
	}
}
