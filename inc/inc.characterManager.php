<?php

class CharacterManager{
    private $m_nCharacterID;
    private $m_sCharacterName;
    private $m_nRealmID;
    private $m_sGuildName;
    private $m_nGuildID;        //-1 represents no guild, -2 represents that the guild does not exist within the database
    private $m_nLevel;

    private $m_nRaceID;
    private $m_nClassID;
    private $m_nMainSpecID;
    private $m_nOffSpecID;
    private $m_sFaction;

    private $m_oItems;

    private $m_nUserID;

    private $m_sThumbnailURL;

    public function __construct($sRegion, $sRealm, $sName){
        $this->m_sCharacterName    =   $sName;
        $this->m_nRealmID           =   Utils::getRealmByName($sRegion, $sRealm)['id'];
        //if the guild exists it is loaded, if it doesn't is created - character information is not loaded
        if ($this->characterExists()){
            $this->loadCharacterFromDatabase();
        } else {
            $this->m_nCharacterID   =   0;
            $this->loadCharacterFromBattleNet();
            $this->storeCharacter();
        }
    }

    //Loading Functions

    //load details of the character
    public function loadCharacterFromDatabase(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "  SELECT
                              *
                           FROM
                              characters
                           WHERE
                              character_name    = '"  .   $this->m_sCharacterName   .   "'  AND
                              realm_fk          = "   .   $this->m_nRealmID         .   "
                           LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                $this->m_nCharacterID   =   $aRow['character_id'];
                $this->m_nClassID       =   $aRow['class_fk'];
                $this->m_nMainSpecID    =   $aRow['spec_fk'];
                $this->m_nOffSpecID     =   $aRow['offspec_fk'];
                $this->m_nLevel         =   $aRow['level'];
                $this->m_nRealmID       =   $aRow['realm_fk'];
                $this->m_nGuildID       =   $aRow['guild_fk'];
                $this->m_nUserID        =   $aRow['user_fk'];

            } else {
                Utils::debugLog("No_Character", "No character with that ID");
            }
        }
    }

    //load character information from battle.net
    public function loadCharacterFromBattleNet(){
        $aFields    =   array('guild', 'talents');
        $aRealm     =   Utils::getRealmByID($this->m_nRealmID);
        $aCharData  =   Utils::battleNetCharacterCurl($aRealm['region'], $aRealm['name'], $this->m_sCharacterName, $aFields);

        if (isset($aCharData['status']) && $aCharData['status'] == "nok"){
            Utils::debugLog("No_BNet_Response", $aCharData['reason']);
        } else {
            $this->m_nClassID       =   $aCharData['class'];
            $this->m_nRaceID        =   $aCharData['race'];
            $this->m_sFaction       =   Utils::getRaceByID($this->m_nRaceID)['faction'];
            $this->$m_sThumbnailURL =   $aCharData['thumbnail'];
            $this->m_nLevel         =   $aCharData['level'];
            if (isset($aCharData['guild']['name'])){
                $this->m_sGuildName =   $aCharData['guild']['name'];
                $this->m_nGuildID   =   GuildManager::getGuildID($this->m_sGuildName, $this->m_nRealmID);
            } else {
                $this->m_sGuildName =   "none";
                $this->m_nGuildID   =   -1;
            }

            if (isset($aCharData['talents'][0]['spec'])){
                $sMainSpecName       =   $aCharData['talents'][0]['spec'];
                $this->m_nMainSpecID =   Utils::getSpecIDByName($this->m_nClassID, $sMainSpecName);
            } else {
                Utils::debugLog("Invalid_BNet_Response", "No Spec data returned from battle.net");
            }


            if (isset($aCharData['talents'][1]['spec'])){
                $sOffSpecName       =   $aCharData['talents'][1]['spec'];
                $this->m_nOffSpecID =   Utils::getSpecIDByName($this->m_nClassID, $sOffSpecName);
            } else {
                $this->m_nOffSpecID =   -1;
            }
        }
    }

    function loadItemDetailsFromDatabase(){
        if ($this->m_nCharacterID > 0){
            $this->m_oItems =   new ItemManager($this->m_nCharacterID);
        } else {
            Utils::debugLog("Item_Error", "Cannot load items for a character that does not exist");
        }
    }

    function loadItemDetailsFromBattleNet(){
        if ($this->m_nCharacterID > 0){
            $this->m_oItems =   new ItemManager($this->m_nCharacterID, true);
        } else {
            Utils::debugLog("Item_Error", "Cannot load items for a character that does not exist");
        }
    }


    //Store the character state
    public function storeCharacter(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   INSERT INTO
                                characters  (
                                    character_id,
                                    character_name,
                                    realm_fk,
                                    class_fk,
                                    spec_fk,
                                    offspec_fk,
                                    level,
                                    guild_fk,
                                    user_fk
                                )
                            VALUES  (
                               "    . $this->m_nCharacterID     .   ",
                               '"   . $this->m_sCharacterName   .   "',
                               "    . $this->m_nRealmID         .   ",
                               "    . $this->m_nClassID         .   ",
                               "    . $this->m_nMainSpecID      .   ",
                               "    . $this->m_nOffSpecID       .   ",
                               "    . $this->m_nLevel           .   ",
                               "    . $this->m_nGuildID         .   ",
                               "    . $this->m_nUserID          .   "
                            )
                            ON DUPLICATE KEY UPDATE
                                character_id    =   LAST_INSERT_ID(guild_id)
                                character_name  =   '"  . $this->m_sCharacterName   .   "',
                                realm_fk        =   "   . $this->m_nRealmID         .   ",
                                class_fk        =   "   . $this->m_nClassID         .   ",
                                spec_fk         =   "   . $this->m_nMainSpecID      .   ",
                                offspec_fk      =   "   . $this->m_nOffSpecID       .   ",
                                level           =   "   . $this->m_nLevel           .   ",
                                guild_fk        =   "   . $this->m_nGuildID         .   ",
                                user_fk         =   "   . $this->m_nUserID;
            Utils::debugLog('SQL_Query', $sSQL);
            $oCon->query($sSQL);
            if ($oCon->affected_rows > 0){
                if ($this->m_nCharacterID == 0){
                    $this->m_nCharacterID   =   $oCon->insert_id;
                }
            } else {
                Utils::debugLog("Insert_Error", "Failed to insert or update character with: $sSQL");
            }

        }
    }

    public function storeItemsAsAudit(){
        $this->m_oItems->storeItemsAsAudit();
    }


    //Getters


    //Setters


    //Checkers

    //check character exists
    public function characterExists(){
        return true;
    }

}