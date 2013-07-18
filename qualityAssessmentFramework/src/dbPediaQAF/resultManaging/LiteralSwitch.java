/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.datatypes.xsd.impl.XSDBaseNumericType;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDDateType;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDDouble;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDFloat;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDMonthDayType;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDTimeType;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDYearMonthType;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDYearType;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.Evaluator;

/**
 *
 * @author Paul
 */
public class LiteralSwitch implements Switch
{

    public Triples unknowns = new Triples();
    public Triples booleans = new Triples();
    public Triples strings = new Triples();
    public Triples integers = new Triples();
    public Triples doubles = new Triples();
    public Triples floats = new Triples();
    public Triples dates = new Triples();
    public Triples gYears = new Triples();
    public Triples gYearMonths = new Triples();
    public Triples gMonthDays = new Triples();
    public Triples times = new Triples();

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(unknowns.getTriples());
        m.add(booleans.getTriples());
        m.add(strings.getTriples());
        m.add(integers.getTriples());
        m.add(doubles.getTriples());
        m.add(floats.getTriples());
        m.add(dates.getTriples());
        m.add(gYears.getTriples());
        m.add(gYearMonths.getTriples());
        m.add(gMonthDays.getTriples());
        m.add(times.getTriples());
        return m;
    }

    public void add(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isLiteral())
        {
            if (object.asLiteral().getDatatype() != null)
            {
                if (object.asLiteral().getDatatype() instanceof XSDBaseNumericType)
                {
                    integers.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDDouble)
                {
                    doubles.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDFloat)
                {
                    floats.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDYearType)
                {
                    gYears.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDDateType)
                {
                    dates.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDYearMonthType)
                {
                    gYearMonths.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDMonthDayType)
                {
                    gMonthDays.add(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDTimeType)
                {
                    times.add(stmt, datastore);
                }
//                    else if (object.asLiteral().getDatatype() instanceof XSDboolean)
//                    {
//                        System.out.println(" -> XSDboolean");
//                        booleans.add(stmt);
//                        System.out.println("datatype:" + object.asLiteral().getDatatype());
//                    }
//                else if (object.asLiteral().getDatatypeURI().contains("usDollar"))
//                {
//                    doubles.add(stmt);
//                }
//                else if (object.asLiteral().getDatatypeURI().contains("euro"))
//                {
//                    doubles.add(stmt);
//                }
                else if (object.asLiteral().getDatatypeURI().contains("dbpedia.org/datatype"))
                {
                    doubles.add(stmt, datastore);
                }
                else
                {
                    unknowns.add(stmt, datastore);
                    Evaluator.printStatement(stmt, "LiteralSwitch - unable to add an unknown data type:");
                    System.out.println("datatype:" + object.asLiteral().getDatatype());
                    System.out.println("datatype uri:" + object.asLiteral().getDatatypeURI());
                }
            }
            else if (object.asLiteral().getLanguage() != null)
            {
                strings.add(stmt, datastore);
            }
            else
            {
                Evaluator.printStatement(stmt, "LiteralSwitch - unable to add an unknown literal type:");
                System.out.println("data type:" + object.asLiteral().getDatatype());
                System.out.println("data type uri:" + object.asLiteral().getDatatypeURI());
            }
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        RDFNode object = stmt.getObject();
        if (object.isLiteral())
        {
            if (object.asLiteral().getDatatype() != null)
            {
                if (object.asLiteral().getDatatype() instanceof XSDBaseNumericType)
                {
                    integers.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDDouble)
                {
                    doubles.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDFloat)
                {
                    floats.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDYearType)
                {
                    gYears.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDDateType)
                {
                    dates.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDYearMonthType)
                {
                    gYearMonths.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDMonthDayType)
                {
                    gMonthDays.remove(stmt, datastore);
                }
                else if (object.asLiteral().getDatatype() instanceof XSDTimeType)
                {
                    times.remove(stmt, datastore);
                }
//                    else if (object.asLiteral().getDatatype() instanceof XSDboolean)
//                    {
//                        booleans.add(stmt);
//                    }
//                else if (object.asLiteral().getDatatypeURI().contains("usDollar"))
//                {
//                    doubles.add(stmt);
//                }
//                else if (object.asLiteral().getDatatypeURI().contains("euro"))
//                {
//                    doubles.add(stmt);
//                }
                else if (object.asLiteral().getDatatypeURI().contains("dbpedia.org/datatype"))
                {
                    doubles.remove(stmt, datastore);
                }
                else
                {
                    unknowns.remove(stmt, datastore);
                }
            }
            else if (object.asLiteral().getLanguage() != null)
            {
                strings.remove(stmt, datastore);
            }
            else
            {
                Evaluator.printStatement(stmt, "LiteralSwitch - unable to remove an unknown literal type:");
                System.out.println("data type:" + object.asLiteral().getDatatype());
                System.out.println("data type uri:" + object.asLiteral().getDatatypeURI());
            }
        }
    }

    public int getNumberOfLiterals()
    {
        int num = (int) unknowns.getTriples().size()
                + (int) booleans.getTriples().size()
                + (int) strings.getTriples().size()
                + (int) integers.getTriples().size()
                + (int) doubles.getTriples().size()
                + (int) floats.getTriples().size()
                + (int) dates.getTriples().size()
                + (int) gYears.getTriples().size()
                + (int) gYearMonths.getTriples().size()
                + (int) gMonthDays.getTriples().size()
                + (int) times.getTriples().size();
        return num;
    }

    public void printStats()
    {
        System.out.println("Literal Results:");
        System.out.println("Overall triples: " + getNumberOfLiterals());
        System.out.println("Number of booleans: " + booleans.getTriples().size());
        System.out.println("Number of strings: " + strings.getTriples().size());
        System.out.println("Number of integers: " + integers.getTriples().size());
        System.out.println("Number of doubles: " + doubles.getTriples().size());
        System.out.println("Number of floats: " + floats.getTriples().size());
        System.out.println("Number of dates: " + dates.getTriples().size());
        System.out.println("Number of gYears: " + gYears.getTriples().size());
        System.out.println("Number of gYearMonths: " + gYearMonths.getTriples().size());
        System.out.println("Number of gMonthDays: " + gMonthDays.getTriples().size());
        System.out.println("Number of times: " + times.getTriples().size());
        System.out.println("Number of unknowns: " + unknowns.getTriples().size());

    }
}
