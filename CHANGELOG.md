-------------------------------------------------------------------------------------------------
-> BWS\InfoWidgets.php
-------------------------------------------------------------------------------------------------

v1.03:
+ Added namespace BWS to prevent "class could not be re-declared" errors and to adhere ManiaControl plugin conventions
+ Added support for TheM\WhoKarma plugin (if installed, click on the KarmaWidget to show how other players rated the current map)
- Fixed potential crash when Karma Plugin was not activated
- other minor bugfixes

v1.0: 
- Initial release including ServerInfo, MapInfo, NextMapInfo, KarmaInfo and Clock widget.

-------------------------------------------------------------------------------------------------
-> BWS\RecordsWidgets.php
-------------------------------------------------------------------------------------------------

changelog until v1.14:
+ Added namespace BWS
+ Added records lists (click either LocalRecords or DedimaniaRecords widget to show a list of all records on current map)
+ Made newest record time blink for 10 seconds
+ Added online indicator to highlight the records of players who are on the server
+ Added own record indicator (highlights your record time in green)
+ Added automatic sorting, so widget doesn't only show the top records, but also records above and below your own record
  Default if no record: show TopX records and fills up widget with the lowest records.
- minor bugfixes

v1.0: 
- Initial release

-------------------------------------------------------------------------------------------------
-> Chris92\CheckpointsWidget.php
-------------------------------------------------------------------------------------------------

Features planned for v2.0:
- show time difference for next best record (LocalRecords and Dedimania supported)
- Customizable widget style

v1.0: 
+ Added namespace Chris92

v0.1:
Initial release

-------------------------------------------------------------------------------------------------
-> Chris92\CustomizeQuitScreen.php
-------------------------------------------------------------------------------------------------

Features planned for future versions:
- Provide a few default layouts to choose from. Advanced users can still use custom option and provide URL to manialink file.

v0.1:
- Initial release
  Allows the customizing of "Quit server" prompt by providing URL to a manialink file