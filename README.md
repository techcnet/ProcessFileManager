# File Manager for ProcessWire

![GitHub](https://img.shields.io/github/license/techcnet/ProcessFileManager)
![GitHub last commit](https://img.shields.io/github/last-commit/techcnet/ProcessFileManager)

File Manager for ProcessWire is a module to manager files and folders from the CMS backend. It supports creating, deleting, renaming, packing, unpacking, uploading, downloading and editing of files and folders. The integrated code editor ACE supports highlighting of all common programming languages.

!["Screenshot showing the file manager"](https://tech-c.net/site/assets/files/1199/screenshot.jpg)

## Warning
This module is probably the most powerful module. You might destroy your processwire installation if you don't exactly know what you doing. Be careful and use it at your own risk!

## ACE code editor
This module uses [ACE](https://ace.c9.io/ "ACE") code editor available from: https://github.com/ajaxorg/ace

!["Screenshot showing the code editor"](https://tech-c.net/site/assets/files/1199/ace.jpg)

## Dragscroll
This module uses the JavaScript dragscroll available from: http://github.com/asvd/dragscroll. Dragscroll adds the ability to drag the table horizontally with the mouse pointer.

## PHP File Manager
This module uses a modified version of PHP File Manager available from: https://github.com/alexantr/filemanager

## Known issue about files containing "index.php" in the name
Files, containing "index.php" in the name, cannot be opened, renamed or downloaded. This is not a bug in the file-manager or the module. This is a behavior of ProcessWire located in the file /wire/core/PagesRequest.php in line 306. If you want to avoid this problem, comment out the following lines.

!["Screenshot showing the code editor"](https://tech-c.net/site/assets/files/1199/pagesrequest.jpg)
