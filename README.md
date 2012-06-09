PHP-DB-Migration-Class
======================

PHP DB Migration Class to create and update db using migrations across environments

Migration code based off Yii Framework

Description
----------------
Allows one to use migrations within any project without requiring to use a full blown framework
Currently supports MYSQL but class uses PDO so can support all PDO supported drivers

Reason why this class was created is to give the option to use the great migration feature Yii Framework provides without the need to use the entire framework.
This is a standalone lightweight package that can be incorporated into any project easily.

Requirements
=======================
- PHP > 5
- PHP PDO


Installation
=======================
- Extract folder into your document root where you can access that migrate.php file.
- Open config.php and update the db settings
- Test to make sure everything works by running php migrate.php through the command line.

You should see something like this:

PHP DB Migration v1.0

Creating migration history table "migrations"...done.
No new migration found. Your system is up-to-date.


Usage
=======================

The easiest thing to do is to run:

`php migrate.php help`

It will display help and usage examples.

To Create a migration you'll do:

`php migrate.php create some_migration_name`

To run all migrations:

`php migrate.php`


Contributors & Resources
=======================
- The Migration process code was taken from Yii Framework v1.1.10
