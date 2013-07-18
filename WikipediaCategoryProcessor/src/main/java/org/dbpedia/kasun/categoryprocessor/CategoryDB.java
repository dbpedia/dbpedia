/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Date Author Changes Jul 6, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;


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

        String query = "SELECT COUNT(*) FROM `category` WHERE `cat_subcats`=0 AND `cat_pages`>0 AND `cat_pages`< ? ";


        try
        {
            ps = connection.prepareStatement( query );
            ps.setInt( 1, threshold );

            rs = ps.executeQuery();
            int nodeId = 0;
            while ( rs.next() )
            {
                nodeId = rs.getInt(1);
            }
            return nodeId;
        } catch ( SQLException e )
        {
            e.printStackTrace();
            return 0;
        }

    }
}
