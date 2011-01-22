<?php

/**
 * Unfortunately this interface is currently pretty useless, as there
 * is no uniform parse result schema.
 *
 */
interface IValueParser
{
    function parse($value);
}
