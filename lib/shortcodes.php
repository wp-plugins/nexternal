<?php

class nexternal_shortcodes {

	function nexternal_shortcodes() {
		add_shortcode( 'nexternal', array(&$this, 'show_products') );
	}

    /**
     * Turns shortcode into HTML
    */
	function show_products ($atts) {

        $data = get_option('nexternal');
        if ($data['activeKey'] == '') return; // abort if there is no active key

        $randomId = "default-" . dechex(rand(0, hexdec('ff')));

        // all variables needs to be lower cased
		extract(shortcode_atts(array(
			'carousel' => 'none',
			'productsinview' => '3',
			'displayproductrating' => 'false',
			'displayproductprice' => 'false',
			'displayproductoriginalprice' => 'false',
			'displayproductimage' => 'true',
			'displayproductname' => 'true',
			'displayproductshortdescription' => 'false',
            'gridsizerows' => '3',
            'gridsizecolumns' => '3',
			'productskus' => 'false',
            'style' => 'none',
            'id' => $randomId
		), $atts));

        $sku = explode(",", $productskus);

        if ($style == 'none') $style = $data['defaultStyle'];

        // use 'grid' instead of 'none' for carousel for readability in CSS
        if ($carousel == 'none') $carousel = 'grid';

        // determine how many items to render
        $itemsToDisplay = $gridsizerows * $gridsizecolumns;
        if ($carousel == 'horizontal' || $carousel == 'vertical') $itemsToDisplay = count($sku);
        if ($carousel == 'single') $itemsToDisplay = 1;

        // remove the .css extension from $style
        $style = substr($style, 0, strlen($style)-4);

        // display navigation button if this a carousel
        if ($carousel == 'horizontal' || $carousel == 'vertical') $out .= "<a class='nexternal-$style-$carousel-previous $id-previous'></a>";

        $out .= "<div class='nexternal-$style-$carousel $id'><ul>";

        for ($i = 0; $i < $itemsToDisplay; $i++) {

            $productSKU = $sku[$i];
            $productData = $data['productData'][$productSKU];

            // if the product data is not available OR the product data has expired, reload it from nexternal
            if (!$productData or $productData['expires'] < time()) {
                // retreive product data

                $url = "https://www.nexternal.com/shared/xml/productquery.rest";
                $xml = generateProductQuery($data['accountName'], $data['activeKey'], $productSKU);
                $xmlResponse = curl_post($url, $xml);
                $xmlData = new SimpleXMLElement($xmlResponse);

                // calculate average rating
                if ($xmlData->Product->ProductReviews->ProductReview) {
                    $totalRating = 0;
                    $reviews = $xmlData->Product->ProductReviews->ProductReview;
                    foreach ($reviews as $review) $totalRating += $review->Rating;
                    $productData['rating'] = $totalRating / count($reviews);
                } else {
                    $productData['rating'] = '';
                }

                // calculate original and current price
                $price = floatval($xmlData->Product->Pricing->Price);
                $originalPrice = $price;
                if ($xmlData->Product->Pricing->Price['PercentDiscount']) {
                    $percentDiscount = $xmlData->Product->Pricing->Price['PercentDiscount']/100;
                    $discountPrice = $price * (1-$percentDiscount);
                    $price = floor($discountPrice*100)/100;
                }
                if ($price == 0) {                 
                    foreach ($xmlData->Product->SKU as $SKU)
                        foreach ($SKU as $element) if ($element->getName() == "Default") $price = $SKU->Pricing->Price . '';
                }

                $productData['price'] = $price;
                $productData['originalPrice'] = $originalPrice;

                $productData['name'] = $xmlData->Product->ProductName . '';
                $productData['image'] = $xmlData->Product->Images->Thumbnail . ''; // optionally ->Main or ->Large
                $productData['url'] = $xmlData->Product->ProductLink->StoreFront . '';
                $productData['shortDescription'] = $xmlData->Product->Description->Short . '';

                $cacheDurationMin = 23*60*60;
                $cacheDurationMax = 25*60*60;
                $productData['expires'] = time() + rand($cacheDurationMin, $cacheDurationMax); // expire randomly, between 23 and 24 hours from now

                $data['productData'][$productSKU] = $productData;
                update_option('nexternal', $data);
            }

            // extract the values to show from the cache/datastore
            $productName = $productData['name'];
            $productPrice = $productData['price'];
            $productUrl = $productData['url'];
            $productImage = $productData['image'];
            $productOriginalPrice = $productData['originalPrice'];
            $productShortDescription = $productData['shortDescription'];
            $productRating = $productData['rating'];

            $productRatingWidth = $productRating * 20;

            // if we're generating a grid and at the end of the row, we need to step to the next row by closing the <ul> and starting another
            if ($carousel == 'grid' && $i % $gridsizecolumns == 0 && $i != 0) $out .= "<br style='clear: both;'>";

            if ($productName != '') {
            
                $out .= "<li class='nexternal-$style-product nexternal-$style-product-$i nexternal-$style-product-sku-$productSKU'>";
                
                if ($displayproductname == 'true')
                    $out .= "<div class='nexternal-$style-product-name nexternal-$style-product-name-$productSKU'><a href='$productUrl'>$productName</a></div>";
                    
                if ($displayproductimage == 'true')
                    $out .= "<div class='nexternal-$style-product-image nexternal-product-image-$productSKU'><a href='$productUrl'><img src='$productImage' border='0'></a></div>";
                    
                if ($displayproductrating == 'true' and $productRating != '')
                    $out .= "<div class='nexternal-$style-product-rating nexternal-$style-product-rating-$productSKU' style='width: ".$productRatingWidth."px'></div>";

                if ($displayproductshortdescription == 'true')
                    $out .= "<div class='nexternal-$style-product-shortdescription nexternal-$style-product-shortdescription-$productSKU'>$productShortDescription</div>";
                    
                if ($displayproductoriginalprice == 'true' and $productOriginalPrice != $price)
                    $out .= "<div class='nexternal-$style-product-original-price nexternal-$style-product-original-price-$productSKU'>$$productOriginalPrice</div>";
                    
                if ($displayproductprice == 'true')
                    $out .= "<div class='nexternal-$style-product-price nexternal-$style-product-price-$productSKU'>$$productPrice</div>";
                    
                $out .= "</li>";
            }

        }

        $out .= "</ul></div>";

        // display navigation button if this a carousel
        if ($carousel == 'horizontal' || $carousel == 'vertical') $out .= "<a class='nexternal-$style-$carousel-next $id-next'></a>";

        if ($carousel == 'horizontal' || $carousel == 'vertical') {
            // create the carousel via javascript

            $out .= '<script type="text/javascript">';
            $out .= '    jQuery(function() {';
            $out .= '        jQuery(".'.$id.'").jCarouselLite({';
            $out .= '        btnNext: ".'.$id.'-next",';
            $out .= '        btnPrev: ".'.$id.'-previous",';
            $out .= '        visible: '.$productsinview.'';
            if ($carousel == 'vertical') $out .= ',        vertical: true';
            $out .= '    });';
            $out .= '});';
            $out .= '</script>';
        }

		return $out;
	}

}

$nexternalShortcodes = new nexternal_Shortcodes;	

?>