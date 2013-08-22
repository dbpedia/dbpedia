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


import java.io.FileWriter;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;



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
