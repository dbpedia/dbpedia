package org.mediawiki.importer;

import java.io.IOException;
import java.nio.charset.Charset;

public class TruncateCommentFilter implements DumpWriter {

  DumpWriter sink;
	
	/**
	 * @param sink
	 * @param param ignored
	 */
	public TruncateCommentFilter(DumpWriter sink, String param) {
		this.sink = sink;
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
		sink.writeStartPage(page);
	}
	
	public void writeEndPage() throws IOException {
		sink.writeEndPage();
	}
	
	public void writeRevision(Revision revision) throws IOException {
	  // TODO: make length and encoding configurable?
	  if (revision.Comment != null) revision.Comment =  truncate(revision.Comment, 255);
	  sink.writeRevision(revision);
	}

  private static final Charset UTF8 = Charset.forName("UTF-8");
  
  // necessary for some revision comments. 
  // See http://en.wikipedia.org/wiki/User:Chrisahn/CommentTooLong 
  private String truncate( String comment, int length )
  {
    while (comment.getBytes(UTF8).length > length)
    {
      comment = comment.substring(0, comment.length() - 1);
    }
    return comment;
  }

}
