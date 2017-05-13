/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 * 
 *      Date             Author          Changes 
 *      Jun 29, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.categoryprocessor;


import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.HashSet;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class NodeDB {
    
        public static void insertNode( int nodeID, String categoryName){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "INSERT IGNORE INTO node(node_id,category_name,is_leaf,is_prominent) VALUES (?,?,?,?)";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, nodeID);
            ps.setString( 2, categoryName);
            ps.setBoolean( 3, false);
            ps.setBoolean( 4, false);
            updateQuery = ps.executeUpdate();
           
//            while (rs.next())
//            {
//            }
            
         }
        catch(SQLException e)
        {
            e.printStackTrace();
           // return null;
        }
        
    }
        
        public static int getCategoryId(String cateName){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query =  "select node_id,category_name from node where category_name=?";

 
        try
        {
            ps = connection.prepareStatement(query);
            ps.setString( 1, cateName);
           
             rs = ps.executeQuery();
          int nodeId=0;
            while (rs.next())
            {
                nodeId=rs.getInt("node_id");
           }
            return nodeId;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return 0;
        }
        
    }
        
        
        public static String getCategoryName(int categoryId){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query =  "select category_name from node where node_id=?";

 
        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, categoryId);
           
             rs = ps.executeQuery();
          String nodeName = null;
            while (rs.next())
            {
                nodeName=rs.getString( "category_name");
           }
            return nodeName;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
    }
        
         public static void updateNode(ArrayList<String> categoryName){
            
          DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "UPDATE node SET is_leaf=? WHERE category_name=?";


        try
        {
            for(int i=0; i<categoryName.size();i++){
            ps = connection.prepareStatement(query);
            ps.setBoolean( 1, true);
            ps.setString( 2, categoryName.get( i ) );         
            updateQuery = ps.executeUpdate();
            }
//            while (rs.next())
//            {
//            }
            
         }
        catch(SQLException e)
        {
            e.printStackTrace();
           // return null;
        }
          
        }

         public static void updateLanguageScore(Double score, int nodeID){
            
          DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "UPDATE node SET score_interlang=? WHERE node_id=?";


        try
        {
            
            ps = connection.prepareStatement(query);
            ps.setDouble( 1, score);
            ps.setInt( 2, nodeID );         
            updateQuery = ps.executeUpdate();
            
//            while (rs.next())
//            {
//            }
            
         }
        catch(SQLException e)
        {
            e.printStackTrace();
           // return null;
        }
          
        }

         
        
        public static void updateProminetNode(HashSet<Integer> prominentNodes){
            
          DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "UPDATE node SET is_prominent=? WHERE node_id=?";


        try
        {
            for (Integer i : prominentNodes) {
            ps = connection.prepareStatement(query);
            ps.setBoolean( 1, true);
            ps.setInt( 2, i );         
            updateQuery = ps.executeUpdate();
            }
//            while (rs.next())
//            {
//            }
            
         }
        catch(SQLException e)
        {
            e.printStackTrace();
           // return null;
        }
          
        }

        public static ArrayList<String> getCategoriesByHead( String head )
    {
        ArrayList<String> categoryList =new ArrayList<String>();
        
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query =  "select node_id, category_name from node where head_of_name=?";

 
        try
        {
            ps = connection.prepareStatement(query);
            ps.setString( 1, head);
           
             rs = ps.executeQuery();
         
            while (rs.next())
            {
                categoryList.add( rs.getString( "category_name") );
                //nodeName=rs.getString( "category_name");
           }
             connection.close();
            return categoryList;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
        
    }
        
    static void updateProminetNode( Integer s )
    {
        throw new UnsupportedOperationException( "Not yet implemented" );
    }

}
