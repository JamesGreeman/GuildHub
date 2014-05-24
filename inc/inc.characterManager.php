<?php

class CharacterManager{
    private $m_nCharacterID;
    private $m_sCharacterName;
    private $m_nRealmID;
    private $m_sGuildName;
    private $m_nGuildID;        //-1 represents no guild, -2 represents that the guild does not exist within the database
    private $m_nLevel;

    private $m_nRaceID;
    private $m_sFaction;
    private $m_nClassID;
    private $m_nMainSpecID;
    private $m_nOffSpecID;

    private $m_oItems;

    private $m_nUserID;

    private $m_sThumbnailURL;

    public function __construct($sRegion, $sRealm, $sName){
        $this->m_sCharacterName =   $sName;
        $aRealm                 =   Utils::getRealmByName($sRegion, $sRealm);
        $this->m_nRealmID       =   $aRealm['id'];
        //if the guild exists it is loaded, if it doesn't is created - character information is not loaded
        if ($this->characterExists()){
            $this->loadCharacterFromDatabase();
        } else {
            $this->m_nCharacterID   =   0;
            $this->m_nUserID        =   -1;
            $this->loadCharacterFromBattleNet();
            $this->storeCharacter();
            $this->loadItemDetailsFromBattleNet();
            $this->storeItemsAsAudit();
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
                $this->m_nRaceID        =   $aRow['race_fk'];
                $this->m_sFaction       =   $aRow['faction'];
                $this->m_nClassID       =   $aRow['class_fk'];
                $this->m_nMainSpecID    =   $aRow['spec_fk'];
                $this->m_nOffSpecID     =   $aRow['offspec_fk'];
                $this->m_nLevel         =   $aRow['level'];
                $this->m_nRealmID       =   $aRow['realm_fk'];
                $this->m_nGuildID       =   $aRow['guild_fk'];
                $this->m_sThumbnailURL  =   $aRow['thumbnail_url'];
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
            $aRace                  =   Utils::getRaceByID($this->m_nRaceID);
            $this->m_sFaction       =   $aRace['faction'];
            $this->m_sThumbnailURL =   $aCharData['thumbnail'];
            $this->m_nLevel         =   $aCharData['level'];
            if (isset($aCharData['guild']['name'])){
                $this->m_sGuildName =   $aCharData['guild']['name'];
                $this->m_nGuildID   =   GuildManager::getGuildID($this->m_sGuildName, $this->m_nRealmID);
            } else {
                $this->m_sGuildName =   "none";
                $this->m_nGuildID   =   -1;
            }

            if (isset($aCharData['talents'][0]['spec']['name'])){
                $sMainSpecName       =   $aCharData['talents'][0]['spec']['name'];
                $this->m_nMainSpecID =   Utils::getSpecIDByName($this->m_nClassID, $sMainSpecName);
            } else {
                $this->m_nMainSpecID =  -1;
                Utils::debugLog("Invalid_BNet_Response", "No Spec data returned from battle.net");
            }


            if (isset($aCharData['talents'][1]['spec']['name'])){
                $sOffSpecName       =   $aCharData['talents'][1]['spec']['name'];
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
                                    race_fk,
                                    faction,
                                    realm_fk,
                                    class_fk,
                                    spec_fk,
                                    offspec_fk,
                                    level,
                                    guild_fk,
                                    thumbnail_url,
                                    user_fk
                                )
                            VALUES  (
                               "    . $this->m_nCharacterID     .   ",
                               '"   . $this->m_sCharacterName   .   "',
                               "    . $this->m_nRaceID          .   ",
                               '"   . $this->m_sFaction         .   "',
                               "    . $this->m_nRealmID         .   ",
                               "    . $this->m_nClassID         .   ",
                               "    . $this->m_nMainSpecID      .   ",
                               "    . $this->m_nOffSpecID       .   ",
                               "    . $this->m_nLevel           .   ",
                               "    . $this->m_nGuildID         .   ",
                               '"   . $this->m_sThumbnailURL    .   "',
                               "    . $this->m_nUserID          .   "
                            )
                            ON DUPLICATE KEY UPDATE
                                character_id    =   LAST_INSERT_ID(character_id),
                                character_name  =   '"  . $this->m_sCharacterName   .   "',
                                race_fk         =   "   . $this->m_nRaceID          .   ",
                                faction         =   '"  . $this->m_sFaction         .   "',
                                realm_fk        =   "   . $this->m_nRealmID         .   ",
                                class_fk        =   "   . $this->m_nClassID         .   ",
                                spec_fk         =   "   . $this->m_nMainSpecID      .   ",
                                offspec_fk      =   "   . $this->m_nOffSpecID       .   ",
                                level           =   "   . $this->m_nLevel           .   ",
                                guild_fk        =   "   . $this->m_nGuildID         .   ",
                                thumbnail_url   =   '"  . $this->m_sThumbnailURL    .   "',
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

    public function getCharacterItemArray(){
        $aCharacterData =   array();
        $aCharacterData['character_id']     =   $this->m_nCharacterID;
        $aCharacterData['character_name']   =   $this->m_sCharacterName;

        $aCharacterData['items']            =   $this->m_oItems->toArray();

        return $aCharacterData;
    }

    public function getCharacterDetailArray(){
        $aCharacterData =   array();
        $aCharacterData['character_id']     =   $this->m_nCharacterID;
        $aCharacterData['character_name']   =   $this->m_sCharacterName;
        $aCharacterData['realm']            =   Utils::getRealmByID($this->m_nRealmID);
        $aCharacterData['guild_name']       =   $this->m_sGuildName;
        $aCharacterData['guild_id']         =   $this->m_nGuildID;        //-1 represents no guild, -2 represents that the guild does not exist within the database
        $aCharacterData['character_level']  =   $this->m_nLevel;

        $aCharacterData['race_id']          =   $this->m_nRaceID;
        $aRace                              =   Utils::getRaceByID($this->m_nRaceID);
        $aCharacterData['race_name']        =   $aRace['name'];
        $aCharacterData['faction']          =   $this->m_sFaction;
        $aCharacterData['class']            =   Utils::getClassByID($this->m_nClassID);
        $aCharacterData['main_spec']        =   Utils::getSpecByID($this->m_nMainSpecID);
        $aCharacterData['off_spec']         =   Utils::getSpecByID($this->m_nOffSpecID);

        $aCharacterData['user_id']          =   $this->m_nUserID;
        $aCharacterData['thumbnail_url']    =   $this->m_sThumbnailURL;

        return $aCharacterData;
    }

    public function getCharacterArray(){
        $aCharacterData             =   $this->getCharacterDetailArray();
        $aCharacterData['items']    =   $this->m_oItems->toArray();
        return $aCharacterData;
    }
    //Getters
    public function getThumbnailURL(){
        return $this->m_sThumbnailURL;
    }

    public function getGuildID(){
        return $this->m_nGuildID;
    }

    public function getRealmID(){
        return $this->m_nRealmID;
    }

    //Setters
    public function updateGuild($nID){
        //TODO: change functionality to perform checks (character is unowned, or owner is making request)
        $this->m_nGuildID   =   $nID;
        $this->storeCharacter();
    }

    //Checkers

    //check character exists
    public function characterExists(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                character_id
                            FROM
                                characters
                            WHERE
                                character_name  =   '"  .   $this->m_sCharacterName .   "'  AND
                                realm_fk        =   "   .   $this->m_nRealmID   .   "
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes && $oRes->num_rows > 0){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //Static guild methods

    //get guild ID
    public static function geCharacterID($sCharacterName, $nRealmID){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                character_id
                            FROM
                                characters
                            WHERE
                                guild_name  =   '$sCharacterName'  AND
                                realm_fk    =   $nRealmID
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                return $aRow['character_id'];
            } else {
                Utils::debugLog("No_Character", "Character '$sCharacterName' does not exist on realm with ID '$nRealmID'");
                return -2;
            }
        }
        return -1;
    }

    //returns basic information of a character using ID
    public static function getCharacterInfo($nID){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   " SELECT
                                *
                            FROM
                                characters
                            WHERE
                                character_id  = $nID
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow                               =   $oRes->fetch_assoc();
                $aCharacterInfo                     =   array();
                $aCharacterInfo['character_name']   =   $aRow['character_name'];
                $aCharacterInfo['realm_id']         =   $aRow['realm_fk'];
                $aCharacterInfo['realm_info']       =   Utils::getRealmByID($aCharacterInfo['realm_id']);
                return $aCharacterInfo;
            } else {
                Utils::debugLog("No_Character", "Character with ID $nID does not exist");
                return -2;
            }
        }
        return -1;
    }
}