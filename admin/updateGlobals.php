<?php

include_once '../settings.php';

if (isset($_REQUEST['action'])){

    if ($_REQUEST['action'] == 'reload_globals'){

        //open the file
        file_put_contents('../cache/cache.globals.php', "<?php\n\n");

        //add factions
        $sFactions  =   "\$g_aFactions = array();\n";
        $sFactions  .=  "\$g_aFactions[0] = 'Alliance';\n";
        $sFactions  .=  "\$g_aFactions[1] = 'Horde';\n";
        file_put_contents('../cache/cache.globals.php', $sFactions, FILE_APPEND);

        //import class data
        $oCon   =   Utils::getConnection("guild_hub");
        $sSQL   =   "SELECT * FROM classes";
        $oRes   =   $oCon->query($sSQL);
        $sClasses   =   "\n\$g_aClasses = array();\n";
        $aClassID   =   array();
        while ($aRow = $oRes->fetch_assoc()){
            $sClasses   .=  "\$g_aClasses['" . $aRow['class_id'] . "'] = array( 'name' => '". $aRow['class_name'] ."', 'armour-type' => '". $aRow['armour_type'] ."', 'tier-token' => '". $aRow['tier_token'] ."');\n";
            $aClassID[$aRow['class_name']] = $aRow['class_id'];
        }
        file_put_contents('../cache/cache.globals.php', $sClasses, FILE_APPEND);
        $sClasses   =   "\n\$g_aClassIDs = array();\n";
        foreach($aClassID as $name => $id){
            $sClasses   .=  "\$g_aClassIDs['" . strtolower($name) . "'] = '". $id ."';\n";
        }
        file_put_contents('../cache/cache.globals.php', $sClasses, FILE_APPEND);

        //import spec data
        $sSQL   =   "SELECT specs.*, classes.class_name FROM specs, classes WHERE specs.class_fk = classes.class_id";
        $oRes   =   $oCon->query($sSQL);
        file_put_contents('../cache/cache.globals.php', "\n\$g_aSpecs = array();\n", FILE_APPEND);
        $aSpecIDs   =   array();
        while ($aRow = $oRes->fetch_assoc()){
            file_put_contents('../cache/cache.globals.php', "\$g_aSpecs['" . $aRow['spec_id'] . "'] = array( 'name' => '". $aRow['spec_name'] ."', 'class_id' => ". $aRow['class_fk'] .", 'role' => '" . $aRow['role'] . "', 'main_stat' => '" . $aRow['main_stat'] . "'  );\n", FILE_APPEND);
            $aSpecIDs[str_replace(" ", "-", strtolower($aRow['class_name'] . "_" . $aRow['spec_name']))] = $aRow['spec_id'];
        }

        file_put_contents('../cache/cache.globals.php', "\n\$g_aSpecIDs = array();\n", FILE_APPEND);
        foreach($aSpecIDs as $name => $id){
            file_put_contents('../cache/cache.globals.php', "\$g_aSpecIDs['" . $name . "'] = '". $id ."';\n", FILE_APPEND);
        }

        //import race data
        $sSQL   =   "SELECT * FROM races";
        $oRes   =   $oCon->query($sSQL);
        $sRaces =   "\n\$g_aRaces = array();\n";

        $aSpecIDs   =   array();
        while ($aRow = $oRes->fetch_assoc()){
            $sRaces .=  "\$g_aRaces['" . $aRow['race_id'] . "'] = array( 'name' => '". $aRow['name'] ."', 'faction' => '". $aRow['faction'] ."' );\n";
        }
        file_put_contents('../cache/cache.globals.php', $sRaces, FILE_APPEND);
    }
}