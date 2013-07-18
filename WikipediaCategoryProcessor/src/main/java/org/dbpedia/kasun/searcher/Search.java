/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Jul 17, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.searcher;


import java.io.File;
import java.util.Date;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.queryparser.classic.QueryParser;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.ScoreDoc;
import org.apache.lucene.search.TopScoreDocCollector;

import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.lucene.store.NIOFSDirectory;
import org.apache.lucene.util.Version;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Search
{

    public static void search( File indexDir, String q ,String pathToIndex)
        throws Exception
    {
        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
 NIOFSDirectory dir = new NIOFSDirectory(new File(pathToIndex)) ;
        String querystr = "lucene";
        Query query = new QueryParser( Version.LUCENE_43, "title", analyzer ).parse( querystr );



        int hitsPerPage = 10;
        IndexReader reader = IndexReader.open( dir);
        IndexSearcher searcher = new IndexSearcher( reader );
        TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
        searcher.search( query, collector );
        ScoreDoc[] hits = collector.topDocs().scoreDocs;

    }
}
