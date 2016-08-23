# Command-line re-installer

The files in this directory are an example on how you could (re-)install Kimai automatically.

For the sake of this documentation, we assume that:

- The scripts will be running on a Linux box
- Kimai installation is located in /var/www/kimai/
- Virtual Host entry points to /var/www/kimai/htdocs/
- Your Virtual host has a public DNS entry
- Mysql is running on the local machine
- The following command line tools are available: php, mysql, git, grep, gawk, curl
- The box has an working internet connection (Kimai will be cloned from GitHub)

## Prepare your environment

- Create the directory /var/www/kimai/htdocs/
- Copy the files autoconf.php / install.sh and reinstall.php to /var/www/kimai/
- Adjust autoconf.php to your environment (database connection!)
- Adjust the configuration settings in reinstall.php to match your environment
- Call ./install.sh
- Have fun :-)

## ATTENTION

- These scripts are not meant for production usage, but only serve as example on how you could install Kimai automated.
- The scripts assume that the database, which shall be used by Kimai, is exclusively reserved for Kimai tables.
- The reinstall.php script will *DROP ALL TABLES* from the database every time it is called, you won't be bothered with questions. 
Beware that you could loose all your data!!!!   