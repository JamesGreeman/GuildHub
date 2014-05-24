<?php

class GuildManager{

    private $m_nGuildID;
    private $m_sGuildName;
    private $m_nRealmID;
    private $m_sFaction;
    private $m_nLevel;
    private $m_nLeaderID;

    private $m_aGuildMembers;

    //Constructor for guild takes the region details and the guild name
    public function __construct($sRegion, $sRealm, $sName){
        $this->m_sGuildName =   $sName;
        $aRealm             =   Utils::getRealmByName($sRegion, $sRealm);
        $this->m_nRealmID   =   $aRealm['id'];
        //if the guild exists it is loaded, if it doesn't is created - character information is not loaded
        if ($this->guildExists()){
            $this->loadGuildFromDatabase();
        } else {
            $this->m_nGuildID   =   0;
            $this->m_nLeaderID  =   -1;
            $this->loadGuildFromBattleNet();
            $this->storeGuild();
        }
    }

    //Loading Functions

    //load details of the guild
    public function loadGuildFromDatabase(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "  SELECT
                              *
                           FROM
                              guilds
                           WHERE
                              guild_name  = '"  .   $this->m_sGuildName .   "'  AND
                              realm_fk    = "   .   $this->m_nRealmID   .   "
                           LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                $this->m_nGuildID   =   $aRow['guild_id'];
                $this->m_nLevel     =   $aRow['guild_level'];
                $this->m_sFaction   =   $aRow['faction'];
                $this->m_nLeaderID  =   $aRow['leader_fk'];
            } else {
                Utils::debugLog("No_Guild", "No guild with that ID");
            }
        }
    }

    //Load Guild information from battle.net
    public function loadGuildFromBattleNet(){
        $aRealm     =   Utils::getRealmByID($this->m_nRealmID);
        $aGuildData =   Utils::battleNetGuildCurl($aRealm['region'], $aRealm['name'], $this->m_sGuildName);
        if (isset($aGuildData['status']) && $aGuildData['status'] == "nok"){
            Utils::debugLog("No_BNet_Response", $aGuildData['reason']);
        } else {
            global $g_aFactions;
            if (isset($g_aFactions[$aGuildData['side']])){
                $this->m_sFaction   =   $g_aFactions[$aGuildData['side']];
            } else {
                Utils::debugLog("Faction_Error", "Invalid side: " . $aGuildData['side']);
            }
            $this->m_nLevel =   $aGuildData['level'];
        }
    }

    //loads all guild members into the guild
    public function loadGuildMembers(){
        $oCon   =   Utils::getConnection('guild_hub');
        $this->m_aGuildMembers    =   array();
        if ($oCon){
            $sSQL   =   "   SELECT
                                character_id,
                                character_name,
                                realm_fk
                            FROM
                                characters
                            WHERE
                                guild_fk  = " . $this->m_nGuildID;
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                while ($aRow = $oRes->fetch_assoc()){
                    if (isset($aRow['character_id'])){
                        $aRealm     =   Utils::getRealmByID($aRow['realm_fk']);
                        $oCharacter =   new CharacterManager($aRealm['region'], $aRealm['name'], $aRow['character_name']);
                        $this->m_aGuildMembers[$aRow['character_id']]   =   $oCharacter;
                    }
                }
            } else {
                Utils::debugLog("SQL_Result", "No characters in guild");
            }
        }
    }

    public function loadGuildMemberItems(){
        foreach($this->m_aGuildMembers as $nID => $oCharacter){
            $oCharacter->loadItemDetailsFromDatabase();
            $this->m_aGuildMembers[$nID]    =   $oCharacter;
        }
    }

    //Store current guild information
    public function storeGuild(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   INSERT INTO
                                guilds  (
                                    guild_id,
                                    guild_name,
                                    realm_fk,
                                    guild_level,
                                    faction,
                                    leader_fk
                                )
                            VALUES  (
                               "    . $this->m_nGuildID     .   ",
                               '"   . $this->m_sGuildName   .   "',
                               "    . $this->m_nRealmID     .   ",
                               "    . $this->m_nLevel       .   ",
                               '"   . $this->m_sFaction     .   "',
                               "    . $this->m_nLeaderID    .   "
                            )
                            ON DUPLICATE KEY UPDATE
                                guild_id    =   LAST_INSERT_ID(guild_id),
                                guild_name  =   '"  .   $this->m_sGuildName .   "',
                                realm_fk    =   "   .   $this->m_nRealmID   .   ",
                                guild_level =   "   .   $this->m_nLevel     .   ",
                                faction     =   '"  .   $this->m_sFaction   .   "',
                                leader_fk   =   "   .   $this->m_nLeaderID;
            Utils::debugLog('SQL_Query', $sSQL);
            $oCon->query($sSQL);
            if ($oCon->affected_rows > 0){
                if ($this->m_nGuildID == 0){
                    $this->m_nGuildID   =   $oCon->insert_id;
                }
            } else {
                Utils::debugLog("Insert_Error", "Failed to insert or update guild with: $sSQL");
            }

        }
    }

    //Run audit
    public function runAudit(){
        foreach ($this->m_aGuildMembers as $oCharacter){
            $oCharacter->loadItemDetailsFromBattleNet();
            $oCharacter->storeItemsAsAudit();
        }
    }

    //Checkers

    //check guild exists
    public function guildExists(){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                guild_id
                            FROM
                                guilds
                            WHERE
                                guild_name  =   '"  .   $this->m_sGuildName .   "'  AND
                                realm_fk    =   "   .   $this->m_nRealmID   .   "
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes && $oRes->num_rows > 0){
                return true;
            } else {
                return false;
            }
        }
    }

    public function toArray($sFilter="", $sSort="", $sLimit=""){
        $aGuildData                 =   array();
        $aGuildData['guild_id']     =   $this->m_nGuildID;
        $aGuildData['guild_name']   =   $this->m_sGuildName;
        $aGuildData['realm_id']     =   $this->m_nRealmID;
        $aRealm                     =   Utils::getRealmByID($this->m_nRealmID);
        $aGuildData['region']       =   $aRealm['region'];
        $aGuildData['realm_name']   =   $aRealm['name'];
        $aGuildData['faction']      =   $this->m_sFaction;
        $aGuildData['guild_level']  =   $this->m_nLevel;
        $aGuildData['owner_id']     =   $this->m_nLeaderID;

        if (sizeof($this->m_aGuildMembers) > 0){
            $oCon   =   Utils::getConnection('guild_hub');
            if ($oCon){
                $aGuildData['characters']   =   array();
                if ($sFilter){
                    $sFilterStatement   =   Utils::processFilterConditions($sFilter);
                } else {
                    $sFilterStatement   =   "";
                }
                if ($sSort){
                    $sSortStatement     =   Utils::processFilterConditions($sSort);
                }else {
                    $sSortStatement     =   "";
                }
                if ($sLimit){
                    $sLimitStatement    =   Utils::processFilterConditions($sLimit);
                }else {
                    $sLimitStatement    =   "";
                }
                $sSQL   =   "   SELECT
                                    character_id
                                FROM
                                    characters
                                WHERE
                                     guild_fk   =   "   .   $this->m_nGuildID   .   "
                                     $sFilterStatement
                                $sSortStatement
                                $sLimitStatement";
                Utils::debugLog('SQL_Query', $sSQL);
                $oRes   =   $oCon->query($sSQL);
                if ($oRes){
                    while ($aRow = $oRes->fetch_assoc()){
                        $nID    =   $aRow['character_id'];
                        $aGuildData['characters'][$nID] =   $this->m_aGuildMembers[$nID]->toArray();
                    }
                } else {
                    Utils::debugLog('No_Character', 'No Characters matching conditions: ' . $sFilterStatement);
                }
            }
        } else {
            Utils::debugLog('No_Characters', 'No Characters are loaded into the guild');
        }
        return $aGuildData;
    }

    //Getters
    public function getGuildMembers(){
        if (!sizeof($this->m_aGuildMembers > 0)){
            $this->getGuildMembers();
        }
        return $this->m_aGuildMembers;
    }

    //Setters


    public function setGuildOwner($aData){
    }

    //Static guild methods

    //get guild ID
    public static function getGuildID($sGuildName, $nRealmID){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   "   SELECT
                                guild_id
                            FROM
                                guilds
                            WHERE
                                guild_name  =   '$sGuildName'  AND
                                realm_fk    =   $nRealmID
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                return $aRow['guild_id'];
            } else {
                Utils::debugLog("No_Guild", "Guild '$sGuildName' does not exist on realm with ID '$nRealmID'");
                return -2;
            }
        }
        return -1;
    }

    public static function getGuildInfo($nID){
        $oCon   =   Utils::getConnection('guild_hub');
        if ($oCon){
            $sSQL   =   " SELECT
                                *
                            FROM
                                guilds
                            WHERE
                                guild_id  = $nID
                            LIMIT 1";
            Utils::debugLog('SQL_Query', $sSQL);
            $oRes   =   $oCon->query($sSQL);
            if ($oRes){
                $aRow   =   $oRes->fetch_assoc();
                $aGuildInfo['guild_name']   =   $aRow['guild_name'];
                $aGuildInfo['realm_id']     =   $aRow['realm_fk'];
                $aGuildInfo['realm_info']   =   Utils::getRealmByID($aGuildInfo['realm_id']);
                $aGuildInfo['guild_level']  =   $aRow['guild_level'];
                $aGuildInfo['faction']      =   $aRow['faction'];
                $aGuildInfo['leader_fk']    =   $aRow['leader_fk'];
                return $aGuildInfo;
            } else {
                Utils::debugLog("No_Guild", "Guild with ID $nID does not exist");
                return -2;
            }
        }
        return -1;
    }

}