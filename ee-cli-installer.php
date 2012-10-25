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


class SystemExit extends Exception {
    const C_OK                      = 0;
    const C_UNKNOWN_ERROR           = 1;
    const C_USAGE                   = 2;
    const C_MISSING_ARGUMENT        = 3;
    const C_INVALID_ARGUMENT        = 4;
    const C_UNKNOWN_OPTION          = 5;
    const C_OPTION_PARSING_ERROR    = 6;
    const C_MISSING_REQ_OPTION      = 7;
    const C_UNHANDLED_EXCEPTION     = 8;
}

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
        return _eei_log( 'DEBUG: ' . trim( $message ) );
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
function _eei_die( $message, $code = SystemExit::C_UNKNOWN_ERROR ) {
    if( $message ) {
        throw new SystemExit( _eei_log( 'Exiting...' ), $code );
    } else {
        throw new SystemExit(
            _eei_log( 'FATAL ERROR: ' . trim( $message ) ), $code );
    }
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
    _eei_die( null, SystemExit::C_USAGE );
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

/**
 * This function doesn't actually work, EE exit()'s if it's bootstrapped outside
 * global scope. Instead we use _eei_get_func_code to read it's contents and
 * run it through eval (GOTO's would also work but are PHP5.3 only)
 *
 * @param  string $syspath path to system/ dir
 * @return null
 */
function _eei_ee_bootstrap( $syspath ) {
    _eei_debug( 'Bootstrapping with syspath: ' . $syspath );
    $systemIndex = realpath( sprintf( '%s/index.php', $syspath ) );
    if( !file_exists( $systemIndex ) ) {
        _eei_die( 'Unable to find system/index.php, is the system path argument wrong?',
            SystemExit::C_INVALID_ARGUMENT );
    }
    //pretend that we're running from system/index.php
    $_SERVER['SCRIPT_FILENAME'] = $_SERVER['SCRIPT_NAME'] = $systemIndex;
    _eei_debug( 'Loading bootstrap files' );
    ob_start(); //need to catch the junk that comes from ee startup (welcome page)
    require_once $systemIndex;
    _eei_debug( 'System bootstrap output: ' . ob_get_clean() );
    _eei_debug( 'Loaded system bootstrap' );
    ob_start();
    require_once sprintf( '%s/installer/controllers/wizard.php', $syspath );
    _eei_debug( 'Install Wizard bootstrap output: ' . ob_get_clean() );
    _eei_debug( 'Loaded install wizard' );
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

/**
 * Apply default option values and check for missing required options
 *
 * @param  array $parsedOpts [description]
 * @return array
 */
function _eei_clean_opts( $parsedOpts ) {
    if( !is_array( $parsedOpts ) ) {
        _eei_die( 'Failed parsing options: ' . $result->getMessage(),
            SystemExit::C_OPTION_PARSING_ERROR );
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
        'screen_name'       => 'First Last',
        'modules'           => array( 'comment', 'email', 'emoticon',
            'jquery', 'member', 'query', 'rss', 'search', 'stats', 'channel',
            'mailinglist', 'safecracker', 'rte' ),
        'site_url'          => null,
        'base_url'          => null,
        'site_index'        => 'index.php',
        'cp_url'            => null,
        'license_number'    => null,
        'theme'             => '',
    );

    $options = array_merge( $defaultOptions, $parsedOpts );
    $options['password_confirm'] = $options['password'];
    foreach( $defaultOptions as $key => $value ) {
        if( is_null( $value ) && is_null( $options[$key] ) ) {
            _eei_die( 'Option is required: ' . $key,
                SystemExit::C_MISSING_REQ_OPTION );
        }
    }
    return $options;
}

/**
 * Take parsed option data and turn it into a useful array
 *
 * @param  array $optionData [description]
 * @return array
 */
function _eei_parse_options( $optionData ) {
    $options = array();
    foreach( $optionData as $option ) {
        switch( ltrim( $option[0], '-' ) ) {
            case 'b':
            case 'base-url':
                $options['site_url'] = $option[1];
                $options['base_url'] = $option[1];
                break;
            case 'c':
            case 'cp-url':
                $options['cp_url'] = $option[1];
                break;
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
            case 'e':
            case 'email-addr':
                $options['webmaster_email'] = $option[1];
                $options['email_address'] = $option[1];
                break;
            case 'h':
            case 'help':
                _eei_usage();
                break;
            case 'I':
            case 'site-index':
                $options['site_index'] = $option[1];
                break;
            case 'l':
            case 'lic-key':
                $options['license_number'] = $option[1];
                break;
            case 'L':
            case 'lang':
                $options['language'] = $option[1];
                $options['deft_lang'] = $option[1];
                break;
            case 'M':
            case 'modules':
                $options['modules'] = array_map( 'trim',
                    explode( ',', $option[1] ) );
                break;
            case 'p':
            case 'password':
                $options['password'] = $option[1];
                break;
            case 'u':
            case 'username':
                $options['username'] = $option[1];
                break;
            case 'T':
            case 'theme':
                $options['theme'] = $option[1];
                break;
            case 's':
            case 'screen-name':
                $options['screen_name'] = $option[1];
                break;
            case 'S':
            case 'site-label':
                $options['site_label'] = $option[1];
                break;
            case 'v':
            case 'verbose':
                define( '_EEI_VERBOSE', true );
                break;
            default:
                _eei_die( 'Unrecognized option: ' . $option[0],
                    SystemExit::C_UNKNOWN_OPTION );
                break;
        }
    }
    return $options;
}

/**
 * Main parsing function
 *
 * @param  array $argValues
 * @return array
 */
function _eei_do_parsing( $argValues ) {
    $shortOptions = 'b:c:e:hI:l:L:M:p:u:T:s:S:v';
    $longOptions = array(
        'base-url=',
        'cp-url=',
        'dbuser=',
        'dbpass=',
        'dbname=',
        'dbhost=',
        'email-addr=',
        'help',
        'site-index=',
        'lic-key=',
        'lang=',
        'modules=',
        'password=',
        'username=',
        'theme=',
        'screen-name=',
        'site-label=',
        'verbose',
    );

    require_once 'Console/Getopt.php';
    $reader = new Console_Getopt;
    $result = $reader->getopt(
        $argValues, $shortOptions, $longOptions );
    if( PEAR::isError( $result ) ) {
        _eei_die( 'Error parsing options', SystemExit::C_OPTION_PARSING_ERROR );
    } else {
        return array( _eei_clean_opts( _eei_parse_options( $result[0] ) ),
            $result[1] );
    }
}

/**
 * Run the actual EE install processes
 *
 * @return bool
 */
function _eei_do_install() {
    $installer = new EE_CLI_Installer();
    _eei_debug( 'Doing pre-installation check' );
    ob_start();
    $result = $installer->_preflight();
    $output = ob_get_clean();
    if( !$result ) {
        _eei_die( 'Preflight check failed: ' . $output );
    } else {
        _eei_debug( $output );
    }
    _eei_log( 'Running installation' );
    ob_start();
    $result = $installer->_do_install();
    $output = ob_get_clean();
    _eei_log( 'Installation finished' );
    _eei_debug( $output );
    if( $result === false ) {
        _eei_log( 'Installation failed!' );
        return false;
    } else {
        _eei_log( 'Installation was successful' );
        return true;
    }
}

/**
 * Do post-install clean up tasks
 *
 * @param  string $syspath [description]
 * @return bool
 */
function _eei_post_install( $syspath ) {
    $installer = sprintf( '%s/installer', $syspath );
    if( is_dir( $installer ) && !is_dir( $installer . '.bak' ) ) {
        rename( $installer, $installer . '.bak' );
        _eei_debug( 'Renamed installer dir to: ' . $installer . '.bak' );
    }
    return true;
}

/**
 * Bootstrap and setup post/get vars for installation. This function should not
 * be run directly (it will die), instead run through eval/_eei_get_func_code
 *
 * @return null
 */
function _eei_init() {
    array_shift( $_SERVER['argv'] );
    list( $options, $args ) = _eei_do_parsing( $_SERVER['argv'] );
    _eei_debug( 'Found options: ' . print_r( $options, true ) );
    _eei_debug( 'Found args: ' . print_r( $args, true ) );
    if( count( $args ) >= 1 ) {
        if( is_dir( $syspath = realpath( $args[0] ) ) ) {
            _eei_debug( 'Found system_path: ' . $syspath );
        } else {
            _eei_die( 'Path is not a directory: ' . $args[0],
                SystemExit::C_INVALID_ARGUMENT );
        }
    } else {
        _eei_die( 'Missing system_path argument',
            SystemExit::C_MISSING_ARGUMENT );
    }
    eval( _eei_get_func_code( '_eei_ee_bootstrap' ) );
    //_eei_ee_bootstrap( $syspath );
    foreach( $options as $key => $value ) {
        //some of these are GET vars, some are POST, hopefully we won't get
        //conflicts by just taking the shotgun approach
        $_POST[$key] = $_GET[$key] = $value;
    }
    $_SERVER['HTTP_HOST'] = parse_url( $_POST['site_url'], PHP_URL_HOST );
}

/**
 * Get the raw PHP source code for a function in this file
 *
 * This is a ridiculous hack to work around global scoping issues in EE
 *
 * @param  string $funcName
 * @return string
 */
function _eei_get_func_code( $funcName ) {
    _eei_debug( 'Loading function code for: ' . $funcName );
    $func = new ReflectionFunction( $funcName );
    $start = $func->getStartLine();
    $end = $func->getEndLine();
    $lines = file( __FILE__ );
    $code = implode( '', array_slice( $lines, $start, $end - 1 - $start ) );
    return $code;
}


if( isset( $_SERVER['argv'] ) &&
    realpath( $_SERVER['argv'][0] ) === __FILE__ ) {
    //we weren't included, probably
    // Suppress Deprecated and PHP Strict Messages
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    try {
        try {
            eval( _eei_get_func_code( '_eei_init' ) );

            //syspath comes from _eei_init
            if( _eei_do_install() && _eei_post_install( $syspath ) ) {
                _eei_log( 'Base URL: ' . $_POST['site_url'] );
                _eei_log( 'Admin URL: ' . $_POST['cp_url'] );
                _eei_log( 'Username: ' . $_POST['username'] );
                _eei_log( 'Password: ' . $_POST['password'] );
            }
        } catch( Exception $err ) {
            if( get_class( $err ) !== 'SystemExit' ) {
                _eei_die( $err->getMessage(), SystemExit::C_UNHANDLED_EXCEPTION );
            } else {
                throw $err;
            }
        }
    } catch( SystemExit $err ) {
        exit( $err->getCode() );
    }
    exit( SystemExit::C_OK );
}
