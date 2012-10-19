<?php

/**
 * Nexcess.net ExpressionEngine CLI Installer
 * Copyright (C) 2011  Nexcess.net L.L.C.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @author Alex Headley <aheadley@nexcess.net>
 */

// Suppress Deprecated and PHP Strict Messages
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

/**
 * Simple logging function, returns message passed to it
 *
 * @param  string $message message to log
 * @return string
 */
function _eei_log( $message ) {
    printf( '%s %s' . PHP_EOL, @date( 'c' ), trim( $message ) );
    return $message;
}

/**
 * Simple debug logging function, returns message passed to it. does not print
 * if _EEI_VERBOSE is false
 *
 * @param  string $message
 * @return string
 */
function _eei_debug( $message ) {
    if( _EEI_VERBOSE ) {
        return _eei_log( $message );
    } else {
        return $message;
    }
}

/**
 * Log a message and exit script
 *
 * This function never returns
 *
 * @param  string  $message
 * @param  integer $code    exit code
 * @return null
 */
function _eei_die( $message, $code = 1 ) {
    _eei_log( 'FATAL ERROR: ' . trim( $message ) );
    exit( $code );
}

/**
 * Print usage info and exit with code 2
 *
 * This function never returns
 *
 * @return null
 */
function _eei_usage() {
    print '';
    exit( 2 );
}

/**
 * Generate a random alphanumeric string
 *
 * Probably not the most efficient way but it works
 *
 * @param  integer $length length of string to generate
 * @return string
 */
function _eei_random_string( $length = 12 ) {
    $validChars = array_merge(
        range( 'a', 'z' ),
        range( 'A', 'Z' ),
        // here twice so that numbers are more common
        range( '0', '9' ),
        range( '0', '9' ) );
    $validCharCount = count( $validChars );
    $pass = '';
    while( strlen( $pass ) < $length ) {
        $pass .= $validChars[rand() % $validCharCount];
    }
    return $pass;
}
