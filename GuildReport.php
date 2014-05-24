<?php
include_once 'inc/inc.header.php';
?>
<div><h2>Report</h2></div>
<div id="content"></div>
<script>

    var url =   "/guildhub/api/api.guild.php?action=getGuildMemberItems&guildName=Lore&region=EU&realm=Quel'Thalas";
    $.getJSON(url , function(data) {
        table_html  =   jsonToTable(data);
        $("#content").html(table_html);
    });



    function jsonToTable(data){
        var table_html = '<table class="ilvl_table">';
        var table_body = '<tbody>';
        var table_head = '';
        var characters  =   data.characters;


        $.each(characters, function(characterID, character) {
            var table_row   =   '';
            table_head      =   '<thead><tr><th scope="col">Character</th>';
            table_row       +=  '<td>' + character.character_name + '</td>';
            var charItems   =   character.items;
            $.each(charItems, function(itemSlot , item) {
                table_head  +=  '<th scope="col">' +itemSlot + '</th>';
                table_row   +=  '<td>' +item.item_level + '</td>';

            });
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


