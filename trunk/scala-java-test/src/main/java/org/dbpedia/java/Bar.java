package org.dbpedia.java;

import java.util.Arrays;

import org.dbpedia.scala.Foo;

import scala.Function0;

@Special // @org.dbpedia.scala.VerySpecial
public class Bar implements Function0<Void>
{
  public static void main( String[] args )
  {
    Foo.run();
    Bar.run();
  }

  public static void run()
  {
    System.out.println(Foo.foo());
    Foo.foo_$eq("Foo.foo.changed");
    System.out.println(Foo.foo());
    Foo.apply();
    Foo.update("Bar.run.key", "Bar.run.value");
    new Bar().apply();
    new Bar().update("Bar.run.key", "Bar.run.value");
    
    System.out.println("Bar annos: " + Arrays.toString(Bar.class.getAnnotations()));
    
    // no annos before 2.8 - see http://lampsvn.epfl.ch/trac/scala/ticket/1802
    System.out.println("Foo annos: " + Arrays.toString(Foo.class.getAnnotations()));
    
    // this doesn't really compile - only when javac runs after scalac
    // System.out.println("Foo$ annos: " + Arrays.toString(org.dbpedia.scala.Foo$.class.getAnnotations()));
  }
  
  public static String bar()
  {
    return "Bar.bar()";
  }
  
  public static final String bar = "Bar.bar";
  
  public void update( String key, String value )
  {
	  System.out.println("Bar.update("+key+", "+value+")");
  }

  @Override
  public Void apply()
  {
    System.out.println("Bar.apply()");
    return null;
  }
}
