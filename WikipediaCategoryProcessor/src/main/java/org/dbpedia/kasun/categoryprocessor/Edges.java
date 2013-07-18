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

    ArrayList<String> leafNodes = new ArrayList<String>();

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

    public void findProminetNodes( Scanner leafNodeFileScanner ) throws IOException
    {

        //all leaf nodes

        HashSet<String> prominetNodeList= new HashSet<String>();

        while ( leafNodeFileScanner.hasNextLine() )
        {
            // System.out.println(fileScanner.nextLine());

            leafNodes.add( leafNodeFileScanner.nextLine() );

        }

        //creating a clode of leafnodes
        ArrayList<String> leafNodesClone = new ArrayList<String>( leafNodes.size() );
        for ( String p : leafNodes )
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
                ArrayList<String> childnodes = EdgeDB.getChildren( parentId.get( j ) );

                //boolean prominentNode = isProminent( childnodes );
                if(isProminent( childnodes )){
                    
                    //duplicates automatically removed
                    prominetNodeList.add( NodeDB.getCategoryName(parentId.get( j )) );
                    isLeafProminent=false;
                    
                }
            }
            
            if(isLeafProminent){
                prominetNodeList.add( leafNodes.get( i ) );
            }
        }
        
        
       FileWriter outFile4 = new FileWriter( "F:\\GSOC 2013\\DbPedia\\Task 2- processing wikipedia catogories\\program_outputs\\promiment_nodes.txt", true );
        
for (String s : prominetNodeList) {
    //TO-DO insert this in to the database
    outFile4.append( s+"\n");
    System.out.println(s);
}
outFile4.close();

    }

    private boolean isProminent( ArrayList<String> childnodes )
    {
        boolean status = true;
        for ( int k = 0; k < childnodes.size(); k++ )
        {
            if ( !leafNodes.contains( (String) childnodes.get( k ) ) )
            {
                status = false;
                break;
            }
        }
        return status;
    }
}
