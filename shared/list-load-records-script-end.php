<#
    let bundleIds = [formName, "load"];

    if (UseCustomTemplate)
        bundleIds.push("customtemplate");

    if (gridAdd || listEdit) {
#>
<?php if (<#= rowType #> == ROWTYPE_ADD || <#= rowType #> == ROWTYPE_EDIT) { ?>

<script>
loadjs.ready(<#= JSON.stringify(bundleIds) #>, function () {
    <#= formName #>.updateLists(<#= Code.write(rowIndex) #>);
});
</script>
<?php } ?>
<#
    }
#>

<?php
    }

    <# if (ctrlId == "grid" || ctrlId == "list" && gridAddOrEdit) { #>

    } // End delete row checking

    <# if (ctrlId == "grid") { #>
    if (!<#= isGridAdd #> || <#= currentMode #> == "copy")
    <# } else { #>
    if (!<#= isGridAdd #>)
    <# } #>
        if (!$<#= pageObj #>->Recordset->EOF) {
            $<#= pageObj #>->Recordset->moveNext();
        }

    <# } else { #>

    if (!<#= isGridAdd #>) {
        $<#= pageObj #>->Recordset->moveNext();
    }

    <# } #>

}
?>
