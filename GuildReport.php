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
                print "Item Report for: " . $aGuildInfo['guild_name'] . " - " . $aGuildInfo['realm_info']['region'] . "-" . $aGuildInfo['realm_info']['name'];
            }else {
                header("Location: $sURL");
                die();
            }
        ?>
</h2></div>
<div id="content"></div>
<script>
    var guild_id    =   '<?php if (isset($_REQUEST['id'])){echo $_REQUEST['id'];}else{echo 1;}?>';
    var url =   "/guildhub/api/api.guild.php?action=getGuildMemberItems&guild_id="+guild_id;
    $.getJSON(url , function(data) {
        table_html  =   jsonToTable(data);
        $("#content").html(table_html);
    });



    function jsonToTable(data){
        var table_html = '<table class="ilvl_table">';
        var table_body = '<tbody>';
        table_head      =   '<thead><tr><th scope="col">Character</th>';
        table_head      +=  '<th scope="col">Head</th>';
        table_head      +=  '<th scope="col">Neck</th>';
        table_head      +=  '<th scope="col">Shoulder</th>';
        table_head      +=  '<th scope="col">Back</th>';
        table_head      +=  '<th scope="col">Chest</th>';
        table_head      +=  '<th scope="col">Wrist</th>';
        table_head      +=  '<th scope="col">Hands</th>';
        table_head      +=  '<th scope="col">Waist</th>';
        table_head      +=  '<th scope="col">Legs</th>';
        table_head      +=  '<th scope="col">Feet</th>';
        table_head      +=  '<th scope="col">Finger 1</th>';
        table_head      +=  '<th scope="col">Finger 2</th>';
        table_head      +=  '<th scope="col">Trinket 1</th>';
        table_head      +=  '<th scope="col">Trinket 2</th>';
        table_head      +=  '<th scope="col">Main Hand</th>';
        table_head      +=  '<th scope="col">Off Hand</th>';
        table_head      +=  '</tr></thead>';
        var characters  =   data.characters;

        var count   =   0;
        $.each(characters, function(id, character) {
            var table_row   =   '';
            table_row       +=  '<td>' + character.character_name + '</td>';
            var charItems   =   character.items;
            $.each(charItems, function(itemSlot , item) {
                table_row   +=  '<td>' +item.item_level + '</td>';
            });
            table_body  +=  '<tr>' + table_row + '</tr>';
            count++;
        });
        table_body  +=  '</tbody>';
        table_html  +=  table_head + table_body + '</table>';
        return table_html;
    }
</script>
<?php
include_once 'inc/inc.footer.php';
?>


