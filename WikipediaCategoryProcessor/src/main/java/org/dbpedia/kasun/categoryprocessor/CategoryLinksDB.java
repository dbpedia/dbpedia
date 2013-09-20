/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Aug 13, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.LinkedList;
import org.apache.lucene.queryparser.classic.ParseException;
import org.dbpedia.kasun.searcher.Search;

/**
 * TODO- describe the purpose of the class
 *
 */
public class CategoryLinksDB
{

    public static void getCategoryByPageID() throws IOException
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

        FileWriter outFile;
        FileWriter outFile1;
        int pageID;
        String leafcategory;


        PreparedStatement ps = null;
        ResultSet rs = null;
        int updateQuery = 0;
        String temp = null;




        // System.out.println(line);
        // System.out.println(temp);

        //   String query = "SELECT cl_to  FROM `categorylinks` WHERE `cl_from` = ? ";

        // String query = "SELECT `cl_to` FROM  `category_only_page` JOIN  `categorylinks` ON  `category_only_page`.`page_id` =  `categorylinks`.`cl_from` WHERE  `page_title` =  '"+leafcategory+"'";

        try
        {


            File catPagesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\leaf_categories\\page_id_page_title_leaf_categories_page_less_than_90.txt" );

            String line;
            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( catPagesFile ) );
            //FileWriter outFile;
            // FileWriter outFileCatNotFound;

            while ( ( line = fileReader.readLine() ) != null )
            {
                if ( !line.isEmpty() )
                {
                    String splitLine[] = line.split( "\t" );
                    leafcategory = splitLine[1].trim();
                    pageID = Integer.valueOf( splitLine[0] );

                    String query = "SELECT `cl_to` FROM  `categorylinks` WHERE  `cl_from` =  " + splitLine[0].trim();




                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    // ps.setInt( 1, pageID );
                    rs = ps.executeQuery();
                    int count = 0;

                    if ( rs.next() )
                    {
                        NodeDB.insertNode( pageID, leafcategory );
                        // int childID= NodeDB.getCategoryId( leafcategory );
                        do
                        {
                            //outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\category_match_article_pages.txt", true );
                            //outFile.append( rs.getString( "cat_id" ) + "\t" + rs.getString( "cat_title" ) + "\t" + rs.getString( "cat_pages" ) + "\t" + rs.getString( "cat_subcats" ) + "\t" + rs.getString( "cat_files" ) + "\t" + rs.getString( "cat_hidden" ) + "\n" );
                            // outFile.close();
                            //insertCategory( rs.getInt( "cat_id"), rs.getString( "cat_title" ), rs.getInt( "cat_pages"), rs.getInt( "cat_subcats"), rs.getInt( "cat_files"), rs.getBoolean( "cat_hidden" ) );
                            int parentID = PageDB.getPageId( rs.getString( "cl_to" ).trim() );
                            if ( parentID > 0 )
                            {
                                NodeDB.insertNode( parentID, rs.getString( "cl_to" ).trim() );
                                // int parentID= NodeDB.getCategoryId( rs.getString( "cl_to" ) );

                                EdgeDB.insertEdge( parentID, pageID );
                            } else
                            {
                                outFile1 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\Parent_child_not_inderted_to_node_table.txt", true );
                                outFile1.append( rs.getString( "cl_to" ).trim() + "\n" );
                                outFile1.close();
                            }
                            count++;

                        } while ( rs.next() );
                    } else
                    {

                        outFile = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categories_pages_not_found_in_page_table.txt", true );
                        outFile.append( pageID + "\t" + leafcategory + "\n" );
                        outFile.close();

                        //System.out.println( line );
                        // No data
                    }

                    System.out.println( count );
                }
            }

            connection.close();
        } catch ( SQLException e )
        {
            e.printStackTrace();
            // return 0;
        }



    }

    public static void insertParentChild() throws IOException, ParseException
    {


        FileWriter outFile;
        FileWriter outFile1;
        FileWriter outFile2;
        int pageID;
        // int catID;
        String leafcategory;



        int updateQuery = 0;
        String temp = null;




        try
        {


            File catPagesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\leaf_categories\\leaf_categories_page_less_than_90.txt" );

            String line;
            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( catPagesFile ) );
            //FileWriter outFile;
            // FileWriter outFileCatNotFound;

            while ( ( line = fileReader.readLine() ) != null )
            {
                if ( !line.isEmpty() )
                {
                    String splitLine[] = line.split( "\t" );
                    leafcategory = splitLine[1].trim();
                    // catID= ;
                    pageID = PageDB.getPageId( leafcategory );

                    if ( pageID > 0 )
                    {
                        NodeDB.insertNode( pageID, leafcategory );

                        /*
                         * search index and get the cl_to by pageID
                         */

                        ArrayList<String> listOfClTo = Search.SearchCatPageLinks( pageID );

                        for ( int i = 0; i < listOfClTo.size(); i++ )
                        {

                            int parentID = PageDB.getPageId( listOfClTo.get( i ) );
                            if ( parentID > 0 )
                            {
                                NodeDB.insertNode( parentID, listOfClTo.get( i ) );
                                // int parentID= NodeDB.getCategoryId( rs.getString( "cl_to" ) );

                                EdgeDB.insertEdge( parentID, pageID );
                            } else
                            {
                                outFile1 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\data_not_inserted_node_table\\Parent_child_not_inderted_to_node_table_V2.txt", true );
                                outFile1.append( listOfClTo.get( i ) + "\n" );
                                outFile1.close();
                            }
                            // count++;

                        }
                    } else
                    {
                        outFile2 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\data_not_inserted_node_table\\Child_nodes_not_inderted_to_node_table_V2.txt", true );
                        outFile2.append( line + "\n" );
                        outFile2.close();
                    }
                }
            }
        } catch ( Exception e )
        {
            e.printStackTrace();
            // return 0;
        }



    }

    public static void insertParentChildModified() throws IOException, ParseException
    {


        FileWriter outFile;
        FileWriter outFile1;
        FileWriter outFile2;
     
        // int catID;
        String leafcategory;



        int updateQuery = 0;
        String temp = null;

int count=0;


        try
        {


            File catPagesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\leaf_categories\\leaf_categories_page_less_than_90_edited_4.txt" );

            String line;
            BufferedReader fileReader;
            fileReader = new BufferedReader( new FileReader( catPagesFile ) );
            //FileWriter outFile;
            // FileWriter outFileCatNotFound;

           // HashMap<String, Integer> pageMap = PageDB.getAllPages();

            while ( ( line = fileReader.readLine() ) != null )
            {
                if ( !line.isEmpty() )
                {
                    String splitLine[] = line.split( "\t" );
                    leafcategory = splitLine[1].trim();
                    // catID= ;
                    // pageID = PageDB.getPageId( leafcategory );
                       int pageID=0;
                       LinkedList<Integer> pageIdList= Search.SearchCategoryPages( leafcategory );
                      if(!pageIdList.isEmpty() ){
                            pageID =pageIdList.get(0);
                       }
                   
                    if ( pageID > 0 )
                    {
                        NodeDB.insertNode( pageID, leafcategory );

                        /*
                         * search index and get the cl_to by pageID
                         */

                        ArrayList<String> listOfClTo = Search.SearchCatPageLinks( pageID );

                        for ( int i = 0; i < listOfClTo.size(); i++ )
                        {
                            int parentID = 0;
                            // int parentID = PageDB.getPageId( listOfClTo.get( i ) );
                            
                                 LinkedList<Integer> parentIdList= Search.SearchCategoryPages( listOfClTo.get( i ) );
                      if(!parentIdList.isEmpty() ){
                            parentID =parentIdList.get(0);
                       }
                            if ( parentID > 0 )
                            {
                                NodeDB.insertNode( parentID, listOfClTo.get( i ) );
                                // int parentID= NodeDB.getCategoryId( rs.getString( "cl_to" ) );

                                EdgeDB.insertEdge( parentID, pageID );
                            } else
                            {
                                outFile1 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\data_not_inserted_node_table\\Parent_child_not_inderted_to_node_table_V2.txt", true );
                                outFile1.append( listOfClTo.get( i ) + "\n" );
                                outFile1.close();
                            }
                            // count++;

                        }
                    } else
                    {
                        outFile2 = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\data_not_inserted_node_table\\Child_nodes_not_inderted_to_node_table_V2.txt", true );
                        outFile2.append( line + "\n" );
                        outFile2.close();
                    }
                }
                 count++;
                 System.out.println(count);
            }
        } catch ( Exception e )
        {
            e.printStackTrace();
            // return 0;
        }



    }
    
    public static ArrayList<Integer> getPagesLinkedByCatName( String catName )
    {
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
          
          ArrayList<Integer> listOfPages= new  ArrayList<Integer>();
        
         String query =  "select cl_from from categorylinks where cl_to=?";

 
        try
        {
            ps = connection.prepareStatement(query);
            ps.setString( 1, catName);
           
             rs = ps.executeQuery();
         
            while (rs.next())
            {
                listOfPages.add(rs.getInt( "cl_from" ) );
           }
            connection.close();
            return listOfPages;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
    }
}
