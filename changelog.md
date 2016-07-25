# ImageKit Changelog

- `1.0.0-beta2` (2016/07/25)
  - **Changed Job-File Suffix:** Pending thumbs aka placeholder files aka job files now have a suffix of `-imagekitjob.php` instead of `.imagekitjob.php`. This fixes errors with Apache’s `MultiViews` feature (read [explanation](http://stackoverflow.com/questions/25423141/what-exactly-does-the-the-multiviews-options-in-htaccess)). You should clear your thumbs folder after upgrading.
  - **Error Handling:** ImageKit now tries it’s best to show you if there was an error in the thumbnail creating process. The widget is now able to display errors and if thumbnail creation failed, an error image is returned instead of nothing.
  - **Discovery Feature:** The widget now scans your whole site for thumbnails, so you don’t have to open every page manually. 
  - **Widget Code:** The widget logic has been improved on both the server and the client side for better extensibility.
  - **Widget UI:** Added text underneath the progress bar to give the user a better understanding of what the widget is currently doing. Added animation while the progress bar is visible. If an operation is cancelled, Widget UI is now blocked until another operation can be started.
  - **Permissions:** The widget now shows an error message when the user has been logged out. The widget is now accessible for all logged-in panel users by default.
  - **Refactoring:** The whole plugin has been refactored here and there …

- `1.0.0-beta1` (2016/06/04)
  - First public release
