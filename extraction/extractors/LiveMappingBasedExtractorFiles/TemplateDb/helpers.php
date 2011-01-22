<?php


/**
 * Not used in the release. But a convenience function for testing stuff.
 *
 */
function myDefaultOdbcConnect()
{
    $dataSourceName = Options::getOption('dsn');
    $username       = Options::getOption('user');
    $password       = Options::getOption('pw');

    $con = myOdbcConnect($dataSourceName, $username, $password);

    return $con;
}

/**
 * Helper function for connecting to a odb database.
 * Just does some error checking.
 *
 * Returns NULL on error.
 *
 */
function myOdbcConnect($dataSourceName, $username, $password)
{
    if (!function_exists('odbc_connect')) {
        //$logger->log(WARN,
        Logger::warn(
            "Virtuoso adapter requires PHP ODBC extension to be loaded");
        return NULL;
    }

    $con = @odbc_connect($dataSourceName, $username, $password);

    if (null == $con) {
        //$logger->log(WARN,
        Logger::warn(
            "Unable to connect to Virtuoso Universal Server via ODBC: " .
                odbc_errormsg());
        return NULL;
    }

    return $con;
}
