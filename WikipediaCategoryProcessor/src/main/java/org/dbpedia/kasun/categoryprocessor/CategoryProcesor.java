/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.*;
import java.util.Scanner;
import org.apache.lucene.queryparser.classic.ParseException;

/**
 *
 * Date Author Changes Jun 28, 2013 Kasun Perera Created
 *
 */
public class CategoryProcesor
{

    /**
     * @param args the command line arguments
     */
    public static void main( String[] args ) throws IOException, ParseException
    {
        
        Edges edge= new Edges();
        edge.findProminetNodes();
      // CategoryLinksDB.insertParentChildModified();
     //   PageDB.getAllPages();
        
        /*
        // inser category_only_pages
        
                  //File catPagesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_dir\\pages_page_namespace_14_new_complete_line.txt" );
       
        
        
                  File catPagesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\leaf_categories\\leaf_categories_page_less_than_90.txt" );
      
                  String line;
        BufferedReader fileReader;
        fileReader = new BufferedReader( new FileReader( catPagesFile  ) );
        //FileWriter outFile;
       // FileWriter outFileCatNotFound;
        FileWriter outFile = new FileWriter("F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\leaf_categories\\page_id_page_title_leaf_categories_page_less_than_90.txt", true);
	
        while ( ( line = fileReader.readLine() ) != null )
        {
            if ( !line.isEmpty() )
            {
                String splitLine[]= line.split("\t");
           int pageId=  PageDB.getPageId( splitLine[1].trim() );
          outFile.append( pageId +"\t"+splitLine[1].trim()+"\n" );
           //  CategoryLinksDB.getCategoryByPageID( );
                
            
            }
        }
        
        outFile.close();
           
        */
        
     
       // CategoryDB.getCategoryByName();
        /*
          File uniqueCatNamesFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\categories_not_found_in_category_table_ca_replaced_part_3.txt" );
        String line;
        BufferedReader fileReader;
        fileReader = new BufferedReader( new FileReader( uniqueCatNamesFile ) );
        //FileWriter outFile;
       // FileWriter outFileCatNotFound;
       
        while ( ( line = fileReader.readLine() ) != null )
        {
            if ( !line.isEmpty() )
            {
               // CategoryDB.getCategoryDirectedByArticlePage(line);
                CategoryDB.getCategoryByName(line);
            }
        }
        
        */
        
       /* 
        System.out.println("Threshold \t" +"Page Count");
        // TODO code application logic here
       	    	
			 
for(int i=1; i<100000; i++){
     FileWriter outFile = new FileWriter("F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\results_new\\page_threshold_values.txt", true);
		
    int pageCount= CategoryDB.getCategoryPageCount( i );
     outFile.append(i+"\t" +pageCount+"\n");
   // System.out.println(i+"\t" +pageCount);
   
       outFile.close();
}
   */     
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
