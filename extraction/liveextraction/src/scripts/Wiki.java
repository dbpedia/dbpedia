package scripts;

/*
 * Source code taken from
 * 	http://en.wikipedia.org/wiki/User:MER-C/Wiki.java
 *
 * Known glitches with Wiki.java
 * -----------------------------
 * Logon might be lowercase, but return from server might be ucFirst.
 * 		In this case Wiki.java doesn't recognize the cookie correctly.
 * 
 * 
 */


/**
 *  @(#)Wiki.java 0.20 27/03/2009
 *  Copyright (C) 2007 - 2009 MER-C
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 3
 *  of the License, or (at your option) any later version. Additionally
 *  this file is subject to the "Classpath" exception.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software Foundation,
 *  Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */
 
import java.awt.image.*;
import java.io.*;
import java.net.*;
import java.util.*;
import java.util.logging.*;
import java.util.zip.*;
import javax.imageio.*;
import javax.security.auth.login.*; // useful exception types
 
/**
 *  This is a somewhat sketchy bot framework for editing MediaWiki wikis.
 *  Requires JDK 1.5 (5.0) or greater. Uses the [[mw:API|MediaWiki API]] for
 *  most operations. It is recommended that the server runs the latest version
 *  of MediaWiki (1.14), otherwise some functions may not work.
 *
 *  <p>
 *  A typical program would go something like this:
 *
 *  <pre>
 *  Wiki wiki;
 *  File f = new File("wiki.dat");
 *  if (f.exists()) // we already have a copy on disk
 *  {
 *      ObjectInputStream in = new ObjectInputStream(new FileInputStream(f));
 *      wiki = (Wiki)in.readObject();
 *  }
 *  else
 *  {
 *      try
 *      {
 *          wiki = new Wiki("en.wikipedia.org"); // create a new wiki connection to en.wikipedia.org
 *          wiki.setThrottle(5000); // set the edit throttle to 0.2 Hz
 *          wiki.login("ExampleBot", password); // log in as user ExampleBot, with the specified password
 *      }
 *      catch (FailedLoginException ex)
 *      {
 *          // deal with failed login attempt
 *      }
 *  }
 *  String[] titles = . . . ; // fetch a list of titles
 *  try
 *  {
 *      for (int i = 0; i < titles.length; i++)
 *      {
 *          try
 *          {
 *              // do something with titles[i]
 *          }
 *          catch (Exception ex)
 *          {
 *              // this exception isn't fatal - probably won't affect the task as a whole
 *              if (ex.getClass().equals(CredentialException.class))
 *                  // deal with protected page
 *              else
 *                  throw ex;
 *          }
 *      }
 *  }
 *  catch (Exception ex)
 *  {
 *      // these exceptions are fatal - we need to abandon the task
 *      if (ex instanceof CredentialNotFoundException)
 *          // deal with trying to do something we can't
 *      else if (ex instanceof AccountLockedException)
 *          // deal with being blocked
 *      else if (ex instanceof IOException)
 *          // deal with network error
 *  }
 *  </pre>
 *
 *  Don't forget to release system resources held by this object when done.
 *  This may be achieved by logging out of the wiki. Since <tt>logout()</tt> is
 *  entirely offline, we can have a persistent session by simply serializing
 *  this wiki, then logging out as follows:
 *
 *  <pre>
 *  File f = new File("wiki.dat");
 *  ObjectOutputStream out = new ObjectOutputStream(new FileOutputStream(f));
 *  out.writeObject(wiki); // if we want the session to persist
 *  out.close();
 *  wiki.logout();
 *  </pre>
 *
 *  Long term storage of data (particularly greater than 20 days) is not
 *  recommended as the cookies may expire on the server.
 *
 *  <h4>Assertions</h4>
 *
 *  Without too much effort, it is possible to emulate assertions supported
 *  by [[mw:Extension:Assert Edit]]. The extension need not be installed
 *  for these assertions to work. Use <tt>setAssertionMode(int mode)</tt>
 *  to set the assertion mode. Checking for login, bot flag or new messages is
 *  supported by default. Other assertions can easily be defined, see {@link
 *  http://java.sun.com/j2se/1.4.2/docs/guide/lang/assert.html Programming
 *  With Assertions}. Assertions are applied on write methods only and are
 *  disabled by default.
 *
 *  <p>
 *  IMPORTANT: You need to run the program with the flag -enableassertions
 *  or -ea to enable assertions, example: <tt>java -ea Mybot</tt>.
 *
 *  <p>
 *  Please file bug reports at [[User talk:MER-C/Wiki.java]]. Revision
 *  history is on the same page.
 *  <!-- all wikilinks are relative to the English Wikipedia -->
 *
 *  @author MER-C
 *  @version 0.20
 */
public class Wiki implements Serializable
{
    // NAMESPACES
 
    /**
     *  Denotes the namespace of images and media, such that there is no
     *  description page. Uses the "Media:" prefix.
     *  @see IMAGE_NAMESPACE
     *  @since 0.03
     */
    public static final int MEDIA_NAMESPACE = -2;
 
    /**
     *  Denotes the namespace of pages with the "Special:" prefix. Note
     *  that many methods dealing with special pages may spew due to
     *  raw content not being available.
     *  @since 0.03
     */
    public static final int SPECIAL_NAMESPACE = -1;
 
    /**
     *  Denotes the main namespace, with no prefix.
     *  @since 0.03
     */
    public static final int MAIN_NAMESPACE = 0;
 
    /**
     *  Denotes the namespace for talk pages relating to the main
     *  namespace, denoted by the prefix "Talk:".
     *  @since 0.03
     */
    public static final int TALK_NAMESPACE = 1;
 
    /**
     *  Denotes the namespace for user pages, given the prefix "User:".
     *  @since 0.03
     */
    public static final int USER_NAMESPACE = 2;
 
    /**
     *  Denotes the namespace for user talk pages, given the prefix
     *  "User talk:".
     *  @since 0.03
     */
    public static final int USER_TALK_NAMESPACE = 3;
 
    /**
     *  Denotes the namespace for pages relating to the project,
     *  with prefix "Project:". It also goes by the name of whatever
     *  the project name was.
     *  @since 0.03
     */
    public static final int PROJECT_NAMESPACE = 4;
 
    /**
     *  Denotes the namespace for talk pages relating to project
     *  pages, with prefix "Project talk:". It also goes by the name
     *  of whatever the project name was, + "talk:".
     *  @since 0.03
     */
    public static final int PROJECT_TALK_NAMESPACE = 5;
 
    /**
     *  Denotes the namespace for image/file description pages. Has the prefix
     *  prefix "File:". Do not create these directly, use upload() instead.
     *  (This namespace used to have the prefix "Image:", hence the name.)
     *  @see MEDIA_NAMESPACE
     *  @since 0.03
     */
    public static final int IMAGE_NAMESPACE = 6;
 
    /**
     *  Denotes talk pages for image description pages. Has the prefix
     *  "File talk:".
     *  @since 0.03
     */
    public static final int IMAGE_TALK_NAMESPACE = 7;
 
    /**
     *  Denotes the namespace for (wiki) system messages, given the prefix
     *  "MediaWiki:".
     *  @since 0.03
     */
    public static final int MEDIAWIKI_NAMESPACE = 8;
 
    /**
     *  Denotes the namespace for talk pages relating to system messages,
     *  given the prefix "MediaWiki talk:".
     *  @since 0.03
     */
    public static final int MEDIAWIKI_TALK_NAMESPACE = 9;
 
    /**
     *  Denotes the namespace for templates, given the prefix "Template:".
     *  @since 0.03
     */
    public static final int TEMPLATE_NAMESPACE = 10;
 
    /**
     *  Denotes the namespace for talk pages regarding templates, given
     *  the prefix "Template talk:".
     *  @since 0.03
     */
    public static final int TEMPLATE_TALK_NAMESPACE = 11;
 
    /**
     *  Denotes the namespace for help pages, given the prefix "Help:".
     *  @since 0.03
     */
    public static final int HELP_NAMESPACE = 12;
 
    /**
     *  Denotes the namespace for talk pages regarding help pages, given
     *  the prefix "Help talk:".
     *  @since 0.03
     */
    public static final int HELP_TALK_NAMESPACE = 13;
 
    /**
     *  Denotes the namespace for category description pages. Has the
     *  prefix "Category:".
     *  @since 0.03
     */
    public static final int CATEGORY_NAMESPACE = 14;
 
    /**
     *  Denotes the namespace for talk pages regarding categories. Has the
     *  prefix "Category talk:".
     *  @since 0.03
     */
    public static final int CATEGORY_TALK_NAMESPACE = 15;
 
    /**
     *  Denotes all namespaces.
     *  @since 0.03
     */
    public static final int ALL_NAMESPACES = 0x09f91102;
 
    // USER RIGHTS
 
    /**
     *  Denotes no user rights.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int IP_USER = -1;
 
    /**
     *  Denotes a registered account.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int REGISTERED_USER = 1;
 
    /**
     *  Denotes a user who has admin rights.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int ADMIN = 2;
 
    /**
     *  Denotes a user who has bureaucrat rights.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int BUREAUCRAT = 4;
 
    /**
     *  Denotes a user who has steward rights.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int STEWARD = 8;
 
    /**
     *  Denotes a user who has a bot flag.
     *  @see User#userRights()
     *  @since 0.05
     */
    public static final int BOT = 16;
 
    // LOG TYPES
 
    /**
     *  Denotes all logs.
     *  @since 0.06
     */
    public static final String ALL_LOGS = "";
 
    /**
     *  Denotes the user creation log.
     *  @since 0.06
     */
    public static final String USER_CREATION_LOG = "newusers";
 
    /**
     *  Denotes the upload log.
     *  @since 0.06
     */
    public static final String UPLOAD_LOG = "upload";
 
    /**
     *  Denotes the deletion log.
     *  @since 0.06
     */
    public static final String DELETION_LOG = "delete";
 
    /**
     *  Denotes the move log.
     *  @since 0.06
     */
    public static final String MOVE_LOG = "move";
 
    /**
     *  Denotes the block log.
     *  @since 0.06
     */
    public static final String BLOCK_LOG = "block";
 
    /**
     *  Denotes the protection log.
     *  @since 0.06
     */
    public static final String PROTECTION_LOG = "protect";
 
    /**
     *  Denotes the user rights log.
     *  @since 0.06
     */
    public static final String USER_RIGHTS_LOG = "rights";
 
    /**
     *  Denotes the user renaming log.
     *  @since 0.06
     */
    public static final String USER_RENAME_LOG = "renameuser";
 
    /**
     *  Denotes the bot status log.
     *  @since 0.08
     *  @deprecated [[Special:Makebot]] is deprecated, use
     *  <tt>USER_RIGHTS_LOG</tt> instead.
     */
    public static final String BOT_STATUS_LOG = "makebot";
 
    /**
     *  Denotes the page importation log.
     *  @since 0.08
     */
    public static final String IMPORT_LOG = "import";
 
    /**
     *  Denotes the edit patrol log.
     *  @since 0.08
     */
    public static final String PATROL_LOG = "patrol";
 
    // PROTECTION LEVELS
 
    /**
     *  Denotes a non-protected page.
     *  @since 0.09
     */
    public static final int NO_PROTECTION = -1;
 
    /**
     *  Denotes semi-protection (i.e. only autoconfirmed users can edit this page)
     *  [edit=autoconfirmed;move=autoconfirmed].
     *  @since 0.09
     */
    public static final int SEMI_PROTECTION = 1;
 
    /**
     *  Denotes full protection (i.e. only admins can edit this page)
     *  [edit=sysop;move=sysop].
     *  @see #ADMIN
     *  @see User#userRights()
     *  @since 0.09
     */
    public static final int FULL_PROTECTION = 2;
 
    /**
     *  Denotes move protection (i.e. only admins can move this page) [move=sysop].
     *  We don't define semi-move protection because only autoconfirmed users
     *  can move pages anyway.
     *
     *  @see #ADMIN
     *  @see User#userRights()
     *  @since 0.09
     */
    public static final int MOVE_PROTECTION = 3;
 
    /**
     *  Denotes move and semi-protection (i.e. autoconfirmed editors can edit the
     *  page, but you need to be a sysop to move) [edit=autoconfirmed;move=sysop].
     *  Naturally, this value (4) is equal to SEMI_PROTECTION (1) +
     *  MOVE_PROTECTION (3).
     *
     *  @see #ADMIN
     *  @see User#userRights()
     *  @since 0.09
     */
    public static final int SEMI_AND_MOVE_PROTECTION = 4;
 
    /**
     *  Denotes protected deleted pages [create=sysop].
     *  @since 0.12
     *  @see #ADMIN
     */
     public static final int PROTECTED_DELETED_PAGE = 5;
 
    // ASSERTION MODES
 
    /**
     *  Use no assertions (i.e. 0).
     *  @see #setAssertionMode
     *  @since 0.11
     */
    public static final int ASSERT_NONE = 0;
 
    /**
     *  Assert that we are logged in (i.e. 1).
     *  @see #setAssertionMode
     *  @since 0.11
     */
    public static final int ASSERT_LOGGED_IN = 1;
 
    /**
     *  Assert that we have a bot flag (i.e. 2).
     *  @see #setAssertionMode
     *  @since 0.11
     */
    public static final int ASSERT_BOT = 2;
 
    /**
     *  Assert that we have no new messages. Not defined in Assert Edit, but
     *  some bots have this.
     *  @see #setAssertionMode
     *  @since 0.11
     */
    public static final int ASSERT_NO_MESSAGES = 4;
 
    // RC OPTIONS
 
    /**
     *  In queries against the recent changes table, this would mean we don't
     *  fetch anonymous edits.
     *  @since 0.20
     */
    public static final int HIDE_ANON = 1;
 
    /**
     *  In queries against the recent changes table, this would mean we don't
     *  fetch edits made by bots.
     *  @since 0.20
     */
    public static final int HIDE_BOT = 2;
 
    /**
     *  In queries against the recent changes table, this would mean we don't
     *  fetch by the logged in user.
     *  @since 0.20
     */
    public static final int HIDE_SELF = 4;
 
    /**
     *  In queries against the recent changes table, this would mean we don't
     *  fetch minor edits.
     *  @since 0.20
     */
    public static final int HIDE_MINOR = 8;
 
    /**
     *  In queries against the recent changes table, this would mean we don't
     *  fetch patrolled edits.
     *  @since 0.20
     */
    public static final int HIDE_PATROLLED = 16;
 
    // the domain of the wiki
    private String domain, query, base;
    private String scriptPath = "/w"; // need this for sites like partyvan.info
 
    // user management
    private HashMap cookies = new HashMap(12);
    private HashMap cookies2 = new HashMap(10);
    private User user;
    private int statuscounter = 0;
 
    // various caches
    private HashMap namespaces = null;
    private ArrayList<String> watchlist = null;
 
    // preferences
    private int max = 500; // awkward workaround
    private static Logger logger = Logger.getLogger("wiki"); // only one required
    private int throttle = 10000; // throttle
    private int maxlag = 5;
    private volatile long lastlagcheck;
    private int assertion = 0; // assertion mode
    private int statusinterval = 100; // status check
 
    // retry flag
    private boolean retry = true;
 
    // serial version
    private static final long serialVersionUID = -8745212681497644126L;
 
    // CONSTRUCTORS AND CONFIGURATION
 
    /**
     *  Logs which version we're using.
     *  @since 0.12
     */
    static
    {
        logger.logp(Level.CONFIG, "Wiki", "<init>", "Using Wiki.java v0.20.");
    }
 
    /**
     *  Creates a new connection to the English Wikipedia.
     *  @since 0.02
     */
    public Wiki()
    {
        this("");
    }
 
    /**
     *  Creates a new connection to a wiki. WARNING: if the wiki uses a
     *  $wgScriptpath other than the default <tt>/w</tt>, you need to call
     *  <tt>getScriptPath()</tt> to automatically set it. Alternatively, you
     *  can use the constructor below if you know it in advance.
     *
     *  @param domain the wiki domain name e.g. en.wikipedia.org (defaults to
     *  en.wikipedia.org)
     */
    public Wiki(String domain)
    {
        if (domain == null || domain.equals(""))
            domain = "en.wikipedia.org";
        this.domain = domain;
 
        // init variables
        base = "http://" + domain + scriptPath + "/index.php?title=";
        query = "http://" + domain + scriptPath +  "/api.php?format=xml&";
    }
 
    /**
     *  Creates a new connection to a wiki with $wgScriptpath set to
     *  <tt>scriptPath</tt>.
     *
     *  @param domain the wiki domain name
     *  @param scriptPath the script path
     *  @since 0.14
     */
    public Wiki(String domain, String scriptPath)
    {
        this.domain = domain;
        this.scriptPath = scriptPath;
 
        // init variables
        base = "http://" + domain + scriptPath + "/index.php?title=";
        query = "http://" + domain + scriptPath +  "/api.php?format=xml&";
    }
 
    /**
     *  Gets the domain of the wiki, as supplied on construction.
     *  @return the domain of the wiki
     *  @since 0.06
     */
    public String getDomain()
    {
        return domain;
    }
 
    /**
     *  Gets the editing throttle.
     *  @return the throttle value in milliseconds
     *  @see #setThrottle
     *  @since 0.09
     */
    public int getThrottle()
    {
        return throttle;
    }
 
    /**
     *  Sets the editing throttle. Read requests are not throttled or restricted
     *  in any way. Default is 10s.
     *  @param throttle the new throttle value in milliseconds
     *  @see #getThrottle
     *  @since 0.09
     */
    public void setThrottle(int throttle)
    {
        this.throttle = throttle;
        log(Level.CONFIG, "Throttle set to " + throttle + " milliseconds", "setThrottle");
    }
 
    /**
     *  Detects the $wgScriptpath wiki variable and sets the bot framework up
     *  to use it. You need not call this if you know the script path is
     *  <tt>/w</tt>. See also [[mw:Manual:$wgScriptpath]].
     *
     *  @throws IOException if a network error occurs
     *  @return the script path, if you have any use for it
     *  @since 0.14
     */
    public String getScriptPath() throws IOException
    {
        scriptPath = parseAndCleanup("{{SCRIPTPATH}}");
        base = "http://" + domain + scriptPath + "/index.php?title=";
        query = "http://" + domain + scriptPath +  "/api.php?format=xml&";
        return scriptPath;
    }
 
    /**
     *  Determines whether this wiki is equal to another object.
     *  @param obj the object to compare
     *  @return whether this wiki is equal to such object
     *  @since 0.10
     */
    public boolean equals(Object obj)
    {
        if (!(obj instanceof Wiki))
            return false;
        return domain.equals(((Wiki)obj).domain);
    }
 
    /**
     *  Returns a hash code of this object.
     *  @return a hash code
     *  @since 0.12
     */
    public int hashCode()
    {
        return domain.hashCode() * maxlag - throttle;
    }
 
    /**
     *   Returns a string representation of this Wiki.
     *   @return a string representation of this Wiki.
     *   @since 0.10
     */
    public String toString()
    {
        try
        {
            // domain
            StringBuilder buffer = new StringBuilder("Wiki[domain=");
            buffer.append(domain);
 
            // user
            buffer.append(",user=");
            if (user != null)
            {
                buffer.append(user.getUsername());
                buffer.append("[rights=");
                buffer.append(user.userRights());
                buffer.append("],");
            }
            else
                buffer.append("null,");
 
            // throttle mechanisms
            buffer.append("throttle=");
            buffer.append(throttle);
            buffer.append(",maxlag=");
            buffer.append(maxlag);
            buffer.append(",assertionMode=");
            buffer.append(assertion);
            buffer.append(",statusCheckInterval=");
            buffer.append(statusinterval);
            buffer.append(",cookies=");
            buffer.append(cookies);
            buffer.append(",cookies2=");
            buffer.append(cookies2);
            return buffer.toString();
        }
        catch (IOException ex)
        {
            // this shouldn't happen due to the user rights cache
            logger.logp(Level.SEVERE, "Wiki", "toString()", "Cannot retrieve user rights!", ex);
            return "";
        }
    }
 
    /**
     *  Gets the maxlag parameter. See [[mw:Manual:Maxlag parameter]].
     *  @return the current maxlag, in seconds
     *  @see #setMaxLag
     *  @see #getCurrentDatabaseLag
     *  @since 0.11
     */
    public int getMaxLag()
    {
        return maxlag;
    }
 
    /**
     *  Sets the maxlag parameter. A value of less than 1s disables this
     *  mechanism. Default is 5s.
     *  @param lag the desired maxlag in seconds
     *  @see #getMaxLag
     *  @see #getCurrentDatabaseLag
     *  @since 0.11
     */
    public void setMaxLag(int lag)
    {
        maxlag = lag;
        log(Level.CONFIG, "Setting maximum allowable database lag to " + lag, "setMaxLag");
    }
 
    /**
     *  Gets the assertion mode. See [[mw:Extension:Assert Edit]] for what
     *  functionality this mimics. Assertion modes are bitmasks.
     *  @return the current assertion mode
     *  @see #setAssertionMode
     *  @since 0.11
     */
    public int getAssertionMode()
    {
        return assertion;
    }
 
    /**
     *  Sets the assertion mode. See [[mw:Extension:Assert Edit]] for what this
     *  functionality this mimics. Assertion modes are bitmasks. Default is
     *  <tt>ASSERT_NONE</tt>.
     *  @param an assertion mode
     *  @see #getAssertionMode
     *  @since 0.11
     */
    public void setAssertionMode(int mode)
    {
        assertion = mode;
        log(Level.CONFIG, "Set assertion mode to " + mode, "setAssertionMode");
    }
 
    /**
     *  Gets the number of actions (edit, move, block, delete, etc) between
     *  status checks. A status check is where we update user rights, block
     *  status and check for new messages (if the appropriate assertion mode
     *  is set).
     *
     *  @return the number of edits between status checks
     *  @see #setStatusCheckInterval
     *  @since 0.18
     */
    public int getStatusCheckInterval()
    {
        return statusinterval;
    }
 
    /**
     *  Sets the number of actions (edit, move, block, delete, etc) between
     *  status checks. A status check is where we update user rights, block
     *  status and check for new messages (if the appropriate assertion mode
     *  is set). Default is 100.
     *
     *  @param interval the number of edits between status checks
     *  @see #getStatusCheckInterval
     *  @since 0.18
     */
    public void setStatusCheckInterval(int interval)
    {
        statusinterval = interval;
        log(Level.CONFIG, "Status check interval set to " + interval, "setStatusCheckInterval");
    }
 
    // META STUFF
 
    /**
     *  Logs in to the wiki. This method is thread-safe. If the specified
     *  username or password is incorrect, the thread blocks for 20 seconds
     *  then throws an exception.
     *
     *  @param username a username
     *  @param password a password (as a char[] due to JPasswordField)
     *  @throws FailedLoginException if the login failed due to incorrect
     *  username and/or password
     *  @throws IOException if a network error occurs
     *  @see #logout
     */
    public synchronized void login(String username, char[] password) throws IOException, FailedLoginException
    {
        // @revised 0.11 to remove screen scraping
 
        // sanitize
        String ps = URLEncoder.encode(new String(password), "UTF-8");
        username = URLEncoder.encode(username, "UTF-8");
 
        // start
        String url = query + "action=login";
        URLConnection connection = new URL(url).openConnection();
        logurl(url, "login");
        setCookies(connection, cookies);
        connection.setDoOutput(true);
        connection.connect();
 
        // send
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        out.print("lgname=");
        out.print(username);
        out.print("&lgpassword=");
        out.print(password);
        out.close();
 
        // get the cookies
        grabCookies(connection, cookies);
 
        // determine success
        BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
        String line = in.readLine();
        boolean success = line.contains("result=\"Success\"");
        in.close();
        if (success)
        {
            user = new User(username);
            boolean apihighlimit = (user.userRights() & BOT) == BOT || (user.userRights() & ADMIN) == ADMIN;
            max = apihighlimit ? 5000 : 500;
            log(Level.INFO, "Successfully logged in as " + username + ", highLimit = " + apihighlimit, "login");
        }
        else
        {
            log(Level.WARNING, "Failed to log in as " + username, "login");
            try
            {
                Thread.sleep(20000); // to prevent brute force
            }
            catch (InterruptedException e)
            {
                // nobody cares
            }
            // test for some common failure reasons
            if (line.contains("WrongPass") || line.contains("WrongPluginPass"))
                throw new FailedLoginException("Login failed: incorrect password.");
            else if (line.contains("NotExists"))
                throw new FailedLoginException("Login failed: user does not exist.");
            throw new FailedLoginException("Login failed: unknown reason.");
        }
    }
 
    /**
     *  Logs out of the wiki. This method is thread safe (so that we don't log
     *  out during an edit). All operations are conducted offline, so you can
     *  serialize this Wiki first.
     *  @see #login
     *  @see #logoutServerSide
     */
    public synchronized void logout()
    {
        cookies.clear();
        cookies2.clear();
        user = null;
        max = 500;
        log(Level.INFO, "Logged out", "logout");
    }
 
    /**
     *  Logs out of the wiki and destroys the session on the server. You will
     *  need to log in again instead of just reading in a serialized wiki.
     *  Equivalent to [[Special:Userlogout]]. This method is thread safe
     *  (so that we don't log out during an edit). WARNING: kills all
     *  concurrent sessions as well - if you are logged in with a browser this
     *  will log you out there as well.
     *
     *  @throws IOException if a network error occurs
     *  @since 0.14
     *  @see #login
     *  @see #logout
     */
    public synchronized void logoutServerSide() throws IOException
    {
        fetch(query + "action=logout", "logoutServerSide", false);
        logout(); // destroy local cookies
    }
 
    /**
     *  Determines whether the current user has new messages. (A human would
     *  notice a yellow bar at the top of the page).
     *  @return whether the user has new messages
     *  @throws IOException if a network error occurs
     *  @since 0.11
     */
    public boolean hasNewMessages() throws IOException
    {
        String url = query + "action=query&meta=userinfo&uiprop=hasmsg";
        return fetch(url, "hasNewMessages", false).contains("messages=\"\"");
    }
 
    /**
     *  Determines the current database replication lag.
     *  @return the current database replication lag
     *  @throws IOException if a network error occurs
     *  @see #setMaxLag
     *  @see #getMaxLag
     *  @since 0.10
     */
    public int getCurrentDatabaseLag() throws IOException
    {
        String line = fetch(query + "action=query&meta=siteinfo&siprop=dbrepllag", "getCurrentDatabaseLag", false);
        int z = line.indexOf("lag=\"") + 5;
        String lag = line.substring(z, line.indexOf("\" />", z));
        log(Level.INFO, "Current database replication lag is " + lag + " seconds", "getCurrentDatabaseLag");
        return Integer.parseInt(lag);
    }
 
    /**
     *  Fetches some site statistics, namely the number of articles, pages,
     *  files, edits, users and admins. Equivalent to [[Special:Statistics]].
     *
     *  @return a map containing the stats. Use "articles", "pages", "files"
     *  "edits", "users" or "admins" to retrieve the respective value
     *  @throws IOException if a network error occurs
     *  @since 0.14
     */
    public HashMap<String, Integer> getSiteStatistics() throws IOException
    {
        // ZOMG hack to avoid excessive substring code
        String text = parseAndCleanup("{{NUMBEROFARTICLES:R}} {{NUMBEROFPAGES:R}} {{NUMBEROFFILES:R}} {{NUMBEROFEDITS:R}} " +
                "{{NUMBEROFUSERS:R}} {{NUMBEROFADMINS:R}}");
        String[] values = text.split("\\s");
        HashMap<String, Integer> ret = new HashMap<String, Integer>();
        String[] keys =
        {
           "articles", "pages", "files", "edits", "users", "admins"
        };
        for (int i = 0; i < values.length; i++)
        {
            Integer value = new Integer(values[i]);
            ret.put(keys[i], value);
        }
        return ret;
    }
 
    /**
     *  Gets the version of MediaWiki this wiki runs e.g. 1.13 alpha (r31567).
     *  The r number corresponds to a revision in MediaWiki subversion
     *  (http://svn.wikimedia.org/viewvc/mediawiki/).
     *  @return the version of MediaWiki used
     *  @throws IOException if a network error occurs
     *  @since 0.14
     */
    public String version() throws IOException
    {
        return parseAndCleanup("{{CURRENTVERSION}}"); // ahh, the magicness of magic words
    }
 
    /**
     *  Renders the specified wiki markup by passing it to the MediaWiki
     *  parser through the API. (Note: this isn't implemented locally because
     *  I can't be stuffed porting Parser.php). One use of this method is to
     *  emulate the previewing functionality of the MediaWiki software.
     *
     *  @param markup the markup to parse
     *  @return the parsed markup as HTML
     *  @throws IOException if a network error occurs
     *  @since 0.13
     */
    public String parse(String markup) throws IOException
    {
        // This is POST because markup can be arbitrarily large, as in the size
        // of an article (over 10kb).
        String url = query + "action=parse";
        URLConnection connection = new URL(url).openConnection();
        logurl(url, "parse");
        setCookies(connection, cookies);
        connection.setDoOutput(true);
        connection.connect();
 
        // send
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        out.print("prop=text&text=");
        out.print(URLEncoder.encode(markup, "UTF-8"));
        out.close();
 
        // parse
        BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
        String line;
        StringBuilder text = new StringBuilder(100000);
        while ((line = in.readLine()) != null)
        {
            int y = line.indexOf("<text>");
            int z = line.indexOf("</text>");
            if (y != -1)
            {
                text.append(line.substring(y + 6));
                text.append("\n");
            }
            else if (z != -1)
            {
                text.append(line.substring(0, z));
                text.append("\n");
                break; // done
            }
            else
            {
                text.append(line);
                text.append("\n");
            }
        }
        return decode(text.toString());
    }
 
    /**
     *  Same as <tt>parse()</tt>, but also strips out unwanted crap. This might
     *  be useful to subclasses.
     *
     *  @param in the string to parse
     *  @return that string without the crap
     *  @throws IOException if a network error occurs
     *  @since 0.14
     */
    protected String parseAndCleanup(String in) throws IOException
    {
        String output = parse(in);
        output = output.replace("<p>", "").replace("</p>", ""); // remove paragraph tags
        output = output.replace("\n", ""); // remove new lines
 
        // strip out the parser report, which comes at the end
        int a = output.indexOf("<!--");
        return output.substring(0, a);
    }
 
    /**
     *  Fetches a random page in the main namespace. Equivalent to
     *  [[Special:Random]].
     *  @return the title of the page
     *  @throws IOException if a network error occurs
     *  @since 0.13
     */
    public String random() throws IOException
    {
        return random(MAIN_NAMESPACE);
    }
 
    /**
     *  Fetches a random page in the specified namespace. Equivalent to
     *  [[Special:Random]].
     *
     *  @param namespace a namespace
     *  @return the title of the page
     *  @throws IOException if a network error occurs
     *  @since 0.13
     */
    public String random(int namespace) throws IOException
    {
        // fetch
        String url = query + "action=query&list=random";
		url += (namespace == ALL_NAMESPACES ? "" : "&rnnamespace=" + namespace);
		String line = fetch(url, "random", false);
 
        // parse
        int a = line.indexOf("title=\"") + 7;
        int b = line.indexOf("\"", a);
        return line.substring(a, b);
    }
 
    // STATIC MEMBERS
 
    /**
     *   Parses a list of links into its individual elements. Such a list
     *   should be in the form:
     *
     *  <pre>
     *  * [[Main Page]]
     *  * [[Wikipedia:Featured picture candidates]]
     *  * [[:File:Example.png]]
     *  </pre>
     *
     *  in which case <tt>{ "Main Page", "Wikipedia:Featured picture
     *  candidates", "File:Example.png" }</tt> is the return value.
     *
     *  @param list a list of pages
     *  @see #formatList
     *  @return an array of the page titles
     *  @since 0.11
     */
    public static String[] parseList(String list)
    {
        StringTokenizer tokenizer = new StringTokenizer(list, "[]");
        ArrayList<String> titles = new ArrayList<String>(667);
        tokenizer.nextToken(); // skip the first token
        while (tokenizer.hasMoreTokens())
        {
            String token = tokenizer.nextToken();
 
            // skip any containing new lines or double letters
            if (token.contains("\n"))
                continue;
            if (token.equals(""))
                continue;
 
            // trim the starting colon, if present
            if (token.startsWith(":"))
                token = token.substring(1);
 
            titles.add(token);
        }
        return titles.toArray(new String[0]);
    }
 
    /**
     *  Formats a list of pages, say, generated from one of the query methods
     *  into something that would be editor-friendly. Does the exact opposite
     *  of <tt>parseList()</tt>, i.e. { "Main Page", "Wikipedia:Featured
     *  picture candidates", "File:Example.png" } becomes the string:
     *
     *  <pre>
     *  *[[:Main Page]]
     *  *[[:Wikipedia:Featured picture candidates]]
     *  *[[:File:Example.png]]
     *  </pre>
     *
     *  @param pages an array of page titles
     *  @return see above
     *  @see #parseList
     *  @since 0.14
     */
    public static String formatList(String[] pages)
    {
        StringBuilder buffer = new StringBuilder(10000);
        for (int i = 0; i < pages.length; i++)
        {
            buffer.append("*[[:");
            buffer.append(pages[i]);
            buffer.append("]]\n");
        }
        return buffer.toString();
    }
 
    /**
     *  Determines the intersection of two lists of pages a and b, i.e. a ∩ b.
     *  Such lists might be generated from the various list methods below.
     *  Examples from the English Wikipedia:
     *
     *  <pre>
     *  // find all orphaned and unwikified articles
     *  String[] articles = Wiki.intersection(wikipedia.getCategoryMembers("All orphaned articles", Wiki.MAIN_NAMESPACE),
     *      wikipedia.getCategoryMembers("All pages needing to be wikified", Wiki.MAIN_NAMESPACE));
     *
     *  // find all (notable) living people who are related to Barack Obama
     *  String[] people = Wiki.intersection(wikipedia.getCategoryMembers("Living people", Wiki.MAIN_NAMESPACE),
     *      wikipedia.whatLinksHere("Barack Obama", Wiki.MAIN_NAMESPACE));
     *  </pre>
     *
     *  @param a a list of pages
     *  @param b another list of pages
     *  @return a ∩ b (as String[])
     *  @since 0.04
     */
    public static String[] intersection(String[] a, String[] b)
    {
        // @revised 0.11 to take advantage of Collection.retainAll()
        // @revised 0.14 genericised to all page titles, not just category members
 
        ArrayList<String> aa = new ArrayList<String>(5000); // silly workaroiund
        aa.addAll(Arrays.asList(a));
        aa.retainAll(Arrays.asList(b));
        return aa.toArray(new String[0]);
    }
 
    /**
     *  Determines the list of articles that are in a but not b, i.e. a \ b.
     *  This is not the same as b \ a. Such lists might be generated from the
     *  various lists below. Some examples from the English Wikipedia:
     *
     *  <pre>
     *  // find all Martian crater articles that do not have an infobox
     *  String[] articles = Wiki.relativeComplement(wikipedia.getCategoryMembers("Craters on Mars"),
     *      wikipedia.whatTranscludesHere("Template:MarsGeo-Crater", Wiki.MAIN_NAMESPACE));
     *
     *  // find all images without a description that haven't been tagged "no license"
     *  String[] images = Wiki.relativeComplement(wikipedia.getCategoryMembers("Images lacking a description"),
     *      wikipedia.getCategoryMembers("All images with unknown copyright status"));
     *  </pre>
     *
     *  @param a a list of pages
     *  @param b another list of pages
     *  @return a \ b
     *  @since 0.14
     */
    public static String[] relativeComplement(String[] a, String[] b)
    {
        ArrayList<String> aa = new ArrayList<String>(5000); // silly workaroiund
        aa.addAll(Arrays.asList(a));
        aa.removeAll(Arrays.asList(b));
        return aa.toArray(new String[0]);
    }
 
    // PAGE METHODS
 
    /**
     *  Returns the corresponding talk page to this page. Override to add
     *  custom namespaces.
     *
     *  @param title the page title
     *  @return the name of the talk page corresponding to <tt>title</tt>
     *  or "" if we cannot recognise it
     *  @throws IllegalArgumentException if given title is in a talk namespace
     *  or we try to retrieve the talk page of a Special: or Media: page.
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String getTalkPage(String title) throws IOException
    {
        int namespace = namespace(title);
        if (namespace % 2 == 1)
            throw new IllegalArgumentException("Cannot fetch talk page of a talk page!");
        if (namespace < 0)
            throw new IllegalArgumentException("Special: and Media: pages do not have talk pages!");
        if (namespace != MAIN_NAMESPACE) // remove the namespace
            title = title.substring(title.indexOf(':') + 1, title.length());
 
        switch(namespace)
        {
            case MAIN_NAMESPACE:
                return "Talk:" + title;
            case USER_NAMESPACE:
                return "User talk:" + title;
            case PROJECT_NAMESPACE:
                return "Project talk:" + title;
            case TEMPLATE_NAMESPACE:
                return "Template talk:" + title;
            case CATEGORY_NAMESPACE:
                return "Category talk:" + title;
            case MEDIAWIKI_NAMESPACE:
                return "MediaWiki talk:" + title;
            case HELP_NAMESPACE:
                return "Help talk:" + title;
            case IMAGE_NAMESPACE:
                return "File talk:" + title;
        }
        return "";
    }
 
    /**
     *  Gets the protection status of a page. WARNING: returns NO_PROTECTION
     *  for pages that are protected through the cascading mechanism, e.g.
     *  [[Talk:W/w/index.php]].
     *
     *  @param title the title of the page
     *  @return one of the various protection levels (i.e,. NO_PROTECTION,
     *  SEMI_PROTECTION, MOVE_PROTECTION, FULL_PROTECTION,
     *  SEMI_AND_MOVE_PROTECTION, PROTECTED_DELETED_PAGE)
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public int getProtectionLevel(String title) throws IOException
    {
        // fetch
        String url = query + "action=query&prop=info&inprop=protection&titles=" + URLEncoder.encode(title, "UTF-8");
        String line = fetch(url, "getProtectionLevel", false);
 
        // parse
        int z = line.indexOf("type=\"edit\"");
        if (z != -1)
        {
            String s = line.substring(z, z + 30);
            if (s.contains("sysop"))
                return FULL_PROTECTION;
            s = line.substring(z + 30, line.length()); // cut out edit tag
            if (line.contains("level=\"sysop\""))
                return SEMI_AND_MOVE_PROTECTION;
            return SEMI_PROTECTION;
        }
        if (line.contains("type=\"move\""))
            return MOVE_PROTECTION;
        if (line.contains("type=\"create\""))
            return PROTECTED_DELETED_PAGE;
        return NO_PROTECTION;
    }
 
    /**
     *  Returns the namespace a page is in. No need to override this to add
     *  custom namespaces, though you may want to define static fields e.g.
     *  <tt>public static final int PORTAL_NAMESPACE = 100;</tt> for the Portal
     *  namespace on the English Wikipedia.
     *
     *  @param title the title of the page
     *  @return one of namespace types above, or a number for custom
     *  namespaces or ALL_NAMESPACES if we can't make sense of it
     *  @throws IOException if a network error occurs
     *  @since 0.03
     */
    public int namespace(String title) throws IOException
    {
        // sanitise
        title = title.replace('_', ' ');
        if (!title.contains(":"))
            return MAIN_NAMESPACE;
        String namespace = title.substring(0, title.indexOf(':'));
 
        // all wiki namespace test
        if (namespace.equals("Project talk"))
            return PROJECT_TALK_NAMESPACE;
        if (namespace.equals("Project"))
            return PROJECT_NAMESPACE;
 
        // cache this, as it will be called often
        if (namespaces == null)
        {
            String line = fetch(query + "action=query&meta=siteinfo&siprop=namespaces", "namespace", false);
            namespaces = new HashMap(30);
            while (line.contains("<ns"))
            {
                int x = line.indexOf("<ns id=");
                if (line.charAt(x + 8) == '0') // skip main, it's a little different
                {
                    line = line.substring(13, line.length());
                    continue;
                }
                int y = line.indexOf("</ns>");
                String working = line.substring(x + 8, y);
                int ns = Integer.parseInt(working.substring(0, working.indexOf('"')));
                String name = working.substring(working.indexOf(">") + 1, working.length());
                namespaces.put(name, new Integer(ns));
                line = line.substring(y + 5, line.length());
            }
            log(Level.INFO, "Successfully retrieved namespace list (" + (namespaces.size() + 1) + " namespaces)", "namespace");
        }
 
        // look up the namespace of the page in the namespace cache
        if (!namespaces.containsKey(namespace))
            return MAIN_NAMESPACE; // For titles like UN:NRV
        Iterator i = namespaces.entrySet().iterator();
        while (i.hasNext())
        {
            Map.Entry entry = (Map.Entry)i.next();
            if (entry.getKey().equals(namespace))
                return ((Integer)entry.getValue()).intValue();
        }
        return ALL_NAMESPACES; // unintelligble title
    }
 
    /**
     *  Determines whether a series of pages exist. Requires the
     *  [[mw:Extension:ParserFunctions|ParserFunctions extension]].
     *
     *  @param titles the titles to check
     *  @return whether the pages exist
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public boolean[] exists(String... titles) throws IOException
    {
        // @revised 0.15 optimized for multiple queries, now up to 500x faster!
 
        StringBuilder wikitext = new StringBuilder(15000);
        StringBuilder parsed = new StringBuilder(1000);
        for (int i = 0; i < titles.length; i++)
        {
            // build up the parser string
            wikitext.append("{{#ifexist:");
            wikitext.append(titles[i]);
            wikitext.append("|1|0}}"); // yay! binary! (well, almost)
 
            // Send them off in batches of 500. Change this if your expensive
            // parser function limit is different.
            if (i % 500 == 499 || i == titles.length - 1)
            {
                parsed.append(parseAndCleanup(wikitext.toString()));
                wikitext = new StringBuilder(15000);
            }
        }
 
        // now parse the resulting "binary"
        char[] characters = parsed.toString().toCharArray();
        boolean[] ret = new boolean[characters.length];
        for (int i = 0; i < characters.length; i++)
        {
            // we would want to use the ternary operator here but other things can go wrong
            if (characters[i] != '1' && characters[i] != '0')
                throw new UnknownError("Unable to parse output. Perhaps the ParserFunctions extension is not installed, or this is a bug.");
            ret[i] = (characters[i] == '1') ? true : false;
        }
        return ret;
    }
 
    /**
     *  Gets the raw wikicode for a page. WARNING: does not support special
     *  pages. Check [[User talk:MER-C/Wiki.java#Special page equivalents]]
     *  for fetching the contents of special pages. Use <tt>getImage()</tt> to
     *  fetch an image.
     *
     *  @param title the title of the page.
     *  @return the raw wikicode of a page.
     *  @throws UnsupportedOperationException if you try to retrieve the text of a
     *  Special: or Media: page
     *  @throws FileNotFoundException if the page does not exist
     *  @throws IOException if a network error occurs
     *  @see #edit
     */
    public String getPageText(String title) throws IOException
    {
        // pitfall check
        if (namespace(title) < 0)
            throw new UnsupportedOperationException("Cannot retrieve Special: or Media: pages!");
 
        // go for it
        String url = base + URLEncoder.encode(title, "UTF-8") + "&action=raw";
        String temp = fetch(url, "getPageText", false);
        log(Level.INFO, "Successfully retrieved text of " + title, "getPageText");
        return decode(temp);
    }
 
    /**
     *  Gets the contents of a page, rendered in HTML (as opposed to
     *  wikitext). WARNING: only supports special pages in certain
     *  circumstances, for example <tt>getRenderedText("Special:Recentchanges")
     *  </tt> returns the 50 most recent change to the wiki in pretty-print
     *  HTML. You should test any use of this method on-wiki through the text
     *  <tt>{{Special:Specialpage}}</tt>. Use <tt>getImage()</tt> to fetch an
     *  image. Be aware of any transclusion limits, as outlined at
     *  [[Wikipedia:Template limits]].
     *
     *  @param title the title of the page
     *  @return the rendered contents of that page
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String getRenderedText(String title) throws IOException
    {
        // @revised 0.13 genericised to parse any wikitext
        return parse("{{:" + title + "}}");
    }
 
    /**
     *  Edits a page by setting its text to the supplied value. This method is
     *  thread safe and blocks for a minimum time as specified by the
     *  throttle.
     *
     *  @param text the text of the page
     *  @param title the title of the page
     *  @param summary the edit summary. See [[Help:Edit summary]]. Summaries
     *  longer than 200 characters are truncated server-side.
     *  @param minor whether the edit should be marked as minor. See
     *  [[Help:Minor edit]].
     *  @throws IOException if a network error occurs
     *  @throws AccountLockedException if user is blocked
     *  @throws CredentialException if page is protected and we can't edit it
     *  @throws UnsupportedOperationException if you try to edit a Special: or a
     *  Media: page
     *  @see #getPageText
     */
    public void edit(String title, String text, String summary, boolean minor) throws IOException, LoginException
    {
        edit(title, text, summary, minor, -2);
    }
 
    /**
     *  Edits a page by setting its text to the supplied value. This method is
     *  thread safe and blocks for a minimum time as specified by the
     *  throttle.
     *
     *  @param text the text of the page
     *  @param title the title of the page
     *  @param summary the edit summary. See [[Help:Edit summary]]. Summaries
     *  longer than 200 characters are truncated server-side.
     *  @param minor whether the edit should be marked as minor. See
     *  [[Help:Minor edit]].
     *  @param section the section to edit. Use -1 to specify a new section and
     *  -2 to disable section editing.
     *  @throws IOException if a network error occurs
     *  @throws AccountLockedException if user is blocked
     *  @throws CredentialException if page is protected and we can't edit it
     *  @throws UnsupportedOperationException if you try to edit a Special: or
     *  Media: page
     *  @see #getPageText
     *  @since 0.17
     */
    public synchronized void edit(String title, String text, String summary, boolean minor, int section) throws IOException, LoginException
    {
        // @revised 0.16 to use API edit. No more screenscraping - yay!
        // @revised 0.17 section editing
        long start = System.currentTimeMillis();
        statusCheck();
 
        // sanitize some params
        String title2 = URLEncoder.encode(title, "UTF-8");
 
        // Check the protection level. We don't use getProtectionLevel(title), as we
        // can fetch a move token at the same time!
        String url = query + "action=query&prop=info&inprop=protection&intoken=edit&titles=" + title2;
        String line = fetch(url, "edit", true);
 
        // parse the page
        int level = NO_PROTECTION;
        int z = line.indexOf("type=\"edit\"");
        if (z != -1)
        {
            String s = line.substring(z, z + 30);
            if (s.contains("sysop"))
                level = FULL_PROTECTION;
            s = line.substring(z + 30, line.length()); // cut out edit tag
            if (line.contains("level=\"sysop\""))
                level = SEMI_AND_MOVE_PROTECTION;
            level = SEMI_PROTECTION;
        }
        else if (line.contains("type=\"create\""))
            level = PROTECTED_DELETED_PAGE;
 
        // do the check
        if (!checkRights(level, false))
        {
            CredentialException ex = new CredentialException("Permission denied: page is protected.");
            logger.logp(Level.WARNING, "Wiki", "edit()", "[" + getDomain() + "] Cannot edit - permission denied.", ex);
            throw ex;
        }
 
        // find the edit token
        int a = line.indexOf("token=\"") + 7;
        int b = line.indexOf("\"", a);
        String wpEditToken = line.substring(a, b);
 
        // fetch the appropriate URL
        url = query + "action=edit";
        logurl(url, "edit");
        URLConnection connection = new URL(url).openConnection();
        setCookies(connection, cookies2);
        connection.setDoOutput(true);
        connection.connect();
 
        // send the data
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        // PrintWriter out = new PrintWriter(System.out); // debug version

        out.write("title=");
        out.write(title2);
        out.write("&bot=true");
        out.write("&text=");
        out.write(URLEncoder.encode(text, "UTF-8"));
        out.write("&summary=");
        out.write(URLEncoder.encode(summary, "UTF-8"));
        out.write("&token=");
        out.write(URLEncoder.encode(wpEditToken, "UTF-8"));
        if (minor)
            out.write("&minor=1");
        if (section == -1)
            out.write("&section=new");
        else if (section != -2)
        {
            out.write("&section=");
            out.write("" + section);
        }
        out.close();
 
        // done
        try
        {
            // it's somewhat strange that the edit only sticks when you start reading the response...
            BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
            checkErrors(in.readLine(), "edit");
            in.close();
        }
        catch (IOException e)
        {
            // retry once
            if (retry)
            {
                retry = false;
                log(Level.WARNING, "Exception: " + e.getMessage() + " Retrying...", "edit");
                edit(title, text, summary, minor, section);
            }
            else
            {
                logger.logp(Level.SEVERE, "Wiki", "edit()", "[" + domain + "] EXCEPTION:  ", e);
                throw e;
            }
        }
        if (retry)
            log(Level.INFO, "Successfully edited " + title, "edit");
        retry = true;
 
        // throttle
        try
        {
            long time = throttle - System.currentTimeMillis() + start;
            if (time > 0)
                Thread.sleep(time);
        }
        catch (InterruptedException e)
        {
            // nobody cares
        }
    }
 
    /**
     *  Creates a new section on the specified page.
     *
     *  @param title the title of the page to edit
     *  @param subject the subject of the new section
     *  @param text the text of the new section
     *  @param minor whether the edit should be marked as minor (see
     *  [[Help:Minor edit]])
     *  @throws IOException if a network error occurs
     *  @throws AccountLockedException if user is blocked
     *  @throws CredentialException if page is protected and we can't edit it
     *  @throws UnsupportedOperationException if you try to edit a Special: or
     *  Media: page
     *  @since 0.17
     */
    public void newSection(String title, String subject, String text, boolean minor) throws IOException, LoginException
    {
        edit(title, text, subject, minor, -1);
    }
 
    /**
     *  Prepends something to the given page. A convenience method for
     *  adding maintainance templates, rather than getting and setting the
     *  page yourself. Edit summary is automatic, being "+whatever".
     *
     *  @param title the title of the page
     *  @param stuff what to prepend to the page
     *  @param minor whether the edit is minor
     *  @throws AccountLockedException if user is blocked
     *  @throws CredentialException if page is protected and we can't edit it
     *  @throws UnsupportedOperationException if you try to retrieve the text
     *  of a Special: page or a Media: page
     *  @throws IOException if a network error occurs
     */
    public void prepend(String title, String stuff, boolean minor) throws IOException, LoginException
    {
        StringBuilder text = new StringBuilder(100000);
        text.append(stuff);
        text.append(getPageText(title));
        edit(title, text.toString(), "+" + stuff, minor);
    }
 
    /**
     *  Purges the server-side cache for various pages.
     *  @param titles the titles of the page to purge
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @since 0.17
     */
    public void purge(String... titles) throws IOException, CredentialNotFoundException
    {
        if (user == null)
            throw new CredentialNotFoundException("You need to be logged in to purge pages via the API.");
        StringBuilder url = new StringBuilder(query);
        StringBuilder log = new StringBuilder("Successfully purged { \""); // log statement
        url.append("action=purge&titles=");
        for (int i = 0; i < titles.length; i++)
        {
            url.append(URLEncoder.encode(titles[i], "UTF-8"));
            log.append(titles[i]);
            if (i != titles.length - 1)
            {
                url.append("|");
                log.append("\", ");
            }
            else
                log.append("\" }");
        }
        fetch(url.toString(), "purge", false);
        // System.out.println(fetch(url.toString(), "purge", false)); // for debugging purposes
        log(Level.INFO, log.toString(), "purge"); // done, log
    }
 
    /**
     *  Gets the list of images used on a particular page. Capped at
     *  <tt>max</tt> number of images, there's no reason why there should be
     *  more than that.
     *
     *  @param title a page
     *  @return the list of images used in the page
     *  @throws IOException if a network error occurs
     *  @since 0.16
     */
    public String[] getImagesOnPage(String title) throws IOException
    {
        String url = query + "action=query&prop=images&imlimit=max&titles=" + URLEncoder.encode(title, "UTF-8");
        String line = fetch(url, "getImagesOnPage", false);
 
        // parse the list
        // typical form: <im ns="6" title="File:Example.jpg" />
        ArrayList<String> images = new ArrayList<String>(750);
        while (line.contains("title=\""))
        {
            int a = line.indexOf("title=\"File:") + 7;
            int b = line.indexOf("\"", a);
            images.add(decode(line.substring(a, b)));
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved images used on " + title + " (" + images.size() + " images)", "getImagesOnPage");
        return images.toArray(new String[0]);
    }
 
    /**
     *  Gets the list of categories a particular page is in. Includes hidden
     *  categories. Capped at <tt>max</tt> number of categories, there's no
     *  reason why there should be more than that.
     *
     *  @param title a page
     *  @return the list of categories that page is in
     *  @throws IOException if a network error occurs
     *  @since 0.16
     */
    public String[] getCategories(String title) throws IOException
    {
        String url = query + "action=query&prop=categories&cllimit=max&titles=" + URLEncoder.encode(title, "UTF-8");
        String line = fetch(url, "getCategories", false);
 
        // parse the list
        // typical form: <cl ns="14" title="Category:1879 births" />
        ArrayList<String> categories = new ArrayList<String>(750);
        while (line.contains("title=\""))
        {
            int a = line.indexOf("title=\"Category:") + 7;
            int b = line.indexOf("\"", a);
            categories.add(line.substring(a, b));
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved categories of " + title + " (" + categories.size() + " categories)", "getCategories");
        return categories.toArray(new String[0]);
    }
 
    /**
     *  Gets the list of templates used on a particular page. Capped at
     *  <tt>max</tt> number of templates, there's no reason why there should
     *  be more than that.
     *
     *  @param title a page
     *  @return the list of templates used on that page
     *  @throws IOException if a network error occurs
     *  @since 0.16
     */
    public String[] getTemplates(String title) throws IOException
    {
        return getTemplates(title, ALL_NAMESPACES);
    }
 
    /**
     *  Gets the list of templates used on a particular page that are in a
     *  particular namespace. Capped at <tt>max</tt> number of templates,
     *  there's no reason why there should be more than that.
     *
     *  @param title a page
     *  @param namespace a namespace
     *  @return the list of templates used on that page in that namespace
     *  @throws IOException if a network error occurs
     *  @since 0.16
     */
    public String[] getTemplates(String title, int namespace) throws IOException
    {
        String url = query + "action=query&prop=templates&tllimit=max&titles=" + URLEncoder.encode(title, "UTF-8");
        if (namespace != ALL_NAMESPACES)
            url += ("&tlnamespace=" + namespace);
        String line = fetch(url, "getTemplates", false);
 
        // parse the list
        // typical form: <tl ns="10" title="Template:POTD" />
        ArrayList<String> templates = new ArrayList<String>(750);
        line = line.substring(line.indexOf("<templates>")); // drop off the first title, which is <tt>title</tt>
        while (line.contains("title=\""))
        {
            int a = line.indexOf("title=\"") + 7;
            int b = line.indexOf("\"", a);
            templates.add(line.substring(a, b));
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved templates used on " + title + " (" + templates.size() + " templates)", "getTemplates");
        return templates.toArray(new String[0]);
    }
 
    /**
     *  Gets the list of interwiki links a particular page has. The returned
     *  map has the format language code => the page on the external wiki
     *  linked to.
     *
     *  @param title a page
     *  @return a map of interwiki links that page has
     *  @throws IOException if a network error occurs
     *  @since 0.18
     */
    public HashMap<String, String> getInterwikiLinks(String title) throws IOException
    {
        String url = query + "action=parse&prop=langlinks&page=" + URLEncoder.encode(title, "UTF-8");
        String line = fetch(url, "getInterwikiLinks", false);
 
        // parse the list
        // typical form: <ll lang="en" />Main Page</ll>
        HashMap<String, String> interwikis = new HashMap<String, String>(750);
        while (line.contains("lang=\""))
        {
            int a = line.indexOf("lang=\"") + 6;
            int b = line.indexOf("\"", a);
            String language = line.substring(a, b);
            a = line.indexOf(">", a) + 1;
            b = line.indexOf("<", a);
            String page = decode(line.substring(a, b));
            interwikis.put(language, page);
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved categories used on " + title, "getCategories");
        return interwikis;
    }
 
    /**
     *  Gets the list of sections on a particular page. The returned map pairs
     *  the section numbering as in the table of contents with the section
     *  title, as in the following example:
     *
     *  1 => How to nominate
     *  1.1 => Step 1 - Evaluate
     *  1.2 => Step 2 - Create subpage
     *  1.2.1 => Step 2.5 - Transclude and link
     *  1.3 => Step 3 - Update image
     *  ...
     *
     *  @param page the page to get sections for
     *  @return the section map for that page
     *  @throws IOException if a network error occurs
     *  @since 0.18
     */
    public LinkedHashMap<String, String> getSectionMap(String page) throws IOException
    {
        String url = query + "action=parse&text={{:" + URLEncoder.encode(page, "UTF-8") + "}}__TOC__&prop=sections";
        String line = fetch(url, "getSectionMap", false);
 
        // expected format: <s toclevel="1" level="2" line="How to nominate" number="1" />
        LinkedHashMap<String, String> map = new LinkedHashMap<String, String>();
        while (line.contains("<s "))
        {
            // section title
            int a = line.indexOf("line=\"") + 6;
            int b = line.indexOf("\"", a);
            String title = decode(line.substring(a, b));
 
            // section number
            a = line.indexOf("number=") + 8;
            b = line.indexOf("\"", a);
            String number = line.substring(a, b);
 
            map.put(number, title);
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved section map for " + page, "getSectionMap");
        return map;
    }
 
    /**
     *  Gets the creator of a page. Note the return value here is <tt>String
     *  </tt>, as we cannot assume the creator has an account.
     *  @param title the title of the page to fetch the creator for
     *  @return the creator of that page
     *  @throws IOException if a network error occurs
     *  @since 0.18
     */
    public String getPageCreator(String title) throws IOException
    {
        String url = query + "action=query&prop=revisions&rvlimit=1&rvdir=newer&titles=" + URLEncoder.encode(title, "UTF-8");
        String line = fetch(url, "getPageCreator", false);
        int a = line.indexOf("user=\"") + 6;
        int b = line.indexOf("\"", a);
        return line.substring(a, b);
    }
 
    /**
     *  Gets the entire revision history of a page. Be careful when using
     *  this method as some pages (such as [[Wikipedia:Administrators'
     *  noticeboard/Incidents]] have ~10^6 revisions.
     *
     *  @param title a page
     *  @return the revisions of that page
     *  @throws IOException if a network error occurs
     *  @since 0.19
     */
    public Revision[] getPageHistory(String title) throws IOException
    {
        return getPageHistory(title, null, null);
    }
 
    /**
     *  Gets the revision history of a page between two dates.
     *  @param title a page
     *  @param start the date to start enumeration (the latest of the two
     *  dates)
     *  @param end the date to stop enumeration (the earliest of the two dates)
     *  @return the revisions of that page in that time span
     *  @throws IOException if a network error occurs
     *  @since 0.19
     */
    public Revision[] getPageHistory(String title, Calendar start, Calendar end) throws IOException
    {
        // set up the url
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&prop=revisions&rvlimit=max&titles=");
        url.append(URLEncoder.encode(title, "UTF-8"));
        if (end != null)
        {
            url.append("&rvend=");
            url.append(calendarToTimestamp(end));
        }
        url.append("&rvstart");
 
        // Hack time: since we cannot use rvstart (a timestamp) and rvstartid
        // (an oldid) together, let's make them the same thing.
        String rvstart = "=" + calendarToTimestamp(start == null ? new GregorianCalendar() : start);
        ArrayList<Revision> revisions = new ArrayList<Revision>(1500);
 
        // main loop
        do
        {
            String temp = url.toString();
            if (rvstart.charAt(0) != '=')
                temp += ("id=" + rvstart);
            else
                temp += rvstart;
            String line = fetch(temp, "getPageHistory", false);
 
            // set continuation parameter
            if (line.contains("rvstartid=\""))
            {
                int a = line.indexOf("rvstartid") + 11;
                int b = line.indexOf("\"", a);
                rvstart = line.substring(a, b);
            }
            else
                rvstart = "done";
 
            // parse stuff
            while (line.contains("<rev "))
            {
                int a = line.indexOf("<rev");
                int b = line.indexOf("/>", a);
                revisions.add(parseRevision(line.substring(a, b), title));
                line = line.substring(b);
            }
        }
        while (!rvstart.equals("done"));
        log(Level.INFO, "Successfully retrieved page history of " + title + " (" + revisions.size() + " revisions)", "getPageHistory");
        return revisions.toArray(new Revision[0]);
    }
 
    /**
     *  Moves a page. Moves the associated talk page and leaves redirects, if
     *  applicable. Equivalent to [[Special:MovePage]]. This method is thread
     *  safe and is subject to the throttle.
     *
     *  @param title the title of the page to move
     *  @param newTitle the new title of the page
     *  @param reason a reason for the move
     *  @throws UnsupportedOperationException if the original page is in the
     *  Category or Image namespace. MediaWiki does not support moving of
     *  these pages.
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @throws CredentialException if page is protected and we can't move it
     *  @since 0.16
     */
    public void move(String title, String newTitle, String reason) throws IOException, LoginException
    {
        move(title, newTitle, reason, false, true);
    }
 
    /**
     *  Moves a page. Equivalent to [[Special:MovePage]]. This method is
     *  thread safe and is subject to the throttle.
     *
     *  @param title the title of the page to move
     *  @param newTitle the new title of the page
     *  @param reason a reason for the move
     *  @param noredirect don't leave a redirect behind. You need to be a
     *  admin to do this, otherwise this option is ignored.
     *  @param movetalk move the talk page as well (if applicable)
     *  @throws UnsupportedOperationException if the original page is in the
     *  Category or Image namespace. MediaWiki does not support moving of
     *  these pages.
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @throws CredentialException if page is protected and we can't move it
     *  @since 0.16
     */
    public synchronized void move(String title, String newTitle, String reason, boolean noredirect, boolean movetalk) throws IOException, LoginException
    {
        long start = System.currentTimeMillis();
        statusCheck();
 
        // check for log in
        if (user == null)
        {
            CredentialNotFoundException ex = new CredentialNotFoundException("Permission denied: you need to be autoconfirmed to move pages.");
            logger.logp(Level.SEVERE, "Wiki", "move()", "[" + domain + "] Cannot move - permission denied.", ex);
            throw ex;
        }
 
        // check namespace
        int ns = namespace(title);
        if (ns == IMAGE_NAMESPACE || ns == CATEGORY_NAMESPACE)
            throw new UnsupportedOperationException("Tried to move a category/image.");
        // TODO: image renaming? TEST ME (MediaWiki, that is).
 
        // sanitize some params
        String title2 = URLEncoder.encode(title, "UTF-8");
 
        // fetch page info
        String url = query + "action=query&prop=info&inprop=protection&intoken=move&titles=" + title2;
        String line = fetch(url, "move", true);
 
        // determine whether the page exists
        if (line.contains("missing=\"\""))
            throw new IllegalArgumentException("Tried to move a non-existant page!");
 
        // check protection level
        if (line.contains("type=\"move\" level=\"sysop\"") && (user.userRights() & ADMIN) == 0)
        {
            CredentialException ex = new CredentialException("Permission denied: page is protected.");
            logger.logp(Level.WARNING, "Wiki", "move()", "[" + getDomain() + "] Cannot move - permission denied.", ex);
            throw ex;
        }
 
        // find the move token
        int a = line.indexOf("token=\"") + 7;
        int b = line.indexOf("\"", a);
        String wpMoveToken = line.substring(a, b);
 
        // check target
        if (!checkRights(getProtectionLevel(newTitle), true))
        {
            CredentialException ex = new CredentialException("Permission denied: target page is protected.");
            logger.logp(Level.WARNING, "Wiki", "move()", "[" + getDomain() + "] Cannot move - permission denied.", ex);
            throw ex;
        }
 
        // fetch the appropriate URL
        url = query + "action=move";
        logurl(url, "move");
        URLConnection connection = new URL(url).openConnection();
        setCookies(connection, cookies2);
        connection.setDoOutput(true);
        connection.connect();
 
        // send the data
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        // PrintWriter out = new PrintWriter(System.out); // debug version
        out.write("from=");
        out.write(title2);
        out.write("&to=");
        out.write(URLEncoder.encode(newTitle, "UTF-8"));
        out.write("&reason=");
        out.write(URLEncoder.encode(reason, "UTF-8"));
        out.write("&token=");
        out.write(URLEncoder.encode(wpMoveToken, "UTF-8"));
        if (movetalk)
            out.write("&movetalk=1");
        if (noredirect && (user.userRights() & ADMIN) == ADMIN)
            out.write("&noredirect=1");
        out.close();
 
        // done
        try
        {
            // it's somewhat strange that the edit only sticks when you start reading the response...
            BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
            String temp = in.readLine();
 
            // success
            if (temp.contains("move from"))
                in.close();
            // failure
            checkErrors(temp, "move");
        }
        catch (IOException e)
        {
            // retry once
            if (retry)
            {
                retry = false;
                log(Level.WARNING, "Exception: " + e.getMessage() + " Retrying...", "move");
                move(title, newTitle, reason, noredirect, movetalk);
            }
            else
            {
                logger.logp(Level.SEVERE, "Wiki", "move()", "[" + domain + "] EXCEPTION:  ", e);
                throw e;
            }
        }
        if (retry)
            log(Level.INFO, "Successfully moved " + title + " to " + newTitle, "move");
        retry = true;
 
        // throttle
        try
        {
            long time = throttle - System.currentTimeMillis() + start;
            if (time > 0)
                Thread.sleep(time);
        }
        catch (InterruptedException e)
        {
            // nobody cares
        }
    }
 
    /**
     *  Exports the current revision of this page. Equivalent to
     *  [[Special:Export]].
     *  @param title the title of the page to export
     *  @return the exported text
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public String export(String title) throws IOException
    {
        return fetch(query + "action=query&export&exportnowrap&titles=" + URLEncoder.encode(title, "UTF-8"), "export", false);
    }
 
    // REVISION METHODS
 
    /**
     *  Gets a revision based on a given oldid. Automatically fills out all
     *  attributes of that revision except <tt>rcid</tt>.
     *
     *  @param oldid a particular oldid
     *  @return the revision corresponding to that oldid. If a particular
     *  revision has been deleted, returns null.
     *  @throws IOException if a network error occurs
     *  @since 0.17
     */
    public Revision getRevision(long oldid) throws IOException
    {
        // build url and connect
        String url = query + "action=query&prop=revisions&rvprop=ids|timestamp|user|comment|flags&revids=" + oldid;
        String line = fetch(url, "getRevision", false);
        // check for deleted revisions
        if (line.contains("<badrevids>"))
            return null;
        return parseRevision(line, "");
    }
 
    /**
     *  Reverts a series of edits on the same page by the same user quickly
     *  provided that they are the most recent revisions on that page. If this
     *  is not the case, then this method does nothing. See
     *  [[mw:Manual:Parameters to index.php#Actions]] (look under rollback)
     *  for more information.
     *
     *  @param revision the revision to revert. <tt>revision.isTop()</tt> must
     *  be true for the rollback to succeed
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if the user is not an admin
     *  @throws AccountLockedException if the user is blocked
     *  @since 0.19
     */
    public void rollback(Revision revision) throws IOException, LoginException
    {
        rollback(revision, false, "");
    }
 
    /**
     *  Reverts a series of edits on the same page by the same user quickly
     *  provided that they are the most recent revisions on that page. If this
     *  is not the case, then this method does nothing. See
     *  [[mw:Manual:Parameters to index.php#Actions]] (look under rollback)
     *  for more information.
     *
     *  @param revision the revision to revert. <tt>revision.isTop()</tt> must
     *  be true for the rollback to succeed
     *  @param bot whether to mark this edit and the reverted revisions as
     *  bot edits
     *  @param reason (optional) a reason for the rollback. Use "" for the
     *  default ([[MediaWiki:Revertpage]]).
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if the user is not an admin
     *  @throws AccountLockedException if the user is blocked
     *  @since 0.19
     */
    public synchronized void rollback(Revision revision, boolean bot, String reason) throws IOException, LoginException
    {
        // check rights
        if (user == null || (user.userRights() & ADMIN) != ADMIN)
            throw new CredentialNotFoundException("Permission denied: You need to be an admin to rollback.");
        statusCheck();
        String url = query + "action=query&prop=revisions&titles=" + revision.getPage() + "&rvlimit=1&rvtoken=rollback";
        String line = fetch(url, "rollback", true);
 
        // check whether we are "on top".
        Revision top = parseRevision(line, revision.getPage());
        System.out.println(top);
        System.out.println(revision);
        if (!top.equals(revision))
        {
            log(Level.INFO, "Rollback failed: revision is not the most recent", "rollback");
            return;
        }
 
        // get the rollback token
        int a = line.indexOf("rollbacktoken=\"") + 15;
        int b = line.indexOf("\"", a);
        String token = URLEncoder.encode(line.substring(a, b), "UTF-8");
 
        // perform the rollback. Although it's easier through the human interface, we want
        // to make sense of any resulting errors.
        url = query + "action=rollback";
        logurl(url, "rollback");
        URLConnection connection = new URL(url).openConnection();
        setCookies(connection, cookies2);
        connection.setDoOutput(true);
        connection.connect();
 
        // send data
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        out.write("title=");
        out.write(revision.getPage());
        out.write("&user=");
        out.write(revision.getUser());
        out.write("&token=");
        out.write(token);
        if (bot)
            out.write("&markbot=1");
        if (!reason.equals(""))
        {
            out.write("&summary=");
            out.write(reason);
        }
        out.close();
 
        // done
        try
        {
            // read the response
            BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
            String temp = in.readLine();
 
            // success
            if (temp.contains("rollback title="))
                in.close();
            // ignorable errors
            else if (temp.contains("alreadyrolled"))
                log(Level.INFO, "Edit has already been rolled back.", "rollback");
            else if (temp.contains("onlyauthor"))
                log(Level.INFO, "Cannot rollback as the page only has one author.", "rollback");
            // probably not ignorable
            else
                checkErrors(temp, "rollback");
            in.close();
        }
        catch (IOException e)
        {
            // retry once
            if (retry)
            {
                retry = false;
                log(Level.WARNING, "Exception: " + e.getMessage() + " Retrying...", "rollback");
                rollback(revision, bot, reason);
            }
            else
            {
                logger.logp(Level.SEVERE, "Wiki", "rollback()", "[" + domain + "] EXCEPTION:  ", e);
                throw e;
            }
        }
        if (retry)
            log(Level.INFO, "Successfully reverted edits by " + user + " on " + revision.getPage(), "rollback");
        retry = true;
    }
 
     /**
     *  Undoes revisions, equivalent to the "undo" button in the GUI page
     *  history. A quick explanation on how this might work - suppose the edit
     *  history was as follows:
     *
     *  <ul>
     *  <li> (revid=541) 2009-01-13 00:01 92.45.43.227
     *  <li> (revid=325) 2008-12-10 11:34 Example user
     *  <li> (revid=314) 2008-12-10 10:15 127.0.0.1
     *  <li> (revid=236) 2008-08-08 08:00 Anonymous
     *  <li> (revid=200) 2008-07-31 16:46 EvilCabalMember
     *  </ul>
     *  Then:
     *  <pre>
     *  wiki.undo(wiki.getRevision(314L), null, reason, false); // undo revision 314 only
     *  wiki.undo(wiki.getRevision(236L), wiki.getRevision(325L), reason, false); // undo revisions 236-325
     *  </pre>
     *
     *  This will only work if revision 541 or any subsequent edits do not
     *  clash with the change resulting from the undo.
     *
     *  @param rev a revision to undo
     *  @param to the most recent in a range of revisions to undo. Set to null
     *  to undo only one revision.
     *  @param reason an edit summary (optional). Use "" to get the default
     *  [[MediaWiki:Undo-summary]].
     *  @param minor whether this is a minor edit
     *  @throws IOException if a network error occurs
     *  @throws AccountLockedException if user is blocked
     *  @throws CredentialException if page is protected and we can't edit it
     *  @throws IllegalArgumentException if the revisions are not on the same
     *  page.
     *  @since 0.20
     */
    public synchronized void undo(Revision rev, Revision to, String reason, boolean minor) throws IOException, LoginException
    {
        // throttle
        long start = System.currentTimeMillis();
        statusCheck();
 
        // check here to see whether the titles correspond
        if (to != null && !rev.getPage().equals(to.getPage()))
            throw new IllegalArgumentException("Cannot undo - the revisions supplied are not on the same page!");
 
        // Check the protection level. We don't use getProtectionLevel(title), as we
        // can fetch a move token at the same time!
        String url = query + "action=query&prop=info&inprop=protection&intoken=edit&titles=" + rev.getPage();
        String line = fetch(url, "edit", true);
 
        // parse the page
        int level = NO_PROTECTION;
        int z = line.indexOf("type=\"edit\"");
        if (z != -1)
        {
            String s = line.substring(z, z + 30);
            if (s.contains("sysop"))
                level = FULL_PROTECTION;
            s = line.substring(z + 30, line.length()); // cut out edit tag
            if (line.contains("level=\"sysop\""))
                level = SEMI_AND_MOVE_PROTECTION;
            level = SEMI_PROTECTION;
        }
        else if (line.contains("type=\"create\""))
            level = PROTECTED_DELETED_PAGE;
 
        // do the check
        if (!checkRights(level, false))
        {
            CredentialException ex = new CredentialException("Permission denied: page is protected.");
            logger.logp(Level.WARNING, "Wiki", "undo()", "[" + getDomain() + "] Cannot undo - permission denied.", ex);
            throw ex;
        }
 
        // find the edit token
        int a = line.indexOf("token=\"") + 7;
        int b = line.indexOf("\"", a);
        String wpEditToken = line.substring(a, b);
 
        // connect
        url = query + "action=edit";
        logurl(url, "undo");
        URLConnection connection = new URL(url).openConnection();
        connection.setDoOutput(true);
        setCookies(connection, cookies2);
        connection.connect();
 
        // send data
        PrintWriter out = new PrintWriter(connection.getOutputStream());
        out.write("title=");
        out.write(rev.getPage());
        if (!reason.equals(""))
            out.write("&summary=" + reason);
        out.write("&undo=" + rev.getRevid());
        if (to != null)
            out.write("&undoafter=" + to.getRevid());
        if (minor)
            out.write("&minor=1");
        out.write("&token=");
        out.write(URLEncoder.encode(wpEditToken, "UTF-8"));
        out.close();
 
        // done
        try
        {
            BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
            checkErrors(in.readLine(), "undo");
            in.close();
        }
        catch (IOException e)
        {
            // retry once
            if (retry)
            {
                retry = false;
                log(Level.WARNING, "Exception: " + e.getMessage() + " Retrying...", "undo");
                undo(rev, to, reason, minor);
            }
            else
            {
                logger.logp(Level.SEVERE, "Wiki", "undo()", "[" + domain + "] EXCEPTION:  ", e);
                throw e;
            }
        }
        if (retry)
        {
            String log = "Successfully undid revision(s) " + rev.getRevid();
            if (to != null)
                log += (" - " + to.getRevid());
            log(Level.INFO, log, "undo");
        }
        retry = true;
 
        // throttle
        try
        {
            long time = throttle - System.currentTimeMillis() + start;
            if (time > 0)
                Thread.sleep(time);
        }
        catch (InterruptedException e)
        {
            // nobody cares
        }
    }
 
    /**
     *  Parses stuff of the form <tt>title="L. Sprague de Camp"
     *  timestamp="2006-08-28T23:48:08Z" minor="" comment="robot  Modifying:
     *  [[bg:Лион Спраг де Камп]]"</tt> into useful revision objects. Used by
     *  <tt>contribs()</tt>, <tt>watchlist()</tt>, <tt>getPageHistory()</tt>
     *  <tt>rangeContribs()</tt> and <tt>recentChanges()</tt>. NOTE: if
     *  RevisionDelete was used on a revision, the relevant values will be null.
     *
     *  @param xml the XML to parse
     *  @param title an optional title parameter if we already know what it is
     *  (use "" if we don't)
     *  @return the Revision encoded in the XML
     *  @since 0.17
     */
    protected Revision parseRevision(String xml, String title)
    {
        // oldid
        int a = xml.indexOf("revid=\"") + 7;
        int b = xml.indexOf("\"", a);
        long oldid = Long.parseLong(xml.substring(a, b));
 
        // timestamp
        a = xml.indexOf("timestamp=\"") + 11;
        b = xml.indexOf("\"", a);
        Calendar timestamp = timestampToCalendar(convertTimestamp(xml.substring(a, b)));
 
        // title
        if (title.equals(""))
        {
            a = xml.indexOf("title=\"") + 7;
            b = xml.indexOf("\"", a);
            title = xml.substring(a, b);
        }
 
        // summary
        String summary;
        if (xml.contains("commenthidden=\""))
            summary = null; // oversighted
        else
        {
            a = xml.indexOf("comment=\"") + 9;
            b = xml.indexOf("\"", a);
            summary = (a == 8) ? "" : decode(xml.substring(a, b));
        }
 
        // user
        String user2 = null;
        if (xml.contains("user=\""))
        {
            a = xml.indexOf("user=\"") + 6;
            b = xml.indexOf("\"", a);
            user2 = xml.substring(a, b);
        }
 
        // minor
        boolean minor = xml.contains("minor=\"\"");
 
        Revision revision = new Revision(oldid, timestamp, title, summary, user2, minor);
        if (xml.contains("rcid=\""))
        {
            // set rcid
            a = xml.indexOf("rcid=\"") + 6;
            b = xml.indexOf(xml);
            revision.setRcid(Long.parseLong(xml.substring(a, b)));
        }
        return revision;
    }
 
    /**
     *  Turns a list of revisions into human-readable wikitext. Be careful, as
     *  slowness may result when copying large amounts of wikitext produced by
     *  this method, or by the wiki trying to parse it. Takes the form of:
     *
     *  <p>*(diff link) 2009-01-01 00:00 User (talk | contribs) (edit summary)
     *  @param revisions a list of revisions
     *  @return those revisions as wikitext
     *  @since 0.20
     */
    public String revisionsToWikitext(Revision[] revisions)
    {
        StringBuilder sb = new StringBuilder(revisions.length * 100);
        for (int i = 0; i < revisions.length; i++)
        {
            // base oldid link
            StringBuilder base2 = new StringBuilder(50);
            base2.append("<span class=\"plainlinks\">[");
            base2.append(base);
            base2.append(revisions[i].getPage().replace(" ", "_"));
            base2.append("&oldid=");
            base2.append(revisions[i].getRevid());
 
            // diff link
            sb.append("*(");
            sb.append(base2);
            sb.append("&diff=prev diff]</span>) ");
 
            // timestamp, link to oldid
            Calendar timestamp = revisions[i].getTimestamp();
            sb.append(base2);
            sb.append(" ");
            sb.append(timestamp.get(Calendar.YEAR));
            sb.append("-");
            int month = timestamp.get(Calendar.MONTH) + 1;
            if (month < 9)
                sb.append("0");
            sb.append(month);
            sb.append("-");
            int day = timestamp.get(Calendar.DAY_OF_MONTH);
            if (day < 10)
                sb.append("0");
            sb.append(day);
            sb.append(" ");
            int hour = timestamp.get(Calendar.HOUR);
            if (hour < 10)
                sb.append("0");
            sb.append(hour);
            sb.append(":");
            int minute = timestamp.get(Calendar.MINUTE);
            if (minute < 10)
                sb.append("0");
            sb.append(minute);
            sb.append("]</span> ");
 
            // user
            String user2 = revisions[i].getUser();
            sb.append("[[User:");
            sb.append(user2);
            sb.append("|");
            sb.append(user2);
            sb.append("]] ([[User talk:");
            sb.append(user2);
            sb.append("|talk]] | [[Special:Contributions/");
            sb.append(user2);
            sb.append("|contribs]]) (");
 
            // edit summary - nowiki any templates
            String summary = revisions[i].getSummary();
            if (summary.contains("}}"))
            {
                int a = summary.indexOf(("}}"));
                sb.append(summary.substring(0, a));
                sb.append("<nowiki>}}</nowiki>");
                sb.append(summary.substring(a + 2));
            }
            else
                sb.append(summary);
            sb.append(")\n");
        }
        return sb.toString();
    }
 
    // IMAGE METHODS
 
    /**
     *  Fetches a raster image file. This method uses <tt>ImageIO.read()</tt>,
     *  and as such only JPG, PNG, GIF and BMP formats are supported. SVG
     *  images are supported only if a thumbnail width and height are
     *  specified. Animated GIFs have not been tested yet.
     *
     *  @param title the title of the image (i.e. Example.jpg, not
     *  File:Example.jpg)
     *  @return the image, encapsulated in a BufferedImage
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public BufferedImage getImage(String title) throws IOException
    {
        return getImage(title, -1, -1);
    }
 
    /**
     *  Fetches a thumbnail of a raster image file. This method uses
     *  <tt>ImageIO.read()</tt>, and as such only JPG, PNG, GIF and BMP
     *  formats are supported. SVG images are supported only if a thumbnail
     *  width and height are specified. Animated GIFs have not been tested yet.
     *
     *  @param title the title of the image without the File: prefix (i.e.
     *  Example.jpg, not File:Example.jpg)
     *  @param width the width of the thumbnail (use -1 for actual width)
     *  @param height the height of the thumbnail (use -1 for actual height)
     *  @return the image, encapsulated in a BufferedImage, null if we cannot
     *  read the image
     *  @throws IOException if a network error occurs
     *  @since 0.13
     */
    public BufferedImage getImage(String title, int width, int height) throws IOException
    {
        // sanitise the title
        title = URLEncoder.encode(title, "UTF-8");
 
        // this is a two step process - first we fetch the image url
        StringBuilder url = new StringBuilder(query);
        url.append("&action=query&prop=imageinfo&iiprop=url&titles=File:");
        url.append(URLEncoder.encode(title, "UTF-8"));
        url.append("&iiurlwidth=");
        url.append(width);
        url.append("&iirulheight=");
        url.append(height);
        String line = fetch(url.toString(), "getImage", false);
        int a = line.indexOf("url=\"") + 5;
        int b = line.indexOf("\"", a);
        String url2 = line.substring(a, b);
 
        // then we use ImageIO to read from it
        logurl(url2, "getImage");
        BufferedImage image = ImageIO.read(new URL(url2));
        log(Level.INFO, "Successfully retrieved image \"" + title + "\"", "getImage");
        return image;
    }
 
    /**
     *  Gets the file metadata for a file. Note that <tt>getImage()</tt>
     *  reads directly into a <tt>BufferedImage</tt> object, so you won't be
     *  able to get all metadata that way. The keys are:
     *
     *  * size (file size, Integer)
     *  * width (Integer)
     *  * height (Integer)
     *  * mime (MIME type, String)
     *  * plus EXIF metadata (Strings)
     *
     *  @param image the image to get metadata for, without the File: prefix
     *  @return the metadata for the image
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public HashMap<String, Object> getFileMetadata(String file) throws IOException
    {
        // This seems a good candidate for bulk queries.
 
        // fetch
        String url = query + "action=query&prop=imageinfo&iiprop=size|mime|metadata&titles=File:" + URLEncoder.encode(file, "UTF-8");
        String line = fetch(url, "getFileMetadata", false);
        HashMap<String, Object> metadata = new HashMap<String, Object>();
 
        // size, width, height, mime type
        int a = line.indexOf("size=\"") + 6;
        int b = line.indexOf("\"", a);
        metadata.put("size", new Integer(line.substring(a, b)));
        a = line.indexOf("width=\"") + 7;
        b = line.indexOf("\"", a);
        metadata.put("width", new Integer(line.substring(a, b)));
        a = line.indexOf("height=\"") + 8;
        b = line.indexOf("\"", a);
        metadata.put("height", new Integer(line.substring(a, b)));
        a = line.indexOf("mime=\"") + 6;
        b = line.indexOf("\"", a);
        metadata.put("mime", line.substring(a, b));
 
        // exif
        while (line.contains("metadata name=\""))
        {
            a = line.indexOf("name=\"") + 6;
            b = line.indexOf("\"", a);
            String name = line.substring(a, b);
            a = line.indexOf("value=\"") + 7;
            b = line.indexOf("\"", a);
            String value = line.substring(a, b);
            metadata.put(name, value);
            line = line.substring(b);
        }
        return metadata;
    }
 
    /**
     *  Gets duplicates of this file. Capped at <tt>max</tt> number of
     *  duplicates, there's no good reason why there should be more than that.
     *  Equivalent to [[Special:FileDuplicateSearch]].
     *
     *  @param file the file for checking duplicates (without the File:)
     *  @return the duplicates of that file
     *  @throws IOException if a network error occurs
     *  @since 0.18
     */
    public String[] getDuplicates(String file) throws IOException
    {
        String url = query + "action=query&prop=duplicatefiles&dflimit=max&titles=File:" + URLEncoder.encode(file, "UTF-8");
        String line = fetch(url, "getDuplicates", false);
 
        // do the parsing
        // Expected format: <df name="Star-spangled_banner_002.ogg" other stuff >
        ArrayList<String> duplicates = new ArrayList<String>();
        while (line.contains("<df "))
        {
            int a = line.indexOf("name=") + 6;
            int b = line.indexOf("\"", a);
            duplicates.add("File:" + line.substring(a, b));
            line = line.substring(b);
        }
        log(Level.INFO, "Successfully retrieved duplicates of File:" + file + " (" + duplicates.size() + " files)", "getDuplicates");
        return duplicates.toArray(new String[0]);
    }
 
    /**
     *  Returns the upload history of an image. This is not the same as
     *  <tt>getLogEntries(null, null, Integer.MAX_VALUE, Wiki.UPLOAD_LOG,
     *  title, Wiki.IMAGE_NAMESPACE)</tt>, as the image may have been deleted.
     *  This returns only the live history of an image.
     *
     *  @param title the title of the image, excluding the File prefix
     *  @return the image history of the image
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public LogEntry[] getImageHistory(String title) throws IOException
    {
        String url = query + "action=query&prop=imageinfo&iiprop=timestamp|user|comment&iilimit=max&titles=File:" + title;
        String line = fetch(url, "getImageHistory", false);
        ArrayList<LogEntry> history = new ArrayList<LogEntry>(40);
        while (line.contains("<ii "))
        {
            int a = line.indexOf("<ii");
            int b = line.indexOf(">", a);
            LogEntry entry = parseLogEntry(line.substring(a, b), 2);
            entry.target = title;
            history.add(entry);
            line = line.substring(b);
        }
 
        // crude hack: action adjusting for first image (in the history, not our list)
        LogEntry last = history.get(history.size() - 1);
        last.action = "upload";
        history.set(history.size() - 1, last);
        return history.toArray(new LogEntry[0]);
    }
 
    /**
     *  Gets an old image revision. You will have to do the thumbnailing
     *  yourself.
     *  @param entry the upload log entry that corresponds to the image being
     *  uploaded
     *  @return the image that was uploaded, as long as it is still live or
     *  null if the image doesn't exist
     *  @throws IOException if a network error occurs
     *  @throws IllegalArgumentException if the entry is not in the upload log
     *  @since 0.20
     */
    public BufferedImage getOldImage(LogEntry entry) throws IOException
    {
        // check for type
        if (!entry.getType().equals(UPLOAD_LOG))
            throw new IllegalArgumentException("You must provide an upload log entry!");
        // no thumbnails for image history, sorry.
        String title = entry.getTarget();
        String url = query + "action=query&prop=imageinfo&iilimit=max&iiprop=timestamp|url|archivename&titles=File:" + title;
        String line = fetch(url, "getOldImage", false);
 
        // find the correct log entry by comparing timestamps
        while (line.contains("<ii "))
        {
            int a = line.indexOf("timestamp=") + 11;
            int b = line.indexOf("\"", a);
            String timestamp = convertTimestamp(line.substring(a, b));
            if (timestamp.equals(calendarToTimestamp(entry.getTimestamp())))
            {
                // this is it
                a = line.indexOf(" url=\"") + 6; // the space is important!
                b = line.indexOf("\"", a);
                url = line.substring(a, b);
                logurl(url, "getOldImage");
                BufferedImage image = ImageIO.read(new URL(url));
 
                // scrape archive name for logging purposes
                a = line.indexOf("archivename=\"") + 13;
                b = line.indexOf("\"", a);
                String archive = line.substring(a, b);
                log(Level.INFO, "Successfully retrieved old image \"" + archive + "\"", "getImage");
                return image;
            }
            line = line.substring(b + 10);
        }
        return null;
    }
 
    /**
     *  Uploads an image. Equivalent to [[Special:Upload]]. Supported
     *  extensions are (case-insensitive) "png", "jpg", "gif" and "svg". You
     *  need to be logged on to do this. This method is thread safe and subject
     *  to the throttle.
     *
     *  @param file the image file
     *  @param filename the target file name (Example.png, not File:Example.png)
     *  @param contents the contents of the image description page
     *  @throws CredentialNotFoundException if not logged in
     *  @throws CredentialException if page is protected and we can't upload
     *  @throws IOException if a network/local filesystem error occurs
     *  @throws AccountLockedException if user is blocked
     *  @since 0.11
     */
    public synchronized void upload(File file, String filename, String contents) throws IOException, LoginException
    {
        // TODO: API upload? Still in the pipeline, unfortunately.
        // throttle
        long start = System.currentTimeMillis();
        statusCheck();
 
        // check for log in
        if (user == null)
        {
            CredentialNotFoundException ex = new CredentialNotFoundException("Permission denied: you need to be registered to upload files.");
            logger.logp(Level.SEVERE, "Wiki", "upload()", "[" + domain + "] Cannot upload - permission denied.", ex);
            throw ex;
        }
 
        // UTF-8 vodoo
        try {
            contents = new String(contents.getBytes("UTF-8"), "iso-8859-1");
        } catch (UnsupportedEncodingException ex) {
            //logger.logp(Level.SEVERE, null, ex);
        	ex.printStackTrace();
        }
 
 
        // check if the page is protected, and if we can upload (incorporates lag check)
        String filename2 = filename.replaceAll(" ", "_");
//        String filename2 = URLEncoder.encode(filename.replaceAll(" ", "_"), "UTF-8");
        try {
            filename2 = new String(filename2.getBytes("UTF-8"), "iso-8859-1");
        } catch (UnsupportedEncodingException ex) {
            //logger.logp(Level.SEVERE, null, ex);
        	ex.printStackTrace();
        }
 
 
        String fname = "File:" + filename2;
        if (!checkRights(getProtectionLevel(fname), false))
        {
            CredentialException ex = new CredentialException("Permission denied: image is protected.");
            logger.logp(Level.WARNING, "Wiki", "upload()", "[" + domain + "] Cannot upload - permission denied.", ex);
            throw ex;
        }
 
        // prepare MIME type
        String extension = filename2.substring(filename2.length() - 3).toUpperCase().toLowerCase();
        if (extension.equals("jpg"))
            extension = "jpeg";
        else if (extension.equals("svg"))
            extension += "+xml";
 
        // upload the image
        // this is how we do multipart post requests, by the way
        // see also: http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.2
        String url = base + "Special:Upload";
        logurl(url, "upload");
        URLConnection connection = new URL(url).openConnection();
        String boundary = "----------NEXT PART----------";
        connection.setRequestProperty("Accept-Charset", "iso-8859-1,*,utf-8");
        connection.setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary);
        setCookies(connection, cookies);
        connection.setDoOutput(true);
        connection.connect();
 
        // send data
        boundary = "--" + boundary + "\r\n";
        DataOutputStream out = new DataOutputStream(connection.getOutputStream());
//        DataOutputStream out = new DataOutputStream(System.out); // debug version
        out.writeBytes(boundary);
        out.writeBytes("Content-Disposition: form-data; name=\"wpIgnoreWarning\"\r\n\r\n");
        out.writeBytes("true\r\n");
        out.writeBytes(boundary);
        out.writeBytes("Content-Disposition: form-data; name=\"wpDestFile\"\r\n");
        out.writeBytes("Content-Type: text/plain; charset=utf-8\r\n\r\n");
        out.writeBytes(filename2);
        out.writeBytes("\r\n");
        out.writeBytes(boundary);
        out.writeBytes("Content-Disposition: form-data; name=\"wpUploadFile\"; filename=\"");
        out.writeBytes(filename);
        out.writeBytes("\"\r\n");
        out.writeBytes("Content-Type: image/");
        out.writeBytes(extension);
        out.writeBytes("\r\n\r\n");
 
        // write image
        FileInputStream fi = new FileInputStream(file);
        byte[] b = new byte[fi.available()];
        fi.read(b);
        out.write(b);
        fi.close();
 
        // write the rest
        out.writeBytes("\r\n");
        out.writeBytes(boundary);
        out.writeBytes("Content-Disposition: form-data; name=\"wpUploadDescription\"\r\n");
        out.writeBytes("Content-Type: text/plain\r\n\r\n");
        out.writeBytes(contents);
        out.writeBytes("\r\n");
        out.writeBytes(boundary);
        out.writeBytes("Content-Disposition: form-data; name=\"wpUpload\"\r\n\r\n");
        out.writeBytes("Upload file\r\n");
        out.writeBytes(boundary.substring(0, boundary.length() - 2) + "--\r\n");
        out.close();
 
        // done
        BufferedReader in;
        try
        {
            // it's somewhat strange that the edit only sticks when you start reading the response...
 
            String line ;
//            in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
            in = new BufferedReader(new InputStreamReader(connection.getInputStream()));
            line = in.readLine();
//            while ((line = in.readLine()) != null) System.out.println(line);
            in.close();
 
        }
        catch (IOException e)
        {
            // retry once
            if (retry)
            {
                retry = false;
                log(Level.WARNING, "Exception: " + e.getMessage() + " Retrying...", "upload");
                upload(file, filename, contents);
            }
            else
            {
                logger.logp(Level.SEVERE, "Wiki", "upload()", "[" + domain + "] EXCEPTION:  ", e);
                throw e;
            }
        }
        if (retry)
            log(Level.INFO, "Successfully uploaded " + filename, "upload");
        retry = true;
 
        // throttle
        try
        {
            long z = throttle - System.currentTimeMillis() + start;
            if (z > 0)
                Thread.sleep(z);
        }
        catch (InterruptedException e)
        {
            // nobody cares
        }
    }
 
    // USER METHODS
 
    /**
     *  Determines whether a specific user exists. Should evaluate to false
     *  for anons.
     *
     *  @param username a username
     *  @return whether the user exists
     *  @throws IOException if a network error occurs
     *  @since 0.05
     */
    public boolean userExists(String username) throws IOException
    {
        return allUsers(username, 1)[0].equals(username);
    }
 
    /**
     *  Gets the specified number of users (as a String) starting at the
     *  given string, in alphabetical order. Equivalent to [[Special:Listusers]].
     *
     *  @param start the string to start enumeration
     *  @param number the number of users to return
     *  @return a String[] containing the usernames
     *  @throws IOException if a network error occurs
     *  @since 0.05
     */
    public String[] allUsers(String start, int number) throws IOException
    {
        // sanitise
        String url = query + "action=query&list=allusers&aulimit=" + (number > max ? max : number) + "&aufrom=";
 
        // work around an arbitrary and silly limitation
        ArrayList<String> members = new ArrayList<String>(6667); // enough for most requests
        String next = URLEncoder.encode(start, "UTF-8");
        do
        {
            next = URLEncoder.encode(next, "UTF-8");
            String line = fetch(url + next, "allUsers", false);
 
            // parse
            int a = line.indexOf("aufrom=\"") + 8;
            next = line.substring(a, line.indexOf("\" />", a));
            while (line.contains("<u ") && members.size() < number)
            {
                int x = line.indexOf("name=");
                int y = line.indexOf(" />", x);
                members.add(line.substring(x + 6, y - 1));
                line = line.substring(y + 2, line.length());
            }
        }
        while (members.size() < number);
        log(Level.INFO, "Successfully retrieved user list (" + number + " users starting at " + start + ")", "allUsers");
        return members.toArray(new String[0]);
    }
 
    /**
     *  Gets the user with the given username. Returns null if it doesn't
     *  exist.
     *  @param username a username
     *  @return the user with that username
     *  @since 0.05
     *  @throws IOException if a network error occurs
     */
    public User getUser(String username) throws IOException
    {
        return userExists(username) ? new User(username) : null;
    }
 
    /**
     *  Gets the user we are currently logged in as. If not logged in, returns
     *  null.
     *  @return the current logged in user
     *  @since 0.05
     */
    public User getCurrentUser()
    {
        return user;
    }
 
    /**
     *  Gets the contributions of a user. Equivalent to
     *  [[Special:Contributions]] Be careful when using this method because
     *  the user may have a high edit count e.g. <tt>
     *  enWiki.contribs("MER-C").length</tt> > 90000.
     *
     *  @param user the user or IP to get contributions for
     *  @return the contributions of the user
     *  @throws IOException if a network error occurs
     *  @since 0.17
     */
    public Revision[] contribs(String user) throws IOException
    {
        return contribs(user, "", null, ALL_NAMESPACES);
    }
 
    /**
     *  Gets the contributions of a user in a particular namespace. Equivalent
     *  to [[Special:Contributions]]. Be careful when using this method because
     *  the user may have a high edit count e.g. <tt>enWiki.contribs("MER-C",
     *  Wiki.MAIN_NAMESPACE).length</tt> > 30000.
     *
     *  @param user the user or IP to get contributions for
     *  @return the contributions of the user
     *  @throws IOException if a network error occurs
     *  @since 0.17
     */
    public Revision[] contribs(String user, int namespace) throws IOException
    {
        return contribs(user, "", null, namespace);
    }
 
    /**
     *  Gets the contributions by a range of IP v4 addresses. Supported ranges
     *  are /8, /16 and /24. Do be careful with this, as calls such as
     *  <tt>enWiki.rangeContribs("152.163.0.0/16"); // let's get all the
     *  contributions for this AOL range!</tt> might just kill your program.
     *
     *  @param range the CIDR range of IP addresses to get contributions for
     *  @return the contributions of that range
     *  @throws IOException if a network error occurs
     *  @throws NumberFormatException if we aren't able to parse the range
     *  @since 0.17
     */
    public Revision[] rangeContribs(String range) throws IOException
    {
        // sanitize range
        int a = range.indexOf("/");
        if (a < 7)
            throw new NumberFormatException("Not a valid CIDR range!");
        int size = Integer.parseInt(range.substring(a + 1));
        String[] numbers = range.substring(0, a).split("\\.");
        if (numbers.length != 4)
            throw new NumberFormatException("Not a valid CIDR range!");
        switch (size)
        {
            case 8:
                return contribs("", numbers[0] + ".", null, ALL_NAMESPACES);
            case 16:
                return contribs("", numbers[0] + "." + numbers[1] + ".", null, ALL_NAMESPACES);
            case 24:
                return contribs("", numbers[0] + "." + numbers[1] + "." + numbers[2] + ".", null, ALL_NAMESPACES);
            case 32: // not that people are silly enough to do this...
                return contribs(range.substring(0, range.length() - 3), "", null, ALL_NAMESPACES);
            default:
                throw new NumberFormatException("Range is not supported.");
        }
    }
 
    /**
     *  Gets the contributions for a user, an IP address or a range of IP
     *  addresses. Equivalent to [[Special:Contributions]].
     *
     *  @param user the user to get contributions for
     *  @param offset fetch edits no older than this date
     *  @param namespace a namespace
     *  @param prefix a prefix of usernames. Overrides <tt>user</tt>.
     *  @return contributions of this user
     *  @throws IOException if a network error occurs
     *  @since 0.17
     */
    public Revision[] contribs(String user, String prefix, Calendar offset, int namespace) throws IOException
    {
        // prepare the url
        StringBuilder temp = new StringBuilder(query);
        temp.append("action=query&list=usercontribs&ucprop=title|timestamp|flags|comment|ids&uclimit=max&");
        if (prefix.equals(""))
        {
            temp.append("ucuser=");
            temp.append(user);
        }
        else
        {
            temp.append("ucuserprefix=");
            temp.append(prefix);
        }
        if (namespace != ALL_NAMESPACES)
        {
            temp.append("&ucnamespace=");
            temp.append(namespace);
        }
        temp.append("&ucstart=");
        ArrayList<Revision> revisions = new ArrayList<Revision>(7500);
        String ucstart = calendarToTimestamp(offset == null ? new GregorianCalendar() : offset);
 
        // fetch data
        do
        {
            String line = fetch(temp.toString() + ucstart, "contribs", false);
 
            // set offset parameter
            int aa = line.indexOf("ucstart=\"") + 9;
            if (aa < 9)
                ucstart = "done"; // depleted list
            else
            {
                int bb = line.indexOf("\"", aa);
                ucstart = line.substring(aa, bb);
            }
            // parse revisions
            while (line.contains("<item"))
            {
                int a = line.indexOf("<item");
                int b = line.indexOf(" />", a);
                revisions.add(parseRevision(line.substring(a, b), ""));
                line = line.substring(b);
            }
        }
        while (!ucstart.equals("done"));
 
        // clean up
        log(Level.INFO, "Successfully retrived contributions for " + (prefix.equals("") ? user : prefix) + " (" + revisions.size() + " edits)", "contribs");
        return revisions.toArray(new Revision[0]);
    }
 
    // WATCHLIST METHODS
 
    /**
     *  Adds a page to the watchlist. You need to be logged in to use this.
     *  @param title the page to add to the watchlist
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @see #unwatch
     *  @since 0.18
     */
    public void watch(String title) throws IOException, CredentialNotFoundException
    {
        /*
         *  Ideally, we would have a setRawWatchlist() equivalent in the API, and as such
         *  make title(s) varargs. Then we can do away with watchInternal() and this method
         *  will consist of the following:
         *
         *  watchlist.addAll(Arrays.asList(titles);
         *  setRawWatchlist(watchlist.toArray(new String[0]));
         */
        watchInternal(title, false);
        watchlist.add(title);
    }
 
    /**
     *  Removes a page from the watchlist. You need to be logged in to use
     *  this. (Does not do anything if the page is not watched).
     *
     *  @param title the page to remove from the watchlist.
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @see #watch
     *  @since 0.18
     */
    public void unwatch(String title) throws IOException, CredentialNotFoundException
    {
        watchInternal(title, true);
        watchlist.remove(title);
    }
 
    /**
     *  Internal method for interfacing with the watchlist, since the API URLs
     *  for (un)watching are very similar.
     *
     *  @param title the title to (un)watch
     *  @param unwatch whether we should unwatch this page
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @see #watch
     *  @see #unwatch
     *  @since 0.18
     */
    protected void watchInternal(String title, boolean unwatch) throws IOException, CredentialNotFoundException
    {
        // create the watchlist cache
        String state = unwatch ? "unwatch" : "watch";
        if (watchlist == null)
            getRawWatchlist();
        String url = query + "action=watch&title=" + URLEncoder.encode(title, "UTF-8");
        if (unwatch)
            url += "&unwatch";
        fetch(url, state, false);
        log(Level.INFO, "Successfully " + state + "ed " + title, state);
    }
 
    /**
     *  Fetches the list of titles on the currently logged in user's watchlist.
     *  Equivalent to [[Special:Watchlist/raw]].
     *  @return the contents of the watchlist
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @since 0.18
     */
    public String[] getRawWatchlist() throws IOException, CredentialNotFoundException
    {
        return getRawWatchlist(true);
    }
 
    /**
     *  Fetches the list of titles on the currently logged in user's watchlist.
     *  Equivalent to [[Special:Watchlist/raw]].
     *  @param cache whether we should use the watchlist cache
     *  (no online activity, if the cache exists)
     *  @return the contents of the watchlist
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @since 0.18
     */
    public String[] getRawWatchlist(boolean cache) throws IOException, CredentialNotFoundException
    {
        // filter anons
        if (user == null)
            throw new CredentialNotFoundException("The watchlist is available for registered users only.");
 
        // cache
        if (watchlist != null && cache)
            return watchlist.toArray(new String[0]);
 
        // set up some things
        String url = query + "action=query&list=watchlistraw&wrlimit=max";
        String wrcontinue = "";
        watchlist = new ArrayList<String>(750);
        // fetch the watchlist
        do
        {
            String line = fetch(url + wrcontinue, "getRawWatchlist", false);
            // set continuation parameter
            int a = line.indexOf("wrcontinue=\"") + 12;
            if (a > 12)
            {
                int b = line.indexOf("\"", a);
                wrcontinue = "&wrcontinue=" + URLEncoder.encode(line.substring(a, b), "UTF-8");
            }
            else
                wrcontinue = "done";
            // parse the xml
            while (line.contains("<wr "))
            {
                a = line.indexOf("title=\"") + 7;
                int b = line.indexOf("\"", a);
                String title = line.substring(a, b);
                // is this supposed to not retrieve talk pages?
                if (namespace(title) % 2 == 0)
                    watchlist.add(title);
                line = line.substring(b);
            }
        }
        while (!wrcontinue.equals("done"));
        // log
        log(Level.INFO, "Successfully retrieved raw watchlist (" + watchlist.size() + " items)", "getRawWatchlist");
        return watchlist.toArray(new String[0]);
    }
 
    /**
     *  Determines whether a page is watched. (Uses a cache).
     *  @param title the title to be checked
     *  @return whether that page is watched
     *  @throws IOException if a network error occurs
     *  @throws CredentialNotFoundException if not logged in
     *  @since 0.18
     */
    public boolean isWatched(String title) throws IOException, CredentialNotFoundException
    {
        // populate the watchlist cache
        if (watchlist == null)
            getRawWatchlist();
        return watchlist.contains(title);
    }
 
    // LISTS
 
    /**
     *  Performs a full text search of the wiki. Equivalent to
     *  [[Special:Search]], or that little textbox in the sidebar.
     *
     *  @param search a search string
     *  @param namespaces the namespaces to search. If no parameters are passed
     *  then the default is MAIN_NAMESPACE only.
     *  @return the search results
     *  @throws IOException if a network error occurs
     *  @since 0.14
     */
    public String[] search(String search, int... namespaces) throws IOException
    {
        // this varargs thing is really handy, there's no need to define a
        // separate search(String search) while allowing multiple namespaces
 
        // default to main namespace
        if (namespaces.length == 0)
            namespaces = new int[] { MAIN_NAMESPACE };
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=search&srwhat=text&srlimit=max&srsearch=");
        url.append(URLEncoder.encode(search, "UTF-8"));
        url.append("&srnamespace=");
        for (int i = 0; i < namespaces.length; i++)
        {
            url.append(namespaces[i]);
            if (i != namespaces.length - 1)
                url.append("|");
        }
        url.append("&sroffset=");
 
        // some random variables we need later
        boolean done = false;
        ArrayList<String> results = new ArrayList<String>(5000);
 
        // fetch and iterate through the search results
        while (!done)
        {
            String line = fetch(url.toString() + results.size(), "search", false);
 
            // if this is the last page of results then there is no sroffset parameter
            if (!line.contains("sroffset=\""))
                done = true;
 
            // strip the search results
            // typical form: <p ns="0" title="Main Page" />
            while (line.contains("title=\""))
            {
                int a = line.indexOf("title=\"") + 7;
                int b = line.indexOf("\"", a);
                results.add(line.substring(a, b));
                line = line.substring(b);
            }
        }
        log(Level.INFO, "Successfully searched for string \"" + search + "\" (" + results.size() + " items found)", "search");
        return results.toArray(new String[0]);
    }
 
    /**
     *  Returns a list of pages which the use the specified image.
     *  @param image the image (Example.png, not File:Example.png)
     *  @return the list of pages that use this image
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String[] imageUsage(String image) throws IOException
    {
        return imageUsage(image, ALL_NAMESPACES);
    }
 
    /**
     *  Returns a list of pages in the specified namespace which use the
     *  specified image.
     *  @param image the image (Example.png, not File:Example.png)
     *  @param namespace a namespace
     *  @return the list of pages that use this image
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String[] imageUsage(String image, int namespace) throws IOException
    {
        String url = query + "action=query&list=imageusage&iutitle=File:" + URLEncoder.encode(image, "UTF-8") + "&iulimit=max";
        if (namespace != ALL_NAMESPACES)
            url += "&iunamespace=" + namespace;
 
        // fiddle
        ArrayList<String> pages = new ArrayList<String>(1333);
        String next = "";
        do
        {
            // connect
            if (pages.size() != 0)
                next = "&iucontinue="  + next;
            String line = fetch(url + next, "imageUsage", false);
 
            // set continuation parameter
            if (line.contains("iucontinue"))
            {
                int a = line.indexOf("iucontinue") + 12;
                next = line.substring(a, line.indexOf("\" />", a));
            }
            else
                next = "done";
 
            // parse
            while (line.contains("title"))
            {
                int x = line.indexOf("title=\"");
                int y = line.indexOf("\" />", x);
                pages.add(decode(line.substring(x + 7, y)));
                line = line.substring(y + 4, line.length());
            }
        }
        while (!next.equals("done"));
        log(Level.INFO, "Successfully retrieved usages of File:" + image + " (" + pages.size() + " items)", "imageUsage");
        return pages.toArray(new String[0]);
    }
 
    /**
     *  Returns a list of all pages linking to this page. Equivalent to
     *  [[Special:Whatlinkshere]].
     *
     *  @param title the title of the page
     *  @return the list of pages linking to the specified page
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String[] whatLinksHere(String title) throws IOException
    {
        return whatLinksHere(title, ALL_NAMESPACES, false);
    }
 
    /**
     *  Returns a list of all pages linking to this page. Equivalent to
     *  [[Special:Whatlinkshere]].
     *
     *  @param title the title of the page
     *  @param namespace a namespace
     *  @return the list of pages linking to the specified page
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String[] whatLinksHere(String title, int namespace) throws IOException
    {
        return whatLinksHere(title, namespace, false);
    }
 
    /**
     *  Returns a list of all pages linking to this page within the specified
     *  namespace. Alternatively, we can retrive a list of what redirects to a
     *  page by setting <tt>redirects</tt> to true. Equivalent to
     *  [[Special:Whatlinkshere]].
     *
     *  @param title the title of the page
     *  @param namespace a namespace
     *  @param redirects whether we should limit to redirects only
     *  @return the list of pages linking to the specified page
     *  @throws IOException if a network error occurs
     *  @since 0.10
     */
    public String[] whatLinksHere(String title, int namespace, boolean redirects) throws IOException
    {
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=backlinks&bllimit=max&bltitle=");
        url.append(URLEncoder.encode(title, "UTF-8"));
        if (namespace != ALL_NAMESPACES)
        {
            url.append("&blnamespace=");
            url.append(namespace);
        }
        if (redirects)
            url.append("&blfilterredir=redirects");
 
        // main loop
        ArrayList<String> pages = new ArrayList<String>(6667); // generally enough
        String temp = url.toString();
        String next = "";
        do
        {
            // fetch data
            String line = fetch(temp + next, "whatLinksHere", false);
 
            // set next starting point
            if (line.contains("blcontinue"))
            {
                int a = line.indexOf("blcontinue=\"") + 12;
                int b = line.indexOf("\"", a);
                next = "&blcontinue=" + line.substring(a, b);
            }
            else
                next = "done";
 
            // parse items
            while (line.contains("title"))
            {
                int x = line.indexOf("title=\"");
                int y = line.indexOf("\" ", x);
                pages.add(decode(line.substring(x + 7, y)));
                line = line.substring(y + 4, line.length());
            }
        }
        while (!next.equals("done"));
 
        log(Level.INFO, "Successfully retrieved " + (redirects ? "redirects to " : "links to ") + title + " (" + pages.size() + " items)", "whatLinksHere");
        return pages.toArray(new String[0]);
    }
 
    /**
     *  Returns a list of all pages transcluding to a page.
     *
     *  @param title the title of the page, e.g. "Template:Stub"
     *  @return the list of pages transcluding the specified page
     *  @throws IOException if a netwrok error occurs
     *  @since 0.12
     */
    public String[] whatTranscludesHere(String title) throws IOException
    {
        return whatTranscludesHere(title, ALL_NAMESPACES);
    }
 
    /**
     *  Returns a list of all pages transcluding to a page within the specified
     *  namespace.
     *
     *  @param title the title of the page, e.g. "Template:Stub"
     *  @param namespace a namespace
     *  @return the list of pages transcluding the specified page
     *  @throws IOException if a netwrok error occurs
     *  @since 0.12
     */
    public String[] whatTranscludesHere(String title, int namespace) throws IOException
    {
        String url = query + "action=query&list=embeddedin&eilimit=max&eititle=" + URLEncoder.encode(title, "UTF-8");
        if (namespace != ALL_NAMESPACES)
            url += "&einamespace=" + namespace;
 
        // main loop
        ArrayList<String> pages = new ArrayList<String>(6667); // generally enough
        String next = "";
        do
        {
            // fetch data
            String line = fetch(url + next, "whatTranscludesHere", false);
 
            // set next starting point
            if (line.contains("eicontinue"))
            {
                int a = line.indexOf("eicontinue=\"") + 12;
                int b = line.indexOf("\"", a);
                next = "&eicontinue=" + line.substring(a, b);
            }
            else
                next = "done";
 
            // parse items
            while (line.contains("title"))
            {
                int x = line.indexOf("title=\"");
                int y = line.indexOf("\" ", x);
                pages.add(decode(line.substring(x + 7, y)));
                line = line.substring(y + 4, line.length());
            }
        }
        while (!next.equals("done"));
        log(Level.INFO, "Successfully retrieved transclusions of " + title + " (" + pages.size() + " items)", "whatTranscludesHere");
        return pages.toArray(new String[0]);
    }
 
    /**
     *  Gets the members of a category.
     *
     *  @param name the name of the category (e.g. Candidates for speedy
     *  deletion, not Category:Candidates for speedy deletion)
     *  @return a String[] containing page titles of members of the category
     *  @throws IOException if a network error occurs
     *  @since 0.02
     */
    public String[] getCategoryMembers(String name) throws IOException
    {
        return getCategoryMembers(name, ALL_NAMESPACES);
    }
 
    /**
     *  Gets the members of a category.
     *
     *  @param name the name of the category (e.g. Candidates for speedy
     *  deletion, not Category:Candidates for speedy deletion)
     *  @param namespace filters by namespace, returns empty if namespace
     *  does not exist
     *  @return a String[] containing page titles of members of the category
     *  @throws IOException if a network error occurs
     *  @since 0.03
     */
    public String[] getCategoryMembers(String name, int namespace) throws IOException
    {
        // WARNING: currently broken on Wikimedia, but should be fixed when
        // r53304 goes live
        String url = query + "action=query&list=categorymembers&cmprop=title&cmlimit=max&cmtitle=Category:" + URLEncoder.encode(name, "UTF-8");
        if (namespace != ALL_NAMESPACES)
            url += "&cmnamespace=" + namespace;
 
        // work around an arbitrary and silly limitation
        ArrayList<String> members = new ArrayList<String>(6667); // enough for most cats
        String next = "";
        do
        {
            if (members.size() != 0)
                next = "&cmcontinue=" + URLEncoder.encode(next, "UTF-8");
            String line = fetch(url + next, "getCategoryMembers", false);
 
            // parse
            if (line.contains("cmcontinue"))
            {
                int a = line.indexOf("cmcontinue") + 12;
                next = line.substring(a, line.indexOf("\" />", a));
            }
            else
                next = "done";
 
            // parse
            while (line.contains("title"))
            {
                int x = line.indexOf("title=\"");
                int y = line.indexOf("\" />", x);
                members.add(decode(line.substring(x + 7, y)));
                line = line.substring(y + 4, line.length());
            }
        }
        while (!next.equals("done"));
        log(Level.INFO, "Successfully retrieved contents of Category:" + name + " (" + members.size() + " items)", "getCategoryMembers");
        return members.toArray(new String[0]);
    }
 
    /**
     *  Searches the wiki for external links. Equivalent to [[Special:Linksearch]].
     *  Returns two lists, where the first is the list of pages and the
     *  second is the list of urls. The index of a page in the first list
     *  corresponds to the index of the url on that page in the second list.
     *  Wildcards (*) are only permitted at the start of the search string.
     *
     *  @param pattern the pattern (String) to search for (e.g. example.com,
     *  *.example.com)
     *  @throws IOException if a network error occurs
     *  @return two lists - index 0 is the list of pages (String), index 1 is
     *  the list of urls (instance of <tt>java.net.URL</tt>)
     *  @since 0.06
     */
    public ArrayList[] spamsearch(String pattern) throws IOException
    {
        return spamsearch(pattern, ALL_NAMESPACES);
    }
 
    /**
     *  Searches the wiki for external links. Equivalent to [[Special:Linksearch]].
     *  Returns two lists, where the first is the list of pages and the
     *  second is the list of urls. The index of a page in the first list
     *  corresponds to the index of the url on that page in the second list.
     *  Wildcards (*) are only permitted at the start of the search string.
     *
     *  @param pattern the pattern (String) to search for (e.g. example.com,
     *  *.example.com)
     *  @param namespace filters by namespace, returns empty if namespace
     *  does not exist
     *  @throws IOException if a network error occurs
     *  @return two lists - index 0 is the list of pages (String), index 1 is
     *  the list of urls (instance of <tt>java.net.URL</tt>)
     *  @since 0.06
     */
    public ArrayList[] spamsearch(String pattern, int namespace) throws IOException
    {
        // set it up
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=exturlusage&euprop=title|url&euquery=");
        url.append(pattern);
        url.append("&eulimit=max");
        if (namespace != ALL_NAMESPACES)
        {
            url.append("&eunamespace=");
            url.append(namespace);
        }
        url.append("&euoffset=");
 
        // some variables we need later
        boolean done = false;
        ArrayList[] ret = new ArrayList[] // no reason for more than 500 spamlinks
        {
            new ArrayList<String>(667), // page titles
            new ArrayList<URL>(667) // urls
        };
 
        // begin
        while (!done)
        {
            // if this is the last page of results then there is no euoffset parameter
            String line = fetch(url.toString() + ret[0].size(), "spamsearch", false);
            if (!line.contains("euoffset=\""))
                done = true;
 
            // parse
            // typical form: <eu ns="0" title="Main Page" url="http://example.com" />
            while (line.contains("<eu "))
            {
                int x = line.indexOf("title=\"");
                int y = line.indexOf("\" url=\"");
                int z = line.indexOf("\" />", y);
 
                String title = line.substring(x + 7, y);
                String link = line.substring(y + 7, z);
                ret[0].add(decode(title));
                ret[1].add(new URL(link));
                line = line.substring(z + 3);
            }
        }
 
        // return value
        log(Level.INFO, "Successfully returned instances of external link " + pattern + " (" + ret[0].size() + " links)", "spamsearch");
        return ret;
    }
 
    /**
     *  Looks up a particular user in the IP block list, i.e. whether a user
     *  is currently blocked. Equivalent to [[Special:Ipblocklist]].
     *
     *  @param user a username or IP (e.g. "127.0.0.1")
     *  @return the block log entry
     *  @throws IOException if a network error occurs
     *  @since 0.12
     */
    public LogEntry[] getIPBlockList(String user) throws IOException
    {
        return getIPBlockList(user, null, null, 1);
    }
 
    /**
     *  Lists currently operating blocks that were made in the specified
     *  interval. Equivalent to [[Special:Ipblocklist]].
     *
     *  @param start the start date
     *  @param end the end date
     *  @return the currently operating blocks that were made in that interval
     *  @throws IOException if a network error occurs
     *  @since 0.12
     */
    public LogEntry[] getIPBlockList(Calendar start, Calendar end) throws IOException
    {
        return getIPBlockList("", start, end, Integer.MAX_VALUE);
    }
 
    /**
     *  Fetches part of the list of currently operational blocks. Equivalent to
     *  [[Special:Ipblocklist]]. WARNING: cannot tell whether a particular IP
     *  is autoblocked as this is non-public data (see also [[bugzilla:12321]]
     *  and [[foundation:Privacy policy]]). Don't call this directly, use one
     *  of the two above methods instead.
     *
     *  @param user a particular user that might have been blocked. Use "" to
     *  not specify one. May be an IP (e.g. "127.0.0.1") or a CIDR range (e.g.
     *  "127.0.0.0/16") but not an autoblock (e.g. "#123456").
     *  @param start what timestamp to start. Use null to not specify one.
     *  @param end what timestamp to end. Use null to not specify one.
     *  @param amount the number of blocks to retrieve. Use
     *  <tt>Integer.MAX_VALUE</tt> to not specify one.
     *  @return a LogEntry[] of the blocks
     *  @throws IOException if a network error occurs
     *  @throws IllegalArgumentException if start date is before end date
     *  @since 0.12
     */
    protected LogEntry[] getIPBlockList(String user, Calendar start, Calendar end, int amount) throws IOException
    {
        // quick param check
        if (start != null && end != null)
            if (start.before(end))
                throw new IllegalArgumentException("Specified start date is before specified end date!");
        String bkstart = calendarToTimestamp(start == null ? new GregorianCalendar() : start);
 
        // url base
        StringBuilder urlBase = new StringBuilder(query);
        urlBase.append("action=query&list=blocks");
        if (end != null)
        {
            urlBase.append("&bkend=");
            urlBase.append(calendarToTimestamp(end));
        }
        if (!user.equals(""))
        {
            urlBase.append("&bkusers=");
            urlBase.append(user);
        }
        urlBase.append("&bklimit=");
        urlBase.append(amount < max ? amount : max);
        urlBase.append("&bkstart=");
 
        // connection
        ArrayList<LogEntry> entries = new ArrayList<LogEntry>(1333);
		do
        {
            String line = fetch(urlBase.toString() + bkstart, "getIPBlockList", false);
 
            // set start parameter to new value if required
            if (line.contains("bkstart"))
            {
                int a = line.indexOf("bkstart=\"") + 9;
                bkstart = line.substring(a, line.indexOf("\"", a));
            }
            else
                bkstart = "done";
 
            // parse xml
            while (entries.size() < amount && line.contains("<block "))
            {
                // find entry
                int a = line.indexOf("<block ");
                int b = line.indexOf("/>", a);
                entries.add(parseLogEntry(line.substring(a, b), 1));
                line = line.substring(b);
            }
        }
        while (!bkstart.equals("done") && entries.size() < amount);
 
        // log statement
        StringBuilder logRecord = new StringBuilder("Successfully fetched IP block list ");
        if (!user.equals(""))
        {
            logRecord.append(" for ");
            logRecord.append(user);
        }
        if (start != null)
        {
            logRecord.append(" from ");
            logRecord.append(start.getTime().toString());
        }
        if (end != null)
        {
            logRecord.append(" to ");
            logRecord.append(end.getTime().toString());
        }
        logRecord.append(" (");
        logRecord.append(entries.size());
        logRecord.append(" entries)");
        log(Level.INFO, logRecord.toString(), "getIPBlockList");
        return entries.toArray(new LogEntry[0]);
     }
 
    /**
     *  Gets the most recent set of log entries up to the given amount.
     *  Equivalent to [[Special:Log]].
     *
     *  @param amount the amount of log entries to get
     *  @return the most recent set of log entries
     *  @throws IOException if a network error occurs
     *  @throws IllegalArgumentException if amount < 1
     *  @since 0.08
     */
    public LogEntry[] getLogEntries(int amount) throws IOException
    {
        return getLogEntries(null, null, amount, ALL_LOGS, null, "", ALL_NAMESPACES);
    }
 
    /**
     *  Gets log entries for a specific user. Equivalent to [[Special:Log]]. Dates
     *  and timestamps are in UTC.
     *
     *  @param user the user to get log entries for
     *  @throws IOException if a network error occurs
     *  @return the set of log entries created by that user
     *  @since 0.08
     */
    public LogEntry[] getLogEntries(User user) throws IOException
    {
        return getLogEntries(null, null, Integer.MAX_VALUE, ALL_LOGS, user, "", ALL_NAMESPACES);
    }
 
    /**
     *  Gets the log entries representing actions that were performed on a
     *  specific target. Equivalent to [[Special:Log]]. Dates and timestamps are
     *  in UTC.
     *
     *  @param target the target of the action(s).
     *  @throws IOException if a network error occurs
     *  @return the specified log entries
     *  @since 0.08
     */
    public LogEntry[] getLogEntries(String target) throws IOException
    {
        return getLogEntries(null, null, Integer.MAX_VALUE, ALL_LOGS, null, target, ALL_NAMESPACES);
    }
 
    /**
     *  Gets all log entries that occurred between the specified dates.
     *  WARNING: the start date is the most recent of the dates given, and
     *  the order of enumeration is from newest to oldest. Equivalent to
     *  [[Special:Log]]. Dates and timestamps are in UTC.
     *
     *  @param start what timestamp to start. Use null to not specify one.
     *  @param end what timestamp to end. Use null to not specify one.
     *  @throws IOException if something goes wrong
     *  @throws IllegalArgumentException if start &lt; end
     *  @return the specified log entries
     *  @since 0.08
     */
    public LogEntry[] getLogEntries(Calendar start, Calendar end) throws IOException
    {
        return getLogEntries(start, end, Integer.MAX_VALUE, ALL_LOGS, null, "", ALL_NAMESPACES);
    }
 
    /**
     *  Gets the last how ever many log entries in the specified log. Equivalent
     *  to [[Special:Log]] and [[Special:Newimages]] when
     *  <tt>type.equals(UPLOAD_LOG)</tt>. Dates and timestamps are in UTC.
     *
     *  @param amount the number of entries to get
     *  @param type what log to get (e.g. DELETION_LOG)
     *  @throws IOException if a network error occurs
     *  @throws IllegalArgumentException if the log type doesn't exist
     *  @return the specified log entries
     */
    public LogEntry[] getLogEntries(int amount, String type) throws IOException
    {
        return getLogEntries(null, null, amount, type, null, "", ALL_NAMESPACES);
    }
 
    /**
     *  Gets the specified amount of log entries between the given times by
     *  the given user on the given target. Equivalent to [[Special:Log]].
     *  WARNING: the start date is the most recent of the dates given, and
     *  the order of enumeration is from newest to oldest. Dates and timestamps
     *  are in UTC.
     *
     *  @param start what timestamp to start. Use null to not specify one.
     *  @param end what timestamp to end. Use null to not specify one.
     *  @param amount the amount of log entries to get. If both start and
     *  end are defined, this is ignored. Use Integer.MAX_VALUE to not
     *  specify one.
     *  @param log what log to get (e.g. DELETION_LOG)
     *  @param user the user performing the action. Use null not to specify
     *  one.
     *  @param target the target of the action. Use "" not to specify one.
     *  @param namespace filters by namespace. Returns empty if namespace
     *  doesn't exist.
     *  @throws IOException if a network error occurs
     *  @throws IllegalArgumentException if start &lt; end or amount &lt; 1
     *  @return the specified log entries
     *  @since 0.08
     */
    public LogEntry[] getLogEntries(Calendar start, Calendar end, int amount, String log, User user, String target, int namespace) throws IOException
    {
        // construct the query url from the parameters given
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=logevents&leprop=title|type|user|timestamp|comment|details");
        StringBuilder console = new StringBuilder("Successfully retrieved "); // logger statement
 
        // check for amount
        if (amount < 1)
            throw new IllegalArgumentException("Tried to retrieve less than one log entry!");
 
        // log type
        if (!log.equals(ALL_LOGS))
        {
            url.append("&letype=");
            url.append(log);
        }
 
        // specific log types
        if (log.equals(USER_CREATION_LOG))
            console.append("user creation");
        else if (log.equals(DELETION_LOG))
            console.append("deletion");
        else if (log.equals(PROTECTION_LOG))
            console.append("protection");
        else if (log.equals(USER_RIGHTS_LOG))
            console.append("user rights");
        else if (log.equals(USER_RENAME_LOG))
            console.append("user rename");
        else if (log.equals(BOT_STATUS_LOG))
            console.append("bot status");
        else
        {
            console.append(" ");
            console.append(log);
        }
        console.append(" log ");
 
        // check for user parameter
        if (user != null)
        {
            url.append("&leuser=");
            url.append(URLEncoder.encode(user.getUsername(), "UTF-8"));
            console.append("for ");
            console.append(user.getUsername());
            console.append(" ");
        }
 
        // check for target
        if (!target.equals(""))
        {
            url.append("&letitle=");
            url.append(URLEncoder.encode(target, "UTF-8"));
            console.append("on ");
            console.append(target);
            console.append(" ");
        }
 
        // set maximum
        url.append("&lelimit=");
        url.append(amount > max || namespace != ALL_NAMESPACES ? max : amount);
 
        // check for start/end dates
        String lestart = ""; // we need to account for lestart being the continuation parameter too.
        if (start != null)
        {
            if (end != null && start.before(end)) //aargh
                throw new IllegalArgumentException("Specified start date is before specified end date!");
            lestart = new String(calendarToTimestamp(start));
            console.append("from ");
            console.append(start.getTime().toString());
            console.append(" ");
        }
        if (end != null)
        {
            url.append("&leend=");
            url.append(calendarToTimestamp(end));
            console.append("to ");
            console.append(end.getTime().toString());
            console.append(" ");
        }
 
        // only now we can actually start to retrieve the logs
        ArrayList<LogEntry> entries = new ArrayList<LogEntry>(6667); // should be enough
        do
        {
            String line = fetch(url.toString() + "&lestart=" + lestart, "getLogEntries", false);
 
            // set start parameter to new value
            if (line.contains("lestart=\""))
            {
                int ab = line.indexOf("lestart=\"") + 9;
                lestart = line.substring(ab, line.indexOf("\"", ab));
            }
            else
                lestart = "done";
 
            // parse xml. We need to repeat the test because the XML may contain more than the required amount.
            while (line.contains("<item") && entries.size() < amount)
            {
                // find entry
                int a = line.indexOf("<item");
                // end may be " />" or "</item>", followed by next item
                int b = line.indexOf("><item", a);
                if (b < 0) // last entry
                    b = line.length();
                LogEntry entry = parseLogEntry(line.substring(a, b), 0);
                line = line.substring(b);
 
                // namespace processing
                if (namespace == ALL_NAMESPACES || namespace(entry.getTarget()) == namespace)
                    entries.add(entry);
            }
        }
        while (entries.size() < amount && !lestart.equals("done"));
 
        // log the success
        console.append(" (");
        console.append(entries.size());
        console.append(" entries)");
        log(Level.INFO, console.toString(), "getLogEntries");
        return entries.toArray(new LogEntry[0]);
    }
 
    /**
     *  Parses xml generated by <tt>getLogEntries()</tt>,
     *  <tt>getImageHistory()</tt> and <tt>getIPBlockList()</tt> into LogEntry
     *  objects. Override this if you want custom log types. NOTE: if
     *  RevisionDelete was used on a log entry, the relevant values will be
     *  null.
     *
     *  @param xml the xml to parse
     *  @param caller 1 if ipblocklist, 2 if imagehistory
     *  @return the parsed log entry
     *  @since 0.18
     */
    protected LogEntry parseLogEntry(String xml, int caller)
    {
        // if the caller is not getLogEntries(), we can take a shortcut.
        String type, action = null;
        if (caller == 1)
        {
            type = BLOCK_LOG;
            action = "block";
        }
        else if (caller == 2)
        {
            type = UPLOAD_LOG;
            action = "overwrite";
        }
        else
        {
            // log type
            int a = xml.indexOf("type=\"") + 6;
            int b = xml.indexOf("\" ", a);
            type = xml.substring(a, b);
 
            // action
            if (!xml.contains("actionhidden=\"")) // not oversighted
            {
                a = xml.indexOf("action=\"") + 8;
                b = xml.indexOf("\" ", a);
                action = xml.substring(a, b);
            }
        }
 
        // reason
        String reason;
        if (xml.contains("commenthidden=\""))
            reason = null;
        else if (type.equals(USER_CREATION_LOG)) // there is no reason for creating a user
            reason = "";
        else
        {
            int a = caller == 1 ? xml.indexOf("reason=\"") + 8 : xml.indexOf("comment=\"") + 9;
            int b = xml.indexOf("\"", a);
            reason = decode(xml.substring(a, b));
        }
 
        // target, performer
        String target = null;
        User performer = null;
        if (caller == 1) // RevisionDeleted entries don't appear in ipblocklist
        {
            // performer
            int a = xml.indexOf("by=\"") + 4;
            int b = xml.indexOf("\"", a);
            performer = new User(xml.substring(a, b));
 
            // target
            a = xml.indexOf("user=\"") + 6;
            if (a < 6) // autoblock, use block ID instead
            {
                a = xml.indexOf("id=\"") + 4;
                b = xml.indexOf("\" ", a);
                target = "#" + xml.substring(a, b);
            }
            else
            {
                b = xml.indexOf("\" ", a);
                target = xml.substring(a, b);
            }
        }
        // normal logs, not oversighted
        else if (!xml.contains("userhidden=\"") && xml.contains("title=\""))
        {
            // performer
            int a = xml.indexOf("user=\"") + 6;
            int b = xml.indexOf("\" ", a);
            performer = new User(xml.substring(a, b));
 
            // target
            a = xml.indexOf("title=\"") + 7;
            b = xml.indexOf("\" ", a);
            target = xml.substring(a, b);
        }
        else if (caller == 2)
        {
           // no title here, we can set that in getImageHistory
            int a = xml.indexOf("user=\"") + 6;
            int b = xml.indexOf("\" ", a);
            performer = new User(xml.substring(a, b));
        }
 
        // timestamp
        int a = xml.indexOf("timestamp=\"") + 11;
        int b = a + 20;
        String timestamp = convertTimestamp(xml.substring(a, b));
 
        // details
        Object details = null;
        if (xml.contains("commenthidden")) // oversighted
            details = null;
        else if (type.equals(MOVE_LOG))
        {
            a = xml.indexOf("new_title=\"") + 11;
            b = xml.indexOf("\" />", a);
            details = decode(xml.substring(a, b)); // the new title
        }
        else if (type.equals(BLOCK_LOG))
        {
            a = xml.indexOf("<block") + 7;
            String s = xml.substring(a);
            int c = caller == 1 ? s.indexOf("expiry=") + 8 : s.indexOf("duration=") + 10;
            if (c > 10) // not an unblock
            {
                int d = s.indexOf("\"", c);
                details = new Object[]
                {
                    s.indexOf("anononly") > -1, // anon-only
                    s.indexOf("nocreate") > -1, // account creation blocked
                    s.indexOf("noautoblock") > -1, // autoblock disabled
                    s.indexOf("noemail") > -1, // email disabled
                    s.indexOf("nousertalk") > -1, // cannot edit talk page
                    s.substring(c, d) // duration
                };
            }
        }
        else if (type.equals(PROTECTION_LOG))
        {
            if (action.equals("unprotect"))
                details = null;
            else
            {
                a = xml.indexOf("<param>") + 7;
                b = xml.indexOf("</param>", a);
                String temp = xml.substring(a, b);
                if (action.equals("move_prot")) // moved protection settings
                    details = temp;
                else if (action.equals("protect") || action.equals("modify"))
                {
                    if (temp.contains("create=sysop"))
                        details = PROTECTED_DELETED_PAGE;
                    else if (temp.contains("edit=sysop"))
                        details = FULL_PROTECTION;
                    else if (temp.contains("move=autoconfirmed"))
                        details = SEMI_PROTECTION;
                    else if (temp.contains("edit=autoconfirmed"))
                        details = SEMI_AND_MOVE_PROTECTION;
                    else if (temp.contains("move=sysop"))
                        details = MOVE_PROTECTION;
                    else
                        details = -2; // unrecognized
                }
            }
        }
        else if (type.equals(USER_RENAME_LOG))
        {
            a = xml.indexOf("<param>") + 7;
            b = xml.indexOf("</param>", a);
                details = xml.substring(a, b); // the new username
        }
        else if (type.equals(USER_RIGHTS_LOG))
        {
            a = xml.indexOf("new=\"") + 5;
            b = xml.indexOf("\"", a);
            String z = xml.substring(a, b);
            int rights = 1; // no ips in user rights log
            rights += (z.indexOf("sysop") != -1 ? ADMIN : 0); // sysop
            rights += (z.indexOf("bureaucrat") != -1 ? BUREAUCRAT : 0); // bureaucrat
            rights += (z.indexOf("steward") != -1 ? STEWARD : 0); // steward
            rights += (z.indexOf("bot") != -1 ? BOT : 0); // bot
            details = new Integer(rights);
        }
 
        return new LogEntry(type, action, reason, performer, target, timestamp, details);
    }
 
    /**
     *  Lists pages that start with a given prefix. Equivalent to
     *  [[Special:Prefixindex]].
     *
     *  @param prefix the prefix
     *  @return the list of pages with that prefix
     *  @throws IOException if a network error occurs
     *  @since 0.15
     */
    public String[] prefixIndex(String prefix) throws IOException
    {
        return listPages(prefix, NO_PROTECTION, ALL_NAMESPACES, -1, -1);
    }
 
    /**
     *  List pages below a certain size in the main namespace. Equivalent to
     *  [[Special:Shortpages]].
     *  @param cutoff the maximum size in bytes these short pages can be
     *  @return pages below that size
     *  @throws IOException if a network error occurs
     *  @since 0.15
     */
    public String[] shortPages(int cutoff) throws IOException
    {
        return listPages("", NO_PROTECTION, MAIN_NAMESPACE, -1, cutoff);
    }
 
    /**
     *  List pages below a certain size in any namespace. Equivalent to
     *  [[Special:Shortpages]].
     *  @param cutoff the maximum size in bytes these short pages can be
     *  @param namespace a namespace
     *  @throws IOException if a network error occurs
     *  @return pages below that size in that namespace
     *  @since 0.15
     */
    public String[] shortPages(int cutoff, int namespace) throws IOException
    {
        return listPages("", NO_PROTECTION, namespace, -1, cutoff);
    }
 
    /**
     *  List pages above a certain size in the main namespace. Equivalent to
     *  [[Special:Longpages]].
     *  @param cutoff the minimum size in bytes these long pages can be
     *  @return pages above that size
     *  @throws IOException if a network error occurs
     *  @since 0.15
     */
    public String[] longPages(int cutoff) throws IOException
    {
        return listPages("", NO_PROTECTION, MAIN_NAMESPACE, cutoff, -1);
    }
 
    /**
     *  List pages above a certain size in any namespace. Equivalent to
     *  [[Special:Longpages]].
     *  @param cutoff the minimum size in nbytes these long pages can be
     *  @param namespace a namespace
     *  @return pages above that size
     *  @throws IOException if a network error occurs
     *  @since 0.15
     */
    public String[] longPages(int cutoff, int namespace) throws IOException
    {
        return listPages("", NO_PROTECTION, namespace, cutoff, -1);
    }
 
    /**
     *  Lists pages with titles containing a certain prefix with a certain
     *  protection level and in a certain namespace. Equivalent to
     *  [[Special:Allpages]], [[Special:Prefixindex]], [[Special:Protectedpages]]
     *  and [[Special:Allmessages]] (if namespace == MEDIAWIKI_NAMESPACE).
     *  WARNING: Limited to 500 values (5000 for bots), unless a prefix or
     *  protection level is specified.
     *
     *  @param prefix the prefix of the title. Use "" to not specify one.
     *  @param level a protection level. Use NO_PROTECTION to not specify one.
     *  WARNING: it is not currently possible to specify a combination of both
     *  semi and move protection
     *  @param namespace a namespace. ALL_NAMESPACES is not suppported, an
     *  UnsupportedOperationException will be thrown.
     *  @return the specified list of pages
     *  @since 0.09
     *  @throws IOException if a network error occurs
     */
    public String[] listPages(String prefix, int level, int namespace) throws IOException
    {
        return listPages(prefix, level, namespace, -1, -1);
    }
 
    /**
     *  Lists pages with titles containing a certain prefix with a certain
     *  protection level and in a certain namespace. Equivalent to
     *  [[Special:Allpages]], [[Special:Prefixindex]], [[Special:Protectedpages]]
     *  [[Special:Allmessages]] (if namespace == MEDIAWIKI_NAMESPACE),
     *  [[Special:Shortpages]] and [[Special:Longpages]]. WARNING: Limited to
     *  500 values (5000 for bots), unless a prefix, (max|min)imum size or
     *  protection level is specified.
     *
     *  @param prefix the prefix of the title. Use "" to not specify one.
     *  @param level a protection level. Use NO_PROTECTION to not specify one.
     *  WARNING: it is not currently possible to specify a combination of both
     *  semi and move protection
     *  @param namespace a namespace. ALL_NAMESPACES is not suppported, an
     *  UnsupportedOperationException will be thrown.
     *  @param minimum the minimum size in bytes these pages can be. Use -1 to
     *  not specify one.
     *  @param maximum the maximum size in bytes these pages can be. Use -1 to
     *  not specify one.
     *  @return the specified list of pages
     *  @since 0.09
     *  @throws IOException if a network error occurs
     */
    public String[] listPages(String prefix, int level, int namespace, int minimum, int maximum) throws IOException
    {
        // @revised 0.15 to add short/long pages
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=allpages&aplimit=max");
        if (!prefix.equals("")) // prefix
        {
            // cull the namespace prefix
            namespace = namespace(prefix);
            if (prefix.contains(":") && namespace != MAIN_NAMESPACE)
                prefix = prefix.substring(prefix.indexOf(":") + 1);
            url.append("&apprefix=");
            url.append(URLEncoder.encode(prefix, "UTF-8"));
        }
        else if (namespace == ALL_NAMESPACES) // check for namespace
            throw new UnsupportedOperationException("ALL_NAMESPACES not supported in MediaWiki API.");
        url.append("&apnamespace=");
        url.append(namespace);
        switch (level) // protection level
        {
            case NO_PROTECTION: // squelch, this is the default
                break;
            case SEMI_PROTECTION:
                url.append("&apprlevel=autoconfirmed&apprtype=edit");
                break;
            case FULL_PROTECTION:
                url.append("&apprlevel=sysop&apprtype=edit");
                break;
            case MOVE_PROTECTION:
                url.append("&apprlevel=sysop&apprtype=move");
                break;
            case SEMI_AND_MOVE_PROTECTION: // squelch, not implemented
                break;
            default:
                throw new IllegalArgumentException("Invalid protection level!");
        }
		// max and min
        if (minimum != -1)
        {
            url.append("&apminsize=");
			url.append(minimum);
        }
        if (maximum != -1)
        {
            url.append("&apmaxsize=");
			url.append(maximum);
        }
 
        // parse
        ArrayList<String> pages = new ArrayList<String>(6667);
        String next = "";
        do
        {
            // connect and read
            String s = url.toString();
            if (!next.equals(""))
                s += ("&apfrom=" + next);
            String line = fetch(s, "listPages", false);
 
            // don't set a continuation if no max, min, prefix or protection level
            if (maximum < 0 && minimum < 0 && prefix.equals("") && level == NO_PROTECTION)
                next = "done";
            // find next value
            else if (line.contains("apfrom="))
            {
                int a = line.indexOf("apfrom=\"") + 8;
                int b = line.indexOf("\"", a);
                next = URLEncoder.encode(line.substring(a, b), "UTF-8");
            }
            else
                next = "done";
 
            // find the pages
            while (line.contains("<p "))
            {
                int a = line.indexOf("title=\"") + 7;
                int b = line.indexOf("\" />", a);
                pages.add(decode(line.substring(a, b)));
                line = line.substring(b, line.length());
            }
        }
        while (!next.equals("done"));
 
        // tidy up
        log(Level.INFO, "Successfully retrieved page list (" + pages.size() + " pages)", "listPages");
        return pages.toArray(new String[0]);
    }
 
    /**
     *  Fetches the <tt>amount</tt> most recently created pages in the main
     *  namespace. WARNING: The recent changes table only stores new pages
     *  for about a month. It is not possible to retrieve changes before then.
     *
     *  @param amount the number of pages to fetch
     *  @return the titles of recently created pages that satisfy requirements
     *  above
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public String[] newPages(int amount) throws IOException
    {
        return newPages(amount, MAIN_NAMESPACE, 0);
    }
 
    /**
     *  Fetches the <tt>amount</tt> most recently created pages in the main
     *  namespace subject to the specified constraints. WARNING: The
     *  recent changes table only stores new pages for about a month. It is not
     *  possible to retrieve changes before then. Equivalent to
     *  [[Special:Newpages]].
     *
     *  @param rcoptions a bitmask of HIDE_ANON etc that dictate which pages
     *  we return (e.g. exclude patrolled pages => rcoptions = HIDE_PATROLLED).
     *  @param amount the amount of new pages to get
     *  @return the titles of recently created pages that satisfy requirements
     *  above
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public String[] newPages(int amount, int rcoptions) throws IOException
    {
        return newPages(amount, MAIN_NAMESPACE, rcoptions);
    }
 
    /**
     *  Fetches the <tt>amount</tt> most recently created pages in the
     *  specified namespace, subject to the specified constraints. WARNING: The
     *  recent changes table only stores new pages for about a month. It is not
     *  possible to retrieve changes before then. Equivalent to
     *  [[Special:Newpages]].
     *
     *  @param rcoptions a bitmask of HIDE_ANON etc that dictate which pages
     *  we return (e.g. exclude patrolled pages => rcoptions = HIDE_PATROLLED).
     *  @param amount the amount of new pages to get
     *  @param namespace the namespace to search (not ALL_NAMESPACES)
     *  @return the titles of recently created pages that satisfy requirements
     *  above
     *  @throws IOException if a network error occurs
     *  @since 0.20
     */
    public String[] newPages(int amount, int namespace, int rcoptions) throws IOException
    {
        StringBuilder url = new StringBuilder(query);
        url.append("action=query&list=recentchanges&rctype=new&rcprop=title&rclimit=max&rcnamespace=");
        url.append(namespace);
        // rc options
        if (rcoptions > 0)
        {
            url.append("&rcshow=");
            if ((rcoptions & HIDE_ANON) == HIDE_ANON)
                url.append("!anon|");
            if ((rcoptions & HIDE_SELF) == HIDE_SELF)
                url.append("!self|");
            if ((rcoptions & HIDE_MINOR) == HIDE_MINOR)
                url.append("!minor|");
            if ((rcoptions & HIDE_PATROLLED) == HIDE_PATROLLED)
                url.append("!patrolled|");
            if ((rcoptions & HIDE_BOT) == HIDE_BOT)
                url.append("!bot");
            // chop off last |
            url.deleteCharAt(url.length() - 1);
        }
 
        // fetch, parse
        url.append("&rcstart=");
        String rcstart = calendarToTimestamp(new GregorianCalendar());
        ArrayList<String> pages = new ArrayList<String>(750);
        do
        {
            String temp = url.toString();
            String line = fetch(temp + rcstart, "newPages", false);
 
            // set continuation parameter
            int a = line.indexOf("rcstart=\"") + 9;
            int b = line.indexOf("\"", a);
            rcstart = line.substring(a, b);
 
            while (line.contains("title="))
            {
                // typical form <rc type="new" ns="0" title="Article" />
                a = line.indexOf("title=\"") + 7;
                b = line.indexOf("\"", a);
                pages.add(line.substring(a, b));
                line = line.substring(b);
            }
        }
        while (pages.size() < amount);
        return pages.toArray(new String[0]);
    }
 
    // INNER CLASSES
 
    /**
     *  Subclass for wiki users.
     *  @since 0.05
     */
    public class User implements Cloneable
    {
        private String username;
        private int rights = -484; // cache for userRights()
 
        /**
         *  Creates a new user object. Does not create a new user on the
         *  wiki (we don't implement this for a very good reason). Shouldn't
         *  be called for anons.
         *
         *  @param username the username of the user
         *  @since 0.05
         */
        protected User(String username)
        {
            this.username = username;
        }
 
        /**
         *  Gets a user's rights. Returns a bitmark of the user's rights.
         *  See fields above (be aware that IP_USER = -1, but you shouldn't
         *  be calling from a null object anyway). Uses the cached value for
         *  speed.
         *
         *  @return a bitwise mask of the user's rights.
         *  @throws IOException if a network error occurs
         *  @since 0.05
         */
        public int userRights() throws IOException
        {
            return userRights(true);
        }
 
        /**
         *  Gets a user's rights. Returns a bitmark of the user's rights.
         *  See fields above (be aware that IP_USER = -1, but you
         *  shouldn't be calling from a null object anyway). The value
         *  returned is cached which is returned by default, specify
         *  <tt>cache = false</tt> to retrieve a new one.
         *
         *  @return a bitwise mask of the user's rights.
         *  @throws IOException if a network error occurs
         *  @param cache whether we should use the cached value
         *  @since 0.07
         */
        public int userRights(boolean cache) throws IOException
        {
            // retrieve cache (if valid)
            if (cache && rights != -484)
                return rights;
 
            // begin
            String url = query + "action=query&list=users&usprop=groups&ususers=" + URLEncoder.encode(username, "UTF-8");
            String line = fetch(url, "User.userRights", false);
 
            // parse
            ArrayList<String> members = new ArrayList<String>();
            while (line.contains("<g>"))
            {
                int x = line.indexOf("<g>");
                int y = line.indexOf("</g>");
                members.add(line.substring(x + 3, y));
                line = line.substring(y + 4, line.length());
            }
            log(Level.INFO, "Successfully retrived user rights for " + username, "User.userRights");
 
            int ret = 1;
            if (members.contains("sysop"))
                ret += ADMIN;
            if (members.contains("bureaucrat"))
                ret += BUREAUCRAT;
            if (members.contains("steward"))
                ret += STEWARD;
            if (members.contains("bot"))
                ret += BOT;
 
            //System.out.println(ret);
            // store
            rights = ret;
            return ret;
        }
 
        /**
         *  Gets this user's username. (Should have implemented this earlier).
         *  @return this user's username
         *  @since 0.08
         */
        public String getUsername()
        {
            return username;
        }
 
        /**
         *  Returns a log of the times when the user has been blocked.
         *  @return records of the occasions when this user has been blocked
         *  @throws IOException if something goes wrong
         *  @since 0.08
         */
        public LogEntry[] blockLog() throws IOException
        {
            return getLogEntries(null, null, Integer.MAX_VALUE, BLOCK_LOG, null, "User:" + username, USER_NAMESPACE);
        }
 
        /**
         *  Determines whether this user is blocked by looking it up on the IP
         *  block list.
         *  @return whether this user is blocked
         *  @throws IOException if we cannot retrieve the IP block list
         *  @since 0.12
         */
        public boolean isBlocked() throws IOException
        {
            // @revised 0.18 now check for errors after each edit, including blocks
            return getIPBlockList(username, null, null, 1).length != 0;
        }
 
        /**
         *  Fetches the internal edit count for this user, which includes all
         *  live edits and deleted edits after (I think) January 2007. If you
         *  want to count live edits only, use the slower
         *  <tt>int count = user.contribs().length;</tt>.
         *
         *  @return the user's edit count
         *  @throws IOException if a network error occurs
         *  @since 0.16
         */
        public int countEdits() throws IOException
        {
            String url = query + "action=query&list=users&usprop=editcount&ususers=" + URLEncoder.encode(username, "UTF-8");
            String line = fetch(url, "User.countEdits", false);
            int a = line.indexOf("editcount=\"") + 11;
            int b = line.indexOf("\"", a);
            return Integer.parseInt(line.substring(a, b));
        }
 
        /**
         *  Fetches the contributions for this user.
         *  @return a revision array of contributions
         *  @since 0.17
         */
        public Revision[] contribs() throws IOException
        {
            return Wiki.this.contribs(username);
        }
 
        /**
         *  Fetches the contributions for this user in a particular namespace.
         *  @param namespace a namespace
         *  @return a revision array of contributions
         *  @since 0.17
        */
        public Revision[] contribs(int namespace) throws IOException
        {
            return Wiki.this.contribs(username, namespace);
        }
 
        /**
         *  Copies this user object.
         *  @return the copy
         *  @since 0.08
         */
        public Object clone()
        {
            return new User(username);
        }
 
        /**
         *   Tests whether this user is equal to another one.
         *   @return whether the users are equal
         *   @since 0.08
         */
        public boolean equals(Object x)
        {
            if (!(x instanceof User))
                return false;
            return username.equals(((User)x).username);
        }
 
        /**
         *  Returns a string representation of this user.
         *  @return see above
         *  @since 0.17
         */
        public String toString()
        {
            return "User[username=" + username + ",rights=" + (rights == -484 ? "unset" : rights) + "]";
        }
 
        /**
         *  Returns a hashcode of this user.
         *  @return see above
         *  @since 0.19
         */
        public int hashCode()
        {
            return username.hashCode() * 2 + 1;
        }
    }
 
    /**
     *  A wrapper class for an entry in a wiki log, which represents an action
     *  performed on the wiki.
     *
     *  @see #getLogEntries
     *  @since 0.08
     */
    public class LogEntry implements Comparable<LogEntry>
    {
        // internal data storage
        private String type;
        private String action;
        private String reason;
        private User user;
        private String target;
        private Calendar timestamp;
        private Object details;
 
        /**
         *  Creates a new log entry. WARNING: does not perform the action
         *  implied. Use Wiki.class methods to achieve this.
         *
         *  @param type the type of log entry, one of USER_CREATION_LOG,
         *  DELETION_LOG, BLOCK_LOG, etc.
         *  @param action the type of action that was performed e.g. "delete",
         *  "unblock", "overwrite", etc.
         *  @param reason why the action was performed
         *  @param user the user who performed the action
         *  @param target the target of the action
         *  @param timestamp the local time when the action was performed.
         *  We will convert this back into a Calendar.
         *  @param details the details of the action (e.g. the new title of
         *  the page after a move was performed).
         *  @since 0.08
         */
        protected LogEntry(String type, String action, String reason, User user, String target, String timestamp, Object details)
        {
            this.type = type;
            this.action = action;
            this.reason = reason;
            this.user = user;
            this.target = target;
            this.timestamp = timestampToCalendar(timestamp);
            this.details = details;
        }
 
        /**
         *  Gets the type of log that this entry is in.
         *  @return one of DELETION_LOG, USER_CREATION_LOG, BLOCK_LOG, etc.
         *  @since 0.08
         */
        public String getType()
        {
            return type;
        }
 
        /**
         *  Gets a string description of the action performed, for example
         *  "delete", "protect", "overwrite", ... WARNING: returns null if the
         *  action was RevisionDeleted.
         *  @return the type of action performed
         *  @since 0.08
         */
        public String getAction()
        {
            return action;
        }
 
        /**
         *  Gets the reason supplied by the perfoming user when the action
         *  was performed. WARNING: returns null if the reason was
         *  RevisionDeleted.
         *  @return the reason the action was performed
         *  @since 0.08
         */
        public String getReason()
        {
            return reason;
        }
 
        /**
         *  Gets the user object representing who performed the action.
         *  WARNING: returns null if the user was RevisionDeleted.
         *  @return the user who performed the action.
         *  @since 0.08
         */
        public User getUser()
        {
            return user;
        }
 
        /**
         *  Gets the target of the action represented by this log entry. WARNING:
         *  returns null if the content was RevisionDeleted.
         *  @return the target of this log entry
         *  @since 0.08
         */
        public String getTarget()
        {
            return target;
        }
 
        /**
         *  Gets the timestamp of this log entry.
         *  @return the timestamp of this log entry
         *  @since 0.08
         */
        public Calendar getTimestamp()
        {
            return timestamp;
        }
 
        /**
         *  Gets the details of this log entry. Return values are as follows:
         *
         *  <table>
         *  <tr><th>Log type <th>Return value
         *  <tr><td>MOVE_LOG
         *      <td>The new page title
         *  <tr><td>USER_RENAME_LOG
         *      <td>The new username
         *  <tr><td>BLOCK_LOG 
         *      <td>new Object[] { boolean anononly, boolean nocreate, boolean noautoblock, boolean noemail, boolean nousertalk, String duration }
         *  <tr><td>USER_RIGHTS_LOG
         *      <td>The new user rights (Integer)
         *  <tr><td>PROTECTION_LOG
         *      <td>action == "protect" or "modify" => the protection level (int, -2 if unrecognized), action == "move_prot" => the old title, else null
         *  <tr><td>Others or RevisionDeleted
         *      <td>null
         *  </table>
         *
         *  Note that the duration of a block may be given as a period of time
         *  (e.g. "31 hours") or a timestamp (e.g. 20071216160302). To tell
         *  these apart, feed it into <tt>Long.parseLong()</tt> and catch any
         *  resulting exceptions.
         *
         *  @return the details of the log entry
         *  @since 0.08
         */
        public Object getDetails()
        {
            return details;
        }
 
        /**
         *  Returns a string representation of this log entry.
         *  @return a string representation of this object
         *  @since 0.08
         */
        public String toString()
        {
            // @revised 0.17 to a more traditional Java approach
            StringBuilder s = new StringBuilder("LogEntry[type=");
            s.append(type);
            s.append(",action=");
            s.append(action == null ? "[hidden]" : action);
            s.append(",user=");
            s.append(user == null ? "[hidden]" : user.getUsername());
            s.append(",timestamp=");
            s.append(calendarToTimestamp(timestamp));
            s.append(",target=");
            s.append(target == null ? "[hidden]" : target);
            s.append(",reason=");
            s.append(reason == null ? "[hidden]" : reason);
            s.append(",details=");
            if (details instanceof Object[])
                s.append(Arrays.asList((Object[])details)); // crude formatting hack
            else
                s.append(details);
            s.append("]");
            return s.toString();
        }
 
        /**
         *  Compares this log entry to another one based on the recentness
         *  of their timestamps.
         *  @param other the log entry to compare
         *  @return whether this object is equal to
         *  @since 0.18
         */
        public int compareTo(Wiki.LogEntry other)
        {
            if (timestamp.equals(other.timestamp))
                return 0; // might not happen, but
            return timestamp.after(other.timestamp) ? 1 : -1;
        }
    }
 
    /**
     *  Represents a contribution and/or a revision to a page.
     *  @since 0.17
     */
    public class Revision implements Comparable<Revision>
    {
        private boolean minor;
        private String summary;
        private long revid, rcid = -1;
        private Calendar timestamp;
        private String user;
        private String title;
 
        /**
         *  Constructs a new Revision object.
         *  @param revid the id of the revision (this is a long since
         *  {{NUMBEROFEDITS}} on en.wikipedia.org is now (November 2007) ~10%
         *  of <tt>Integer.MAX_VALUE</tt>
         *  @param timestamp when this revision was made
         *  @param article the concerned article
         *  @param summary the edit summary
         *  @param user the user making this revision (may be anonymous, if not
         *  use <tt>User.getUsername()</tt>)
         *  @param minor whether this was a minor edit
         *  @since 0.17
         */
        protected Revision(long revid, Calendar timestamp, String title, String summary, String user, boolean minor)
        {
            this.revid = revid;
            this.timestamp = timestamp;
            this.summary = summary;
            this.minor = minor;
            this.user = user;
			this.title = title;
        }
 
        /**
         *  Fetches the contents of this revision. WARNING: fails if the
         *  revision is deleted.
         *  @return the contents of the appropriate article at <tt>timestamp</tt>
         *  @throws IOException if a network error occurs
         *  @throws IllegalArgumentException if page == Special:Log/xxx.
         *  @since 0.17
         */
        public String getText() throws IOException
        {
            // logs have no content
            if (revid == -1L)
                throw new IllegalArgumentException("Log entries have no valid content!");
 
            // go for it
            String url = base + URLEncoder.encode(title, "UTF-8") + "&oldid=" + revid + "&action=raw";
            String temp = fetch(url, "Revision.getText", false);
            log(Level.INFO, "Successfully retrieved text of revision " + revid, "Revision.getText");
            return decode(temp);
        }
 
        /**
         *  Gets the rendered text of this revision. WARNING: fails if the
         *  revision is deleted.
         *  @return the rendered contents of the appropriate article at
         *  <tt>timestamp</tt>
         *  @throws IOException if a network error occurs
         *  @throws IllegalArgumentException if page == Special:Log/xxx.
         *  @since 0.17
         */
        public String getRenderedText() throws IOException
        {
            // logs have no content
            if (revid == -1L)
                throw new IllegalArgumentException("Log entries have no valid content!");
 
            // go for it
            String url = base + URLEncoder.encode(title, "UTF-8") + "&oldid=" + revid + "&action=render";
            String temp = fetch(url, "Revision.getRenderedText", false);
            log(Level.INFO, "Successfully retrieved rendered text of revision " + revid, "Revision.getRenderedText");
            return decode(temp);
        }
 
        /**
         *  Determines whether this Revision is the most recent revision of
         *  the relevant page.
         *
         *  @return see above
         *  @throws IOException if a network error occurs
         *  @since 0.17
         */
        public boolean isTop() throws IOException
        {
            String url = query + "action=query&prop=revisions&titles=" + URLEncoder.encode(title, "UTF-8") + "&rvlimit=1&rvprop=timestamp|ids";
            String line = fetch(url, "Revision.isTop", false);
            // fetch the oldid
            int a = line.indexOf("revid=\"") + 7;
            int b = line.indexOf("\"", a);
            long oldid2 = Long.parseLong(line.substring(a, b));
            return revid == oldid2;
        }
 
        /**
         *  Determines whether this Revision is equal to another object.
         *  @param o an object
         *  @return whether o is equal to this object
         *  @since 0.17
         */
        public boolean equals(Object o)
        {
            if (!(o instanceof Revision))
                return false;
            return toString().equals(o.toString());
        }
 
        /**
         *  Returns a hash code of this revision.
         *  @return a hash code
         *  @since 0.17
         */
        public int hashCode()
        {
            return (int)revid * 2 - Wiki.this.hashCode();
        }
 
        /**
         *  Checks whether this edit was marked as minor. See
         *  [[Help:Minor edit]] for details.
         *
         *  @return whether this revision was marked as minor
         *  @since 0.17
         */
        public boolean isMinor()
        {
            return minor;
        }
 
        /**
         *  Returns the edit summary for this revision. WARNING: returns null
         *  if the summary was RevisionDeleted.
         *  @return the edit summary
         *  @since 0.17
         */
        public String getSummary()
        {
            return summary;
        }
 
        /**
         *  Returns the user or anon who created this revision. You should
         *  pass this (if not an IP) to <tt>getUser(String)</tt> to obtain a
         *  User object. WARNING: returns null if the user was RevisionDeleted.
         *  @return the user or anon
         *  @since 0.17
         */
        public String getUser()
        {
            return user;
        }
 
        /**
         *  Returns the page to which this revision was made.
         *  @return the page
         *  @since 0.17
         */
        public String getPage()
        {
            return title;
        }
 
        /**
         *  Returns the oldid of this revision. Don't confuse this with
         *  <tt>rcid</tt>
         *  @return the oldid (long)
         *  @since 0.17
         */
        public long getRevid()
        {
            return revid;
        }
 
        /**
         *  Gets the time that this revision was made.
         *  @return the timestamp
         *  @since 0.17
         */
        public Calendar getTimestamp()
        {
            return timestamp;
        }
 
        /**
         *  Returns a string representation of this revision.
         *  @return see above
         *  @since 0.17
         */
        public String toString()
        {
            StringBuilder sb = new StringBuilder("Revision[oldid=");
            sb.append(revid);
            sb.append(",page=\"");
            sb.append(title);
            sb.append("\",user=");
            sb.append(user == null ? "[hidden]" : user);
            sb.append(",timestamp=");
            sb.append(calendarToTimestamp(timestamp));
            sb.append(",summary=\"");
            sb.append(summary == null ? "[hidden]" : summary);
            sb.append("\",minor=");
            sb.append(minor);
            sb.append(",rcid=");
            sb.append(rcid == -1 ? "unset" : rcid);
            sb.append("]");
            return sb.toString();
        }
 
        /**
         *  Compares this revision to another revision based on the recentness
         *  of their timestamps.
         *  @param other the revision to compare
         *  @return whether this object is equal to
         *  @since 0.18
         */
        public int compareTo(Wiki.Revision other)
        {
            if (timestamp.equals(other.timestamp))
                return 0; // might not happen, but
            return timestamp.after(other.timestamp) ? 1 : -1;
        }
 
        /**
         *  Sets the <tt>rcid</tt> of this revision, used for patrolling.
         *  This parameter is optional. I can't think of a good reason why
         *  this should be publicly editable.
         *  @param rcid the rcid of this revision (long)
         *  @since 0.17
         */
        protected void setRcid(long rcid)
        {
            this.rcid = rcid;
        }
 
        /**
         *  Gets the <tt>rcid</tt> of this revision for patrolling purposes.
         *  @return the rcid of this revision (long)
         *  @since 0.17
         */
        public long getRcid()
        {
            return rcid;
        }
 
        /**
         *  Reverts this revision using the rollback method. See
         *  <tt>Wiki.rollback()</tt>.
         *  @throws IOException if a network error occurs
         *  @throws CredentialNotFoundException if not logged in or user is not
         *  an admin
         *  @throws AccountLockedException if the user is blocked
         *  @since 0.19
         */
        public void rollback() throws IOException, LoginException
        {
            Wiki.this.rollback(this, false, "");
        }
 
        /**
         *  Reverts this revision using the rollback method. See
         *  <tt>Wiki.rollback()</tt>.
         *  @param bot mark this and the reverted revision(s) as bot edits
         *  @param reason (optional) a custom reason
         *  @throws IOException if a network error occurs
         *  @throws CredentialNotFoundException if not logged in or user is not
         *  an admin
         *  @throws AccountLockedException if the user is blocked
         *  @since 0.19
         */
        public void rollback(boolean bot, String reason) throws IOException, LoginException
        {
            Wiki.this.rollback(this, bot, reason);
        }
    }
 
    // INTERNALS
 
    // miscellany
 
    /**
     *  A generic URL content fetcher. This is only useful for GET requests,
     *  which is almost everything that doesn't modify the wiki. Might be
     *  useful for subclasses.
     *
     *  Here we also check the database lag and wait 30s if it exceeds
     *  <tt>maxlag</tt>. See [[mw:Manual:Maxlag parameter]] for the server-side
     *  analog (which isn't implemented here, because I'm too lazy to retry
     *  the request).
     *
     *  @param url the url to fetch
     *  @param caller the caller of this method
     *  @param write whether we need to fetch the cookies from this connection
     *  in a token-fetching exercise (edit() and friends)
     *  @throws IOException if a network error occurs
     *  @since 0.18
     */
    protected String fetch(String url, String caller, boolean write) throws IOException
    {
        // check the database lag
        logurl(url, caller);
        do // this is just a dummy loop
        {
            if (maxlag < 1) // disabled
                break;
            // only bother to check every 30 seconds
            if ((System.currentTimeMillis() - lastlagcheck) < 30000) // TODO: this really should be a preference
                break;
 
            try
            {
                // if we use this, this can block unrelated read requests while we edit a page
                synchronized(domain)
                {
                    // update counter. We do this before the actual check, so that only one thread does the check.
                    lastlagcheck = System.currentTimeMillis();
                    int lag = getCurrentDatabaseLag();
                    while (lag > maxlag)
                    {
                        log(Level.WARNING, "Sleeping for 30s as current database lag exceeds the maximum allowed value of " + maxlag + " s", caller);
                        Thread.sleep(30000);
                        lag = getCurrentDatabaseLag();
                    }
                }
            }
            catch (InterruptedException ex)
            {
                // nobody cares
            }
        }
        while (false);
 
        // connect
        URLConnection connection = new URL(url).openConnection();
        setCookies(connection, cookies);
        connection.connect();
        BufferedReader in = new BufferedReader(new InputStreamReader(new GZIPInputStream(connection.getInputStream()), "UTF-8"));
 
        // get the cookies
        if (write)
        {
            grabCookies(connection, cookies2);
            cookies2.putAll(cookies);
        }
 
        // get the text
        String line;
        StringBuilder text = new StringBuilder(100000);
        while ((line = in.readLine()) != null)
        {
            text.append(line);
            text.append("\n");
        }
        in.close();
        return text.toString();
    }
 
    /**
     *  Checks for errors from standard read/write requests.
     *  @param line the response from the server to analyze
     *  @param caller what we tried to do
     *  @throws AccountLockedException if the user is blocked
     *  @throws HttpRetryException if the database is locked or action was
     *  throttled and a retry failed
     *  @throws UnknownError in the case of a MediaWiki bug
     *  @since 0.18
     */
    protected void checkErrors(String line, String caller) throws IOException, LoginException
    {
        // System.out.println(line);
        // empty response from server
        if (line.equals(""))
            throw new UnknownError("Received empty response from server!");
        // successful
        if (line.contains("result=\"Success\""))
            return;
        // rate limit (automatic retry), though might be a long one (e.g. email)
        if (line.contains("error code=\"ratelimited\""))
        {
            log(Level.WARNING, "Server-side throttle hit.", caller);
            throw new HttpRetryException("Action throttled.", 503);
        }
        // blocked!
        if (line.contains("error code=\"blocked") || line.contains("error code=\"autoblocked\""))
        {
            log(Level.SEVERE, "Cannot " + caller + " - user is blocked!.", caller);
            throw new AccountLockedException("Current user is blocked!");
        }
        // cascade protected
        if (line.contains("error code=\"cascadeprotected\""))
        {
            log(Level.WARNING, "Cannot " + caller + " - page is subject to cascading protection.", caller);
            throw new CredentialException("Page is cascade protected");
        }
        // database lock (automatic retry)
        if (line.contains("error code=\"readonly\""))
        {
            log(Level.WARNING, "Database locked!", caller);
            throw new HttpRetryException("Database locked!", 503);
        }
        // unknown error
        if (line.contains("error code=\"unknownerror\""))
            throw new UnknownError("Unknown MediaWiki API error, response was " + line);
        // generic (automatic retry)
        throw new IOException("MediaWiki error, response was " + line);
    }
 
    /**
     *  Strips entity references like &quot; from the supplied string. This
     *  might be useful for subclasses.
     *  @param in the string to remove URL encoding from
     *  @return that string without URL encoding
     *  @since 0.11
     */
    protected String decode(String in)
    {
        // Remove entity references. Oddly enough, URLDecoder doesn't nuke these.
        in = in.replace("&lt;", "<").replace("&gt;", ">"); // html tags
        in = in.replace("&amp;", "&");
        in = in.replace("&quot;", "\"");
        in = in.replace("&#039;", "'");
        return in;
    }
 
    /**
     *  Finalizes the object on garbage collection.
     *  @since 0.14
     */
    protected void finalize()
    {
        // I have no idea why this is called when we are still using
        // this Wiki object. Silly Java.
//        Thread.dumpStack();
//        logout();
//        namespaces = null;
    }
 
    // user rights methods
 
    /**
     *  Checks whether the currently logged on user has sufficient rights to
     *  edit/move a protected page.
     *
     *  @param level a protection level
     *  @param move whether the action is a move
     *  @return whether the user can perform the specified action
     *  @throws IOException if we can't get the user rights
     *  @throws AccountLockedException if user is blocked
     *  @throws AssertionError if any defined assertions are false
     *  @since 0.10
     */
    private boolean checkRights(int level, boolean move) throws IOException, AccountLockedException
    {
    	if(true)
    		return true;

        // admins can do anything, this also covers FULL_PROTECTION
        if ((user.userRights() & ADMIN)  == ADMIN)
            return true;
        switch (level)
        {
            case NO_PROTECTION:
                return true;
            case SEMI_PROTECTION:
                return user != null; // not logged in => can't edit
            case MOVE_PROTECTION:
            case SEMI_AND_MOVE_PROTECTION:
                return !move; // fall through is OK: the user cannot move a protected page
            // cases PROTECTED_DELETED_PAGE and FULL_PROTECTION are unnecessary
            default:
                return false;
        }
    }
 
    /**
     *  Performs a status check, including assertions.
     *  @throws AssertionError if any assertions are false
     *  @throws AccountLockedException if the user is blocked
     *  @throws IOException if a network error occurs
     *  @see #setAssertionMode
     *  @since 0.11
     */
    protected void statusCheck() throws IOException, AccountLockedException
    {
        // @revised 0.18 was assertions(), put some more stuff in here
 
        // check if MediaWiki hasn't logged us out
        if (!cookies.containsValue(user.getUsername()))
        {
            log(Level.WARNING, "Cookies expired", "statusCheck");
            logout();
        }
 
        // perform various status checks every 100 or so edits
        if (statuscounter > statusinterval)
        {
            // purge user rights in case of desysop or loss of other priviliges
            if (user != null)
                user.userRights(false);
            // check for new messages
            if ((assertion & ASSERT_NO_MESSAGES) == ASSERT_NO_MESSAGES)
                assert !(hasNewMessages()) : "User has new messages";
 
            statuscounter = 0;
        }
        else
            statuscounter++;
 
        // do some more assertions
        if ((assertion & ASSERT_LOGGED_IN) == ASSERT_LOGGED_IN)
            assert (user != null) : "Not logged in";
        if ((assertion & ASSERT_BOT) == ASSERT_BOT)
            assert (user.userRights() & BOT) == BOT : "Not a bot";
    }
 
    // cookie methods
 
    /**
     *  Sets cookies to an unconnected URLConnection and enables gzip
     *  compression of returned text.
     *  @param u an unconnected URLConnection
     *  @param map the cookie store
     */
    private void setCookies(URLConnection u, Map map)
    {
        Iterator i = map.entrySet().iterator();
        StringBuilder cookie = new StringBuilder(100);
        while (i.hasNext())
        {
            Map.Entry entry = (Map.Entry)i.next();
            cookie.append(entry.getKey());
            cookie.append("=");
            cookie.append(entry.getValue());
            cookie.append("; ");
        }
        u.setRequestProperty("Cookie", cookie.toString());
 
        // enable gzip compression
        u.setRequestProperty("Accept-encoding", "gzip");
    }
 
    /**
     *  Grabs cookies from the URL connection provided.
     *  @param u an unconnected URLConnection
     *  @param map the cookie store
     */
    private void grabCookies(URLConnection u, Map map)
    {
        // reset the cookie store
        map.clear();
        String headerName = null;
        for (int i = 1; (headerName = u.getHeaderFieldKey(i)) != null; i++)
            if (headerName.equals("Set-Cookie"))
            {
                String cookie = u.getHeaderField(i);
 
                // _session cookies are for cookies2, otherwise this causes problems
                if (cookie.contains("_session") && map == cookies)
                    continue;
 
                cookie = cookie.substring(0, cookie.indexOf(";"));
                String name = cookie.substring(0, cookie.indexOf("="));
                String value = cookie.substring(cookie.indexOf("=") + 1, cookie.length());
                map.put(name, value);
            }
    }
 
    // logging methods
 
    /**
     *  Logs a successful result.
     *  @param text string the string to log
     *  @param method what we are currently doing
     *  @param level the level to log at
     *  @since 0.06
     */
    private void log(Level level, String text, String method)
    {
        StringBuilder sb = new StringBuilder(100);
        sb.append('[');
        sb.append(domain);
        sb.append("] ");
        sb.append(text);
        sb.append('.');
        logger.logp(level, "Wiki", method + "()", sb.toString());
    }
 
    /**
     *  Logs a url fetch.
     *  @param url the url we are fetching
     *  @param method what we are currently doing
     *  @since 0.08
     */
    private void logurl(String url, String method)
    {
        logger.logp(Level.FINE, "Wiki", method + "()", "Fetching URL " + url);
    }
 
    // calendar/timestamp methods
 
    /**
     *  Turns a calendar into a timestamp of the format yyyymmddhhmmss. Might
     *  be useful for subclasses.
     *  @param c the calendar to convert
     *  @return the converted calendar
     *  @see #timestampToCalendar
     *  @since 0.08
     */
    protected final String calendarToTimestamp(Calendar c)
    {
        StringBuilder x = new StringBuilder();
        x.append(c.get(Calendar.YEAR));
        int i = c.get(Calendar.MONTH) + 1; // January == 0!
        if (i < 10)
            x.append("0"); // add a zero if required
        x.append(i);
        i = c.get(Calendar.DATE);
        if (i < 10)
            x.append("0");
        x.append(i);
        i = c.get(Calendar.HOUR_OF_DAY);
        if (i < 10)
            x.append("0");
        x.append(i);
        i = c.get(Calendar.MINUTE);
        if (i < 10)
            x.append("0");
        x.append(i);
        i = c.get(Calendar.SECOND);
        if (i < 10)
            x.append("0");
        x.append(i);
        return x.toString();
    }
 
    /**
     *  Turns a timestamp of the format yyyymmddhhmmss into a Calendar object.
     *  Might be useful for subclasses.
     *
     *  @param timestamp the timestamp to convert
     *  @return the converted Calendar
     *  @see #calendarToTimestamp
     *  @since 0.08
     */
    protected final Calendar timestampToCalendar(String timestamp)
    {
        GregorianCalendar calendar = new GregorianCalendar(TimeZone.getTimeZone("UTC"));
        int year = Integer.parseInt(timestamp.substring(0, 4));
        int month = Integer.parseInt(timestamp.substring(4, 6)) - 1; // January == 0!
        int day = Integer.parseInt(timestamp.substring(6, 8));
        int hour = Integer.parseInt(timestamp.substring(8, 10));
        int minute = Integer.parseInt(timestamp.substring(10, 12));
        int second = Integer.parseInt(timestamp.substring(12, 14));
        calendar.set(year, month, day, hour, minute, second);
        return calendar;
    }
 
    /**
     *  Converts a timestamp of the form used by the API
     *  (yyyy-mm-ddThh:mm:ssZ) to the form
     *  yyyymmddhhmmss, which can be fed into <tt>timestampToCalendar()</tt>.
     *
     *  @param timestamp the timestamp to convert
     *  @return the converted timestamp
     *  @see #timestampToCalendar
     *  @since 0.12
     */
    private String convertTimestamp(String timestamp)
    {
        StringBuilder ts = new StringBuilder(timestamp.substring(0, 4));
        ts.append(timestamp.substring(5, 7));
        ts.append(timestamp.substring(8, 10));
        ts.append(timestamp.substring(11, 13));
        ts.append(timestamp.substring(14, 16));
        ts.append(timestamp.substring(17, 19));
        return ts.toString();
    }
 
    // serialization
 
    /**
     *  Writes this wiki to a file.
     *  @param out an ObjectOutputStream to write to
     *  @throws IOException if there are local IO problems
     *  @since 0.10
     */
    private void writeObject(ObjectOutputStream out) throws IOException
    {
        out.writeObject(user.getUsername());
        out.writeObject(cookies);
        out.writeInt(throttle);
        out.writeInt(maxlag);
        out.writeInt(assertion);
        out.writeObject(scriptPath);
        out.writeObject(domain);
        out.writeObject(namespaces);
        out.write(statusinterval);
    }
 
    /**
     *  Reads a copy of a wiki from a file.
     *  @param in an ObjectInputStream to read from
     *  @throws IOException if there are local IO problems
     *  @throws ClassNotFoundException if we can't recognize the input
     *  @since 0.10
     */
    private void readObject(ObjectInputStream in) throws IOException, ClassNotFoundException
    {
        String z = (String)in.readObject();
        user = new User(z);
        cookies = (HashMap)in.readObject();
        throttle = in.readInt();
        maxlag = in.readInt();
        assertion = in.readInt();
        scriptPath = (String)in.readObject();
        domain = (String)in.readObject();
        namespaces = (HashMap)in.readObject();
        statusinterval = in.readInt();
 
        // various other intializations
        cookies2 = new HashMap(10);
        base = "http://" + domain + scriptPath + "/index.php?title=";
        query = "http://" + domain + scriptPath + "/api.php?format=xml&";
 
        // force a status check on next edit
        statuscounter = statusinterval;
    }
}
