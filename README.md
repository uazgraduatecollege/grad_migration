# Grad College to Quickstart 2 Migration

This is a custom migration module intended to bring over content from Grad College Drupal 7 sites, to the new Arizona Quickstart 2 Drupal 8/9 site.
This consists of a series of migration files and a few custom Source PHP classes.
This is dependent on the az_migration module, a custom module inclued in Quickstart meant to transfer files between QS1 and QS2 sites, as well as a few other custom modules packaged with Quickstart 2.

## Contents

1. Instructions for migrating to a standard Quickstart 2 Installation
2. Instructions for migrating to a Pantheon-hosted Quickstart 2 Installation

## 1. Instructions for Migrating to a Standard Quickstart 2 Installation

To use this module follow the steps below:

1. Configure your environment, install AZQS2. Follow the [instructions here](https://github.com/az-digital/az_quickstart/blob/main/CONTRIBUTING.md#local-development) to setup a local lando stack if working in a local environment.

2. For the sake of simplicity, get a dump of the existing database and import it into the same database server as the new environment's db server. You ultimately want a "drupal9" database, for the new QS2 site, and the old system's data on the same server.

3. Add a connection for the old database dump. This should go in `/sites/default/settings.php`

  ```
  $databases['migrate']['default'] = [
    'driver' => 'mysql',
    'namespace' => 'Drupal\Core\Database\Driver\mysql',
    'database' => 'databasename',
    'username' => 'databaseusername',
    'password' => 'databasepassword',
    'port' => 'databaseport',
    'host' => 'localhost',
    'prefix' => '',
  ];
  ```


4. Additionally, add the following snippet to `/sites/default/settings.php`. This is necessary for the media migration to
work correctly.

```
$settings['media_migration_embed_token_transform_destination_filter_plugin'] = 'media_embed';
$settings['media_migration_embed_media_reference_method'] = 'uuid';
```

5. The AZ migration module assumes that content is being migrated from a D7 QS1 website and accordingly assumes that certain modules are installed which assumes additional database schema in the OLD database that may not strictly be present. Because our sites are not based on QS1, there may be some tables/modules missing that will cause errors to appear while checking migration status. The easiest way to fix this is to simply fill stub schema into the database dump. For most GRAD Drupal sites, there's an example of all the necessary SQL to bridge the database in the `non-qs-schema-fix.sql` file in this repo. This may vary from site to site.

6. In order to enable the media migration to work correctly, an additional change needs to manually made to the old database instance. Note that the string length and site name need to be configured before being run. This step can be skipped if the source site's URL has its own subdomain, and isn't managed using sub-folders, e.g. grad.arizona.edu/some_sub_site.

```
UPDATE variable
SET `value` = 's:26:"mysite/sites/default/files";'
WHERE name = 'file_public_path';
```

7. `git clone` this repo into the modules/custom folder of the site. Install the grad_migration module. This can be done through the website's admin interface or using drush.
`drush en grad_migration`

8. Install supporting modules. Enabling grad_migration in the last step should enable a series of dependent modules. You MUST also enable the **Quickstart Paragraphs - HTML** submodule (`az_paragraphs_html`). There are a number of other modules that Grad Migration is dependent on that need to be installed before enabling it. They should be enabled automatically when Grad Migration is enabled.
```
rm composer.lock
composer require drupal/migrate_tools
composer require drupal/migrate_devel:*
```

9. Update the migration configuration settings by using the following console commands. This will allow for the migration framework to correctly process file downloads handled through a migration script. Update these settings to reflect the site being migrated. Answer 'yes' to adding these to the grad_migration.settings.config.
```
drush cset grad_migration.settings migrate_d7_protocol "https"
drush cset grad_migration.settings migrate_d7_filebasepath "myhost.grad.arizona.edu/mygraddrupalsite"
drush cset grad_migration.settings migrate_d7_public_path "sites/default/files"
```
Note: The migrate_d7_filebasepath variable only requires the base URL if the source site has it's own subdomain.

10. Set the default image import size through the Drupal admin interface. To do this login to the new site as az_admin and navigate to
```
Admin > Structure > Media Types > 'Edit' Image > Manage Display
```
Alternative, use the URL:
```
http://<your site>/admin/structure/media/manage/az_image/display
```
Click on the Settings cog next to the 'Image' field and choose the default image size; the best option during import is 'None (original image)'. Click 'Update' on the field, and then 'Save' on the page.

11. The site is now ready to begin the migration. Users have to be migrated before anything else:
```
drush migrate-import az_user
```
Once that's complete, then the grad college content can be migrated. The following command can be used to take in everything at once:
```
drush migrate-import --group grad_migration --migrate-debug
```

Or migrations can be run individually:
```
drush migrate-import ua_gc_paragraph --migrate-debug
```

The debug flag is of course optional.

## 2. Instructions for Migrating to a Pantheon-hosted Quickstart 2 Installation

If you're attempting to get this package working against a site hosted in Pantheon, the following steps describe how to do so.

1. Login to the Pantheon dashboard and create a new site.

2. From Pantheon, preview the site and install Drupal using the web interface. Just choose all the basic options.

3. Go back to the Pantheon dashboard and use the 'connection info' panel to create a local connection to the MySQL database. You can do this in MySQL workbench or from the command line. From the new MySQL server connection, you need to import the SQL dump file of the site being migrated. This will take some time because of the remote connection.

4. Inside the project there's file named `non-qs-schema-fix.sql`. The commands in this file should be run against the old database once it's been imported. See step #4 in the instructions above for further context the purpose of this step.

5. In order to enable the media migration to work correctly, an additional change needs to manually made to the old database instance. Note that the string length and site name need to be configured before being run. This step can be skipped if the source site's URL has its own subdomain, and isn't managed using sub-folders, e.g. grad.arizona.edu/some_sub_site.

```
UPDATE variable
SET `value` = 's:26:"mysite/sites/default/files";'
WHERE name = 'file_public_path';
```

6. Use the 'connection info' panel again to create a SFTP connection to the site. Create a new file at the following location on the remote server:
`/code/web/sites/default/files/private/migration_config.json`

The contents of the file should be as follows:

```
{
	"mysql_database": "<name of database from old site dump file>",
	"mysql_password": "<mysql password per pantheon>",
	"mysql_host": "<mysql host per pantheon>",
	"mysql_port": "<mysql port per panethon>",
	"mysql_username": "pantheon"
}
```

7. `git clone` the Pantheon site to your local machine. Edit the composer.json file as follows:

 - In the 'repositories' section:
 ```
    {
    "type": "vcs",
    "url": "git@github.com:uazgraduatecollege/grad_migration.git"
    }
  ```
 - And in the 'require' section:
  ```
    "uazgraduatecollege/grad_migration": "dev-main",
    "drupal/migrate_tools": "*",
    "drupal/migrate_devel": "*"
  ```

8. Delete composer.lock file.

9. Additionally, in your local copy of the site's files, add the following snippet to `web/sites/default/settings.php`. This is necessary for the media migration to work correctly.

```
$settings['media_migration_embed_token_transform_destination_filter_plugin'] = 'media_embed';
$settings['media_migration_embed_media_reference_method'] = 'uuid';
```

10. `git add`, `git commit` and `git push origin master` these files back to the Pantheon site. It should rebuild the site automatically, and install the packages.

11. Through the Drupal's web interface login as an admin user and enable the `GC Quickstart Migration`. Enabling this module should also enable all dependent modules automatically.

Alternatively run:
```sh
terminus remote:drush en grad_migration
```

12. Set the default image import size through the Drupal admin interface. To do this login to the new site as az_admin and navigate to
```
Admin > Structure > Media Types > 'Edit' Image > Manage Display
```
Alternative, use the URL:
```
http://<your site>/admin/structure/media/manage/az_image/display
```
Click on the Settings cog next to the 'Image' field and choose the default image size; the best option during import is 'None (original image)'. Click 'Update' on the field, and then 'Save' on the page.

13. From the command line, whilst working from the diretory of the cloned project, enter the following commands:
```sh
terminus drush cset grad_migration.settings migrate_d7_protocol "https"
terminus drush cset grad_migration.settings migrate_d7_filebasepath "myhost.grad.arizona.edu/mygraddrupalsite"
terminus drush cset grad_migration.settings migrate_d7_public_path "sites/default/files"
terminus drush migrate-import az_user
terminus -- drush migrate-import --group grad_migration
```
Note: The migrate_d7_filebasepath variable only requires the base URL if the source site has it's own subdomain.

To perform the migration and see debugging output, use this instead:
```sh
terminus -- drush migrate-import --group grad_migration --migrate-debug
```

NOTE: Configure the variables specified above with the correct values. The migration requires downloading files from the current site as specified so
ensure that firewall access allows http requests against the URL given.
