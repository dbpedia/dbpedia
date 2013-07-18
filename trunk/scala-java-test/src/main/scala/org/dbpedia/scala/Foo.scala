package org.dbpedia.scala;

import org.dbpedia.java.Special
import org.dbpedia.java.Bar

@Special
object Foo extends Function0[Unit]
{
  def main(args : Array[String]) : Unit =
  {
    Foo.run
    Bar.run
  }
  
  def run()
  {
    println(Bar.bar())
    println(Bar.bar)
    println(foo)
    Foo()
    Foo("Foo.run.key") = "Foo.run.value"
    val bar = new Bar()
    bar()
    bar("Foo.run.key") = "Foo.run.value"
    var result = new Bar()()
    println(result)
  }
  
  var foo = "Foo.foo"
  
  def apply : Unit = println("Foo.apply()")
  
  def update( key : String, value : String ) : Unit = println("Foo.update("+key+", "+value+")")
}