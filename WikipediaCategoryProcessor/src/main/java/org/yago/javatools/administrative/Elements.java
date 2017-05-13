/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Sep 4, 2013 Kasun Perera Created
 *
 */
package org.yago.javatools.administrative;


import org.yago.javatools.parsers.NounGroup;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Elements
{

    public static void main( String[] args ) throws Exception
    {

        System.out.println( getHead( "booooooooo" ) );

    }

    public static String getHead( String category )
    {

        String elementList[] = splitObject( new NounGroup( category ).description() );
        if ( elementList == null || elementList.length == 0 )
        {
            return ( null );
        }
        /*
         * lelemnts of the elementList 
         * [0]"NounGroup:
         * [1]Original: "+original+"
         * [2]Stemmed: "+stemmed()+"
         * [3]Determiner: "+determiner+"
         * [4]preModifiers: "+preModifier+"
         * [5]Head: "+head+"
         * [6]Adjective:"+adjective+"
         * [7]Preposition: "+preposition+"
         * [8]postModifier:\n"+(postModifier==null?"":postModifier.description()));
         * 
         */
        String head[] = elementList[5].split( ":" );
        if(head.length<1){
            return (null);
        }
        
        return (head[1].trim());
    }

    public static String[] splitObject( Object... a )
    {
        String objectlist[] = D.toString( a ).split( "\\n" );

        return objectlist;
    }
}
