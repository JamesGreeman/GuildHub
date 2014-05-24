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
<div id="controls">
    <button id="btn_newCharDiag">New Character</button><h3>New Character`</h3>
</div>
<div id="content"></div>

<script>
    window.guildInfo    =   <?php  if (isset($_REQUEST['id'])){echo json_encode(GuildManager::getGuildInfo($_REQUEST['id']));}else{echo -1;}?>;

    function removeCharacter(id, name){
        window.character_id = id;
        $("#lbl_message").text("Really remove " + name + " from " + guildInfo.guild_name + "?");
        $("#dialog_deleteChar").dialog("open");
    }
    $( document).ready(function(){
        window.guild_id     =   '<?php if (isset($_REQUEST['id'])){echo $_REQUEST['id'];}else{echo 1;}?>';

        //initiate elements
        $( document ).tooltip();

        $( "#btn_newCharDiag" ).button({
            icons: { primary: "ui-icon-circle-plus"},
            text: false
        });

        $( "#dialog_newChar" ).dialog({
            autoOpen: false,
            height: 'auto',
            width: 400,
            modal: true
        });

        $("#dialog_deleteChar").dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                "Yes": function() {
                    var parameters  =   {
                        action          :   'removeCharacter',
                        guild_id        :   guild_id,
                        character_id    :   character_id
                    };
                    $.post("/guildhub/api/api.guild.php", parameters, function(data){
                        loadGuildSummary();
                    },"json");
                    $(this).dialog("close");
                },
                "No": function() {
                    $(this).dialog("close");
                }
            }
        });


        $( "#btn_addCharacter").button();

        $.post( '/guildhub/api/api.realm.php', {}, function(data){
            populateRealmLists(data);
            $( "#input_realm" ).autocomplete({
                source: euRealms
            });
        }, "json");

        //on start
        loadGuildSummary();

        //Functions
        function loadGuildSummary(){
            var url =   "/guildhub/api/api.guild.php?action=getGuildMembers&guild_id="+guild_id;
            $.getJSON(url , function(data) {
                table_html  =   jsonToTable(data);
                $("#content").html(table_html);
                $(".btn_remove").button({
                    icons: { primary: "ui-icon-circle-close"},
                    text: false
                });
            });
        }


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
                    '<th scope = "col">Remove</th>' +
                    '</tr>' +
                    '</thead>';
            var characters  =   data.characters;


            $.each(characters, function(id, character) {
                var table_row   =   '<td>' + character.character_name   + '</td>';
                table_row       +=  '<td>' + character.character_level  + '</td>';
                table_row       +=  '<td>' + character.race_name        + '</td>';
                table_row       +=  '<td>' + character.class.name       + '</td>';
                table_row       +=  '<td>' + character.main_spec.name   + '</td>';
                table_row       +=  '<td>' + character.off_spec.name    + '</td>';
                table_row       +=  '<td><button class="btn_remove" id="btn_remove_"'+character.character_id+' onclick="removeCharacter('+character.character_id+',\''+character.character_name+'\')">Remove Character</button></td>';
                table_body  +=  '<tr>' + table_row + '</tr>';
            });
            table_body  +=  '</tbody>';
            table_head  +=  '</tr></thead>';
            table_html  +=  table_head + table_body + '</table>';
            return table_html;
        }

        function populateRealmLists(data){
            euRealms    = new Array();
            usRealms    = new Array();
            allRealms   = new Array();
            $.each(data, function(i, item){
                if (item.region == 'EU'){
                    euRealms.push(item.name);
                }
                if (item.region == 'US'){
                    usRealms.push(item.name);
                }
                allRealms.push(item);
            })
        }

        //JQuery event handlers
        $('#btn_newCharDiag').click(function(event){
            $( "#dialog_newChar" ).dialog( "open" );
        });

        $('#btn_addCharacter').click(function(event){
            var parameters  =   {
                action          :   'addCharacter',
                guild_id        :   guild_id,
                character_name  :   $('#input_name').val(),
                region          :   $('#input_region').val(),
                realm           :   $('#input_realm').val()
            };
            $.post("/guildhub/api/api.guild.php", parameters, function(data){
                $( "#dialog_newChar" ).dialog( "close" );
                loadGuildSummary();
            },"json");
        });

        $('#input_region').change(function(){
            var value = $('#input_region').val();
            $('#input_region').attr('autocomplete', 'off');
            if (value == 'US'){
                $( "#input_realm" ).autocomplete({
                    source: usRealms
                });
            }
            if (value == 'EU'){
                $( "#input_realm" ).autocomplete({
                    source: euRealms
                });
            }
        });

    });
</script>

<div id="dialog_newChar" title="Add Character">
    <label for="input_name">Name</label>
    <input type="text" name="input_name" id="input_name" class="text ui-widget-content ui-corner-all"><br>
    <label for="input_region">Region</label>
    <select name="input_region" id="input_region" class="text ui-widget-content ui-corner-all" style="width:70px;display:inline;">
        <option value='EU'>EU</option>
        <option value="US">US</option>
    </select><br>
    <label for="input_realm">Realm</label>
    <input type="text" name="input_realm" id="input_realm" class="text ui-widget-content ui-corner-all" style="width:70%;display:inline;">
    <button id="btn_addCharacter">Add Character</button>
</div>

<div id="dialog_deleteChar">
    <form>
        <label id="lbl_message">Would you like to remove X from Y?</label>
    </form>
</div>

<?php
include_once 'inc/inc.footer.php';
?>

