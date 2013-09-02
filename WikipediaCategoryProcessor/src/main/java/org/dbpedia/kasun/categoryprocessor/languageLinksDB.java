/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 * 
 *      Date             Author          Changes 
 *      Aug 31, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.categoryprocessor;


import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;



/**
 * Communications with the languagelinks table
 * 
 */
public class languageLinksDB {
    
    public static int getLanguageLinksCount(int pageId){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
        //  int updateQuery = 0;
        
         String query =  "select count(*) from langlinks where ll_from=?";

 
        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, pageId);
           
             rs = ps.executeQuery();
          int nodeId=0;
            while (rs.next())
            {
                nodeId=rs.getInt(1);
           }
            return nodeId;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return 0;
        }
        
    }
    
    

}
