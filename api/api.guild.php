<?php

include_once '../settings.php';

$sAction    =   "";
if(isset($_REQUEST['action'])){
$sAction    =   $_REQUEST['action'];
}
if (isset($_REQUEST['filter'])){
    $sFilter    =   $_REQUEST['filter'];
} else {
    $sFilter    =   "";
}
if (isset($_REQUEST['sort'])){
    $sSort  =   $_REQUEST['sort'];
} else {
    $sSort  =   "character_name:asc";
}
if (isset($_REQUEST['limit'])){
    $sLimit =   $_REQUEST['limit'];
} else {
    $sLimit =   "";
}

//action get character array with item level
if ($sAction == 'getGuildMemberItems'){
    if (isset($_REQUEST['guild_id'])){
        $nID            =   $_REQUEST['guild_id'];
        $aGuildInfo     =   GuildManager::getGuildInfo($nID);
        $oGuildManager  =   new GuildManager($aGuildInfo['realm_info']['region'], $aGuildInfo['realm_info']['name'], $aGuildInfo['guild_name']);
        $oGuildManager->loadGuildMembers();
        $oGuildManager->loadGuildMemberItems();
        $aData  =   $oGuildManager->getGuildMemberItemArray($sFilter, $sSort, $sLimit);
        print json_encode($aData, true);
    } else {
        Utils::debugLog("Invalid_Parameters", "getGuildMemberItems requires 'guild_id' to be set");
    }
}

//action get guild detail array
if ($sAction == 'getGuildMembers'){
    if (isset($_REQUEST['guild_id'])){
        $nID            =   $_REQUEST['guild_id'];
        $aGuildInfo     =   GuildManager::getGuildInfo($nID);
        $oGuildManager  =   new GuildManager($aGuildInfo['realm_info']['region'], $aGuildInfo['realm_info']['name'], $aGuildInfo['guild_name']);
        $oGuildManager->loadGuildMembers();
        $aData  =   $oGuildManager->getGuildMemberDetailArray($sFilter, $sSort, $sLimit);
        print json_encode($aData, true);
    } else {
        Utils::debugLog("Invalid_Parameters", "getGuildMembers requires 'guild_id' to be set");
    }
}

//action add character to guild
if ($sAction == 'addCharacter'){
    if (isset($_REQUEST['guild_id']) && isset($_REQUEST['character_name']) && isset($_REQUEST['region']) && isset($_REQUEST['realm'])){
        $nID            =   $_REQUEST['guild_id'];
        $aGuildInfo     =   GuildManager::getGuildInfo($nID);
        $oGuildManager  =   new GuildManager($aGuildInfo['realm_info']['region'], $aGuildInfo['realm_info']['name'], $aGuildInfo['guild_name']);
        $aData  = $oGuildManager->addCharacter($_REQUEST['region'], $_REQUEST['realm'], $_REQUEST['character_name']);
        print json_encode($aData, true);
    } else {
        Utils::debugLog("Invalid_Parameters", "getGuildMemberItems requires 'guild_id', 'character_name', 'region' and 'realm' to be set");
    }
}

//action to remove character from guild
if ($sAction == 'removeCharacter'){
    if (isset($_REQUEST['guild_id']) && isset($_REQUEST['character_id'])){
        $nID            =   $_REQUEST['guild_id'];
        $aGuildInfo     =   GuildManager::getGuildInfo($nID);
        $oGuildManager  =   new GuildManager($aGuildInfo['realm_info']['region'], $aGuildInfo['realm_info']['name'], $aGuildInfo['guild_name']);
        $nID            =   $_REQUEST['character_id'];
        $aCharacterInfo =   CharacterManager::getCharacterInfo($nID);
        $aData  = $oGuildManager->removeCharacter($aCharacterInfo['realm_info']['region'], $aCharacterInfo['realm_info']['name'], $aCharacterInfo['character_name']);
        print json_encode($aData, true);
    } else {
        Utils::debugLog("Invalid_Parameters", "getGuildMemberItems requires 'guild_id', 'character_id' to be set");
    }
}

