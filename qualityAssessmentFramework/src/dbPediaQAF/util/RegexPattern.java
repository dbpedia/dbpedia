/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.util;

import java.util.regex.Pattern;

/**
 *
 * @author Paul
 */
public class RegexPattern {

    private BaseCategory baseCategory;
    private Pattern pattern;

    public BaseCategory getBaseCategory() {
        return baseCategory;
    }

    public void setBaseCategory(BaseCategory baseCategory) {
        this.baseCategory = baseCategory;
    }

    public Pattern getPattern() {
        return pattern;
    }

    public void setPattern(Pattern pattern) {
        this.pattern = pattern;
    }

    public RegexPattern() {
    }

    public RegexPattern(BaseCategory baseCategory, Pattern pattern) {
        this.baseCategory = baseCategory;
        this.pattern = pattern;
    }
}
