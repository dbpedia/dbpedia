/*
 * DO NOT MODIFY THIS FILE (it is already completed and should not be changed).
 */

package org.dbpedia.kasun.categoryprocessor;
import java.sql.*;

public class DB_connection {
	public DB_connection() {};
  //  "jdbc:mysql://localhost:3306/TweetComparison","root","nbuser"
	//public Connection dbConnect(String db_connect_string, String db_userid, String db_password) {
    public Connection dbConnect() {
		
		Connection conn = null;
	    try {
			Class.forName("com.mysql.jdbc.Driver").newInstance();
	         //conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/kasun","kasun","kasun_perrera_kk");
            conn = DriverManager.getConnection("jdbc:mysql://localhost:3306/wiki_categories","root","nbuser");
		} catch (InstantiationException e) {
			e.printStackTrace();
		} catch (IllegalAccessException e) {
			e.printStackTrace();
		} catch (ClassNotFoundException e) {
			e.printStackTrace();
		} catch (SQLException e) {
			e.printStackTrace();
		}
	    return conn;
	}
}
