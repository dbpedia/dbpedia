/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Jul 18, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.extractdata;


import java.io.*;
import org.dbpedia.kasun.indexer.Index;
import org.dbpedia.kasun.searcher.Search;

/**
 * TODO- describe the purpose of the class
 *
 */
public class DoSearch
{

    public static void main( String[] args ) throws IOException, Exception
    {
        //page
        // String pathToIndex = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index";
        /*
         * categorylinks
         * 
         */
      //  String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\category";
// File categoryTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-category_typles.txt" );
 
        /*
         * languagelinks
         */
        
        /*
        String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\language_links";
        File tuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-langlinks_typles.txt" );
          Index.indexInterLanguageLinks( pathToIndex, tuplesFile );
        */
        /*
         * category_page_links_view
         */
          String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\category_page_links_view";
        File tuplesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_dir\\category_page_links_view\\page_id_cl_to.txt" );
          Index.indexCategoryPageLinksView( pathToIndex, tuplesFile );
          
          
         
          
          
        //Index.indexCategory( pathToIndex, categoryTuplesFile );
        
/*
        
         String pathToIndex1 ="C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index1"; 
         String pathToIndex2= "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index2"; 
         String pathToIndex3 = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index3";
         String pathToIndex4 ="C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index4"; 
         String pathToIndex5= "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index5"; 
         String pathToIndex6 = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index6";
         
        //page tuples
         File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-page_typles.txt" );File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-page_typles.txt" );


         
        //  File ctLinksTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-categorylinks_typles.txt" );


       // Index.indexPage(pathToIndex1,pathToIndex2,pathToIndex3,pathToIndex4,pathToIndex5,pathToIndex6,pageTuplesFile);
//Index.indexPage2(pageTuplesFile);

        //  Index.indexCategoryLinks( pathToIndex, ctLinksTuplesFile );


        // FileWriter outFile = new FileWriter("C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0.txt",true);

        /*
         * String page_q="0"; String page_field="page_namespace"; Search.searchPage( new File(pathToIndex),page_q,
         * page_field,25000000);
         */


       
       // String cateLinksField = "cl_from";


       //  Search.searchCategoryLinks( new File( pathToIndex ), cateLinksField, 200 );
           
 String cateLinksField = "cat_title";
 //Search.searchCategory( new File( pathToIndex ), cateLinksField, 2 );

    }
}
