<?php
/*
Plugin Name: Nexternal
Description: Allows users to include Nexternal product information into their posts and pages
Author: Nathan Smallcomb
Author URI: http://AlreadySetUp.com
Version: 1.3

CHANGELOG:
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
    add_menu_page("Nexternal Global Settings", "Nexternal Settings", 'manage_options', 'nexternal_menu', 'nexternal_display_menu');
}

// converts data value 'on' or empty for a checkbox to checked='yes' or nothing
function nexternal_convertDataToChecked($dataValue) {
    if ($dataValue == 'on') return "checked='yes'";
    return '';
}

function nexternal_display_menu() {

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

    // check to see if the user submitted an accountName, username OR password information
    if (!empty($_POST['nexternal_accountName']) or !empty($_POST['nexternal_username']) or !empty($_POST['nexternal_password'])) {

        // if they are all there
        if (!empty($_POST['nexternal_accountName']) && !empty($_POST['nexternal_username']) && !empty($_POST['nexternal_password'])) {

            // try to get an active key from Nexternal
            $accountName = $_POST['nexternal_accountName'];
            $userName = $_POST['nexternal_username'];
            $password = $_POST['nexternal_password'];
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
    if ($userName != '' && $password != '' && $accountName != '') {
        $linkStatus = <<<HTML
            <p>You are currently linked to the account: $accountName. You do not neeed to enter your username and password again.</p>
            <p><input type="button" value="Link to a Different Account" onclick="document.getElementById('nexternal-link').style.display = 'block';"></p>
HTML;
        $linkDisplay = 'none';
    } else {
        $linkDisplay = 'block';
    }

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
        <h2>Nexternal Global Configuration</h2>

        $displayErrors

	    <form method="post">

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
        echo __('Your Nexternal account needs to be re-authenticated using your Nexternal username and password.  Please <a href="'.admin_url( 'admin.php?page=nexternal_menu').'">re-link your account now</a>.');
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
