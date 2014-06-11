<?php
/*
Plugin Name: Nexternal
Description: Allows users to include Nexternal product information into their posts and pages
Author: Nathan Smallcomb
Author URI: http://AlreadySetUp.com
Plugin URI: http://AlreadySetUp.com/nexternal
Version: 1.4.2

CHANGELOG:
6/9/14	- 1.4.2	- Fix for carousel jquery product ID support and box-model: border-box
6/9/14	- 1.4.1	- Fix for shortcode representation in pages
6/5/14	- 1.4	- Nexternal authentication updates
		- updated plugin access (settings page, uninstall)
		- separated plugin settings to multiple pages
		- added basic instructions for connecting Nexternal account
		- changed product identification from SKU to productNumber, allowing non-SKU product support
		- altered inline editor height to avoid overlapping content
5/23/14	- 1.3	- updated syntax to avoid depreciation warnings (multiple files)
		- updated enqueue_scripts call to use appropriate hook (nexternal.php)
		- changed authentication method to user/pass instead of activeKey (all files)
4/14/14	- 1.2	- updated jquery and jquery ui cdn references (window.php)
1/1/13	- 1.1.7b- stop curl from getting hung up on SSL certs from nexternal (nexternal-api curl_post)
		- fixed bug where tinymce window wasnt able to find javascript file (window.php jquery-1.7.2.min.js)
5/31/12	- 1.1.6	- fixed jquery UI inclusion bug (another bug)
10/11/11- 1.1.5	- fixed jquery inclusion bug
10/3/11 - 1.1.4 - added strrpos to productOptions generation in window.php. This prevents the list of options from ending in a comma
10/3/11 - 1.1.4 - updated jQuery version in window.php, jQuery moved their hosted javascript files to code.jquery.com
7/29/11 - 1.1.3 - added custom attributes link field to nexternal menu. this is put into the texts and images <a> tag.

*/

include_once (dirname (__FILE__)."/lib/nexternal-api.php");
include_once (dirname (__FILE__)."/tinymce/tinymce.php");
include_once (dirname (__FILE__)."/lib/shortcodes.php");

define('nexternalPlugin_ABSPATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
define('nexternalPlugin_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );

function nexternal_scripts() {
  wp_enqueue_script("jquery");
}
add_action( 'wp_enqueue_scripts', 'nexternal_scripts' );

add_action('admin_menu', 'nexternal_menu');
add_action('wp_head', 'nexternal_head');

function nexternal_endsWith($haystack,$needle,$case=true) {
    if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}

function nexternal_head() {

    echo '<script type="text/javascript" src="' . get_option('siteurl') . '/wp-content/plugins/nexternal/carousel/jcarousellite.js"></script>' . "\n";

    $path = ABSPATH.'wp-content/plugins/nexternal/styles';
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (nexternal_endsWith($file, '.css', false))
                echo "<link rel='stylesheet' type='text/css' media='all' href='" . get_option('siteurl') . "/wp-content/plugins/nexternal/styles/$file'/>" . "\n";
        }
        closedir($dh);
    }

}

function nexternal_menu() {
    add_menu_page("Nexternal Settings", "Nexternal", 'manage_options', 'nexternal_menu', 'nexternal_display_menu');
}

// converts data value 'on' or empty for a checkbox to checked='yes' or nothing
function nexternal_convertDataToChecked($dataValue) {
    if ($dataValue == 'on') return "checked='yes'";
    return '';
}

function nexternal_display_menu() {
    $currtab = isset($_GET['tab'])?$_GET['tab']:'account';
    $tabs = array( 'account' => 'Account', 'display' => 'Display', 'instruction' => 'Instructions' );
    $functions = array( 'account' => 'nexternal_display_general_menu', 'display' => 'nexternal_display_display_menu', 'instruction' => 'nexternal_display_instructions_menu' );
    $links = array();
    foreach( $tabs as $tab => $name ) :
        if ( $tab == $currtab ) :
            $links[] = "<a class='nav-tab nav-tab-active' href='?page=nexternal_menu&tab=$tab'>$name</a>";
        else :
            $links[] = "<a class='nav-tab' href='?page=nexternal_menu&tab=$tab'>$name</a>";
        endif;
    endforeach;
    echo '<Style type="text/css">.nav-tab{border-style:solid;border-color:#ccc #ccc #f9f9f9;border-width:1px 1px 0;color:#c1c1c1;text-shadow:rgba(255,255,255,1) 0 1px 0;font-size:12px;line-height:16px;display:inline-block;padding:4px 14px 6px;text-decoration:none;margin:0 6px -1px 0;-moz-border-radius:5px 5px 0 0;-webkit-border-top-left-radius:5px;-webkit-border-top-right-radius:5px;-khtml-border-top-left-radius:5px;-khtml-border-top-right-radius:5px;border-top-left-radius:5px;border-top-right-radius:5px;}.nav-tab-active{border-width:1px;color:#464646;border-bottom:1px solid #f1f1f1;}h2.nav-tab-wrapper,h3.nav-tab-wrapper{border-bottom:1px solid #ccc;padding-bottom:0;}h2 .nav-tab{padding:4px 20px 6px;font:italic normal normal 24px/35px Georgia,"Times New Roman","Bitstream Charter",Times,serif;}</style><h2 class="nav-tab-wrapper">';
    foreach ( $links as $link )
        echo $link;
    echo '</h2>';
    $dofunc = $functions[$currtab];
    $dofunc();
}


function empty_account_credentials() {

	$data = get_option('nexternal');
	unset($data['accountName']);
	unset($data['userName']);
	unset($data['pw']);
	unset($data['activeKey']);
	update_option('nexternal', $data);

}

function nexternal_display_general_menu() {

	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

    // setup error messages array to be populated with potential errors
    $errorMessages = array();

    // get all nexternal options from Wordpress
    $data = get_option('nexternal');
    $activeKey = $data['activeKey'];
    $accountName = $data['accountName'];
    $userName = $data['userName'];
    $password = $data['pw'];

    if(isset($_POST['clear_data']) && $_POST['clear_data']) {
      unset($data['productData']);
      unset($data['productDataById']);
      $errorMessages[] = 'Product Cache Data has been Emptied.';
      update_option('nexternal', $data);
    }

    if(isset($_POST['unlink_account']) && $_POST['unlink_account'] == 'unlink') {
        empty_account_credentials();
      	$errorMessages[] = 'Nexternal Account Unlinked.';
    }

    // check to see if the user submitted an accountName, username OR password information
    if (!empty($_POST['nexternal_accountName']) or !empty($_POST['nexternal_username']) or !empty($_POST['nexternal_password'])) {

        // if they are all there
        if (!empty($_POST['nexternal_accountName']) && !empty($_POST['nexternal_username']) && !empty($_POST['nexternal_password'])) {

            // try to get an active key from Nexternal
            $accountName = $_POST['nexternal_accountName'];
            $userName = $_POST['nexternal_username'];
            $password = $_POST['nexternal_password'];

            empty_account_credentials();


            $verified = nexternal_testCredentials($accountName, $userName, $password);

            // check for failure
            if (!$verified) $errorMessages[] = 'Unable to connect to Nexternal, check username and password.';
            else {
                // if it worked, save the activeKey to Wordpress
                //$data['activeKey'] = $activeKey;
                $data['accountName'] = $accountName;
    		$data['userName'] = $userName;
    		$data['pw'] = $password;
                update_option('nexternal', $data);
            }

        } else {
            // check for missing fields
            if (empty($_POST['nexternal_accountName'])) $errorMessages[] = 'Please enter your account name.';
            if (empty($_POST['nexternal_username'])) $errorMessages[] = 'Please enter your username.';
            if (empty($_POST['nexternal_password'])) $errorMessages[] = 'Please enter your password.';
        }
    }

    // generate error message div element based on $errorMessages
    $displayErrors = '';
    if (count($errorMessages) > 0) {
        $displayErrors = "<div class='nexternal-errors' style='width: 400px; margin: auto; background: #ffaaaa; padding: 5px; font-weight: bold;'>";
        foreach ($errorMessages as $errorMessage) $displayErrors .= "$errorMessage<br>";
        $displayErrors .= "</div>";
    }

    // determine if an activeKey has already been established
    if ($data['userName'] != '' && $data['pw'] != '' && $data['accountName'] != '') {
        $linkStatus = <<<HTML
            <p>You are currently linked to the account: $accountName. You do not neeed to enter your username and password again.</p>
            <p>

              <input type="button" value="Link to a Different Account" onclick="document.getElementById('nexternal-link').style.display = 'block';">
              <input type="hidden" name="unlink_account" id="unlink_account" value=""/>
              <input type="submit" value="Unlink Account" onclick="if(confirm('This will permanently unlink your Nexternal account and forget all credentials.  To re-link your account you will need to provide fresh, correct credentials.  Are you sure you wish to do this?')) { document.getElementById('unlink_account').value='unlink';return true; } else { return false; }"/>
            </p>
HTML;
        $linkDisplay = 'none';
    } else {
        $linkDisplay = 'block';
    }

    // load variables from data to display in HTML form
    $html = <<<HTML
    <div class="wrap">
	    <div id="icon-edit-pages" class="icon32"><br /></div>
        <h2>Nexternal Account Configuration</h2>

        $displayErrors

	    <form method="post">
HTML;

if(isset($data['productData']) || isset($data['productDataById'])) {
    $html .= <<<HTML
        <h2>Product Data Cache</h2>
        <div>
        <input type="hidden" name="clear_data" id="clear_data" value="">
        <p><em>Products using the Nexternal Shortcodes are cached locally for approximately 24 hours before reloading information.</em><br/><br/>
        If products displayed using Nexternal Shortcodes are not showing your most recent product data, you can use the <br/>
        button below to clear local data stores and fetch fresh information from Nexternal on the next page load.</p>
        <input type="submit" name="Submit" onclick="document.getElementById('clear_data').value='clear';" value="Clear Product Cache" /><br/><br/><br/>
	</div>
HTML;
}

    $html .= <<<HTML
        <h2>Account Options</h2>

        $linkStatus

        <div id="nexternal-link" style="display: $linkDisplay">
            <p>To connect to a Nexternal store front, enter the account name.</p>

	        <p><strong>Account Name:</strong>
	        <input type="text" name="nexternal_accountName" size="45"/></p>

            <p>Enter your username and password. This information will be used to communicate with the Nexternal API.</p>
            <p>This user should be a Nexternal user of the type 'XML Tools', with the 'ProductQuery' option enabled.</p>

	        <p><strong>Username:</strong>
	        <input type="text" name="nexternal_username" size="45" /></p>

	        <p><strong>Password:</strong>
	        <input type="text" name="nexternal_password" size="45" /></p>
        </div>


        <input type="hidden" name="updated" id="updated" value="yes">

	    <p><input type="submit" name="Submit" value="Update Options" /></p>

	    </form>

    </div>
HTML;

    echo $html;

}

function nexternal_display_display_menu() {

	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

    // setup error messages array to be populated with potential errors
    $errorMessages = array();

    // get all nexternal options from Wordpress
    $data = get_option('nexternal');
    $activeKey = $data['activeKey'];
    $accountName = $data['accountName'];
    $userName = $data['userName'];
    $password = $data['pw'];

    // copy default options into data and save it, only if the form was submitted
    if (isset($_POST['updated'])) {
        $data['defaultCarouselType'] = $_POST['nexternal_carouselType'];
        $data['defaultGridSizeRows'] = $_POST['nexternal_gridSizeRows'];
        $data['defaultDisplayProductImage'] = $_POST['nexternal_displayProductImage'];
        $data['defaultDisplayProductName'] = $_POST['nexternal_displayProductName'];
        $data['defaultGridSizeColumns'] = $_POST['nexternal_gridSizeColumns'];
        $data['defaultDisplayProductOriginalPrice'] = $_POST['nexternal_displayProductOriginalPrice'];
        $data['defaultDisplayProductPrice'] = $_POST['nexternal_displayProductPrice'];
        $data['defaultDisplayProductRating'] = $_POST['nexternal_displayProductRating'];
        $data['defaultDisplayProductShortDescription'] = $_POST['nexternal_displayProductShortDescription'];
        $data['defaultProductsInView'] = $_POST['nexternal_productsInView'];
        $data['defaultStyle'] = $_POST['nexternal_defaultStyle'];
        $data['customLinkAttributes'] = $_POST['nexternal_customLinkAttributes'];
    }
    update_option('nexternal', $data);

    // load variables from data to display in HTML form
    $defaultGridSizeRows = $data['defaultGridSizeRows'];
    $defaultGridSizeColumns = $data['defaultGridSizeColumns'];
    $defaultProductsInView = $data['defaultProductsInView'];
    $customLinkAttributes = htmlspecialchars(stripslashes($data['customLinkAttributes']));

    $defaultDisplayProductRatingChecked = nexternal_convertDataToChecked($data['defaultDisplayProductRating']);
    $defaultDisplayProductPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductPrice']);
    $defaultDisplayProductOriginalPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductOriginalPrice']);
    $defaultDisplayProductImageChecked = nexternal_convertDataToChecked($data['defaultDisplayProductImage']);
    $defaultDisplayProductNameChecked = nexternal_convertDataToChecked($data['defaultDisplayProductName']);
    $defaultDisplayProductShortDescriptionChecked = nexternal_convertDataToChecked($data['defaultDisplayProductShortDescription']);

    $defaultCarouselTypeHorizontalSelected = ($data['defaultCarouselType'] == 'horizontal') ? ("SELECTED"):('');
    $defaultCarouselTypeVerticalSelected = ($data['defaultCarouselType'] == 'vertical') ? ("SELECTED"):('');
    $defaultCarouselTypeNoneSelected = ($data['defaultCarouselType'] == 'none') ? ("SELECTED"):('');
    $defaultCarouselTypeSingleSelected = ($data['defaultCarouselType'] == 'single') ? ("SELECTED"):('');

    // load available styles
    $styleOptions = "";
    $path = ABSPATH.'wp-content/plugins/nexternal/styles';
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (nexternal_endsWith($file, '.css', false)) {
                if ($data['defaultStyle'] == $file) $selected = 'SELECTED';
                else $selected = '';
                $fileData = get_file_data($path . '/' . $file, array( 'Name' => 'Style Name'));
                $styleName = $fileData['Name'];
                $styleOptions .= "<option value='$file' $selected>" . $fileData['Name'] . " ($file)</option>";
            }
        }
        closedir($dh);
    }

    $html = <<<HTML
    <div class="wrap">
	    <div id="icon-edit-pages" class="icon32"><br /></div>
        <h2>Nexternal Display Configuration</h2>

        $displayErrors

	    <form method="post">

        <h2>Default Display Options</h2>

        <p><label for="nexternal_carouselType">Carousel Type:</label>
        <select id="nexternal_carouselType" name="nexternal_carouselType" style="width: 150px;">
            <option value="none" $defaultCarouselTypeNoneSelected>No Carousel</option>
            <option value="single" $defaultCarouselTypeSingleSelected>Single Product</option>
            <option value="horizontal" $defaultCarouselTypeHorizontalSelected>Horizontal Carousel</option>
            <option value="vertical" $defaultCarouselTypeVerticalSelected>Vertical Carousel</option>
        </select>
        </p>

        <h3>Carousel Options</h3>
        <p><label for="productsInView">Number of Visible Products in Carousel:</label> <input type="text" id="nexternal_productsInView" name="nexternal_productsInView" style="width: 25px" value="$defaultProductsInView"></p>

        <h3>Grid Options</h3>
        <p><label for="gridSizeRows">Grid Size (Width, Height):</label> (
            <input type="text" id="nexternal_gridSizeColumns" name="nexternal_gridSizeColumns" style="width: 25px" value="$defaultGridSizeColumns">,
            <input type="text" id="nexternal_gridSizeRows" name="nexternal_gridSizeRows" style="width: 25px" value="$defaultGridSizeRows">
             )</p>

        <h3>Display Options</h3>

        <p><input type="checkbox" id="nexternal_displayProductRating" name="nexternal_displayProductRating" $defaultDisplayProductRatingChecked> Display Product Rating?</p>

        <p><input type="checkbox" id="nexternal_displayProductPrice" name="nexternal_displayProductPrice" $defaultDisplayProductPriceChecked> Display Product Price?</p>

        <p><input type="checkbox" id="nexternal_displayProductOriginalPrice" name="nexternal_displayProductOriginalPrice" $defaultDisplayProductOriginalPriceChecked> Display Product's Original Price?</p>

        <p><input type="checkbox" id="nexternal_displayProductImage" name="nexternal_displayProductImage" $defaultDisplayProductImageChecked> Display Product's Image?</p>

        <p><input type="checkbox" id="nexternal_displayProductName" name="nexternal_displayProductName" $defaultDisplayProductNameChecked> Display Product's Name?</p>

        <p><input type="checkbox" id="nexternal_displayProductShortDescription" name="nexternal_displayProductShortDescription" $defaultDisplayProductShortDescriptionChecked> Display Product's Short Description?</p>

        <h2>Advanced</h2>

        <p>Custom Link Attributes: $customLinkAttributes
        <br>
        Change to: <input size="40" type="text" id="nexternal_customLinkAttributes" name="nexternal_customLinkAttributes" value=""></p>

        <h2>Default Style</h2>

        <p>Add new styles to: wp-content/plugins/nexternal/styles</p>
        <p>Select Default Style:
        <select id='nexternal_defaultStyle' name='nexternal_defaultStyle'>
            <option value='none'>No Default</option>
            $styleOptions
        </select></p>

        <input type="hidden" name="updated" id="updated" value="yes">

	    <p><input type="submit" name="Submit" value="Update Options" /></p>

	    </form>

    </div>
HTML;

    echo $html;

}

function nexternal_display_instructions_menu() {
  $imgdir = plugins_url( 'img' , __FILE__ );
  $plugindir = plugins_url( '' , __FILE__ );
  $settings_link = '<a href="'.admin_url( 'admin.php?page=nexternal_menu').'">Nexternal Settings</a>';
  echo <<<HTML
<div class="wrap">
<h2>Nexternal Configuration Instructions</h2>
<h3>Use these instructions to activate the XML Tools API and connecting the Nexternal WordPress plugin</h3>
<ol>
<li>Make sure your Nexternal account has the privileges required to use the Nexternal XML Tools API.  To do so, first login to your OMS and click on settings:
  <br/><img src="$imgdir/image003.png"/><br/><br/></li>
<li>Scroll all the way to the bottom of the Settings page and click on Edit next to XML Tools:
  <br/><img src="$imgdir/image004.png"/><br/><br/></li>
<li>Accept the XML Memorandum of Understanding (you may have already done so).
  <br/><img src="$imgdir/image002.png"/><br/><br/></li>
<li>Assign XML Tools access to a user account. You can add access to your existing user account or you can create a new user account. To add a new user account click on Users, and then New:
  <br/><img src="$imgdir/image001.png"/><br/><br/></li>
<li>Create a new user account complete with all the appropriate details such as username and password, etc... and be sure to add XML Tools privileges as shown:
  <br/><img src="$imgdir/image005.png"/><br/><br/></li>
<li>You can now use this user's credentials in the $settings_link.<br/><br/></li>
<li>After your credentials are accepted you can add products to pages and posts using the "N" pushbutton and shortcode generator.
  <br/><img src="$plugindir/screenshot-3.png"/><br/><br/></li>
<li>Begin by entering your product description in the top box and then highlight to product you wish to add and then click add.  You will then see your product (list) in the box. Select the remaining options and then click Insert to generate the shortcode.
  <br/><img src="$plugindir/screenshot-4.png"/><br/><br/></li>
</ol>
</div>
HTML;
}

/* Add a 'Settings' link in the plugins page */
function nexternal_settings_link($links) {
  $settings_link = '<a href="'.admin_url( 'admin.php?page=nexternal_menu').'">Settings</a>';
  $links[] = $settings_link;
  return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'nexternal_settings_link' );



/* Display a notice that can be dismissed */
add_action('admin_notices', 'nexternal_admin_notice');
function nexternal_admin_notice() {
	global $current_user ;
        $user_id = $current_user->ID;

    $data = get_option('nexternal');
    $userName = $data['userName'];
    if(!empty($_POST['nexternal_username'])) {
      $userName = $_POST['nexternal_username'];
    }

        /* Check that the user hasn't already clicked to ignore the message */
	if ( !$userName) {
        echo '<div class="error"><p>';
        echo __('Nexternal has updated the XMLTools API and your credentials need to be re-authenticated via the updated method.  Please <a href="'.admin_url( 'admin.php?page=nexternal_menu&tab=instruction').'">review the instructions</a> and then <a href="'.admin_url( 'admin.php?page=nexternal_menu&tab=account').'">re-link your account</a>.');
        echo "</p></div>";
	}
}
add_action('admin_init', 'nexternal_msg_ignore');
function nexternal_msg_ignore() {
	global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['nexternal_msg_ignore']) && '0' == $_GET['nexternal_msg_ignore'] ) {
             add_user_meta($user_id, 'nexternal_ignore_notice', 'true', true);
	}
}

?>
