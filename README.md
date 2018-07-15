# Demo
https://scraping-teste.herokuapp.com

# Install Dependencies

	composer install

# Configuration

Import the "db.sql" file into your database, you can run something like:

	mysql scraping < db.sql

Configure the credentials of your database in the file "src/settings.php".

	'db' => [
        'host' => 'localhost',
        'dbname' => 'scraping',
        'user' => 'root',
        'pass' => ''
    ],

# Run Scraping

To scraping the site and populating the database, run the command

	composer scraping-news

# Run Site

To run the developing application, you can run this command:

	composer start
