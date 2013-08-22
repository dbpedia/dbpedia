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


import java.io.FileWriter;
import java.io.IOException;
import java.util.Scanner;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class DataProcesor {

    public static void inserDataToDB(Scanner fileScanner) throws IOException{
        
        FileWriter outFile1 ;
        FileWriter outFile2 ;
        String line;
        while ( fileScanner.hasNextLine() )
        {
           // System.out.println(fileScanner.nextLine());
            //split the line by space, will get triples separated 
           line=fileScanner.nextLine();
            String[] typle=line.split("\\ ");
           int parentId;
           int childId;
            
            if(!typle[0].trim().equals("#")&&typle.length>2){
                //<http://dbpedia.org/resource/ETA> begin index=28
                String parent= typle[0].substring( 29, typle[0].length()-1 );
                //<http://dbpedia.org/resource/Category:United_Kingdom_Home_Office_designated_terrorist_groups>
                String child= typle[2].substring( 38, typle[2].length()-1 );
           //  System.out.println( "Line: " +line);  
             //    System.out.println( "Parent: "+parent+" "+"child: "+ child );
                  outFile1 = new FileWriter( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\parents.txt", true );
    outFile2 = new FileWriter( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\all_children.txt", true );
      
                 
                 //insert parent and child to the node- duplicate enties are handle by the SQL 
               //  NodeDB.insertNode( parent );
                 
                 outFile1.append(parent+"\n");
                // NodeDB.insertNode( child);
                 outFile2.append(child+"\n");
                 //get child and parent  Ids
                 parentId=NodeDB.getCategoryId( parent );
                 childId= NodeDB.getCategoryId( child);
                 
                 //
                 EdgeDB.insertEdge( parentId, childId );
                 
   outFile1.close();
        outFile2.close();
            }
            
            
        }
     
    } 
    
}
