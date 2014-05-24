<?php

include_once '../settings.php';

$aRegions   =   array('US', 'EU');


print "Updating Database<br>";
$nCount = array();
$oCon   =   Utils::getConnection('guild_hub');
if ($oCon){
    foreach ($aRegions as $sRegion){
        $nCount[$sRegion]   =   0;


        $aRealmsData = Utils::battleNetRealmCurl($sRegion);
        $aRealmsData = $aRealmsData['realms'];

        foreach ($aRealmsData as $aRealm){
            $sSQL   =   '  INSERT INTO realms (locale, realm_name, slug, region)
                            VALUES ("' . $aRealm['locale'] . '", "' . $aRealm['name'] . '", "' . $aRealm['slug'] . '", "' . $sRegion . '")
                            ON DUPLICATE KEY UPDATE locale="' . $aRealm['locale'] . '",realm_name="' . $aRealm['name'] . '"';
            $oCon->query($sSQL);
            $nCount[$sRegion]++;
        }
        print "Added " . $nCount[$sRegion] . " realms to $sRegion <br>";
    }
}



print "<br>Updating CacheFile<br>";

$oCon   =   Utils::getConnection('guild_hub');
if ($oCon){
    $sSQL   =   "SELECT * FROM realms";
    $oRes   =   $oCon->query($sSQL);
    file_put_contents('../cache/cache.realms.php', "<?php\n");
    $sCacheRealms       =   "\n\$g_aRealms = array();\n";
    $sCacheRealmsByID   =   "\n\$g_aRealmById = array();\n";
    while ($aRow = $oRes->fetch_assoc()){
        $sCacheRealms       .=  "\$g_aRealms[\"" . strtolower($aRow['region']) . "_" . strtolower($aRow['slug']) . '"] = array(' . "\n" .
            '    "id" =>"'. $aRow['realm_id'] .'", ' . "\n" .
            '    "name" => "' . $aRow['realm_name'] . '", ' . "\n" .
            '    "region" => "' . $aRow['region'] . '", ' . "\n" .
            '    "slug" => "' . $aRow['slug'] . '", ' . "\n" .
            '    "locale" => "' . $aRow['locale'] . '"' . "\n" .
            ');' . "\n";
        $sCacheRealmsByID   .=   "\$g_aRealmById['" . $aRow["realm_id"] . '\'] = array(' . "\n" .
            '    "id"   =>"'. $aRow['realm_id'] .'", ' . "\n" .
            '    "name" =>"'. $aRow['realm_name'] .'", ' . "\n" .
            '    "region" => "' . $aRow['region'] . '", ' . "\n" .
            '    "slug" => "' . $aRow['slug'] . '", ' . "\n" .
            '    "locale" => "' . $aRow['locale'] . '"' . "\n" .
            ');' . "\n";
    }
    file_put_contents('../cache/cache.realms.php',$sCacheRealms . $sCacheRealmsByID, FILE_APPEND);
}