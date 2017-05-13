/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Date Author Changes Jun 29, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;


import java.io.FileWriter;
import java.io.IOException;
import java.util.*;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Edges
{

    ArrayList<Integer> leafNodes = new ArrayList<Integer>();

    private int parentId;

    private int childId;

    public int getChildId()
    {
        return this.childId;
    }

    public int getParentId()
    {
        return this.parentId;
    }

    public void setParentId( int parentId )
    {
        this.parentId = parentId;
    }

    public void setChildId( int childId )
    {
        this.childId = childId;
    }

    public void findProminetNodes( ) throws IOException
    {
         // input leaf nodelit as a file to enhance memoery useage    
        //all leaf nodes

        HashSet<Integer> prominetNodeList= new HashSet<Integer>();

       
            //get all leaf nodes
            leafNodes=EdgeDB.getDisinctleafNodes();


        //creating a clode of leafnodes
        ArrayList<Integer> leafNodesClone = new ArrayList<Integer>( leafNodes.size() );
        for ( Integer p : leafNodes )
        {
            leafNodesClone.add( p );
        }


        for ( int i = 0; i < leafNodes.size(); i++ )
        {
            
            //to check whether leaf becomes prominet node
            boolean isLeafProminent=true;

            //To-Do here need to remove the leaf nodes added from the arry list 

            //get parents of the selected leafnode(there could be one or more parents)
            ArrayList<Integer> parentId = EdgeDB.getParent( leafNodes.get( i ) );

            for ( int j = 0; j < parentId.size(); j++ )
            {
                //get the children of parent node and check all children are leaf nodes
                ArrayList<Integer> childnodes = EdgeDB.getChildren( parentId.get( j ) );

                //boolean prominentNode = isProminent( childnodes );
                //check whether all children are leafs
                if(isLeaf( childnodes )){
                    
                    //duplicates automatically removed
                    prominetNodeList.add( parentId.get( j ) );
                    isLeafProminent=false;
                    
                }
            }
            
            if(isLeafProminent){
                prominetNodeList.add( leafNodes.get( i ) );
            }
        }
        
        
     //  FileWriter outFile4 = new FileWriter( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\promiment_nodes.txt", true );
         //insert this in to the database
    NodeDB.updateProminetNode(prominetNodeList );


    }

    private boolean isLeaf( ArrayList<Integer> childnodes )
    {
        boolean status = true;
        for ( int k = 0; k < childnodes.size(); k++ )
        {
            if ( !leafNodes.contains(childnodes.get( k ) ) )
            {
                status = false;
                break;
            }
        }
        return status;
    }
}
