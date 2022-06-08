<#
    let handlers = new Map();
    let configs = new Map();
    for (let t of TABLES) {
        if (t.TblGen) {
            // Fields
            let fields = t.Fields
                .filter(f => ClientScriptExist("Field", "Events", CONTROL, t, f))
                .map(f => [f.FldVar, GetClientScript("Field", "Events", CONTROL, t, f)]);
            if (fields.length)
                handlers.set(t.TblVar, new Map(fields));
            // Charts
            let charts = t.Charts
                .filter(c => ClientScriptExist("Chart", "Config", CONTROL, t, c))
                .map(c => [c.ChartVar, GetClientScript("Chart", "Config", CONTROL, t, c)]);
            if (charts.length)
                configs.set(t.TblVar, new Map(charts));
        }
    }
#>
// User event handlers
ew.events = {
<# Array.from(handlers).forEach(([tblVar, fields], i, art) => { #>
    "<#= tblVar #>": {
        <# Array.from(fields).forEach(([fldVar, events], j, ar) => { #>
        "<#= fldVar #>": <#= events.replace(/\n/g, "\n\t\t\t").replace(/\t/g, "    ") #><# if (j < ar.length - 1) { #>,<# } #>
        <# }); #>
    }<# if (i < art.length - 1) { #>,<# } #>
<# }); #>
};

// Chart user configurations
ew.charts = {
<# Array.from(configs).forEach(([tblVar, charts], i, art) => { #>
    "<#= tblVar #>": {
        <# Array.from(charts).forEach(([chtVar, config], j, ar) => { #>
        "<#= chtVar #>": <#= config.replace(/\n/g, "\n\t\t\t").replace(/\t/g, "    ") #><# if (j < ar.length - 1) { #>,<# } #>
        <# }); #>
    }<# if (i < art.length - 1) { #>,<# } #>
<# }); #>
};

// Global client script
ew.clientScript = function() {
    <#= GetClientScript("Global", "Client Script") #>
};

// Global startup script
ew.startupScript = function() {
    <#= GetClientScript("Global", "Startup Script") #>
};
