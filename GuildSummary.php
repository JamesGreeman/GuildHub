<?php
include_once 'inc/inc.header.php';
?>
<div><h2>
        <?php
            global $g_sURLBase;
            $sURL   =   $g_sURLBase . "/Guilds.php";
            if (isset($_REQUEST['id'])){
               $nID =   $_REQUEST['id'];
            } else {
                $nID =   1;
            }
            $aGuildInfo =   GuildManager::getGuildInfo($_REQUEST['id']);
            if (isset($aGuildInfo['guild_name'])){
                print $aGuildInfo['guild_name'] . " - " . $aGuildInfo['realm_info']['region'] . "-" . $aGuildInfo['realm_info']['name'];
            }else {
                header("Location: $sURL");
                die();
            }
        ?>
</h2></div>
<div id="content"></div>
<script>
    var guild_id    =   '<?php if (isset($_REQUEST['id'])){echo $_REQUEST['id'];}else{echo 1;}?>';
    var url =   "/guildhub/api/api.guild.php?action=getGuildMembers&guild_id=";
    $.getJSON(url , function(data) {
        table_html  =   jsonToTable(data);
        $("#content").html(table_html);
    });

    function jsonToTable(data){
        var table_html = '<table class="ilvl_table">';
        var table_body = '<tbody>';
        var table_head  =
            '<thead>' +
                '<tr>' +
                    '<th scope = "col">Character</th>' +
                    '<th scope = "col">Level</th>' +
                    '<th scope = "col">Race</th>' +
                    '<th scope = "col">Class</th>' +
                    '<th scope = "col">Main Spec</th>' +
                    '<th scope = "col">Off Spec</th>' +
                '</tr>' +
            '</thead>';
        var characters  =   data.characters;


        $.each(characters, function(characterID, character) {
            var table_row   =   '<td>' + character.character_name   + '</td>';
            table_row       +=  '<td>' + character.character_level  + '</td>';
            table_row       +=  '<td>' + character.race_name        + '</td>';
            table_row       +=  '<td>' + character.class.name       + '</td>';
            table_row       +=  '<td>' + character.main_spec.name   + '</td>';
            table_row       +=  '<td>' + character.off_spec.name    + '</td>';
            table_body  +=  '<tr>' + table_row + '</tr>';
        });
        table_body  +=  '</tbody>';
        table_head  +=  '</tr></thead>';
        table_html  +=  table_head + table_body + '</table>';
        return table_html;
    }
</script>
<?php
include_once 'inc/inc.footer.php';
?>

