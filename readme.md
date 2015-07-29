Agility CMS
=======================

CMS for managing Agility actions, races, trainings, clubs, ...

Requirements
------------
* PHP 5.3+
* MySQL

Installing
----------

Copy to your webfolder, create database (file `app/config/dbInstall.sql`), setup
permissions (`temp/`, `www/webtemp` and `log/` needs to be writable by web server)
and set up config.neon and layout.neon


It is CRITICAL that file `app/config/config.neon` & whole `app`, `log`
and `temp` directory are NOT accessible directly via a web browser! If you
don't protect this directory from direct web access, anybody will be able to see
your sensitive data. See [security warning](http://nette.org/security-warning).
