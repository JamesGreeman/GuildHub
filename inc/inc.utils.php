<?php

include_once __CACHEDIR__ . '/cache.databases.php';
include_once __CACHEDIR__ . '/cache.realms.php';

class Utils {
    public static $m_aItemSlots    =  array(
        'head'      => 1,
        'neck'      => 1,
        'shoulder'  => 1,
        'back'      => 1,
        'chest'     => 1,
        'wrist'     => 1,
        'hands'     => 1,
        'waist'     => 1,
        'legs'      => 1,
        'feet'      => 1,
        'finger1'   => 1,
        'finger2'   => 1,
        'trinket1'  => 1,
        'trinket2'  => 1,
        'mainHand'  => 1,
        'offHand'   => 1
    );

    public static $m_aLog  =   array();
    //Database functions

    //get a database connection based on a key
    public static function getConnection($sID){
        global $g_aDatabases;
        $sAddress   =   $g_aDatabases[$sID]['address'];
        $sUsername  =   $g_aDatabases[$sID]['username'];
        $sPassword  =   $g_aDatabases[$sID]['password'];
        $sDatabase  =   $g_aDatabases[$sID]['database'];
        $oCon       =   new mysqli($sAddress, $sUsername, $sPassword, $sDatabase);
        if (!$oCon){
            Utils::debugLog("DB_Connect", "Did not connect to $sID.  Credentials:- <br>host: $sAddress<br>username: $sUsername<br>password: $sPassword<br>database: $sDatabase<br>");
        }
        $oCon->set_charset("utf8");
        return $oCon;
    }

    //process a filter string into a filter condition
    public static function processFilterConditions($sFilterString){
        $sStatement =   "";
        $aConditions    =   explode(',', $sFilterString);
        foreach($aConditions as $sCondition){
            $aCondition =   explode(':', $sCondition);
            if (sizeof($aConditions)    ==  3){
                switch ($aCondition[2]){
                    case "eq":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` = '"     .   $aConditions[1] .   "' \n";
                        break;
                    case "neq":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` <> '"    .   $aConditions[1] .   "' \n";
                        break;
                    case "lt":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` < '"     .   $aConditions[1] .   "' \n";
                        break;
                    case "lte":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` <= '"    .   $aConditions[1] .   "' \n";
                        break;
                    case "gt":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` > '"     .   $aConditions[1] .   "' \n";
                        break;
                    case "gte":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` >= '"    .   $aConditions[1] .   "' \n";
                        break;
                    case "like":
                        $sStatement .=  "AND `" .   $aCondition[0]  .   "` LIKE '%" .   $aConditions[1] .   "%' \n";
                        break;
                    default:
                        Utils::debugLog("Filter_Error", "Invalid operation " . $aCondition[2]);
                        break;
                }
            } else {
                Utils::debugLog("Filter_Error", "Node enough parameters in condition: $sCondition");
            }
        }

        return $sStatement;
    }

    //process a sort string into a sort condition
    public static function processSortConditions($sSortString){
        $sStatement     =   "";
        $aStatement     =   array();
        $aConditions    =   explode(',', $sSortString);
        foreach($aConditions as $sCondition){
            $aCondition =   explode(':', $sCondition);
            if (sizeof($aConditions)    ==  2){
                if ($aCondition[1] == 'asc' || $sCondition[1] == 'desc'){
                    $aStatement[]   =   "`" .   $sCondition[0] . "`" . $aCondition[1];
                } else {
                    Utils::debugLog("Sot_Error", "Invalid sort condition " . $aCondition[1]);
                }
            } else {
                Utils::debugLog("Sort_Error", "Node enough parameters in condition: $sCondition");
            }
        }

        if (sizeof($aStatement) > 0){
            $sStatement =   "ORDER BY " . implode(",\n", $aStatement);
        }
        return $sStatement;
    }

     //process a limit string into a limit condition
    public static function processLimitConditions($sLimitString){
        $sStatement =   "";
        $aConditions    =   explode(',', $sLimitString);
        $aLimitParams   =   array();
        foreach($aConditions as $sCondition){
            $aCondition =   explode(':', $sCondition);
            if (sizeof($aCondition)    ==  2){
                $aLimitParams[$aCondition[0]]   =   $aLimitParams[1];
            }{
                Utils::debugLog("Limit_Error", "Node enough parameters in condition: $sCondition");
            }
        }
        if (isset($aLimitParams['size'])){
            if (isset($aLimitParams['offset'])){
                $sStatement =   "LIMIT " . $aLimitParams['offset'] . ", " . $aLimitParams['limit'];
            } else {
                $sStatement =   "LIMIT " . $aLimitParams['limit'];
            }
        } else {
            Utils::debugLog("Limit_Error", "No size parameter set");
        }

        return $sStatement;
    }

    //The following function fetch data from global variables

    //Get the realm array data
    public static function getRealmByID($nID){
        global $g_aRealmById;
        if (isset($g_aRealmById[$nID])){
            return  $g_aRealmById[$nID];
        } else {
            Utils::debugLog("Invalid_Realm", "No Realm With Id: $nID");
            return null;
        }
    }

    //get realm data based on region and realm names
    public static function getRealmByName($sRegion, $sRealm){
        global $g_aRealms;
        //turn realm into slug
        $sRealm =   Utils::slugifyString($sRealm);
        $sRegion    =   strtolower($sRegion);
        $sKey        =   $sRegion . '_' . $sRealm;

        if (isset($g_aRealms[$sKey])){
            return $g_aRealms[$sKey];
        } else {
            Utils::debugLog("Invalid_Realm", "No Realm matching $sRegion, $sRealm using key: $sKey");
            return null;
        }
    }

    //get class data based on id
    public static function getClassByID($nID){
        global $g_aClasses;
        if(isset($g_aClasses[$nID])){
            return $g_aClasses[$nID];
        } else {
            Utils::debugLog("Invalid_Class", "No Class With Id: $nID");
            return null;
        }
    }

    //get class id by name
    public static function getClassIDByName($sClass){
        global $g_aClassIDs;
        $sKey =   Utils::slugifyString($sClass);
        if(isset($g_aClassIDs[$sKey])){
            return $g_aClassIDs[$sKey];
        } else {
            Utils::debugLog("Invalid_Class", "No Class matching $sClass matching key: $sKey");
            return null;
        }
    }

    //get spec data based on id
    public static function getSpecByID($nID){
        global $g_aSpecs;
        if(isset($g_aSpecs[$nID])){
            return $g_aSpecs[$nID];
        } else {
            Utils::debugLog("Invalid_Spec", "No Spec With Id: $nID");
            return null;
        }
    }

    //get spec id by name
    public static function getSpecIDByName($sClass, $sSpec){
        global $g_aSpecIDs;
        if (is_numeric($sClass)){
            $sClass =   Utils::getClassByID($sClass);
            $sClass =   $sClass['name'];
        }
        $sKey =   Utils::slugifyString($sClass) . "_" . Utils::slugifyString($sSpec);
        if(isset($g_aSpecIDs[$sKey])){
            return $g_aSpecIDs[$sKey];
        } else {
            Utils::debugLog("Invalid_Spec", "No Spec matching $sClass, $sSpec matching key: $sKey");
            return null;
        }
    }

    //get race data based on race id
    public static function getRaceByID($nID){
        global $g_aRaces;
        if(isset($g_aRaces[$nID])){
            return $g_aRaces[$nID];
        } else {
            Utils::debugLog("Invalid_Race", "No Race With Id: $nID");
            return null;
        }
    }


    //function used to turn a string into a "slug"
    static function slugifyString($sString, $bRemoveSpace = false){
        $sString    =   urldecode($sString);
        Utils::debugLog('Slugify_Realm', "Turning $sString into a slug");
        $sString    =   str_replace('\\', '', $sString);
        $sString    =   str_replace("'", '', $sString);
        $sString    =   preg_replace('~[^\\pL\d]+~u', '-', $sString);
        $sString    =   trim($sString, '-');
        $sString    =   iconv('utf-8', 'us-ascii//TRANSLIT', $sString);
        $sString    =   strtolower($sString);
        $sString    =   preg_replace('~[^-\w]+~', '', $sString);
        if ($bRemoveSpace){
            $sString    =   str_replace('-','',$sString);
        }
        return $sString;
    }

    //Battle.net data fetch functions

    static function battleNetGuildCurl($sRegion, $sRealm, $sGuild, $aFields = array()){
        global $g_aApiPrefixes;
        $aData  =   array();
        if (isset($g_aApiPrefixes[$sRegion] )){
            $sRealm     =   Utils::slugifyString($sRealm, true);
            $sRegion    =   trim($sRegion);
            $sGuild =       rawurlencode(trim($sGuild));
            $sFields    =   implode(',', $aFields);
            $sURL       =   $g_aApiPrefixes[$sRegion] . "/api/wow/guild/$sRealm/$sGuild?fields=$sFields";
            $ch         =   curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $sURL);
            $sRes      =   curl_exec($ch);
            if ($sRes){
                $aData      =   json_decode($sRes, true);
            } else {
                Utils::debugLog("Curl_Error", "No results from $sURL");
            }

            curl_close($ch);

        } else {
            Utils::debugLog("Invalid_Region", "'$sRegion' is not a valid region");
        }
        return $aData;
    }

    static function battleNetCharacterCurl($sRegion, $sRealm, $sCharacter, $aFields = array()){
        global $g_aApiPrefixes;
        $aData  =   array();
        if (isset($g_aApiPrefixes[$sRegion] )){
            $sRealm     =   Utils::slugifyString($sRealm, true);
            $sRegion    =   trim($sRegion);
            $sCharacter =   trim($sCharacter);
            $sFields    =   implode(',', $aFields);
            $sURL       =   $g_aApiPrefixes[$sRegion] . "/api/wow/character/$sRealm/$sCharacter?fields=$sFields";
            $ch         =   curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $sURL);
            $sRes      =   curl_exec($ch);
            if ($sRes){
                $aData      =   json_decode($sRes, true);
            } else {
                Utils::debugLog("Curl_Error", "No results from $sURL");
            }

            curl_close($ch);

        } else {
            Utils::debugLog("Invalid_Region", "'$sRegion' is not a valid region");
        }
        return $aData;
    }

    static function battleNetRealmCurl($sRegion){
        global $g_aApiPrefixes;
        $aData  =   array();
        if (isset($g_aApiPrefixes[$sRegion] )){
            $sURL       =   $g_aApiPrefixes[$sRegion] . "/api/wow/realm/status";

            $ch         =   curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $sURL);
            $sRes      =   curl_exec($ch);
            if ($sRes){
                $aData      =   json_decode($sRes, true);
            } else {
                Utils::debugLog("Curl_Error", "No results from $sURL");
            }
            curl_close($ch);

        } else {
            Utils::debugLog("Invalid_Region", "'$sRegion' is not a valid region");
        }
        return $aData;
    }



    //The following functions are debugging features
    static function isDebug(){
        if(isset($_REQUEST['debugLive'])){
            return 2;
        } else if (isset($_REQUEST['debug'])){
            return true;
        } else {
            return false;
        }
    }

    static function debugLog($sKey, $sMessage){
        if (Utils::isDebug() == 2){
            echo "$sKey: $sMessage \n<br>";
        }
        $aLog   =   array('key' => $sKey, 'message' =>$sMessage);
        array_push(Utils::$m_aLog, $aLog);
    }

    public function getPlainLog(){
        $sLogText   =   '';
        if (Utils::isDebug()){
            foreach(Utils::$m_aLog as $aLogItem){
                $sLogText   .=  $aLogItem['key'] . ' : ' . $aLogItem['message'] . '<br>';
            }
        }
        return $sLogText;
    }
}