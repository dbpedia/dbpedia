/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * KarshaAnnotate- Annotation tool for financial documents
 *
 * Copyright (C) 2013, Lanka Software Foundation and and University of Maryland.
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General
 * Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
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
