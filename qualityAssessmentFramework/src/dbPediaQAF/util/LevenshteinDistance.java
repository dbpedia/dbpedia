package dbPediaQAF.util;

/**
 * LD function from:
 * Michael Gilleland
 * http://www.merriampark.com/ld.htm#FLAVORS
 *
 * getSimilarity function from:
 * SimMetrics - SimMetrics is a java library of Similarity or Distance
 * Metrics, e.g. Levenshtein Distance, that provide float based similarity
 * measures between String Data. All metrics return consistant measures
 * rather than unbounded similarity scores.
 *
 * Copyright (C) 2005 Sam Chapman - Open Source Release v1.1
 *
 * http://simmetrics.cvs.sourceforge.net/viewvc/simmetrics/simmetrics/src/uk/ac/shef/wit/
 * simmetrics/similaritymetrics/Levenshtein.java?revision=1.1&view=markup
 */
public class LevenshteinDistance
{

    //****************************
    // Get minimum of three values
    //****************************
    private static int Minimum(int a, int b, int c)
    {
        int mi;

        mi = a;
        if (b < mi)
        {
            mi = b;
        }
        if (c < mi)
        {
            mi = c;
        }
        return mi;

    }

    //*****************************
    // Compute Levenshtein distance
    //*****************************
    private static int LD(String s, String t)
    {
        int d[][]; // matrix
        int n; // length of s
        int m; // length of t
        int i; // iterates through s
        int j; // iterates through t
        char s_i; // ith character of s
        char t_j; // jth character of t
        int cost; // cost

        // Step 1

        n = s.length();
        m = t.length();
        if (n == 0)
        {
            return m;
        }
        if (m == 0)
        {
            return n;
        }
        d = new int[n + 1][m + 1];

        // Step 2

        for (i = 0; i <= n; i++)
        {
            d[i][0] = i;
        }

        for (j = 0; j <= m; j++)
        {
            d[0][j] = j;
        }

        // Step 3

        for (i = 1; i <= n; i++)
        {

            s_i = s.charAt(i - 1);

            // Step 4

            for (j = 1; j <= m; j++)
            {

                t_j = t.charAt(j - 1);

                // Step 5

                if (s_i == t_j)
                {
                    cost = 0;
                }
                else
                {
                    cost = 1;
                }

                // Step 6

                d[i][j] = Minimum(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);

            }

        }

        // Step 7

        return d[n][m];
    }

    /**
     * gets the similarity of the two strings using levenstein distance.
     *
     * @param string1
     * @param string2
     * @return a value between 0-1 of the similarity
     */
    public static float getSimilarity(final String string1, final String string2)
    {
        final float levensteinDistance = LD(string1, string2);
        //convert into zero to one return

        //get the max possible levenstein distance score for string
        float maxLen = string1.length();
        if (maxLen < string2.length())
        {
            maxLen = string2.length();
        }

        //check for 0 maxLen
        if (maxLen == 0)
        {
            return 1.0f; //as both strings identically zero length
        }
        else
        {
            //return actual / possible levenstein distance to get 0-1 range
            return 1.0f - (levensteinDistance / maxLen);
        }

    }
}
