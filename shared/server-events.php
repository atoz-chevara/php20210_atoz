
    <#= GetServerScript(eventCtrlType, "Page_Load") #>
    <#= GetServerScript(eventCtrlType, "Page_Unload") #>

<# if (ctrlId != "custom") { #>
    <#= GetServerScript(eventCtrlType, "Page_Redirecting") #>
    <#= GetServerScript(eventCtrlType, "Message_Showing") #>
<# } #>

<# if (ctrlType == "table" || ctrlType == "report" || ["other", "simple"].includes(ctrlType) && ctrlId != "logout") { #>
    <#= GetServerScript(eventCtrlType, "Page_Render") #>
<# } #>

<# if (ctrlType == "table" || ctrlType == "report" && ["summary", "crosstab", "dashboard"].includes(ctrlId) || ["other", "simple"].includes(ctrlType) && ctrlId != "logout") { #>
    <#= GetServerScript(eventCtrlType, "Page_DataRendering") #>
    <#= GetServerScript(eventCtrlType, "Page_DataRendered") #>
<# } #>

<# if (ctrlType == "report" && ["summary", "crosstab"].includes(ctrlId)) { #>
    <#= GetServerScript(eventCtrlType, "Page_Breaking") #>
    <#= GetServerScript(eventCtrlType, "Page_FilterLoad") #>
    <#= GetServerScript(eventCtrlType, "Page_Selecting") #>
    <#= GetServerScript(eventCtrlType, "Page_FilterValidated") #>
    <#= GetServerScript(eventCtrlType, "Page_Filtering") #>
    <#= GetServerScript(eventCtrlType, "Cell_Rendered") #>
<# } #>
