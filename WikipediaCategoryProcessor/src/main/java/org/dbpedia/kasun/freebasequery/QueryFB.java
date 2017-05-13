/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 * 
 *      Date             Author          Changes 
 *      Sep 16, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.freebasequery;

import com.google.api.client.http.GenericUrl;
import com.google.api.client.http.HttpRequest;
import com.google.api.client.http.HttpRequestFactory;
import com.google.api.client.http.HttpResponse;
import com.google.api.client.http.HttpTransport;
import com.google.api.client.http.javanet.NetHttpTransport;
import com.jayway.jsonpath.JsonPath;
import java.io.FileInputStream;
import java.util.Properties;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;

/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class QueryFB {
    
     public static Properties properties = new Properties();
  public static void main(String[] args) {
      int count = 0;
    try {
     // properties.load(new FileInputStream("freebase.properties"));
      HttpTransport httpTransport = new NetHttpTransport();
      HttpRequestFactory requestFactory = httpTransport.createRequestFactory();
      JSONParser parser = new JSONParser();
      GenericUrl url = new GenericUrl("https://www.googleapis.com/freebase/v1/search");
     // url.put("query", "Cee Lo Green");
      //url.put("filter", "(all type:/music/artist created:\"The Lady Killer\")");
      // url.put("filter", "(all type:/people/person)");
     //   url.put("filter", "(all type:/location/location)");
      url.put("filter", "(all type:/organization/organization)");
        
   
        url.put("cursor", "0");
      url.put("limit", "160");
      url.put("indent", "true");
     // url.put("key", properties.get("API_KEY"));
       url.put("key","AIzaSyDcHfGTZlVm0KE4KKK9JAM61KBDaXtPiJc");
      HttpRequest request = requestFactory.buildGetRequest(url);
      HttpResponse httpResponse = request.execute();
      JSONObject response = (JSONObject)parser.parse(httpResponse.parseAsString());
      JSONArray results = (JSONArray)response.get("result");
      for (Object result : results) {
          count++;
        System.out.println(JsonPath.read(result,"$.name").toString());
      }
       System.out.println("total: "+ count);
    } catch (Exception ex) {
      ex.printStackTrace();
    }
  }

}
