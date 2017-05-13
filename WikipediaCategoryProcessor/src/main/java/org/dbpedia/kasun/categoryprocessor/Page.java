/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * Date Author Changes Sep 17, 2013 Kasun Perera Created
 *
 */
package org.dbpedia.kasun.categoryprocessor;

/**
 * TODO- describe the purpose of the class
 *
 */
public class Page
{

    private int pageId;

    private String pageName;

    private int pageNameSpace;

    public void setPageID( int pageID )
    {
        this.pageId = pageID;
    }

    public void setPageNameSapce( int pageNameSpace )
    {
        this.pageNameSpace = pageNameSpace;
    }

    public void setPageName( String pageName )
    {
        this.pageName = pageName;
    }
    
    public int getPageID(){
        return  this.pageId;
    }
    
    public int getPageNamespace(){
        return this.pageNameSpace;
    }
    
        public String getPageName(){
        return this.pageName;
    }
}
