# Manga API Integration Upgrade Guide

This guide will help you upgrade your existing Manga Tracker database to support the new manga API integration features.

## Database Upgrade

After setting up your database with the original `db_setup.sql` script, you'll need to run the `db_upgrade.sql` script to add the new columns needed for the API integration.

### Steps to upgrade:

1. Open your database management tool (phpMyAdmin, MySQL Workbench, or command line)
2. Connect to your database
3. Execute the contents of the `db_upgrade.sql` file

Example using command line:

```bash
mysql -u your_username -p manga_tracker < db_upgrade.sql
```

## New Features

The manga API integration adds the following features to your Manga Tracker site:

1. **Browse page**: Search for manga from a large external database
2. **Cover images**: Display manga covers in your collection
3. **Descriptions**: View detailed descriptions of manga
4. **Metadata**: Access additional information like ratings, genres, and publication dates

## Technical Details

The integration uses:
- [Jikan API](https://jikan.moe/) - An unofficial MyAnimeList API
- PHP's curl extension to fetch data from the API
- Additional database columns to store manga metadata

## Troubleshooting

If you encounter any issues:
1. PHP's curl extension: 
   - The code includes a fallback to `file_get_contents()` if curl is not available
   - If you want to use curl, you can enable it in php.ini by uncommenting `;extension=curl`
   - On XAMPP: Open php.ini (usually in C:\xampp\php\php.ini), find the line `;extension=curl` and remove the semicolon
   - On WAMP: Go to WAMP icon > PHP > PHP Extensions > Check the "curl" option
   - Restart your web server after making changes

2. Check database connection settings in `config.php`

3. Be patient with API responses as they might be rate-limited (the Jikan API limits requests)

4. Verify that the database upgrade was successful by checking for the new columns

For any further questions or issues, please refer to the project documentation or contact support.
