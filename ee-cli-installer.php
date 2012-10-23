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
    if( defined( '_EEI_VERBOSE' ) && _EEI_VERBOSE ) {
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
    print 'usage string goes here';
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

function _eei_ee_bootstrap( $syspath ) {
    global $system_path;
    $system_path = rtrim( realpath( $syspath ), '/' ) . '/';
    _eei_log( 'Set system_path to: ' . $system_path );

    _eei_debug( 'Loading bootstrap files' );
    ob_start(); //need to catch the junk that comes from ee startup (welcome page)
    require_once sprintf( '%sindex.php', $system_path );
    require_once sprintf( '%sinstaller/controllers/wizard.php', $system_path );
    ob_end_clean();
    _eei_debug( 'Bootstrap files loaded' );

    //this is bad practice but we need to make sure Wizard exists before we
    //define this class, and we probalby can't depend on class autoloading
    //
    //this also probably isn't really needed since apparently EE never uses
    //protected or private members
    if( !class_exists( 'EE_CLI_Installer' ) ) {
        _eei_debug( 'Defining installer class' );
        class EE_CLI_Installer extends Wizard {

        }
    }
}

function _eei_clean_opts( $parsedOpts ) {
    if( !is_array( $parsedOpts ) ) {
        _eei_die( 'Failed parsing options: ' . $result->getMessage(), 6 );
    }
    $defaultOptions = array(
        'db_hostname'       => 'localhost',
        'db_username'       => null,
        'db_name'           => null,
        'db_password'       => null,
        'dbdriver'          => 'mysql',
        'db_conntype'       => '0', //0 - non-persistent; 1 - persistent
        'db_prefix'         => 'exp_',
        'webmaster_email'   => null,
        'email_address'     => null,
        'language'          => 'english',
        'deft_lang'         => 'english',
        'username'          => 'admin',
        'password'          => _eei_random_string( 16 ),
        'screen_name'       => 'admin',
        'modules'           => array( 'comment', 'email', 'emoticon',
            'jquery', 'member', 'query', 'rss', 'search', 'stats', 'channel',
            'mailinglist', 'safecracker', 'rte' ),
        'site_url'          => null,
        'site_index'        => 'index.php',
        'cp_url'            => null,
        'license_number'    => null,
    );

    $options = array_merge( $defaultOptions, $parsedOpts );
    $options['password_confirm'] = $options['password'];
    return $options;
}

function _eei_parse_options( $optionData ) {
    $options = array();
    foreach( $optionData as $option ) {
        switch( $option[0] ) {
            case 'b':
                $options['site_url'] = $option[1];
                break;
            case 'c':
                $options['cp_url'] = $option[1];
                break;
            case 'e':
                $options['webmaster_email'] = $option[1];
                $options['email_address'] = $option[1];
                break;
            case 'h':
                _eei_usage();
                break;
            case 'l':
                $options['license_number'] = $option[1];
                break;
            case 'L':
                $options['language'] = $option[1];
                $options['deft_lang'] = $option[1];
                break;
            case 'M':
                $options['modules'] = array_map( 'trim',
                    explode( ',', $option[1] ) );
                break;
            case 'p':
                $options['password'] = $option[1];
                break;
            case 'u':
                $options['username'] = $option[1];
                break;
            case 'S':
                $options['site_label'] = $option[1];
                break;
            case 'v':
                define( '_EEI_VERBOSE', true );
                break;
            default:
                switch( ltrim( $option[0], '-' ) ) {
                    case 'dbuser':
                        $options['db_username'] = $option[1];
                        break;
                    case 'dbhost':
                        $options['db_hostname'] = $option[1];
                        break;
                    case 'dbpass':
                        $options['db_password'] = $option[1];
                        break;
                    case 'dbname':
                        $options['db_name'] = $option[1];
                        break;
                    default:
                        _wpi_die( 'Unrecognized option: ' . $option[0], 5  );
                        break;
                }
                break;
        }
    }
    return $options;
}

function _eei_do_parsing( $argValues ) {
    ## EE install vars required:
    # 'db_hostname', 'db_username', 'db_name', 'site_label', 'webmaster_email',
    # 'username', 'password', 'email_address', screen_name, modules (array)
    # 'password_confirm', site_url, site_index, cp_url, license_number
    # 'db_password', 'dbdriver', 'db_conntype', 'db_prefix' (exp_)
    $shortOptions = 'b:c:e:hl:L:M:p:u:S:v';
    $longOptions = array(
        'dbuser=',
        'dbpass=',
        'dbname=',
        'dbhost=',
    );

    require_once 'Console/Getopt.php';
    $reader = new Console_Getopt;
    list( $opts, $args ) = $reader->getopt(
        $argValues, $shortOptions, $longOptions );
    return array( _eei_clean_opts( _eei_parse_options( $opts ) ), $args );
}


function _eei_do_install() {
    $installer = new EE_CLI_Installer();
    ob_start();
    $installer->_do_install();
    $output = ob_get_clean();
    if( preg_match( '~~', $output ) ) {
        return true;
    } else {
        return false;
    }
}

function _eei_main() {
    array_shift( $argv );
    list( $options, $args ) = _eei_do_parsing( $argv );
    foreach( $options as $key => $value ) {
        //some of these are GET vars, some are POST, hopefully we won't get
        //conflicts by just taking the shotgun approach
        $_POST[$key] = $_GET[$key] = $value;
    }
    if( count( $args ) >= 1 ) {
        if( is_dir( $syspath = realpath( $args[0] ) ) ) {
            _wpi_debug( 'Found system_path: ' . $syspath );
        } else {
            _wpi_die( 'Path is not a directory: ' . $args[0], 4 );
        }
    } else {
        _wpi_die( 'Missing system_path argument', 4 );
    }
    _eei_ee_bootstrap( $syspath );
    return _eei_do_install();
}


if( isset( $_SERVER['ARGV'] ) &&
    realpath( $_SERVER['ARGV'][0] ) === __FILE__ ) {
    //we weren't included, probably
    _eei_main() || _eei_die( 'Installation failed!' );
}
