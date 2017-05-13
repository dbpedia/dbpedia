/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Date Author Changes Jul 6, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

/**
 * TODO- describe the purpose of the class
 *
 */
public class CategoryDB
{

  

    public static int getCategoryPageCount( int threshold )
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
        int updateQuery = 0;

        String query = "SELECT COUNT(*) FROM `page_category` WHERE `cat_subcats`=0  AND `cat_pages`< ? ";


        try
        {
            ps = connection.prepareStatement( query );
            ps.setInt( 1, threshold );

            rs = ps.executeQuery();
            int nodeId = 0;
            while ( rs.next() )
            {
                nodeId = rs.getInt( 1 );
            }
            return nodeId;
        } catch ( SQLException e )
        {
            e.printStackTrace();
            return 0;
        }

    }

        public static void getCategoryByName(String line) throws IOException
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

         FileWriter outFile;
        
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
                String temp = null;

 

                // System.out.println(line);
                // System.out.println(temp);
                
                String query = "SELECT cat_id, cat_title,cat_pages,cat_subcats,cat_files,cat_hidden FROM `category` WHERE `cat_title` LIKE ? ";
//String query = "SELECT cat_id, cat_title,cat_pages,cat_subcats,cat_files,cat_hidden FROM `category` WHERE `cat_title` = ? ";
//String query = "SELECT cat_id, cat_title,cat_pages,cat_subcats,cat_files,cat_hidden FROM `category` WHERE `cat_title` ="+catTitle;


                try
                {
                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    ps.setString( 1, line );
                    rs = ps.executeQuery();
                    int count = 0;

                    if ( rs.next() )
                    {
                        do
                        {
                            //outFile = new FileWriter( "C:\\Users\\lsf\\Documents\\NetBeansProjects\\CategoryProcesor\\results_dir\\category_match_article_pages.txt", true );
                            //outFile.append( rs.getString( "cat_id" ) + "\t" + rs.getString( "cat_title" ) + "\t" + rs.getString( "cat_pages" ) + "\t" + rs.getString( "cat_subcats" ) + "\t" + rs.getString( "cat_files" ) + "\t" + rs.getString( "cat_hidden" ) + "\n" );
                           // outFile.close();
                            insertCategory( rs.getInt( "cat_id"), rs.getString( "cat_title" ), rs.getInt( "cat_pages"), rs.getInt( "cat_subcats"), rs.getInt( "cat_files"), rs.getBoolean( "cat_hidden" ) );
                            count++;
                            if(count>1){
                               System.out.println( count+" count is over one " + line);
                            }
                        } while ( rs.next() );
                    } else
                    {

                        outFile = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categories_not_found_in_category_table_2.txt", true );
                            outFile.append( line+ "\n" );
                           outFile.close();
                        
                        //System.out.println( line );
                        // No data
                    }



                     connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
 


    }
    
    
    public static void getCategoryDirectedByArticlePage(String line) throws IOException
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

     
                
                 String lineArr[];
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
               
                lineArr=line.split("\t");
             
                // System.out.println(line);
                // System.out.println(temp);
                String query = "SELECT cl_from, cl_to, cl_type FROM `categorylinks` WHERE `cl_from` =" + lineArr[0].trim() ;


                try
                {
                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    //ps.setString( 1, catTitle );
                    rs = ps.executeQuery();
                    int count = 0;

                    if ( rs.next() )
                    {
                        do
                        {
                             FileWriter outFile = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categorylinks_match_article_pages_v1.txt", true );
                            outFile.append( rs.getInt( "cl_from" ) + "\t" + rs.getString( "cl_to" ) + "\t" + rs.getString( "cl_type" ) + "\n" );
                            outFile.close();
                            count++;
                        } while ( rs.next() );
                    } else
                    {

                        FileWriter outFileCatNotFound = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categorylinks_not_found_article_pages_v1.txt", true );
                            outFileCatNotFound.append( line + "\n" );
                            outFileCatNotFound.close();
                        
                        //System.out.println( line +"\t no category found");
                        // No data
                    }



                   connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
            //}
        //}



    }
    
    public static void getCategoryLinkByCatName(String line) throws IOException
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

     
                
                // String lineArr[];
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
               
               // lineArr=line.split("\t");
             
                // System.out.println(line);
                // System.out.println(temp);
                String query = "SELECT cl_from FROM `categorylinks` WHERE `cl_to` LIKE " + line.trim() ;


                try
                {
                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    //ps.setString( 1, catTitle );
                    rs = ps.executeQuery();
                    int count = 0;

                    if ( rs.next() )
                    {
                        do
                        {
                            
                        //if caegory does not have 
                            if(!PageDB.isArticlePage( rs.getInt("cl_from") )){
                          
                            }
//                             FileWriter outFile = new FileWriter( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categorylinks_match_article_pages_v1.txt", true );
//                            outFile.append( rs.getInt( "cl_from" ) + "\t" + rs.getString( "cl_to" ) + "\t" + rs.getString( "cl_type" ) + "\n" );
//                            outFile.close();
//                            count++;
                        } while ( rs.next() );
                    } 



                   connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
            //}
        //}



    }

    public static void insertCategory( int cat_id,String cat_title, int cat_pages,int cat_subcats,int cat_files,boolean cat_hidden)
    {
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
        int updateQuery = 0;
        /*
         *   `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_title` varbinary(255) NOT NULL DEFAULT '',
  `cat_pages` int(11) NOT NULL DEFAULT '0',
  `cat_subcats` int(11) NOT NULL DEFAULT '0',
  `cat_files` int(11) NOT NULL DEFAULT '0',
  `cat_hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
         */

        String query = "INSERT IGNORE INTO page_category(cat_id,cat_title,cat_pages,cat_subcats,cat_files,cat_hidden) VALUES (?,?,?,?,?,?)";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt(1, cat_id);
            ps.setString( 2, cat_title);
            ps.setInt(3, cat_pages);
            ps.setInt( 4, cat_subcats);
            ps.setInt( 5, cat_files);
            ps.setBoolean( 6, cat_hidden);
            updateQuery = ps.executeUpdate();
           
           connection.close();
            
         }
        catch(SQLException e)
        {
            e.printStackTrace();
           // return null;
        }

    }
}
