<?php

function generateProductQueryRequest($accountName, $key) {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ProductQueryRequest>
  <Credentials>
    <AccountName>$accountName</AccountName>
    <Key>$key</Key>
  </Credentials>
  <ProductNoRange>
    <ProductNoStart>0</ProductNoStart>
    <ProductNoEnd>999999999</ProductNoEnd>
  </ProductNoRange>
  <CurrentStatus />
</ProductQueryRequest>
XML;
    return $xml;
}

function generateTestSubmitRequest($accountName, $username, $password) {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<TestSubmitRequest>
  <Credentials>
    <AccountName>$accountName</AccountName>
    <UserName>$username</UserName>
    <Password>$password</Password>
  </Credentials>
</TestSubmitRequest>
XML;
    return $xml;
}

function generateTestVerifyRequest($accountName, $username, $password, $testKey, $testKeyLocation) {

    if ($testKeyLocation == 'Attribute') {

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<TestVerifyRequest>
  <Credentials Key="$testKey">
    <AccountName>$accountName</AccountName>
    <UserName>$username</UserName>
    <Password>$password</Password>
  </Credentials>
</TestVerifyRequest>
XML;

    } else { // node

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<TestVerifyRequest>
  <Credentials>
    <AccountName>$accountName</AccountName>
    <UserName>$username</UserName>
    <Password>$password</Password>
    <Key>$testKey</Key>
  </Credentials>
</TestVerifyRequest>
XML;

    }

    return $xml;

}

function generateProductQuery($accountName, $key, $SKU) {

    return <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<ProductQueryRequest>
  <Credentials>
    <AccountName>$accountName</AccountName>
    <Key>$key</Key>
  </Credentials>
  <ProductSKU>$SKU</ProductSKU>
  <IncludeReviews></IncludeReviews>
</ProductQueryRequest>
XML;

}


function curl_post($url, $xml, $test=false) {

    if ($test) echo "sending:\n$xml\nto: $url\n\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch);

    if ($test) echo "received response:\n$output\n\n";

    return $output;
}

function nexternal_getActiveKey($accountName, $username, $password) {

    // send TestSubmitRequest
    $url = "https://www.nexternal.com/shared/xml/testsubmit.rest";
    $xml = generateTestSubmitRequest($accountName, $username, $password);
    $xmlResponse = curl_post($url, $xml);

    // get key from TestSubmitReply
    if (!$xmlResponse) return null;
    $xmlData = new SimpleXMLElement($xmlResponse);
    $testKey = $xmlData->TestKey;
    $attributes = $xmlData->attributes();
    $testKeyLocation = $attributes['Type'];

    // send TestVerifyRequest
    $url = "https://www.nexternal.com/shared/xml/testverify.rest";
    $xml = generateTestVerifyRequest($accountName, $username, $password, $testKey, $testKeyLocation);
    $xmlResponse = curl_post($url, $xml);

    // get activeKey from TestVerifyReply
    $xmlData = new SimpleXMLElement($xmlResponse);
    $activeKey = $xmlData->ActiveKey;

    //echo "ActiveKey received: $activeKey\n\n";

    return $activeKey . '';
}


?>
