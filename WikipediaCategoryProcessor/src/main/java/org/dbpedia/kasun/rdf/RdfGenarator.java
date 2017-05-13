/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Sep 17, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.rdf;


import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.dbpedia.kasun.categoryprocessor.CategoryLinksDB;
import org.dbpedia.kasun.categoryprocessor.NodeDB;
import org.dbpedia.kasun.categoryprocessor.Page;
import org.dbpedia.kasun.categoryprocessor.PageDB;

/**
 * TODO- describe the purpose of the class
 *
 */
public class RdfGenarator
{

    private static String promintNodeName;

    public static void getCategoriesForHead( String head )
    {

        ArrayList<String> categoriesForHead = NodeDB.getCategoriesByHead( head );

for(int j=0; j<categoriesForHead.size();j++){
    promintNodeName=categoriesForHead.get( j );
    getPagesForCategory( promintNodeName );
}
categoriesForHead.clear();

    }

    public static void getPagesForCategory( String catName )
    {
        ArrayList<Integer> clFromPageID = CategoryLinksDB.getPagesLinkedByCatName( catName );
        FileWriter outfile;

        for ( int i = 0; i < clFromPageID.size(); i++ )
        {

            try
            {
                Page page = PageDB.getPagebyID( clFromPageID.get( i ) );
                if ( page.getPageNamespace() == 0 )
                {
                    //namespace==0 means it's a article page
                    outfile = new FileWriter( "/home/kasun/rdfresult/rdfoutput.txt", true );
                    outfile.append( "<" + page.getPageName() + "> rdf:type <" + promintNodeName + "> \n" );
                    outfile.close();
                } else
                {
                    if ( page.getPageNamespace() == 14 )
                    {

                        //namespace==14 means it's a categorypage recurcive the categorypage
                        //recursion causes segmentation error go for only fist child
                       // getPagesForCategory( page.getPageName() );
                        getPagesForCategoryFirstChild( page.getPageName() );
                    }
                }
            } catch ( IOException ex )
            {
               FileWriter errorfile;
                try
                {
                    errorfile = new FileWriter( "/home/kasun/rdfresult/error.txt", true );
                     errorfile.append( ex.getMessage()+"\n" );
                    errorfile.close();
                } catch ( IOException ex1 )
                {
                    Logger.getLogger( RdfGenarator.class.getName() ).log( Level.SEVERE, null, ex1 );
                }
                   
            }

        }
        
        clFromPageID.clear();
    }
    
    public static void getPagesForCategoryFirstChild( String catName )
    {
        ArrayList<Integer> clFromPageID = CategoryLinksDB.getPagesLinkedByCatName( catName );
        FileWriter outfile;

        for ( int i = 0; i < clFromPageID.size(); i++ )
        {

            try
            {
                Page page = PageDB.getPagebyID( clFromPageID.get( i ) );
                if ( page.getPageNamespace() == 0 )
                {
                    //namespace==0 means it's a article page
                    outfile = new FileWriter( "/home/kasun/rdfresult/rdfoutput.txt", true );
                    outfile.append( "<" + page.getPageName() + "> rdf:type <" + promintNodeName + "> \n" );
                    outfile.close();
                } 
                /*
                else
                {
                    if ( page.getPageNamespace() == 14 )
                    {

                        //namespace==14 means it's a categorypage recurcive the categorypage
                        getPagesForCategory( page.getPageName() );
                    }
                }
                * 
                */
            } catch ( IOException ex )
            {
               FileWriter errorfile;
                try
                {
                    errorfile = new FileWriter( "/home/kasun/rdfresult/error.txt", true );
                     errorfile.append( ex.getMessage()+"\n" );
                    errorfile.close();
                } catch ( IOException ex1 )
                {
                    Logger.getLogger( RdfGenarator.class.getName() ).log( Level.SEVERE, null, ex1 );
                }
                   
            }

        }
        
        clFromPageID.clear();
    }
}
