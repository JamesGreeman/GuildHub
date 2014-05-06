<?php

include_once '../settings.php';

$sAction    =   "";
if(isset($_REQUEST['action'])){
$sAction    =   $_REQUEST['action'];
}

//action get character array with item level
if ($sAction == 'getGuildMemberItems'){
    if (isset($_REQUEST['guildName']) && isset($_REQUEST['region']) && isset($_REQUEST['realm']) ){
        $oGuildManager  =   new GuildManager($_REQUEST['region'], $_REQUEST['realm'], $_REQUEST['guildName']);
        $oGuildManager->loadGuildMembers();
        $oGuildManager->loadGuildMemberItems();
        if (isset($_REQUEST['filter'])){
            $sFilter    =   $_REQUEST['filter'];
        } else {
            $sFilter    =   "";
        }
        if (isset($_REQUEST['sort'])){
            $sSort  =   $_REQUEST['sort'];
        } else {
            $sSort  =   "";
        }
        if (isset($_REQUEST['limit'])){
            $sLimit =   $_REQUEST['limit'];
        } else {
            $sLimit =   "";
        }
        $aData  =   $oGuildManager->toArray($sFilter, $sSort, $sLimit);
        print json_encode($aData, true);
    } else {
        Utils::debugLog("Invalid_Parameters", "getGuildMemberItems requires 'guildName', 'region' and 'realm' parameters to be set");
    }
}