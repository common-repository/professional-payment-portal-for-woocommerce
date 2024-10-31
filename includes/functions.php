<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!defined('PPP4WOO_FUNCTIONS_LOADED')) {
    define('PPP4WOO_FUNCTIONS_LOADED', true);
}

function ppp4woo_isUuid($value)
{
    if (!is_string($value)) {
        return false;
    }

    return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value) > 0;
}

function ppp4woo_mb_splitAddress($sAddress)
{
    // Get everything up to the first number with a regex
    $bHasMatch = preg_match('/^[^0-9]*/', $sAddress, $aMatch);

    // If no matching is possible, return the supplied string as the street
    if (!$bHasMatch) {
        return [$sAddress, '', ''];
    }

    // Remove the street from the sAddress.
    $sAddress = str_replace($aMatch[0], '', $sAddress);
    $sStreetname = trim($aMatch[0]);

    // Nothing left to split, return the streetname alone
    if (strlen($sAddress == 0)) {
        return [$sStreetname, '', ''];
    }

    // Explode sAddress to an array using a multiple explode function
    $aAddress = ppp4woo_mb_multiExplodeArray([' ', '-', '|', '&', '/', '_', '\\'], $sAddress);

    // Shift the first element off the array, that is the house number
    $iHousenumber = array_shift($aAddress);

    // If the array is empty now, there is no extension.
    if (count($aAddress) == 0) {
        return [$sStreetname, $iHousenumber, ''];
    }

    // Join together the remaining pieces as the extension.
    $sExtension = substr(implode(' ', $aAddress), 0, 4);

    return [$sStreetname, $iHousenumber, $sExtension];
}

function ppp4woo_mb_multiExplodeArray($aDelimiter, $sString)
{
    $sInput = str_replace($aDelimiter, $aDelimiter[0], $sString);
    $aArray = explode($aDelimiter[0], $sInput);

    return $aArray;
}

function ppp4woo_isFolder($sPath)
{
    if (file_exists($sPath)) {
        return true;
    } else {
        return false;
    }
}

function ppp4woo_getHeaders()
{
    $aHeaders = [];

    $keys = array_keys($_SERVER);
    // Loop through each key and sanitize it
    $keys = array_map(function ($key) {
        return sanitize_key($key);
    }, $keys);

    $values = array_values($_SERVER);

    // Loop through each value and sanitize it
    $values = array_map(function ($value) {
        return sanitize_text_field($value);
    }, $values);

    $headers = array_combine($keys, $values);

    foreach ($headers as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));

            if ($key == 'X-Signature' || $key == 'Accept-Language') {
                $key = strtolower($key);
            }

            $aHeaders[$key] = sanitize_text_field($value);
        } else {
            $aHeaders[$key] = sanitize_text_field($value);
        }
    }

    return $aHeaders;
}
