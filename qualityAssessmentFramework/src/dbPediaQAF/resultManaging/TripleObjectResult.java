/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import dbPediaQAF.util.PatternCategory;
import java.lang.reflect.Field;
import java.util.HashMap;
import java.util.Map;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 *
 * @author Paul
 */
public class TripleObjectResult
{

    public Map<String, Long> booleanResults = new HashMap<String, Long>();
    public Map<String, Long> stringResults = new HashMap<String, Long>();
    public Map<String, Long> integerResults = new HashMap<String, Long>();
    public Map<String, Long> doubleResults = new HashMap<String, Long>();
    public Map<String, Long> floatResults = new HashMap<String, Long>();
    public Map<String, Long> dateResults = new HashMap<String, Long>();
    public Map<String, Long> gYearResults = new HashMap<String, Long>();
    public Map<String, Long> gYearMonthResults = new HashMap<String, Long>();
    public Map<String, Long> gMonthDayResults = new HashMap<String, Long>();
    public Map<String, Long> timeResults = new HashMap<String, Long>();
    public Map<String, Long> entityResults = new HashMap<String, Long>();
    public Map<String, Long> intermediateResults = new HashMap<String, Long>();
    public Map<String, Long> urlResults = new HashMap<String, Long>();
    public Map<String, Long> booleanIntermResults = new HashMap<String, Long>();
    public Map<String, Long> stringIntermResults = new HashMap<String, Long>();
    public Map<String, Long> integerIntermResults = new HashMap<String, Long>();
    public Map<String, Long> doubleIntermResults = new HashMap<String, Long>();
    public Map<String, Long> floatIntermResults = new HashMap<String, Long>();
    public Map<String, Long> dateIntermResults = new HashMap<String, Long>();
    public Map<String, Long> gYearIntermResults = new HashMap<String, Long>();
    public Map<String, Long> gYearMonthIntermResults = new HashMap<String, Long>();
    public Map<String, Long> gMonthDayIntermResults = new HashMap<String, Long>();
    public Map<String, Long> timeIntermResults = new HashMap<String, Long>();
    public Map<String, Long> entityIntermResults = new HashMap<String, Long>();
    public Map<String, Long> intermediateIntermResults = new HashMap<String, Long>();
    public Map<String, Long> urlIntermResults = new HashMap<String, Long>();

    public TripleObjectResult(CompletenessHandswitch categoryResult, PatternCategory patternCategory)
    {
        switch (patternCategory)
        {
            case PlainProperty:
                putCategory(categoryResult, "plainProperty");
                putIntermCategory(categoryResult, "plainProperty");
                break;
            case NumberUnit:
                putCategory(categoryResult, "numberUnits");
                putIntermCategory(categoryResult, "numberUnits");
                break;
            case Coordinates:
                putCategory(categoryResult, "coordinates");
                putIntermCategory(categoryResult, "coordinates");
                break;
            case List:
                putCategory(categoryResult, "lists");
                putIntermCategory(categoryResult, "lists");
                break;
            case Interval:
                putCategory(categoryResult, "intervals");
                putIntermCategory(categoryResult, "intervals");
                break;
            case OnePropertyTable:
                putCategory(categoryResult, "onePropertyTables");
                putIntermCategory(categoryResult, "onePropertyTables");
                break;
            case MultiPropertyTable:
                putCategory(categoryResult, "multiPropertyTables");
                putIntermCategory(categoryResult, "multiPropertyTables");
                break;
            case OpenProperty:
                putCategory(categoryResult, "openProperties");
                putIntermCategory(categoryResult, "openProperties");
                break;
            case OpenPropertyTable:
                putCategory(categoryResult, "openPropertyTables");
                putIntermCategory(categoryResult, "openPropertyTables");
                break;
            case InternalTemplate:
                putCategory(categoryResult, "internalTemplates");
                putIntermCategory(categoryResult, "internalTemplates");
                break;
            case MergedProperties:
                putCategory(categoryResult, "mergedProperties");
                putIntermCategory(categoryResult, "mergedProperties");
                break;
            default:
                break;
        }
    }

    private void putIntermCategory(CompletenessHandswitch categoryResult, String cat)
    {
        putIntermField(categoryResult, cat, "strings", stringIntermResults);
        putIntermField(categoryResult, cat, "integers", integerIntermResults);
        putIntermField(categoryResult, cat, "doubles", doubleIntermResults);
        putIntermField(categoryResult, cat, "floats", floatIntermResults);
        putIntermField(categoryResult, cat, "dates", dateIntermResults);
        putIntermField(categoryResult, cat, "booleans", booleanIntermResults);
        putIntermField(categoryResult, cat, "gYears", gYearIntermResults);
        putIntermField(categoryResult, cat, "gYearMonths", gYearMonthIntermResults);
        putIntermField(categoryResult, cat, "gMonthDays", gMonthDayIntermResults);
        putIntermField(categoryResult, cat, "times", timeIntermResults);
        putIntermField(categoryResult, cat, "entities", entityIntermResults);
        putIntermField(categoryResult, cat, "intermediateNodes", intermediateIntermResults);
        putIntermField(categoryResult, cat, "urls", urlIntermResults);
    }

    private void putCategory(CompletenessHandswitch categoryResult, String cat)
    {
        putField(categoryResult, cat, "strings", stringResults);
        putField(categoryResult, cat, "integers", integerResults);
        putField(categoryResult, cat, "doubles", doubleResults);
        putField(categoryResult, cat, "floats", floatResults);
        putField(categoryResult, cat, "dates", dateResults);
        putField(categoryResult, cat, "booleans", booleanResults);
        putField(categoryResult, cat, "gYears", gYearResults);
        putField(categoryResult, cat, "gYearMonths", gYearMonthResults);
        putField(categoryResult, cat, "gMonthDays", gMonthDayResults);
        putField(categoryResult, cat, "times", timeResults);
        putField(categoryResult, cat, "entities", entityResults);
        putField(categoryResult, cat, "intermediateNodes", intermediateResults);
        putField(categoryResult, cat, "urls", urlResults);
    }

    public final void putField(CompletenessHandswitch categoryResult, String cat, String datatype, Map<String, Long> map)
    {
        try
        {
            Field catField = CategorySwitch.class.getDeclaredField(cat);
            MappingSwitch presentClass = (MappingSwitch) catField.get(categoryResult.present);
            MappingSwitch missingClass = (MappingSwitch) catField.get(categoryResult.missing);
            Field datatypeField;
            long sp, spm, sm, smm;
            try
            {
                datatypeField = LiteralSwitch.class.getDeclaredField(datatype);
                spm = ((Triples) datatypeField.get(presentClass.withMapping.triples.entityTriples.literals)).getTriples().size();
                sp = spm + ((Triples) datatypeField.get(presentClass.withoutMapping.triples.entityTriples.literals)).getTriples().size();
                smm = ((Triples) datatypeField.get(missingClass.withMapping.triples.entityTriples.literals)).getTriples().size();
                sm = smm + ((Triples) datatypeField.get(missingClass.withoutMapping.triples.entityTriples.literals)).getTriples().size();
            }
            catch (NoSuchFieldException ex)
            {
                datatypeField = ResourceSwitch.class.getDeclaredField(datatype);
                spm = ((Triples) datatypeField.get(presentClass.withMapping.triples.entityTriples.resources)).getTriples().size();
                sp = spm + ((Triples) datatypeField.get(presentClass.withoutMapping.triples.entityTriples.resources)).getTriples().size();
                smm = ((Triples) datatypeField.get(missingClass.withMapping.triples.entityTriples.resources)).getTriples().size();
                sm = smm + ((Triples) datatypeField.get(missingClass.withoutMapping.triples.entityTriples.resources)).getTriples().size();
            }

            map.put("present", sp);
            map.put("presentMapping", spm);
            map.put("missing", sm);
            map.put("missingMapping", smm);
        }
        catch (Exception ex)
        {
            Logger.getLogger(TripleObjectResult.class.getName()).log(Level.SEVERE, null, ex);
        }

    }

    public final void putIntermField(CompletenessHandswitch categoryResult, String cat, String datatype, Map<String, Long> map)
    {
        try
        {
            Field catField = CategorySwitch.class.getDeclaredField(cat);
            MappingSwitch presentClass = (MappingSwitch) catField.get(categoryResult.present);
            MappingSwitch missingClass = (MappingSwitch) catField.get(categoryResult.missing);
            Field datatypeField;
            long sp, spm, sm, smm;
            try
            {
                datatypeField = LiteralSwitch.class.getDeclaredField(datatype);
                spm = ((Triples) datatypeField.get(presentClass.withMapping.triples.intermediateTriples.literals)).getTriples().size();
                sp = spm + ((Triples) datatypeField.get(presentClass.withoutMapping.triples.intermediateTriples.literals)).getTriples().size();
                smm = ((Triples) datatypeField.get(missingClass.withMapping.triples.intermediateTriples.literals)).getTriples().size();
                sm = smm + ((Triples) datatypeField.get(missingClass.withoutMapping.triples.intermediateTriples.literals)).getTriples().size();
            }
            catch (NoSuchFieldException ex)
            {
                datatypeField = ResourceSwitch.class.getDeclaredField(datatype);
                spm = ((Triples) datatypeField.get(presentClass.withMapping.triples.intermediateTriples.resources)).getTriples().size();
                sp = spm + ((Triples) datatypeField.get(presentClass.withoutMapping.triples.intermediateTriples.resources)).getTriples().size();
                smm = ((Triples) datatypeField.get(missingClass.withMapping.triples.intermediateTriples.resources)).getTriples().size();
                sm = smm + ((Triples) datatypeField.get(missingClass.withoutMapping.triples.intermediateTriples.resources)).getTriples().size();
            }

            map.put("present", sp);
            map.put("presentMapping", spm);
            map.put("missing", sm);
            map.put("missingMapping", smm);
        }
        catch (Exception ex)
        {
            Logger.getLogger(TripleObjectResult.class.getName()).log(Level.SEVERE, null, ex);
        }

    }

    public void print()
    {
        System.out.println(stringResults.get("present"));
        System.out.println(stringResults.get("presentMapping"));
        System.out.println(stringResults.get("missing"));
        System.out.println(stringResults.get("missingMapping"));
        System.out.println(stringIntermResults.get("present"));
        System.out.println(stringIntermResults.get("presentMapping"));
        System.out.println(stringIntermResults.get("missing"));
        System.out.println(stringIntermResults.get("missingMapping"));
        System.out.println(integerResults.get("present"));
        System.out.println(integerResults.get("presentMapping"));
        System.out.println(integerResults.get("missing"));
        System.out.println(integerResults.get("missingMapping"));
        System.out.println(integerIntermResults.get("present"));
        System.out.println(integerIntermResults.get("presentMapping"));
        System.out.println(integerIntermResults.get("missing"));
        System.out.println(integerIntermResults.get("missingMapping"));
    }
}
