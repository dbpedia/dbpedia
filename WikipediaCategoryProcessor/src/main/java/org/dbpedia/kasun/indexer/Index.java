/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Jul 17, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.indexer;


import java.io.*;
import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
//import org.apache.lucene.analysis.;
import org.apache.lucene.document.*;
import org.apache.lucene.index.CorruptIndexException;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.index.IndexWriterConfig;
import org.apache.lucene.store.NIOFSDirectory;
import org.apache.lucene.util.Version;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Index
{

    public static void indexPage( String pathToIndex, File pageTuplesFile ) throws IOException
    {
        int noOfDocs = 0;

        IndexWriter iW;

        try
        {
            // NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            // iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );




            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( pageTuplesFile ) );
            int count = 0;
            String line;
            FileWriter outFile;
            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\," );
                if ( strArr.length >= 3 )
                {
//                StringReader page_id = new StringReader( strArr[0] );
//                StringReader page_namespace = new StringReader( strArr[1] );
//                StringReader page_title = new StringReader( strArr[2] );
                    //System.out.println(strArr[0]+strArr[1]+strArr[2]);

                    if ( strArr[1].trim() == "0" )
                    {
                        outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_14_new.txt", true );


                        outFile.append( strArr[0] + "\t" + strArr[1] + "\t" + strArr[2] + "\n" );
                        //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
                        outFile.close();
                    }
                    /*
                     * Document doc = new Document();
                     *
                     * doc.add( new TextField( "page_id", strArr[0], Field.Store.YES ) ); doc.add( new TextField(
                     * "page_namespace", strArr[1], Field.Store.YES ) ); doc.add( new TextField( "page_title",
                     * strArr[2], Field.Store.YES ) );
                     *
                     *
                     * iW.addDocument( doc );
                     */

                } else
                {
                    System.out.println( line + "\n" );
                }

                count++;
            }


            // iW.close();
            //  dir.close();

        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }

    public static void readPageTable( File pageTuplesFile ) throws IOException
    {
        int noOfDocs = 0;

        IndexWriter iW;

        try
        {



            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( pageTuplesFile ) );
            int count = 0;
            String line;
            FileWriter outFile;
            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\," );
                if ( strArr.length >= 3 )
                {


                    if ( strArr[1].trim().equals( "0" ) )
                    {
                        outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0_new.txt", true );


                        outFile.append( strArr[0] + "\t" + strArr[1] + "\t" + strArr[2] + "\n" );
                        //System.out.println((i + 1) + ". " + d.get("page_id") + "\t" + d.get("page_namespace")+ "\t" + d.get("page_title"));
                        outFile.close();
                    }


                } else
                {
                    System.out.println( line + "\n" );
                }

                count++;
            }



        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }

    public static void indexCategoryLinks( String pathToIndex, File tuplesFile ) throws IOException
    {
        //String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\page_index";
        int noOfDocs = 0;

        IndexWriter iW;
        try
        {
            NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );


          //  File tuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-langlinks_typles.txt" );



            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( tuplesFile ) );
            int count = 0;
            String line;

            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\," );
                //`cl_from` ,`cl_to`,`cl_sortkey`,`cl_timestamp`,`cl_sortkey_prefix`,`cl_collation`,`cl_type` enum('page','subcat','file') NOT NULL DEFAULT 'page',

                if ( strArr.length >= 7 )
                {

                    Document doc = new Document();




                    doc.add( new TextField( "cl_from", strArr[0], Field.Store.YES ) );
                    doc.add( new TextField( "cl_to", strArr[1], Field.Store.YES ) );
                    doc.add( new TextField( " cl_sortkey", strArr[2], Field.Store.YES ) );

                    doc.add( new TextField( "cl_type", strArr[6], Field.Store.YES ) );
                    iW.addDocument( doc );
                } else
                {
                    System.out.println( line + "\n" );
                }
            }


            iW.close();
            dir.close();
        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }

    public static void indexCategory( String pathToIndex, File tuplesFile ) throws IOException
    {
        //String pathToIndex = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index";
        int noOfDocs = 0;

        IndexWriter iW;
        try
        {
            NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );


            //File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-category_typles.txt" );



            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( tuplesFile ) );
            int count = 0;
            String line;

            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\," );
                //Data in following order`cat_id`,`cat_title`,`cat_pages`,`cat_subcats` 
                //we need 0,1,2,3 elements of the string
                if ( strArr.length >= 2)
                {

                  //  System.out.println(strArr[0]+"####"+strArr[1]+"####"+strArr[2]+"#####"+strArr[3]+"###"+strArr[4]);
                    Document doc = new Document();




                    doc.add( new TextField( "cat_id", strArr[0], Field.Store.YES ) );
                    doc.add( new TextField( "cat_title", strArr[1], Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_pages", Integer.parseInt( strArr[2].trim() ), Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_subcats", Integer.parseInt( strArr[3].trim() ), Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_files", Integer.parseInt( strArr[4].trim() ), Field.Store.YES ) );
                  //  doc.add( new TextField( "cat_hidden", strArr[5].substring( 0,1), Field.Store.YES ) );



                    iW.addDocument( doc );
                } else
                {
                    System.out.println( line + "\n" );
                }
            }


            iW.close();
            dir.close();
        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }
    
    
    public static void indexCategoryPageLinksView( String pathToIndex, File tuplesFile ) throws IOException
    {
        //String pathToIndex = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\language_links";
        int noOfDocs = 0;

        IndexWriter iW;
        try
        {
            NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );


            //File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-category_typles.txt" );



            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( tuplesFile ) );
            int count = 0;
            String line;

            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\t",2 );
                //Data in following order`cat_id`,`cat_title`,`cat_pages`,`cat_subcats` 
                //we need 0,1,2,3 elements of the string
                if ( strArr.length >= 2)
                {

                  //  System.out.println(strArr[0]+"####"+strArr[1]+"####"+strArr[2]+"#####"+strArr[3]+"###"+strArr[4]);
                    Document doc = new Document();




                    doc.add( new TextField( "page_id", strArr[0].trim(), Field.Store.YES ) );
                    doc.add( new TextField( "page_title", strArr[1], Field.Store.YES ) );
                 
                  //  doc.add( new IntField( "cat_subcats", Integer.parseInt( strArr[3].trim() ), Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_files", Integer.parseInt( strArr[4].trim() ), Field.Store.YES ) );
                  //  doc.add( new TextField( "cat_hidden", strArr[5].substring( 0,1), Field.Store.YES ) );



                    iW.addDocument( doc );
                } else
                {
                    System.out.println( line + "\n" );
                }
            }


            iW.close();
            dir.close();
        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }
    
    
    
      public static void indexInterLanguageLinks( String pathToIndex, File tuplesFile ) throws IOException
    {
        //String pathToIndex = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\language_links";
        int noOfDocs = 0;

        IndexWriter iW;
        try
        {
            NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );


            //File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-category_typles.txt" );



            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( tuplesFile ) );
            int count = 0;
            String line;

            while ( ( line = fileReader.readLine() ) != null )
            {

                String[] strArr = line.split( "\\,",3 );
                //Data in following order`cat_id`,`cat_title`,`cat_pages`,`cat_subcats` 
                //we need 0,1,2,3 elements of the string
                if ( strArr.length >= 3)
                {

                  //  System.out.println(strArr[0]+"####"+strArr[1]+"####"+strArr[2]+"#####"+strArr[3]+"###"+strArr[4]);
                    Document doc = new Document();




                    doc.add( new TextField( "ll_from", strArr[0].trim(), Field.Store.YES ) );
                    doc.add( new TextField( "ll_lang", strArr[1], Field.Store.YES ) );
                  doc.add( new TextField( "ll_title", strArr[2] , Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_subcats", Integer.parseInt( strArr[3].trim() ), Field.Store.YES ) );
                  //  doc.add( new IntField( "cat_files", Integer.parseInt( strArr[4].trim() ), Field.Store.YES ) );
                  //  doc.add( new TextField( "cat_hidden", strArr[5].substring( 0,1), Field.Store.YES ) );



                    iW.addDocument( doc );
                } else
                {
                    System.out.println( line + "\n" );
                }
            }


            iW.close();
            dir.close();
        } catch ( CorruptIndexException e )
        {
            e.printStackTrace();
        } catch ( IOException e )
        {
            e.printStackTrace();
        }
    }
    
}
