<?php
include_once 'inc/inc.header.php';
?>
<div><h2>Report</h2></div>
<div id="content"></div>
<script>

    url =   '/api/reportAPI.php?action=get_ilvl_array&guild_id=1';
    $.getJSON(url , function(data) {
        table_html  =   jsonToTable(data);
        $("#content").html(table_html);
    });



    function jsonToTable(data){
        var table_html = '<table class="ilvl_table">';
        var table_body = '<tbody>';
        var table_head = '';

        $.each(data, function(charName, charItems) {
            var table_row   =   '';
            table_head      =   '<thead><tr><th scope="col">Character</th>';
            table_row       +=  '<td>' + charName + '</td>';
            $.each(charItems, function(itemName , itemLevel) {
                table_head  +=  '<th scope="col">' +itemName + '</th>';
                table_row   +=  '<td>' +itemLevel + '</td>';

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


