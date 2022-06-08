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
loadjs.ready("head", function () {
    // Client script
    <#= GetClientScript(controlType, "Client Script") #>
});
</script>