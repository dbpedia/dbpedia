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
 * $Id: Dumper.java 23800 2007-07-06 21:01:57Z daniel $
 */

/*
	-> read header info
	site name, url, language, namespace keys
	
	-> read pages.....
	<page>
		-> get title, etc
		<revision>
			-> store each revision
			on next one or end of sequence, write out
			[so for 1.4 schema we can be friendly]
	
	progress report: [TODO]
		if possible, a percentage through file. this might not be possible.
		rates and counts definitely
	
	input:
		stdin or file
		gzip and bzip2 decompression on files with standard extensions
	
	output:
		stdout
		file
		gzip file
		bzip2 file
		future: SQL directly to a server?
	
	output formats:
		XML export format 0.3
		1.4 SQL schema
		1.5 SQL schema
		
*/

package org.mediawiki.dumper;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;

import java.sql.Connection;
import java.sql.DriverManager;
import java.util.zip.GZIPOutputStream;
import java.lang.ClassNotFoundException;

import java.text.ParseException;

import org.mediawiki.importer.*;


class Dumper {
	public static void main(String[] args) throws IOException, ParseException {
		InputStream input = null;
		OutputWrapper output = null;
		DumpWriter sink = null;
		MultiWriter writers = new MultiWriter();
		int progressInterval = 1000;
		
		for (int i = 0; i < args.length; i++) {
			String arg = args[i];
			String[] bits = splitArg(arg);
			if (bits != null) {
				String opt = bits[0], val = bits[1], param = bits[2];
				if (opt.equals("output")) {
					if (output != null) {
						// Finish constructing the previous output...
						if (sink == null)
							sink = new XmlDumpWriter(output.getFileStream());
						writers.add(sink);
						sink = null;
					}
					output = openOutputFile(val, param);
				} else if (opt.equals("format")) {
					if (output == null)
						output = new OutputWrapper(Tools.openStandardOutput());
					if (sink != null)
						throw new IllegalArgumentException("Only one format per output allowed.");
					sink = openOutputSink(output, val, param);
				} else if (opt.equals("filter")) {
					if (sink == null) {
						if (output == null)
							output = new OutputWrapper(Tools.openStandardOutput());
						sink = new XmlDumpWriter(output.getFileStream());
					}
					sink = addFilter(sink, val, param);
				} else if (opt.equals("progress")) {
					progressInterval = Integer.parseInt(val);
				} else if (opt.equals("quiet")) {
					progressInterval = 0;
				} else {
					throw new IllegalArgumentException("Unrecognized option " + opt);
				}
			} else if (arg.equals("-")) {
				if (input != null)
					throw new IllegalArgumentException("Input already set; can't set to stdin");
				input = Tools.openStandardInput();
			} else {
				if (input != null)
					throw new IllegalArgumentException("Input already set; can't set to " + arg);
				input = Tools.openInputFile(arg);
			}
		}
		
		if (input == null)
			input = Tools.openStandardInput();
		if (output == null)
			output = new OutputWrapper(Tools.openStandardOutput());
		// Finish stacking the last output sink
		if (sink == null)
			sink = new XmlDumpWriter(output.getFileStream());
		writers.add(sink);
		
		DumpWriter outputSink = (progressInterval > 0)
				? (DumpWriter)new ProgressFilter(writers, progressInterval)
				: (DumpWriter)writers;
		
		XmlDumpReader reader = new XmlDumpReader(input, outputSink);
		reader.readDump();
	}

	/**
	 * @param arg string in format "--option=value:parameter"
	 * @return array of option, value, and parameter, or null if no match
	 */
	static String[] splitArg(String arg) {
		if (!arg.startsWith("--"))
			return null;
		
		String opt = "";
		String val = "";
		String param = "";
		
		String[] bits = arg.substring(2).split("=", 2);
		opt = bits[0];
		
		if (bits.length > 1) {
			String[] bits2 = bits[1].split(":", 2);
			val = bits2[0];
			if (bits2.length > 1)
				param = bits2[1];
		}
		
		return new String[] {opt, val, param};
	}
	
	// ----------------
	
	static class OutputWrapper {
		private OutputStream fileStream = null;
		private Connection sqlConnection = null;
		
		OutputWrapper(OutputStream aFileStream) {
			fileStream = aFileStream;
		}
		
		OutputWrapper(Connection anSqlConnection) {
			sqlConnection= anSqlConnection;
		}
		
		OutputStream getFileStream() {
			if (fileStream != null)
				return fileStream;
			if (sqlConnection != null)
				throw new IllegalArgumentException("Expected file stream, got SQL connection?");
			throw new IllegalArgumentException("Have neither file nor SQL connection. Very confused!");
		}
		
		SqlStream getSqlStream() throws IOException {
			if (fileStream != null)
				return new SqlFileStream(fileStream);
			if (sqlConnection != null)
				return new SqlServerStream(sqlConnection);
			throw new IllegalArgumentException("Have neither file nor SQL connection. Very confused!");
		}
	}
	
	static OutputWrapper openOutputFile(String dest, String param) throws IOException {
		if (dest.equals("stdout"))
			return new OutputWrapper(Tools.openStandardOutput());
		else if (dest.equals("file"))
			return new OutputWrapper(Tools.createOutputFile(param));
		else if (dest.equals("gzip"))
			return new OutputWrapper(new GZIPOutputStream(Tools.createOutputFile(param)));
		else if (dest.equals("bzip2"))
			return new OutputWrapper(Tools.createBZip2File(param));
		else if (dest.equals("mysql"))
			return connectMySql(param);
		else if (dest.equals("postgresql"))
			return connectPostgres(param);
		else
			throw new IllegalArgumentException("Destination sink not implemented: " + dest);
	}

	private static OutputWrapper connectMySql(String param) throws IOException {
		try {
			Class.forName("com.mysql.jdbc.Driver").newInstance();
			Connection conn = DriverManager.getConnection("jdbc:mysql:" + param);
			return new OutputWrapper(conn);
		} catch (Exception e) {
			//e.printStackTrace();
			throw (IOException)new IOException(e.getMessage()).initCause(e);
		}
	}
	
	private static OutputWrapper connectPostgres(String param) throws IOException {
		try {
			Class.forName("org.postgresql.Driver").newInstance();
			Connection conn = DriverManager.getConnection("jdbc:postgresql:" + param);
			return new OutputWrapper(conn);
		} catch (Exception e) {
			throw new IOException(e.toString());
		}
	}

	static DumpWriter openOutputSink(OutputWrapper output, String format, String param) throws IOException {
		if (format.equals("xml"))
			return new XmlDumpWriter(output.getFileStream());
		else if (format.equals("sphinx"))
			return new SphinxWriter(output.getFileStream());
		else if (format.equals("mysql") || format.equals("pgsql") || format.equals("sql")) {
			SqlStream sqlStream = output.getSqlStream();
			SqlWriter ret;

			SqlWriter.Traits tr;
			if (format.equals("pgsql"))
				tr = new SqlWriter.PostgresTraits();
			else
				tr = new SqlWriter.MySQLTraits();

			if (param.equals("1.4"))
				ret = new SqlWriter14(tr, sqlStream);
			else if (param.equals("1.5"))
				ret = new SqlWriter15(tr, sqlStream);
			else
				throw new IllegalArgumentException("SQL version not known: " + param);

			return ret;
		} else
			throw new IllegalArgumentException("Output format not known: " + format);
	}
	
	// ----------------
	
	static DumpWriter addFilter(DumpWriter sink, String filter, String param) throws IOException, ParseException {
		if (filter.equals("latest"))
			return new LatestFilter(sink);
		else if (filter.equals("namespace"))
			return new NamespaceFilter(sink, param);
		else if (filter.equals("notalk"))
			return new NotalkFilter(sink);
		else if (filter.equals("titlematch"))
			return new TitleMatchFilter(sink, param);
		else if (filter.equals("list"))
			return new ListFilter(sink, param);
		else if (filter.equals("exactlist"))
			return new ExactListFilter(sink, param);
		else if (filter.equals("articlelist"))
			return new ArticleListFilter(sink, param);
		else if (filter.equals("revlist"))
			return new RevisionListFilter(sink, param);
		else if (filter.equals("before"))
			return new BeforeTimeStampFilter(sink, param);
		else if (filter.equals("after"))
			return new AfterTimeStampFilter(sink, param);
		else if (filter.equals("truncate-comment"))
			return new TruncateCommentFilter(sink, param);
		else
			throw new IllegalArgumentException("Filter unknown: " + filter);
	}
}
