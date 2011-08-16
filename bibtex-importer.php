<?php
/*
Plugin Name: BibTeX Importer
Plugin URI: http://wordpress.org/extend/plugins/bibtex-importer/
Description: Import links in the BibTeX scholarly reference format.
Author: Martin Fenner
Author URI: http://blogs.plos.org/mfenner
Version: 1.0.1
Stable tag: 1.0.1

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

This code is based on the OMPL Importer Plugin: http://wordpress.org/extend/plugins/opml-importer/

This code uses the bibtexParse library from the Bibliophile project: http://bibliophile.sourceforge.net/

*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

// Load bibTexParse
require_once dirname(__FILE__) . '/bibtexparse/parseentries.php';
require_once dirname(__FILE__) . '/bibtexparse/parsecreators.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

/** Load WordPress Administration Bootstrap */
$parent_file = 'tools.php';
$submenu_file = 'import.php';
$title = __('Import BibTeX', 'bibtex-importer');

/**
 * BibTeX Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class BibTeX_Import extends WP_Importer {
	
	function author_year( $entry ) {
		// Fetch surname from first author and publication year
		$creator = new PARSECREATORS();
		$creatorArray = $creator->parse($entry['author']);
		// creatorArray is $firstname, $initials, $surname, $prefix
		$surname = $creatorArray[0][2];
		$author_year = ($surname != "" ? $surname . ' ': '') . ($entry['year'] != "" ? $entry['year'] . '. ' : '');
		return $author_year;
	}
	
	function doi_or_url( $entry ) {
		// Fetch doi, use url if no doi
		if ($entry['doi'] != "")
			return 'http://dx.doi.org/' . $entry['doi'];
		else
		  return $entry['url'];
	}
	
	function is_duplicate( $entry ) {
		// Check whether link already exists
		// Todo: some duplicate links are not recognized
		$duplicate = get_bookmarks( array( 'limit' => 1, 'search' => $this->doi_or_url( $entry )));
		return (count($duplicate) == 1 ? TRUE : FALSE);
	}
	
	function term_for_entry( $entry ) {
		// Check whether link category for BibTex entry already exists, and create new category if necessary
		$term = term_exists( $entry['bibtexEntryType'], 'link_category');
		if ($term)
		  return $term['term_id'];
		else
		  // Define BibTeX entries
		  global $bibtex_types;
		  $bibtex_types = array('article' => 'Article',
								            'book' => 'Book',
								            'booklet' => 'Booklet',
								            'conference' => 'Conference',
								            'inbook' => 'In Book',
								            'incollection' => 'In Collection',
								            'inproceedings' => 'In Proceedings',
								            'manual' => 'Manual',
								            'masterthesis' => 'Master Thesis',
								            'misc' => 'Miscellaneous',
														'phdthesis' => 'PhD Thesis',
														'proceedings' => 'Proceedings',
														'techreport' => 'Technical Report',
														'unpublished' => 'Unpublished');
			$bibtex_key = $entry['bibtexEntryType'];
      if (!array_key_exists($bibtex_key, $bibtex_types))
        $bibtex_key = 'misc';
			// Create new Link Category
			$category = $bibtex_types[$bibtex_key];
			$term = wp_insert_term($category, 'link_category', array('slug' => $bibtex_key));
			if ( is_wp_error($term) )
			   echo $term->get_error_message();
			else
		    return $term['term_id'];
	}
	
	function trimmed_title( $entry ) {
		// Trim the following: . { }
	  return trim($entry['title'], "\x20\x7B\x7D");
	}

	function dispatch() {
		global $wpdb, $user_ID;
	  $step = isset( $_POST['step'] ) ? $_POST['step'] : 0;

	  switch ($step) {
		  case 0: {
			  include_once( ABSPATH . 'wp-admin/admin-header.php' );
			  if ( !current_user_can('manage_links') )
				  wp_die(__('Cheatin&#8217; uh?', 'bibtex-importer'));
	      ?>

				<div class="wrap">
				  <?php screen_icon(); ?>
				  <h2><?php _e('Import BibTeX references from another system', 'bibtex-importer') ?></h2>
				  <form enctype="multipart/form-data" action="admin.php?import=bibtex" method="post" name="bibtex">
				  <?php wp_nonce_field('import-bookmarks') ?>

				  <p><?php _e('If a program or website you use allows you to export your references as BibTeX you may import them here.', 'bibtex-importer'); ?></p>
				  <div style="width: 90%; margin: auto; height: 8em;">
				    <input type="hidden" name="step" value="1" />
				    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo wp_max_upload_size(); ?>" />
				    <div style="width: 48%;" class="alignleft">
				    <h3><label for="bibtex_url"><?php _e('Specify a BibTeX URL:', 'bibtex-importer'); ?></label></h3>
				    <input type="text" name="bibtex_url" id="bibtex_url" size="50" class="code" style="width: 90%;" value="http://" />
				  </div>

				  <div style="width: 48%;" class="alignleft">
				    <h3><label for="userfile"><?php _e('Or choose from your local disk:', 'bibtex-importer'); ?></label></h3>
				    <input id="userfile" name="userfile" type="file" size="30" />
				  </div>

				</div>

				<p style="clear: both; margin-top: 1em;"><label for="cat_id"><?php _e('The importer will automatically file the links into categories based on the BibTeX entry type (and create the category if necessary).<br/>In addition, all references should use this category:', 'bibtex-importer') ?></label> <select name="cat_id" id="cat_id">
				<?php
				$categories = get_terms('link_category', array('get' => 'all'));
				foreach ($categories as $category) {
				  ?>
				  <option value="<?php echo $category->term_id; ?>"><?php echo esc_html(apply_filters('link_category', $category->name)); ?></option>
				  <?php
				} // end foreach
			
				?>
				</select></p>

				<p class="submit"><input type="submit" name="submit" value="<?php esc_attr_e('Import BibTeX File', 'bibtex-importer') ?>" /></p>
				</form>

				</div>
				<?php
			break;
		} // end case 0

		case 1: {
			check_admin_referer('import-bookmarks');

			include_once( ABSPATH . 'wp-admin/admin-header.php' );
			if ( !current_user_can('manage_links') )
				wp_die(__('Cheatin&#8217; uh?', 'bibtex-importer'));
	?>
	<div class="wrap">

	<h2><?php _e('Importing...', 'bibtex-importer') ?></h2>
	<?php
			$cat_id = abs( (int) $_POST['cat_id'] );
			if ( $cat_id < 1 )
				$cat_id  = 1;
			$import_category = get_term($cat_id, 'link_category');

	    $bibtex = "";
			$bibtex_url = $_POST['bibtex_url'];
			if ( isset($bibtex_url) && $bibtex_url != '' && $bibtex_url != 'http://' ) {
				$bibtex = wp_remote_fopen($bibtex_url);
			} else { // try to get the upload file.
				$overrides = array('test_form' => false, 'test_type' => false);
				$_FILES['userfile']['name'];
				$file = wp_handle_upload($_FILES['userfile'], $overrides);

				if ( isset($file['error']) )
					wp_die($file['error']);

				$bibtex_url = $file['file'];
				$bibtex = file_get_contents($bibtex_url);

			}

			if ( $bibtex != '' ) {
				// Load bibtexParse parser, parse bibtex into array 
				$parse = NEW PARSEENTRIES();
				$parse->loadBibtexString($bibtex);
				$parse->extractEntries();
				list($preamble, $strings, $entries, $undefinedStrings) = $parse->returnArrays();
				
				// Load bibtexParse parser, parse bibtex into bibtex entries
				$bibtex_parse = NEW PARSEENTRIES();
				$bibtex_parse->fieldExtract = FALSE;
				$bibtex_parse->loadBibtexString($bibtex);
				$bibtex_parse->extractEntries();
				list($bibtex_preamble, $bibtex_strings, $bibtex_entries, $bibtex_undefinedStrings) = $bibtex_parse->returnArrays();
				
			  $imports = 0;
				foreach ($entries as $key => $entry) {
					printf('<p>');
					// Links require link_url and link_name, and should not be duplicates (based on doi/url)
					$entry['link'] = $this->doi_or_url( $entry );
					if ($entry['link'] == "") {
						echo sprintf(__('<strong style="color: red;">Not imported because of missing URL.</strong> ', 'bibtex-importer'));
					} elseif ($entry['title'] == "") {
						echo sprintf(__('<strong style="color: red;">Not imported because of missing title.</strong> ', 'bibtex-importer'));
					} elseif ($this->is_duplicate( $entry )) {
						echo sprintf(__('<strong style="color: red;">Not imported because reference already exists.</strong> ', 'bibtex-importer'));
					} else {
						// Fetch original bibtex entry, to be stored in notes field. Strip trailing space
						$notes = ltrim($bibtex_entries[$key]);
						$link = array( 'link_url' => $wpdb->escape($entry['link']), 
													 'link_name' => $wpdb->escape($this->author_year( $entry ) . $this->trimmed_title( $entry )), 
													 'link_category' => array($cat_id, $this->term_for_entry( $entry )), 
													 'link_notes' => $wpdb->escape($notes), 
													 'link_rating' => $wpdb->escape($entry['rating']), 
													 'link_owner' => $user_ID);
											
						wp_insert_link($link);
						$imports++;
					}
					echo sprintf(__('<strong>%s</strong> %s', 'bibtex-importer').'</p>', $this->author_year( $entry ), $this->trimmed_title( $entry ));
				}
	?>

	<p><?php printf(__('<p>Inserted %1$d out of %2$d references into category <strong>%3$s</strong>. Go <a href="%4$s">manage those links</a>.', 'bibtex-importer'), $imports, count($entries), $import_category->name, 'link-manager.php') ?></p>
	<?php
	} // end if got url
	else
	{
		echo "<p>" . __("You need to supply your BibTeX url. Press back on your browser and try again", 'bibtex-importer') . "</p>\n";
	} // end else

	if ( ! $importing_bibtex )
		do_action( 'wp_delete_file', $bibtex_url);
		@unlink($bibtex_url);
	?>
	</div>
	<?php
			break;
		} // end case 1
	} // end switch
		}

	function BibTeX_Import() {}
	}

	$bibtex_importer = new BibTeX_Import();

	register_importer('bibtex', __('BibTeX', 'bibtex-importer'), __('Import links in BibTeX format.', 'bibtex-importer'), array(&$bibtex_importer, 'dispatch'));

}