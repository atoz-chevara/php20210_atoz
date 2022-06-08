<#
    let controlType = "";
switch (ctrlType) {
    case "table":
    case "report":
        controlType = "Table";
        break;
    case "other":
    case "simple":
        controlType = "Other";
        break;
}
#>
<script>
loadjs.ready("load", function () {
    // Startup script
    <#= GetClientScript(controlType, "Startup Script") #>
});
</script>