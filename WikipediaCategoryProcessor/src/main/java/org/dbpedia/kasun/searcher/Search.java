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


import java.io.*;
import java.util.ArrayList;
import java.util.Date;
import java.util.LinkedList;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
import org.apache.lucene.document.Document;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.queryparser.classic.ParseException;
import org.apache.lucene.queryparser.classic.QueryParser;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.ScoreDoc;
import org.apache.lucene.search.TopScoreDocCollector;

import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.lucene.store.NIOFSDirectory;
import org.apache.lucene.util.Version;
import org.apache.lucene.queryparser.classic.ParseException;

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
            if ( !line.isEmpty() )
            {
                String[] strArr = line.split( "\\t" );
                FileWriter outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\categorylinks_match_article_pages.txt", true );


                TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
                Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( strArr[0].trim() );
                searcher.search( query, collector );
                ScoreDoc[] hits = collector.topDocs().scoreDocs;


                System.out.println( strArr[0] + "\t" + hits.length );


                for ( int i = 0; i < hits.length; ++i )
                {
                    int docId = hits[i].doc;
                    Document d = searcher.doc( docId );
                    outFile.append( d.get( "cl_from" ) + "\t" + d.get( "cl_to" ) + "\t" + d.get( "cl_sortkey" ) + d.get( "cl_type" ) + "\n" );
                    //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
                }
                outFile.close();
            }
        }


    }

    public static void searchCategory( File indexDir, String filed, int hitsPerPage )
        throws Exception
    {

        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
        NIOFSDirectory dir = new NIOFSDirectory( indexDir );
        IndexReader reader = IndexReader.open( dir );
        IndexSearcher searcher = new IndexSearcher( reader );




        File uniqueCatFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\sorted_f2_categorylinks_match_article_pages.txt" );
        String line;
        BufferedReader fileReader;
        fileReader = new BufferedReader( new FileReader( uniqueCatFile ) );
        while ( ( line = fileReader.readLine() ) != null )
        {
            if ( !line.isEmpty() )
            {
                // String[] strArr = line.split( "\\t" );
                FileWriter outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\categories_match_article_pages.txt", true );


                TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
                Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( "'" + line.trim() + "'" );
                searcher.search( query, collector );
                ScoreDoc[] hits = collector.topDocs().scoreDocs;

                if ( hits.length == 0 )
                {
                    System.out.println( line );
                }

                for ( int i = 0; i < hits.length; ++i )
                {
                    int docId = hits[i].doc;
                    Document d = searcher.doc( docId );
                    outFile.append( d.get( "cat_id" ) + "\t" + d.get( "cat_title" ) + "\n" );
                    //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
                }
                outFile.close();
            }
        }


    }

    public static ArrayList<String> SearchCatPageLinks( int pageID ) throws IOException, ParseException
    {

        File indexDir = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\category_page_links_view" );
        String filed = "page_id";
        int hitsPerPage = 100;

        ArrayList<String> clToResults = new ArrayList<String>();
        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
        NIOFSDirectory dir = new NIOFSDirectory( indexDir );
        IndexReader reader = IndexReader.open( dir );
        IndexSearcher searcher = new IndexSearcher( reader );


        TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
        Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( "" + pageID + "" );
        searcher.search( query, collector );
        ScoreDoc[] hits = collector.topDocs().scoreDocs;

        if ( hits.length == 0 )
        {
            System.out.println( pageID );
        } else
        {
         //   System.out.println( hits.length );
            for ( int i = 0; i < hits.length; ++i )
            {
                int docId = hits[i].doc;
                Document d = searcher.doc( docId );
                clToResults.add( d.get( "page_title" ) );
                //  outFile.append( d.get( "cat_id" ) + "\t" + d.get( "cat_title" ) + "\n" );
                //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
            }
        }
        reader.close();
        dir.close();
        return clToResults;
    }
    
        public static LinkedList<Integer> SearchCategoryPages( String pageTitle ) throws IOException, ParseException
    {

        File indexDir = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\categoty_page_candidate_index" );
        String filed = "page_title";
        int hitsPerPage = 5;
        
       

        LinkedList<Integer> clToResults = new LinkedList<Integer>();
        WhitespaceAnalyzer analyzer = new WhitespaceAnalyzer( Version.LUCENE_43 );
        NIOFSDirectory dir = new NIOFSDirectory( indexDir );
        IndexReader reader = IndexReader.open( dir );
        IndexSearcher searcher = new IndexSearcher( reader );
        FileWriter outFile2;
 try{

        TopScoreDocCollector collector = TopScoreDocCollector.create( hitsPerPage, true );
        Query query = new QueryParser( Version.LUCENE_43, filed, analyzer ).parse( pageTitle );
        searcher.search( query, collector );
        ScoreDoc[] hits = collector.topDocs().scoreDocs;
        
         if ( hits.length == 0 )
        {
            System.out.println( pageTitle );
        } else
        {
          //  System.out.println( hits.length );
            for ( int i = 0; i < hits.length; ++i )
            {
                int docId = hits[i].doc;
                Document d = searcher.doc( docId );
                clToResults.add( Integer.valueOf( d.get( "page_id" )) );
                //  outFile.append( d.get( "cat_id" ) + "\t" + d.get( "cat_title" ) + "\n" );
                //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
            }
        }
        
         return clToResults;
        }
        catch (ParseException e){
             outFile2 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\data_not_inserted_node_table\\pages_can't_parse.txt", true );
                        outFile2.append(pageTitle + "\n" );
                        outFile2.close();
            
           // System.out.println("Can't parse"+ pageTitle);
            
             return clToResults;
        }
       finally{
     
      reader.close();
      dir.close();
 }
       
    }
    
}
