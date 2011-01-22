<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author raven
 */
interface IBreadCrumbTransformer
{
    function transform(BreadCrumb $breadCrumb);

    //function getSubject(BreadCrumb $breadCrumb);
    //function getProperty(BreadCrumb $breadCrumb);
}
