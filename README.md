# drupal-updater
This code can help you update Drupal 7 or 8 easily.

Usage:
1. Make sure Drupal is installed in a folder (eg `/public`) and not the docroot 
2. Place updatecore.php into the folder where Drupal is installed
3. Go to the `/public` in the command line.
4. Type `php updatecore.php 7.80`in the command line, where `7.80` is the version to which you want to update
5. After finishing the update, you may need to go to http://example.com/update.php and update the database.
6. If your have installed dependencies using composer, you have to go to the terminal and run "composer update --with-dependencies"

