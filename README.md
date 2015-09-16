# voodoo-sheetgen

In order to install the Sheetgen library to your Voodoo `spellbook` it is expected that you've already installed and configured the [Voodoo Engine](https://github.com/Barthak/voodoo).

## How to install

Export all the files in this repository to the `spellbook/Sheetgen` directory on your Voodoo engine instance. The next step is to enable the controller in your voodoo.ini file located in your `/conf` directory.

Open `/conf/voodoo.ini` in a text editor. Under the heading **[controllers]** add the following line: *sheetgen = On*. In case sheetgen is already there as a setting but it is set to Off, change it to On instead of adding an extra line.

Now scroll all the way to the bottom of the file and add the following:

```
[controller.sheetgen]
alias = sheet
```

Save the file and exit the editor. Now we have to add the tables to your database. Please goto http://your/url/setup/Controller/sheetgen, this should show you all the tables needed by the Sheet Generator package that are not present yet. If you have the insecure_sql_execution set to On in your admin.ini file, you can enter your MySQL user credentials to add the tables. It is not recommended to have the insecure_sql_execution enabled.

Once the tables are added your Sheet Generator package is ready to use.

The package comes with two Wiki Potions.

- WikiMySheets
- WikiSheetGenerator 

The first one adds an overview with all the Sheets for the current user to the Wiki page. The second one adds an interface to let the user create and view Sheets like the front page of the official WoD Sheet Generator site.

Please refer to the [Voodoo Engine](https://github.com/Barthak/voodoo) page for more on how to enable Wiki Potions. 
