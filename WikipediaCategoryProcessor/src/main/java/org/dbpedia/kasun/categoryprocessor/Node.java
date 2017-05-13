/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Jun 29, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.FileWriter;
import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.Scanner;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Node
{

    private int nodeId;

    private String categoryName;

    private boolean isProminent;

    private boolean isLeaf;

    private double scoreInterLangu;

    private double scoreEditHisto;

    public void setNodeId( int nodeId )
    {
        this.nodeId = nodeId;
    }

    public void setCategoryName( String catName )
    {
        this.categoryName = catName;
    }

    public void setIsProminent( boolean value )
    {
        this.isProminent = value;
    }

    public void setIsLeaf( boolean value )
    {
        this.isLeaf = value;
    }
    
    public void setScoreInterlangu(double score){
        this.scoreInterLangu=score;
    }
    
    public void setScoreEditHisto(double score){
        this.scoreEditHisto=score;
    }

    public int getNodeId()
    {
        return this.nodeId;
    }

    public String getCategoryName()
    {
        return this.categoryName;
    }
    
    public boolean getIsProminent()
    {
        return this.isProminent;
    }

    public boolean getIsLeaf()
    {
        return this.isLeaf;
    }
    
    public double getScoreInterlangu(){
        return this.scoreInterLangu;
    }
    
    public double getScoreEditHisto(){
        return this.scoreEditHisto;
    }
    
        public static void sortChildren(Scanner parentFileScanner,Scanner childFileScanner) throws IOException{
        String line;
        
        //TO-DO use a HashSet for this
        HashMap<String,String> parentMap=  new HashMap<String, String>();
         HashMap<String,String> childMap=  new HashMap<String, String>();
        while ( parentFileScanner.hasNextLine() )
        {
           // System.out.println(fileScanner.nextLine());
            //split the line by space, will get triples separated 
           line=parentFileScanner.nextLine();
           parentMap.put( line, line );
        }
        
         while ( childFileScanner.hasNextLine() )
        {
           // System.out.println(fileScanner.nextLine());
            //split the line by space, will get triples separated 
           line=childFileScanner.nextLine();
           childMap.put( line, line );
        }
         
         for(Map.Entry entry : parentMap.entrySet()){
             
            if(childMap.containsKey( (String)entry.getKey() ) ){
                childMap.remove((String)entry.getKey());
            }
             
         }
         
         FileWriter outFile3 = new FileWriter( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\leaf_nodes.txt", true );
    
         for(Map.Entry entry : childMap.entrySet()){
             //TO_DO write this data to the database
             outFile3.append((String)entry.getKey()+"\n");
         }
        outFile3.close();
        
    }
}
