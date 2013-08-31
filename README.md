dropbox-php-proxy - version without cURL for Google App Engine
==============================================================

This little tool allows you to serve files directly from your Dropbox account (or a subdirectory of it) to your PHP server. 

For example, you can have a file in your dropbox named 'profile.jpg' that is linked directly to your page at mycoolwebsite.com/images/profile.jpg. 
To change it you only need to sync the file on your computer. That's it.

You can even host full sites using .html files. Or full directories, or whatever you want. You can't run server-side scripts like PHP, Perl, Python, 
SSIs and so on, but you can use browser-side scripts, i.e. JavaScript. This solution is transparent for the user.

## Installation ##
Just upload it to the directory where you want your files to be served and follow the instructions on the setup.

## Requirements ##
- PHP 5.4
- Curl
- Google App Engine account
- Whitelisted application

## Optional ##

Domain name or subdomain registered with Google App Engine
CNAME record e.g. mydomain.com or myapp.mydomain.com pointing e.g. to myapp.appspot.com
