<?php

/*
+----------------------------------------------------------------+
+	nexternalPlugin-tinymce V1.60
+	by Deanna Schneider
+   required for nexternalPlugin and WordPress 2.5
+----------------------------------------------------------------+
*/

include_once ('../../../../wp-load.php');
include_once ('../lib/nexternal-api.php');

global $wpdb;

// check for rights
if ( !is_user_logged_in() || !current_user_can('edit_posts') ) 
	wp_die(__("You are not allowed to be here"));

$errorMessage = '';

// load variables from data to display in HTML form
$data = get_option('nexternal');
$defaultGridSizeRows = $data['defaultGridSizeRows'];
$defaultGridSizeColumns = $data['defaultGridSizeColumns'];
$defaultProductsInView = $data['defaultProductsInView'];

$displayProductRatingChecked = nexternal_convertDataToChecked($data['defaultDisplayProductRating']);
$displayProductPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductPrice']);
$displayProductOriginalPriceChecked = nexternal_convertDataToChecked($data['defaultDisplayProductOriginalPrice']);
$displayProductImageChecked = nexternal_convertDataToChecked($data['defaultDisplayProductImage']);
$displayProductNameChecked = nexternal_convertDataToChecked($data['defaultDisplayProductName']);
$displayProductShortDescriptionChecked = nexternal_convertDataToChecked($data['defaultDisplayProductShortDescription']);

$defaultCarouselTypeHorizontalSelected = ($data['defaultCarouselType'] == 'horizontal') ? ("SELECTED"):('');
$defaultCarouselTypeVerticalSelected = ($data['defaultCarouselType'] == 'vertical') ? ("SELECTED"):('');
$defaultCarouselTypeNoneSelected = ($data['defaultCarouselType'] == 'none') ? ("SELECTED"):('');
$defaultCarouselTypeSingleSelected = ($data['defaultCarouselType'] == 'single') ? ("SELECTED"):('');

// these represent whether or not to initially display the specific option divs
$coureselOptionsDisplay = 'none';
$gridOptionsDisplay = 'none';
$coureselSingleDisplay = 'none';
if ($data['defaultCarouselType'] == 'horizontal' or $data['defaultCarouselType'] == 'vertical') $coureselOptionsDisplay = 'block';
if ($data['defaultCarouselType'] == 'none') $gridOptionsDisplay = 'block';
if ($data['defaultCarouselType'] == 'single') $coureselSingleDisplay = 'block';

// load products for product drop down
$productOptions = '';
$productSKUs = '';
if ($data['activeKey'] == '' || $data['accountName'] == '') {
    wp_die(__("Unable to load product list, please make sure you<br>are linked to an account in the Nexternal Plugin Configuration. (1)"));
} else {
    $url = 'https://www.nexternal.com/shared/xml/productquery.rest';
    $xml = generateProductQueryRequest($data['accountName'], $data['activeKey']);
    $xmlResponse = curl_post($url, $xml);
    if ($xmlResponse == '') wp_die(__("Unable to load product list, please make sure you<br>are linked to an account in the Nexternal Plugin Configuration. (2)"));
    $xmlData = new SimpleXMLElement($xmlResponse);
    foreach ($xmlData->CurrentStatus->children() as $node) {
            $attributes = $node->attributes();
            $productName = addslashes($attributes["Name"]);
            $productSKU = $attributes["SKU"];
            $productOptions .= "\"$productName\",";
            $productSKUs .= "productSKUs['$productName'] = '$productSKU';\n";
    }
    $productOptions = substr($productOptions, 0, -1); // remove the last comma from product 
    if ($productSKUs == '') wp_die(__("There are no products in the Nexternal store: <b>" . $data['accountName'] . "</b><br>You will be unable to add a Product List to your post."));
}

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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Nexternal Product List Options</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>

	<link rel="stylesheet" href="http://jqueryui.com/themes/base/jquery.ui.all.css">

    <script src="http://code.jquery.com/jquery-1.6.3.js"></script> 
	<script src="http://jqueryui.com/ui/jquery.ui.core.js"></script> 
	<script src="http://jqueryui.com/ui/jquery.ui.widget.js"></script> 
	<script src="http://jqueryui.com/ui/jquery.ui.position.js"></script> 
	<script src="http://jqueryui.com/ui/jquery.ui.autocomplete.js"></script> 

	<script language="javascript" type="text/javascript">

    // generate SKUs javascript, since they need to be converted from productName to productSku via the ProductSku javascript scoped array
    var productSKUs = new Array();
    <?php echo $productSKUs; ?>
	
	function init() {
		tinyMCEPopup.resizeToInnerSize();
	}

    function attributeFor(identifier) {
        var value = document.getElementById(identifier).value;
        if (value != '') return " " + identifier + "=\"" + value + "\"";
        return "";
    }

    function attributeForCheckbox(identifier) {
        var value = document.getElementById(identifier).checked;
        return " " + identifier + "=\"" + value + "\"";
    }

	function insertShortcode() {

        var selectElement = document.getElementById('productSKUs');

        if (document.getElementById('product').value != '') {
            alert("You entered a product but did not select 'Add'");
            document.getElementById('product').style.background = '#FF9996';
            return false;
        }
        if (document.getElementById('id').value == '') {
            alert("You must enter a unique identifier");
            document.getElementById('id').style.background = '#FF9996';
            return false;
        }
        if (selectElement.options.length == 0) {
            alert("You must enter at least 1 product.");
            document.getElementById('productSKUs').style.background = '#FF9996';
            document.getElementById('product').style.background = '#FF9996';
            return false;
        }

        var tagtext = "[nexternal ";

        tagtext += attributeFor('id');
        tagtext += attributeFor('carousel');

        if (document.getElementById('carousel').value == 'horizontal' || document.getElementById('carousel').value == 'vertical') {
            tagtext += attributeFor('productsInView');    
        } else if (document.getElementById('carousel').value == 'none') {
            tagtext += attributeFor('gridSizeRows');
            tagtext += attributeFor('gridSizeColumns');
        }       

        tagtext += attributeForCheckbox('displayProductRating');
        tagtext += attributeForCheckbox('displayProductPrice');
        tagtext += attributeForCheckbox('displayProductOriginalPrice');
        tagtext += attributeForCheckbox('displayProductImage');
        tagtext += attributeForCheckbox('displayProductName');
        tagtext += attributeForCheckbox('displayProductShortDescription');
        tagtext += attributeFor('style');

        tagtext += ' productSKUs = "'
        var selectedArray = new Array();
        var i;
        var first = true;
        for (i = 0; i < selectElement.options.length; i++) {
            value = productSKUs[selectElement.options[i].value];
            if (!value) { alert("The product " + selectElement.options[i].value + " is invalid."); return false; }
            if (!first) tagtext += ",";
            first = false;
            tagtext += value;
            if (document.getElementById('carousel').value == 'single') break;
        }
        tagtext += '"';

        tagtext += "]";

		if(window.tinyMCE) {
			window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
			//Peforms a clean up of the current editor HTML. 
			//tinyMCEPopup.editor.execCommand('mceCleanup');
			//Repaints the editor. Sometimes the browser has graphic glitches. 
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.close();
		}
		
		return;
	}

    function updateEvent(eventValue) {
        if (eventValue == 'none') {
            document.getElementById("gridOptions").style.display = 'block';
            document.getElementById("coureselOptions").style.display = 'none';
            document.getElementById("singleOptions").style.display = 'none';  
        }
        if (eventValue == 'horizontal' || eventValue == 'vertical') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("coureselOptions").style.display = 'block'; 
            document.getElementById("singleOptions").style.display = 'none'; 
        }
        if (eventValue == 'single') {
            document.getElementById("gridOptions").style.display = 'none';
            document.getElementById("coureselOptions").style.display = 'none'; 
            document.getElementById("singleOptions").style.display = 'block'; 
        }
    }

    function addProduct() {
        productName = document.getElementById('product').value;        
        
        sku = productSKUs[productName];
        if (!sku) { alert("The product " + productName + " is invalid."); return false; }

        var newOption = document.createElement("option");
        newOption.text = productName;
        newOption.value = productName;
            
        document.getElementById('productSKUs').options.add(newOption);
        document.getElementById('productSKUs').style.background = 'white';
        document.getElementById('product').value = '';
        document.getElementById('product').focus();
        document.getElementById('product').style.background = 'white';
        return false;
    }
    
    function removeProduct() {
        var selected = document.getElementById('productSKUs');
        for(var i = selected.options.length - 1; i >= 0; i--)
            if(selected.options[i].selected) selected.remove(i);
    }

	</script>
	<base target="_self" />
</head>

<body id="link" onload="tinyMCEPopup.executeOnLoad('init();'); document.body.style.display=''; document.getElementById('product').focus();" style="display: none">

	<form name="nexternalPlugin" action="#">

	<div class="panel_wrapper" style="height: 415px; border-top: 1px solid #919B9C;">

		<div id="options_panel" class="panel current">

            <h4 style="color: black">Products in Carousel/Grid:</h4>

	        <script> 
	        $(function() {
		        var availableTags = [
			        <?php echo $productOptions; ?>
		        ];
		        $( "#product" ).autocomplete({
			        source: availableTags
		        });
	        });

	        </script> 
            <p><input id="product" name="product" style="width: 250px;" > <input type="submit" onclick="return addProduct();" value="Add" /></p>

            <p>
                <select multiple size=4 id="productSKUs" name="productSKUs" style="width: 300px"></select>
            </p>
            <p>
                <input type="button" onclick="removeProduct();" value="Remove Selected" />
            </p>

            <hr style="color: #919B9C;">

            <h4 style="color: black">Display Options</h4>

            <p>Display Type
                <select id="carousel" name="carousel" style="width: 150px;" onChange="updateEvent(this[this.selectedIndex].value);">
                    <option value="none" <?php echo $defaultCarouselTypeNoneSelected;?>>Grid</option>
                    <option value="single" <?php echo $defaultCarouselTypeSingleSelected;?>>Single Product</option>
                    <option value="horizontal" <?php echo $defaultCarouselTypeHorizontalSelected;?>>Horizontal Carousel</option>
                    <option value="vertical" <?php echo $defaultCarouselTypeVerticalSelected;?>>Vertical Carousel</option>
                </select>
            <p>

            <div id="gridOptions" style="display: <?php echo $gridOptionsDisplay;?>;">
                <label for="gridSizeRows">Grid Size (Width, Height):</label> (
                <input type="text" id="gridSizeColumns" name="gridSizeColumns" style="width: 25px" value="<?php echo $defaultGridSizeColumns;?>">,
                <input type="text" id="gridSizeRows" name="gridSizeRows" style="width: 25px" value="<?php echo $defaultGridSizeRows;?>">
                )
            </div>
            <div id="coureselOptions" style="display: <?php echo $coureselOptionsDisplay?>;">
                <label for="productsInView">Number of Visible Products in Carousel:</label> <input type="text" id="productsInView" name="productsInView" style="width: 25px" value="<?php echo $defaultProductsInView;?>">
            </div>
            <div id="singleOptions" style="display: <?php echo $coureselSingleDisplay?>;">
                <b>Note:</b> The first product in the Product List (above) will be displayed.
            </div>
     
            <p>
                <input type="checkbox" id="displayProductRating" name="displayProductRating" <?php echo $displayProductRatingChecked;?>> Display Product Rating?<br>       
                <input type="checkbox" id="displayProductPrice" name="displayProductPrice"<?php echo $displayProductPriceChecked;?>> Display Product Price?<br>
                <input type="checkbox" id="displayProductOriginalPrice" name="displayProductOriginalPrice" <?php echo $displayProductOriginalPriceChecked;?>> Display Product's Original Price?<br>
                <input type="checkbox" id="displayProductImage" name="displayProductImage" <?php echo $displayProductImageChecked;?>> Display Product's Image?<br>
                <input type="checkbox" id="displayProductName" name="displayProductName" <?php echo $displayProductNameChecked;?>> Display Product's Name?<br>
                <input type="checkbox" id="displayProductShortDescription" name="displayProductShortDescription" <?php echo $displayProductShortDescriptionChecked;?>> Display Product's Short Description?
            </p>

            <p>Select a Style:
            <select id='style' name='style'>
                <?php echo $styleOptions; ?>
            </select></p>

            <p><b>Unique Identifier</b>: <input id="id" name="id" value="productList-<?php echo dechex(rand(0, hexdec('ffff'))); ?>" size="15"/> (A-Z, 0-9 and hyphens only)</p>

		</div>

	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="button" id="insert" name="insert" value="Insert" onclick="insertShortcode();"  />
		</div>
	</div>

</form>
</body>
</html>
<?php

?>
