package org.mediawiki.importer;

import java.io.IOException;
import java.io.OutputStream;
import java.io.PrintStream;

public class SqlFileStream implements SqlStream {
	protected PrintStream stream;
	
	public SqlFileStream(OutputStream output) throws IOException {
		this.stream = new PrintStream(output, false, "UTF-8");
	}
	
	public void writeComment(CharSequence sql) {
		stream.println(sql.toString());
	}
	
	public void writeStatement(CharSequence sql) {
		stream.print(sql.toString());
		stream.println(';');
	}
	
	public void close() {
		stream.flush();
		stream.close();
	}
}
