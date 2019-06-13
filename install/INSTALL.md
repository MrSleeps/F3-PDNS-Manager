#Install Instructions

*Before you start, you will need PowerDNS up and running with the MySQL backend. You will also need Nginx or Apache (sample configs are included). Installing this is down to you, there are plenty of tutorials out there that will get you up and running in a short space of time.*

So, on to installation.

In the install directory you will see a file called backend.sql, this adds the extra tables that we need. Create the tables using the sql file:

`mysql -u username -p database_name < backend.sql`

Add a new user with access to your powerdns database:

`mysql -u username -p
mysql> GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost' IDENTIFIED BY 'password';`

Hopefully, you will see no errors.

Upload everything to your server, setup your nginx/apache virtual host to point to www directory, an example NGINX config and Apache .htaccess are included in the install directory. You will need to be hosting this on an HTTPS server, so get yourself an SSL certificate. Then, in the root directory create a directory called temp and **give the webserver write access to it, chown is the safest option, don't CHMOD 777 - that's just idiotic** (this is where the template and other temporary files are stored). Setting up your webserver isn't in the scope of this document.

In the config directory (outside of your webroot) copy the config.ini.sample file to config.ini, open up your editor and get cracking on changing what's in the file. You will need to add your Database Details, SMTP details (remove the << >> bits). It's fairly self explanatory, but if you are stuck the important bits are:

DB_DETS = "mysql:host=localhost;port=3306;dbname=<<YOUR DB NAME>>" (Your database name (e.g. pdns))
DB_HOST = localhost (Your database hostname)
DB_NAME = <<YOUR DB NAME>> (Your database name (e.g. pdns))
DB_USER = <<YOUR DB USERNAME>> (Your database username you created)
DB_PSWD = <<YOUR DB PASSWORD>> (Your database password)
DB_PORT = 3306 (Your database host port)
DB_SOCK = "" (Leave blank if you have put in host and ports)

SITENAME = "Your Website Name" (Change to your website name, keep it shortish)
SITEURL = "https://your.web.site/" (The url of your dns manager)
SITEMASTERUSERID = "1" (The master account User ID - usually 1, unless you have changed it)
SITEALLOWREGISTER = "1" (Do you want users to be able to register?)

SMTPHOST = <<YOUR SMTP SERVER>> (The hostname of your outgoing (SMTP) mail server)
SMTPAUTH = true/false (does your mail server need credentials?)
SMTPUSERNAME = <<YOUR SMTP USERNAME>> (Your SMTP username (if needed))
SMTPPASSWORD = <<YOUR SMTP PASSWORD>> (Your SMTP password (if needed))
SMTPSECURE = tls (ssl/tls/none)
SMTPPORT = 587 (Your SMTP port)
SMTPDEBUG = 0 (Problems with SMTP? Enable debug)
SMTPPWRESETFROMNAME = "Password Reset" (Password reset email name)
SMTPPWRESETFROMEMAIL = password-reset@your.do.main (Password reset email address)

DEFAULTSOAREFRESH = "7200" (Default Refresh (SOA) - Leave as is if you have no idea what that is)
DEFAULTSOAEXPIRE = "86400" (Default Expire (SOA) - Leave as is if you have no idea what that is)
DEFAULTSOARETRY = "3600" (Default Retry (SOA) - Leave as is if you have no idea what that is)
DEFAULTSOATTL = "3600" (Default TTL (SOA) - Leave as is if you have no idea what that is)

Config done...

Fire up your web browser and head off to your DNS Manager website, if all has gone to plan it should ask you to set up a new account.. 

If it is working, delete the install directory.