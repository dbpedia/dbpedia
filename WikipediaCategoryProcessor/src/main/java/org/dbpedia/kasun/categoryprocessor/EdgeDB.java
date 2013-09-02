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



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class EdgeDB {
    
    public static void insertEdge(int parentId, int chidId){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query = "INSERT IGNORE INTO edges(parent_id,child_id) VALUES (?, ?)";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt(1, parentId);
            ps.setInt(2, chidId);
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

    public static ArrayList<Integer> getChildren(int parenId){
       
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
          //TO-DO rewrite the query
         String query =  "SELECT child_id FROM edges WHERE parent_id=?";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, parenId);
           
             rs = ps.executeQuery();
             
             
          ArrayList<Integer> childrenList= new ArrayList<Integer>();
           
           
            while (rs.next())
            {
                childrenList.add(rs.getInt("child_id"));
           }
             return childrenList;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
        
    }
    public static ArrayList<Integer> getParent(int leafNode){
       
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
          //TO-DO rewrite the query
         String query =  "SELECT parent_id FROM edges WHERE child_id =?";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, leafNode);
           
             rs = ps.executeQuery();
             
             
          ArrayList<Integer> parents= new ArrayList<Integer>();
           
           
            while (rs.next())
            {
                parents.add(rs.getInt(1));
           }
             return parents;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
        
        
        
       
    }
    
    public static ArrayList<Integer>  getChilren(int parentId){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query =  "select parent_id,child_id from edges where parent_id=?";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt(1, parentId);
           
             rs = ps.executeQuery();
           ArrayList<Integer> chidId= new  ArrayList<Integer>();
            while (rs.next())
            {
                chidId.add(rs.getInt("child_id") );
           }
            return chidId;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
    }
    
     public static ArrayList<Integer>  getDisinctleafNodes(){
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
         String query =  "SELECT  distinct `child_id` FROM edges WHERE  `child_id` NOT IN (SELECT `parent_id` FROM edges )";


        try
        {
            ps = connection.prepareStatement(query);
          //  ps.setInt(1, parentId);
           
             rs = ps.executeQuery();
           ArrayList<Integer> leafId= new  ArrayList<Integer>();
            while (rs.next())
            {
                leafId.add(rs.getInt("child_id") );
           }
            return leafId;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
    }
    
}
