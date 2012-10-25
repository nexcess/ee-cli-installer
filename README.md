### ExpressionEngine CLI Installer

## Description

A script to install ExpressionEngine completely from the CLI, which makes
automation that much easier.

The idea is to have a PHP script that just does what the regular ExpressionEngine
installer does, but done through the CLI. However, since using PHP's CLI binary
is somewhat awkward, you can instead use the bash shell script which is
generated (using update-shell-script.sh) and contains the complete PHP script.
Thus you can do something like this:

    $ wget <url to EE package download> && unzip ExpressionEngine.zip
    $ wget -q http://goo.gl/OokgX && chmod +x ee-cli-installer.sh
    $ ./ee-cli-installer.sh -b http://example.com/ -c http://example.com/system/ -e user@example.com -l 1111-2222-3333-4444 system/

Or you can just use bits of the PHP in your own installing script!

## Usage

You can use the shell script like so:

    Usage: ee-cli-installer.sh [-hv] -b|--base-url <frontend url> -c|--cp-url <backend URL>
        --dbuser <DB username> --dbpass <DB password> --dbname <DB name> --dbhost <DB host>
        -e|--email-addr <email address> -I|--site-index <index filename> -l|--lic-key <license key>
        -L|--lang <language> -M|--modules <modules list> -p|--password <admin password>
        -u|--username <admin username> -T|--theme <theme> -s|--screen-name <screen name>
        -S|--site-label <site label> <SYSTEM_PATH>

Or you can use the PHP script directly with the same options like this::

    $ php -f ee-cli-installer.php -- [options] path/to/ee/system/

# General options

    -b|--base-url <frontend URL>
        URL for the frontend of the site, should include the trailing slash
        Example: http://example.com/my-ee-site/
        **REQUIRED**

    -c|--cp-url <backend URL>
        URL for the admin/system part of the site, should include the trailing slash
        Example: http://example.com/my-ee-site/system/
        **REQUIRED**

    -e|--email-addr <email address>
        Email address for the admin user and webmaster, not validated
        **REQUIRED**

    -h|--help
        Print this message

    -I|--site-index <index filename>
        Directory index filename, will almost always be "index.php"
        Default: index.php

    -l|--lic-key <license key>
        ExpressionEngine license key
        Example: 1111-2222-3333-4444
        **REQUIRED**

    -L|--lang <language>
        Default language, this option has only been tested with "english"
        Default: english

    -M|--modules <module list>
        Comma-separated list of modules to install/enable. This option has not
        been tested.
        Default: empty list, populated with EE default module list

    -p|--password <admin password>
        Admin password, should be at least 5 characters (not validated)
        Default: (randomly generated 16 character password)

    -u|--username <admin username>
        Admin username, should be at least 4 characters (not validated)
        Default: admin

    -T|--theme <theme>
        Theme to install
        Default: agile_records

    -s|--screen-name <screen name>
        Full name of initial admin user
        Default: First Last

    -S|--site-label <site label>
        Site title
        Default: Change Me

    -v|--verbose
        Verbose flag, enable more (debug) output

#Arguments

    SYSTEM_PATH
        Path the to the EE "system" directory. Note that the name of the dir
        does not actually have to be "system"
