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

    public static ArrayList<String> getChildren(int parenId){
       
        DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
          //TO-DO rewrite the query
         String query =  "SELECT tb.category_name FROM (SELECT node.category_name, edges.parent_id, edges.child_id FROM edges LEFT OUTER JOIN node ON edges.child_id=node.node_id)AS tb WHERE tb.parent_id=?";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setInt( 1, parenId);
           
             rs = ps.executeQuery();
             
             
          ArrayList<String> childrenList= new ArrayList<String>();
           
           
            while (rs.next())
            {
                childrenList.add(rs.getString( 1));
           }
             return childrenList;
         }
        catch(SQLException e)
        {
            e.printStackTrace();
            return null;
        }
        
        
    }
    public static ArrayList<Integer> getParent(String leafNode){
       
        
         DB_connection con = new DB_connection();
        Connection connection = con.dbConnect();
        PreparedStatement ps = null;
        ResultSet rs = null;
          int updateQuery = 0;
        
          //TO-DO rewrite the query
         String query =  "SELECT tb.parent_id FROM (SELECT node.category_name, edges.parent_id, edges.child_id FROM edges LEFT OUTER JOIN node ON edges.child_id=node.node_id)AS tb WHERE tb.category_name=?";


        try
        {
            ps = connection.prepareStatement(query);
            ps.setString( 1, leafNode);
           
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
    
}
