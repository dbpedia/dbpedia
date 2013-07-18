/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.util.Scanner;

/**
 * Copyright (C) 2012, Lanka Software Foundation.
 *
 * Date Author Changes Jun 28, 2013 Kasun Perera Created
 *
 */
public class CategoryProcesor
{

    /**
     * @param args the command line arguments
     */
    public static void main( String[] args ) throws IOException
    {
        System.out.println("Threshold \t" +"Page Count");
        // TODO code application logic here
for(int i=1; i<10; i++){
    int pageCount= CategoryDB.getCategoryPageCount( i );
    System.out.println(i+"\t" +pageCount);
    
    
}
        
        /*
        
        Scanner fileScanner = null;
        Scanner childFileScanner= null;
         Scanner  parentFileScanner= null;
        try
        {
            
            //fileScanner = new Scanner( new File( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\preview.txt" ) ).useDelimiter("\\>*.\\<*");
       // fileScanner = new Scanner( new File( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\preview.txt" ) );
       fileScanner = new Scanner( new File( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\dbpedia_categories\\article_categories_en.nt" ) );
      
           DataProcesor.inserDataToDB( fileScanner );
           parentFileScanner = new Scanner( new File(  "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\parents.txt" ));
    childFileScanner = new Scanner( new File(  "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\all_children.txt") );
     
        Node.sortChildren( parentFileScanner, childFileScanner );
           
        } catch ( FileNotFoundException e )
        {
            e.printStackTrace();
        }
        
        */
        //read category file and insert data to the database 
    
        
        //read leaf node file and update the database
        
    }
}
