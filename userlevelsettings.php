<## Common config #>
<#= include('shared/config-common.php') #>
<#
    let userLevels = [], userLevelPrivs = [], userLevelTables = [];
    let secDefault = `-2,"Anonymous",0`; // Add anonymous level
    if (isUserLevel)
        secDefault = (secDefault + ';' + DB.SecDefault).replace(/;$/, ''); // Add other user levels
    userLevels = secDefault.split(';').map(lvl => {
        let ar = lvl.split(',');
        return [ar[0], RemoveQuotes(ar[1])];
    });
    for (let table of TABLES) {
        if (table.TblLoaded) {
            let sec = `-2,"Anonymous",${table.TblAnonymous}`; // Add anonymous level
            if (isUserLevel)
                sec = (sec + ';' + table.TblSecurity).replace(/;$/, ''); // Add other user levels
            let privs = sec.split(';').map(lvl => {
                let ar = lvl.split(',');
                return { id: ar[0], name: ar[1], permission: ar[2] };
            });
            for (let priv of privs)
                userLevelPrivs.push([PROJ.ProjID + table.TblName, priv.id, priv.permission]);
            let url = table.TblGen ? GetRouteUrl(table.TblType == "REPORT" ? table.TblReportType : "list", table) : ""; // Get Report/List Page
            userLevelTables.push([table.TblName, table.TblVar, table.TblCaption, table.TblUserLevelPriv, PROJ.ProjID, url]);
        }
    }
#>
<?php
/**
 * PHPMaker 2021 user level settings
 */
namespace <#= ProjectNamespace #>;

// User level info
$USER_LEVELS = <#= JSON.stringify(userLevels).replace(/\],\[/g, '],\n    [') #>;
// User level priv info
$USER_LEVEL_PRIVS = <#= JSON.stringify(userLevelPrivs).replace(/\],\[/g, '],\n    [') #>;
// User level table info
$USER_LEVEL_TABLES = <#= JSON.stringify(userLevelTables).replace(/\],\[/g, '],\n    [') #>;
