<?php

class ReportManager{
    public $m_oGuildManager;
    
    public function __construct($aData){
        $this->m_oGuildManager  =   new GuildManager();
        $this->m_oGuildManager->loadGuildFromDatabase($aData);

    }

    public function getILvlArray($aData){
        $aILvlData                      =   array();

        $aGuildMembers  =   $this->m_oGuildManager->loadGuildMembers($aData);

        foreach($aGuildMembers as $aCharInfo){
            if (isset($aCharInfo['character_name']) && isset($aCharInfo['character_id'])){
                $sCharName  =   $aCharInfo['character_name'];

                Utils::debugLog("Status", "Getting Audit Items for: $sCharName");
                $aItems =   $this->getRecentAudit($aCharInfo['character_id']);

                if ($aItems){
                    $aNewRow                        =   array();
                    foreach (Utils::$m_aItemSlots as $sItem => $nVal){

                        Utils::debugLog("Status", "Adding item Level for: $sItem");
                        if (isset($aItems[$sItem]) && ($aItems[$sItem] > 0)){
                            $sItemLevel =   $aItems[$sItem];
                        } else if ($sItem == 'offHand' && isset($aItems['mainHand']) && $aItems['mainHand'] > 0){
                            $sItemLevel =   $aItems['mainHand'];
                        } else{
                            $sItemLevel =   '-';
                        }
                        $sItem              =   ucfirst($sItem);
                        $aNewRow[$sItem]    =   $sItemLevel;
                    }
                    $aILvlData[$sCharName]    =   $aNewRow;

                } else {
                    Utils::debugLog("Error", "No Items returned");
                }

            } else {
                Utils::debugLog("Error", "No Character info");
            }
        }

        return $aILvlData;
    }

    public function getRecentAudit($nCharID){
        $aItems =   array();
        if($oCon   =   Utils::getConnection('guild_hub')){
            $sSQL   =   "
                SELECT *
                FROM audit_items
                WHERE audit_fk in (
                    SELECT MAX(audit_id)
                    FROM audit
                    WHERE character_fk = $nCharID
                )
            ";

            Utils::debugLog("Status", "Running Query: $sSQL");
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                while ($aRow = $oRes->fetch_assoc()){
                    $aItems[$aRow['item_slot']] =   $aRow['item_level'];
                }
            } else {
                Utils::debugLog("Error", "No Result");
            }
        } else {
            Utils::debugLog("Error", "No Connection");
        }
        return $aItems;
    }
}