package org.mediawiki.dumper;

import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.zip.GZIPInputStream;

import org.apache.commons.compress.bzip2.CBZip2InputStream;
import org.apache.commons.compress.bzip2.CBZip2OutputStream;

public class Tools {
	static final int IN_BUF_SZ = 1024 * 1024;
	private static final int OUT_BUF_SZ = 1024 * 1024;

	public static InputStream openInputFile(String arg) throws IOException {
		if (arg.equals("-"))
			return openStandardInput();
		InputStream infile = new BufferedInputStream(new FileInputStream(arg), IN_BUF_SZ);
		if (arg.endsWith(".gz"))
			return new GZIPInputStream(infile);
		else if (arg.endsWith(".bz2"))
			return openBZip2Stream(infile);
		else
			return infile;
	}
	
	static InputStream openStandardInput() throws IOException {
		return new BufferedInputStream(System.in, IN_BUF_SZ);
	}

	static InputStream openBZip2Stream(InputStream infile) throws IOException {
		int first = infile.read();
		int second = infile.read();
		if (first != 'B' || second != 'Z')
			throw new IOException("Didn't find BZ file signature in .bz2 file");
		return new CBZip2InputStream(infile);
	}

	static OutputStream openStandardOutput() {
		return new BufferedOutputStream(System.out, OUT_BUF_SZ);
	}

	static OutputStream createBZip2File(String param) throws IOException, FileNotFoundException {
		OutputStream outfile = createOutputFile(param);
		// bzip2 expects a two-byte 'BZ' signature header
		outfile.write('B');
		outfile.write('Z');
		return new CBZip2OutputStream(outfile);
	}

	static OutputStream createOutputFile(String param) throws IOException, FileNotFoundException {
		File file = new File(param);
		file.createNewFile();
		return new BufferedOutputStream(new FileOutputStream(file), OUT_BUF_SZ);
	}
	
	
	// ----------------
	
}
