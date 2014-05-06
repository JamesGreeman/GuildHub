<?php

include_once'../settings.php';
include_once __INCDIR__ . '/inc.utils.php';

$oCon   =   Utils::getConnection('guild_hub');
if ($oCon){
    $sSQL   =   "   SELECT
                        guild_name,
                        realm_fk
                    FROM
                        guilds
                    LIMIT 1000";
    Utils::debugLog('SQL_Query', $sSQL);
    $oRes   =   $oCon->query($sSQL);
    if ($oRes){
        while ($aRow = $oRes->fetch_assoc()){
            $aRealm         =   Utils::getRealmByID($aRow['realm_fk']);
            $sGuildName     =   $aRow['guild_name'];
            $oGuildManager  =   new GuildManager($aRealm['region'], $aRealm['name'], $sGuildName);
            $oGuildManager->loadGuildMembers();
            $oGuildManager->runAudit();
        }
    } else {
        Utils::debugLog('Guild_Error', "failed to select guilds for audit");
    }
}

