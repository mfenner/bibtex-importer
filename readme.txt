=== BibTeX Importer ===
Contributors: mfenner
Donate link: None
Tags: importer, reference, bibtex, citeulike, science, research, res-comms
Requires at least: 3.0
Tested up to: 3.0.5
Stable tag: 1.0.1

Import links in the BibTeX reference format.

== Description ==

Import links in the BibTeX reference format, either from a file or URL. Only valid links (with title and URL, no duplicate) are imported, errors will be reported during importing. The importer will automatically file the links into categories based on the BibTeX entry type (and create the category if necessary). An additional category for all imported links can be assigned. The importer places the original BibTeX entry into the notes field, and uses the format <strong>first_author year.</strong> <em>title</em> for the link name. 

The importer uses the bibtexParse library from the Bibliophile project: http://bibliophile.sourceforge.net/ and is based on the Wordpress OPML Importer: http://wordpress.org/extend/plugins/opml-importer/.

== Installation ==

1. Upload the `bibtex-importer` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the Tools -> Import screen, Click on BibTeX

== Frequently Asked Questions ==

= It don't work - What should I do? =

First of all, make sure that the plugin is activated.

== Screenshots ==

1. The Import page
2. The Import Results page

== Changelog ==

= 1.0.1 =
* Added science-related tags

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0.1 =
* Added science-related tags

= 1.0 =
* Initial release