<?php

class ItemManager{
    private $m_aItems;
    private $m_nCharacterID;

    public function __construct($nCharacterID, $bLoadFromBattleNet = false){
        $this->m_nCharacterID   =   $nCharacterID;
        if ($bLoadFromBattleNet){
            $this->loadItemsFromBattleNet();
        } else {
            $this->loadItemsFromDatabase();
        }
    }

    public function loadItemsFromDatabase(){
        $this->m_aItems =   array();
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                    audit_item_id
                                FROM
                                    audit_items
                                WHERE
                                    audit_fk  IN (
                                        SELECT
                                            MAX(audit_id)
                                        FROM
                                            audits
                                        WHERE
                                            character_fk    =   "   .   $this->m_nCharacterID   .   "
                                        )
                                ";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                while ($aRow = $oRes->fetch_assoc()){
                    $oItem              =   new Item();
                    $oItem->loadItemFromDatabase($aRow['audit_item_id']);
                    $this->m_aItems[$oItem->getItemSlot()] =   $oItem;
                }
            } else {
                Utils::debugLog("No_Audits", "No audits for character with id: " . $this->m_nCharacterID);
            }
        }
    }

    public function loadItemsFromBattleNet(){
        $this->m_aItems =   array();
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                character_name,
                                realm_fk
                            FROM
                                characters
                            WHERE
                                character_id  = "   .   $this->m_nCharacterID   .   "
                            LIMIT
                                1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow           =   $oRes->fetch_assoc();
                $aRealm         =   Utils::getRealmByID($aRow['realm_fk']);
                $sCharacterName =   $aRow['character_name'];

                $aFields    =   array('items','audit');

                $aCharData  = Utils::battleNetCharacterCurl($aRealm['region'], $aRealm['name'], $sCharacterName, $aFields);
                if (isset($aCharData['status']) && $aCharData['status'] ==  'nok'){
                    Utils::debugLog("No_BNet_Response", $aCharData['reason']);
                } else {
                    $aItems =   $aCharData['items'];
                    foreach(Utils::$m_aItemSlots as $sItem => $nID){
                        if (isset($aItems[$sItem])){
                            $oItem                  =   new Item();
                            $oItem->loadItemFromArray($sItem, $aItems[$sItem]);
                            $this->m_aItems[$sItem] =   $oItem;
                        } else if ($sItem != 'offHand') {
                            $oItem                  =   new Item();
                            $oItem->loadItemFromArray($sItem);
                            $this->m_aItems[$sItem] =   $oItem;
                        }
                    }
                }
            } else {
                Utils::debugLog('Item_Error', 'Cannot load items for a character that has not been stored');
            }
        }
    }

   public function storeItemsAsAudit(){
       $oCon   =   Utils::getConnection('guild_hub');
       if ($oCon){
           //create a new audit
           $nAuditID    =   -1;
           $sSQL        =   "   SELECT
                                      audit_id
                                FROM
                                    audits
                                WHERE
                                    DATE(date)      =   DATE(NOW())                             AND
                                    character_fk    =   "   .   $this->m_nCharacterID   .   "
                                LIMIT 1";
           Utils::debugLog('SQL_Query', $sSQL);
           $oRes    =   $oCon->query($sSQL);
           if ($oRes){
               $aRow    =   $oRes->fetch_assoc();
               if (isset($aRow['audit_id'])){
                   $nAuditID    =   $aRow['audit_id'];
               }
           }
           if (!($nAuditID > 0)){
               $sSQL    =   "   INSERT INTO
                                audits (
                                    character_fk,
                                    date)
                            VALUES (
                                "   .   $this->m_nCharacterID   .   ",
                                NOW() )";
               Utils::debugLog('SQL_Query', $sSQL);
               $oCon->query($sSQL);
               if ($oCon->affected_rows > 0){
                   $nAuditID    =   $oCon->insert_id;
               }
           }

           //store each item
           foreach ($this->m_aItems as $oItem){
               $nItemLevel  =   $oItem->getItemLevel();
               $nItemID     =   $oItem->getItemID();
               $sItemSlot   =   $oItem->getItemSlot() ;
               $sItemSQL    =   "   INSERT INTO
                                        audit_items (
                                            item_id,
                                            audit_fk,
                                            item_slot,
                                            item_level
                                        )
                                    VALUES (
                                        $nItemID,
                                        "   .   $nAuditID               .   ",
                                        '$sItemSlot',
                                        $nItemLevel
                                    )
                                    ON DUPLICATE KEY UPDATE
                                        item_id     =   $nItemID,
                                        item_slot   =   '$sItemSlot',
                                        item_level  =   $nItemLevel";
               Utils::debugLog('SQL_Query', $sItemSQL);
               $oCon->query($sItemSQL);
               
           }
       }
   }
    public function toArray(){
        $aItems =   array();
        if (sizeof($this->m_aItems) > 0){
            foreach(Utils::$m_aItemSlots as $sItem => $nEnabled){
                if (isset($this->m_aItems[$sItem])){
                    $oItem  =   $this->m_aItems[$sItem];
                } else {
                    $oItem                  =   new Item();
                    $oItem->loadItemFromArray($sItem);
                }

                $aItems[$sItem] =   $oItem->toArray();
            }
        } else {
            Utils::debugLog('No_Items', 'No Items loaded for this character');
        }
        return $aItems;
    }

}

class Item{
    private $m_nItemID;
    private $m_sItemSlot;
    private $m_nItemLevel;

    public function __construct(){

    }

    public function loadItemFromDatabase($nAuditItemID){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                *
                            FROM
                                audit_items
                            WHERE
                                audit_item_id = "   .   $nAuditItemID   .   "
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                $this->m_nItemID    =   $aRow['item_id'];
                $this->m_sItemSlot  =   $aRow['item_slot'];
                $this->m_nItemLevel =   $aRow['item_level'];
            } else {
                Utils::debugLog('No_Items', "No item with item id: " . $nAuditItemID);
            }
        }
    }

    public function loadItemFromArray($sItemSlot ,$aData = array()){
        $this->m_sItemSlot  =   $sItemSlot;

        if (isset($aData['itemLevel'])){
            $this->m_nItemLevel =   $aData['itemLevel'];
        } else {
            if ($sItemSlot == 'offHand'){
                $this->m_nItemLevel =   -1;
            } else {
                $this->m_nItemLevel =   0;
            }

        }

        if (isset($aData['id'])){
            $this->m_nItemID    =   $aData['id'];
        } else {
            $this->m_nItemID    =   0;
        }

    }
    public function toArray(){
        $aItem                  =   array();
        $aItem['item_id']       =   $this->m_nItemID;
        $aItem['item_slot']     =   $this->m_sItemSlot;
        if ($this->m_nItemLevel == -1){
            $aItem['item_level']    =   '-';
        } else {
            $aItem['item_level']    =   $this->m_nItemLevel;
        }

        return $aItem;
    }
    //Getters

    public function getItemLevel(){
        return $this->m_nItemLevel;
    }

    public function getItemSlot(){
        return $this->m_sItemSlot;
    }

    public function getItemID(){
        return $this->m_nItemID;
    }

}