/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package dbPediaQAF.util;

/**
 *
 * @author Paul
 */
public enum BaseCategory {
        Resource, IntermediateNode, Url,
        String, Booelan, Integer, Double, Float,
        Date, GYear, GYearMonth, Time, GMonthDay,
        NOVALUE;

        private int counter = 0;

        public void count() {
            counter = counter + 1;
        }

        public int getCounter() {
            return counter;
        }

        public static BaseCategory toBaseCategory(String str) {
            try {
                return valueOf(str);
            } catch (Exception ex) {
                return NOVALUE;
            }
        }
}
