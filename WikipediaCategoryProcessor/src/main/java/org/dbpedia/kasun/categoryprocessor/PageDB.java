/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 *   KarshaAnnotate- Annotation tool for financial documents
 *  
 *   Copyright (C) 2013, Lanka Software Foundation and and University of Maryland.
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *      Date             Author          Changes 
 *      Aug 3, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.categoryprocessor;


import java.io.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.document.IntField;
import org.apache.lucene.document.TextField;
import org.apache.lucene.index.CorruptIndexException;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.index.IndexWriterConfig;
import org.apache.lucene.store.NIOFSDirectory;
import org.apache.lucene.util.Version;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class PageDB {
    
    public static boolean isArticlePage(int pageId){
        boolean state= false;
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

     
                
                 String lineArr[];
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
             
             
                // System.out.println(line);
                // System.out.println(temp);
                String query = "SELECT page_namespace FROM `page` WHERE `page_id` = " + pageId ;


                try
                {
                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    //ps.setString( 1, catTitle );
                    rs = ps.executeQuery();
                    int count = 0;

                     while( rs.next() )
                    {
                       if(rs.getInt( "page_namespace" )== 0 ){
                                state=true;
                                break;
                            }
                    }
                  



                   connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
   
        
        return state;
    }
    
     public static int getPageId(String catPageTitle){
        int resultId = 0;
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

     
                
                 String lineArr[];
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
             
             
                // System.out.println(line);
                // System.out.println(temp);
               
                String query = "SELECT page_id FROM `category_only_page` WHERE `page_title` = '" + catPageTitle+"'" ;


                try
                {
                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    //ps.setString( 1, catTitle );
                    rs = ps.executeQuery();
                    int count = 0;

                     while( rs.next() )
                    {
                    resultId= rs.getInt("page_id");
                    }
                  



                   connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
   
        
        return resultId;
    }
      public static HashMap<String,Integer> getAllPages() throws IOException{
        int resultId = 0;
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();

     HashMap<String,Integer> pagesMap= new HashMap<String,Integer>();
                
                 String lineArr[];
                PreparedStatement ps = null;
                ResultSet rs = null;
                int updateQuery = 0;
             
             
                // System.out.println(line);
                // System.out.println(temp);
               
                String query = "SELECT page_id, page_title FROM `category_only_page`" ;


                try
                {
                    
                                      
       String pathToIndex = "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\index\\categoty_page_candidate_index";
        int noOfDocs = 0;

        IndexWriter iW;
       
            NIOFSDirectory dir = new NIOFSDirectory( new File( pathToIndex ) );
            //dir = new RAMDirectory() ;
            iW = new IndexWriter( dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ) );

                    ps = connection.prepareStatement( query );
                    // ps.setString( 1, temp );
                    //ps.setString( 1, catTitle );
                    rs = ps.executeQuery();
                    int count = 0;

                     while( rs.next() )
                    {
      


                    Document doc = new Document();




                    doc.add( new TextField( "page_title",  rs.getString( "page_title" ), Field.Store.YES ) );
                    doc.add( new IntField( "page_id", rs.getInt("page_id"), Field.Store.YES ) );
                
                    iW.addDocument( doc );
                

      
                        
                    //    pagesMap.put( rs.getString( "page_title" ), rs.getInt("page_id") );
             //   System.out.println(pagesMap.size());
                        //   resultId= rs.getInt("page_id");
                    }
                    iW.close();
            dir.close();



                   connection.close();
                } catch ( SQLException e )
                {
                    e.printStackTrace();
                    // return 0;
                }
   
        
        return pagesMap;
    }
     
    
            public static void insertCategoryPage( String data){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "INSERT IGNORE INTO category_only_page(page_id,page_namespace,page_title,page_restrictions, page_counter,page_is_redirect, page_is_new, page_random, page_touched,page_latest,page_len) VALUES ("+data+")";


         

        try
        {
            ps = connection.prepareStatement(query);
 
            updateQuery = ps.executeUpdate();
           
//            while (rs.next())
//            {
//            }
            
         }
        catch(SQLException e)
        {
            
            System.out.println(data);
           // e.printStackTrace();
           // return null;
        }
        
    }

}
