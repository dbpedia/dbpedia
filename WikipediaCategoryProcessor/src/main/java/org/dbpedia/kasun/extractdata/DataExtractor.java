


/** 
 *
 * 
 *      Date             Author          Changes 
 *      Jul 16, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.extractdata;


import java.io.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;



/**
 * Methods of this class extract data from the Wikipedia SQL dumps
 * 
 */
public class DataExtractor {

    
public static void main(String[] args ) throws FileNotFoundException, IOException{
    String line;
    
    /*
     * enwiki-20130604-page.sql- data line start at line #50
     * enwiki-20130604-categorylinks.sql data line start at line #44
     * 
     * change "int count" variable according to the data line for each SQl dump file
     */
     File categoryLinksDumpFile = new File( "F:\\Blogs\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\Wiki_Category_SQL_tables\\enwiki-20130604-categorylinks.sql" );
     File outCategoryLinksDumpFile = new File( "typles_out\\enwiki-20130604-categorylinks_typles.txt"  );
    
     BufferedReader  fileReader;
    fileReader = new BufferedReader( new FileReader( categoryLinksDumpFile ) );
 int count=0;
        while ((line = fileReader.readLine())!=null )
        {
           // line = fileReader.readLine();
             
            if(count>43){
                FileWriter outFile2 = new FileWriter(outCategoryLinksDumpFile,true);
                 //  System.out.println("#############################################################");
                
             String[] strArr = line.split("\\)\\,\\(");
             for(int i=0;i< strArr.length;i++){
                 if(i==0){
                     String[] strArr2= strArr[0].split( "\\(" ) ;
                     outFile2.append(strArr2[1]+"\n");
                        //System.out.println( strArr2[1]);  
                 }
                 else{
                       outFile2.append(strArr[i]+"\n");
               //  System.out.println( strArr[i]); 
                 }
             }
            outFile2.close();
           }
         //   String[] strArr = line.split( "\t" );
            count++;
            
        }
}
}
