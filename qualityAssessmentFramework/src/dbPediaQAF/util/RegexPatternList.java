/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.util;

import java.util.LinkedList;
import java.util.List;
import java.util.regex.Pattern;

/**
 *
 * @author Paul
 */
public class RegexPatternList {

    private List<RegexPattern> regexPatternList = new LinkedList<RegexPattern>() {
    };

    public List<RegexPattern> getRegexPatternList() {
        return regexPatternList;
    }

    public RegexPatternList() {
        try {
            RegexPattern xmlInteger = new RegexPattern();
            xmlInteger.setBaseCategory(BaseCategory.Integer);
            xmlInteger.setPattern(Pattern.compile("XMLSchema#integer"));
            regexPatternList.add(xmlInteger);

            RegexPattern xmlDouble = new RegexPattern();
            xmlDouble.setBaseCategory(BaseCategory.Double);
            xmlDouble.setPattern(Pattern.compile("XMLSchema#double|<http://dbpedia.org/datatype/[^>]+>"));
            regexPatternList.add(xmlDouble);

            RegexPattern xmlFloat = new RegexPattern();
            xmlFloat.setBaseCategory(BaseCategory.Float);
            xmlFloat.setPattern(Pattern.compile("XMLSchema#float"));
            regexPatternList.add(xmlFloat);

            RegexPattern xmlDate = new RegexPattern();
            xmlDate.setBaseCategory(BaseCategory.Date);
            xmlDate.setPattern(Pattern.compile("XMLSchema#date"));
            regexPatternList.add(xmlDate);

            RegexPattern xmlYear = new RegexPattern();
            xmlYear.setBaseCategory(BaseCategory.GYear);
            xmlYear.setPattern(Pattern.compile("XMLSchema#gYear(?!M)"));
            regexPatternList.add(xmlYear);

            RegexPattern xmlYearMonth = new RegexPattern();
            xmlYearMonth.setBaseCategory(BaseCategory.GYearMonth);
            xmlYearMonth.setPattern(Pattern.compile("XMLSchema#gYearMonth"));
            regexPatternList.add(xmlYearMonth);

            RegexPattern xmlTime = new RegexPattern();
            xmlTime.setBaseCategory(BaseCategory.Time);
            xmlTime.setPattern(Pattern.compile("XMLSchema#time"));
            regexPatternList.add(xmlTime);

            RegexPattern xmlMonthDay = new RegexPattern();
            xmlMonthDay.setBaseCategory(BaseCategory.GMonthDay);
            xmlMonthDay.setPattern(Pattern.compile("XMLSchema#gMonthDay"));
            regexPatternList.add(xmlMonthDay);

            RegexPattern xmlString = new RegexPattern();
            xmlString.setBaseCategory(BaseCategory.String);
            xmlString.setPattern(Pattern.compile("\".*\"@[a-z]{2}"));
            regexPatternList.add(xmlString);

            RegexPattern resource = new RegexPattern();
            resource.setBaseCategory(BaseCategory.Resource);
            resource.setPattern(Pattern.compile("<http://dbpedia.org/resource/[^>]+>"));
            regexPatternList.add(resource);

            RegexPattern url = new RegexPattern();
            url.setBaseCategory(BaseCategory.Url);
            url.setPattern(Pattern.compile("<(?!http://dbpedia.org/resource|http://dbpedia.org/datatype/|http://www.w3.org/2001/XMLSchema)[^>]+>"));
            regexPatternList.add(url);
        } catch (Exception ex) {
            System.out.println(ex.getMessage());
            ex.printStackTrace();
        }
    }
}
