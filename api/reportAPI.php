<?php

include_once '../settings.php';
include_once __INCDIR__ . '/inc.reportManager.php';

$aData  =   $_REQUEST;

if(isset($_REQUEST['action'])){
    $sMethod          = $_REQUEST['action'];
    if ($sMethod == 'get_ilvl_array'){
        if ($_REQUEST){
            $aData  =   $_REQUEST;

            $oReport    =   new ReportManager($aData);
            $aResponse  =   $oReport->getILvlArray($aData);
            print Utils::getPlainLog();
            echo json_encode($aResponse);
        }

    }
}