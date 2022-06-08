<?php
<# if (groupFields.length > 0) { #>

    // Next group
    $<#= pageObj #>->loadGroupRowValues();

    // Show header if page break
    if (<#= isExport #>) {
        <#= showHeader #> = ($<#= pageObj #>->ExportPageBreakCount == 0) ? false : (<#= grpCount #> % $<#= pageObj #>->ExportPageBreakCount == 0);
    }

    <# if (ServerScriptExist("Table", "Page_Breaking")) { #>
    // Page_Breaking server event
    if (<#= showHeader #>) {
        $<#= pageObj #>->pageBreaking(<#= showHeader #>, <#= pageBreakContent #>);
    }
    <# } #>

    <#= grpCount #>++;

<# } #>

} // End while
?>
