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
        //categorylinks
        String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\category_links";

/*
        
         String pathToIndex1 ="C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index1"; 
         String pathToIndex2= "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index2"; 
         String pathToIndex3 = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index3";
         String pathToIndex4 ="C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index4"; 
         String pathToIndex5= "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index5"; 
         String pathToIndex6 = "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\index_dir\\page_index6";
         
        //page tuples
         File pageTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-page_typles.txt" );
*/
         
        //  File ctLinksTuplesFile = new File( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\typles_out\\enwiki-20130604-categorylinks_typles.txt" );


       // Index.indexPage(pathToIndex1,pathToIndex2,pathToIndex3,pathToIndex4,pathToIndex5,pathToIndex6,pageTuplesFile);
//Index.indexPage2(pageTuplesFile);

        //  Index.indexCategoryLinks( pathToIndex, ctLinksTuplesFile );


        // FileWriter outFile = new FileWriter("C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\pages_page_namespace_0.txt",true);

        /*
         * String page_q="0"; String page_field="page_namespace"; Search.searchPage( new File(pathToIndex),page_q,
         * page_field,25000000);
         */


       
        String cateLinksField = "cl_from";


         Search.searchCategoryLinks( new File( pathToIndex ), cateLinksField, 200 );
           

    }
}
