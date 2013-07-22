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


import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.util.Date;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
import org.apache.lucene.document.Document;
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

    public static void searchPage( File indexDir, String q, String filed, int hitsPerPage )
        throws Exception
    {

        FileWriter outFile;
        //= new FileWriter("C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0.txt",true);

        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
        NIOFSDirectory dir = new NIOFSDirectory( indexDir );
        Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( q );



        IndexReader reader = IndexReader.open( dir );
        IndexSearcher searcher = new IndexSearcher( reader );
        TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
        searcher.search( query, collector );
        ScoreDoc[] hits = collector.topDocs().scoreDocs;


        System.out.println( "Found " + hits.length + " hits." );


        for ( int i = 0; i < hits.length; ++i )
        {
            outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0.txt", true );

            int docId = hits[i].doc;
            Document d = searcher.doc( docId );
            outFile.append( d.get( "page_id" ) + "\t" + d.get( "page_namespace" ) + "\t" + d.get( "page_title" ) + "\n" );
            //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
            outFile.close();
        }

    }

    public static void searchCategoryLinks( File indexDir, String filed, int hitsPerPage )
        throws Exception
    {
       
        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
        NIOFSDirectory dir = new NIOFSDirectory( indexDir );
          IndexReader reader = IndexReader.open( dir );
        IndexSearcher searcher = new IndexSearcher( reader );
     
        
        
        
        File pageNamespaceResultFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0_new.txt" );
        String line;
        BufferedReader fileReader;
        fileReader = new BufferedReader( new FileReader( pageNamespaceResultFile ) );
        while ( ( line = fileReader.readLine() ) != null )
        {
          if(!line.isEmpty())  {
            String[] strArr = line.split( "\\t" );
            FileWriter outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\categorylinks_match_article_pages.txt", true );
            
        
           TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
        Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( strArr[0].trim() );
        searcher.search( query, collector );
        ScoreDoc[] hits = collector.topDocs().scoreDocs;


        System.out.println( strArr[0]+"\t"+hits.length);


        for ( int i = 0; i < hits.length; ++i )
        {
            int docId = hits[i].doc;
            Document d = searcher.doc( docId );
            outFile.append( d.get( "cl_from" ) + "\t" + d.get( "cl_to" ) + "\t" + d.get( "cl_sortkey" ) + d.get( "cl_type" ) +"\n" );
            //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
        }
         outFile.close();
        }
    }
        
       
    }
}
