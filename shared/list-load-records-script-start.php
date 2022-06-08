<?php
<# if (ctrlId == "list") { #>
if ($<#= pageObj #>->ExportAll && <#= isExport #>) {
    <#= stopRec #> = <#= totalRecs #>;
} else {
    // Set the last record to display
    if (<#= totalRecs #> > <#= startRec #> + <#= displayRecs #> - 1) {
        <#= stopRec #> = <#= startRec #> + <#= displayRecs #> - 1;
    } else {
        <#= stopRec #> = <#= totalRecs #>;
    }
}
<# } else { #>
<#= startRec #> = 1;
<#= stopRec #> = <#= totalRecs #>; // Show all records
<# } #>

<# if (listAddOrEdit) { #>
// Restore number of post back records
if ($CurrentForm && (<#= isConfirm #> || $<#= pageObj #>->EventCancelled)) {
    $CurrentForm->Index = -1;
    if ($CurrentForm->hasValue(<#= formKeyCountName #>) && (<#= isGridAdd #> || <#= isGridEdit #> || <#= isConfirm #>)) {
        <#= keyCount #> = $CurrentForm->getValue(<#= formKeyCountName #>);
        <#= stopRec #> = <#= startRec #> + <#= keyCount #> - 1;
    }
}
<# } #>

<#= recCnt #> = <#= startRec #> - 1;
if ($<#= pageObj #>->Recordset && !$<#= pageObj #>->Recordset->EOF) {
    // Nothing to do
} elseif (!$<#= pageObj #>->AllowAddDeleteRow && <#= stopRec #> == 0) {
    <#= stopRec #> = $<#= pageObj #>->GridAddRowCount;
}
<# if (recPerRow < 1) { // Single Column #>
// Initialize aggregate
<#= rowType #> = ROWTYPE_AGGREGATEINIT;
$<#= pageObj #>->resetAttributes();
$<#= pageObj #>->renderRow();
<# } #>

<# if (inlineEdit) { #>
$<#= pageObj #>->EditRowCount = 0;
if (<#= isEdit #>)
    <#= rowIndex #> = 1;
<# } #>

<# if (gridAdd) { #>
if (<#= isGridAdd #>)
    <#= rowIndex #> = 0;
<# } #>

<# if (gridEdit) { #>
if (<#= isGridEdit #>)
    <#= rowIndex #> = 0;
<# } #>

while (<#= recCnt #> < <#= stopRec #>) {
    <#= recCnt #>++;
    if (<#= recCnt #> >= <#= startRec #>) {
        <#= rowCnt #>++;

    <# if (gridAddOrEdit) { #>
        if (<#= isGridAdd #> || <#= isGridEdit #> || <#= isConfirm #>) {
            <#= rowIndex #>++;
            $CurrentForm->Index = <#= rowIndex #>;
            if ($CurrentForm->hasValue($<#= pageObj #>->FormActionName) && (<#= isConfirm #> || $<#= pageObj #>->EventCancelled)) {
                $<#= pageObj #>->RowAction = strval($CurrentForm->getValue($<#= pageObj #>->FormActionName));
            } elseif (<#= isGridAdd #>) {
                $<#= pageObj #>->RowAction = "insert";
            } else {
                $<#= pageObj #>->RowAction = "";
            }
        }
    <# } #>

        // Set up key count
        <#= keyCount #> = <#= rowIndex #>;

        // Init row class and style
        $<#= pageObj #>->resetAttributes();
        $<#= pageObj #>->CssClass = "";

    <# if (ctrlId == "grid") { #>

        if (<#= isGridAdd #>) {

            if (<#= currentMode #> == "copy") {
                $<#= pageObj #>->loadRowValues($<#= pageObj #>->Recordset); // Load row values
                $<#= pageObj #>->OldKey = $<#= pageObj #>->getKey(true); // Get from CurrentValue
            } else {
                $<#= pageObj #>->loadRowValues(); // Load default values
                $<#= pageObj #>->OldKey = "";
            }

        } else {
            $<#= pageObj #>->loadRowValues($<#= pageObj #>->Recordset); // Load row values
            $<#= pageObj #>->OldKey = $<#= pageObj #>->getKey(true); // Get from CurrentValue
        }
        $<#= pageObj #>->setKey($<#= pageObj #>->OldKey);

     <# } else { #>

        if (<#= isGridAdd #>) {
            $<#= pageObj #>->loadRowValues(); // Load default values
            $<#= pageObj #>->OldKey = "";
            $<#= pageObj #>->setKey($<#= pageObj #>->OldKey);
        } else {
            $<#= pageObj #>->loadRowValues($<#= pageObj #>->Recordset); // Load row values
            if (<#= isGridEdit #>) {
                $<#= pageObj #>->OldKey = $<#= pageObj #>->getKey(true); // Get from CurrentValue
                $<#= pageObj #>->setKey($<#= pageObj #>->OldKey);
            }
        }

    <# } #>

        <#= rowType #> = ROWTYPE_VIEW; // Render view

<#
    if (gridAdd) {
#>

        if (<#= isGridAdd #>) { // Grid add
            <#= rowType #> = ROWTYPE_ADD; // Render add
        }

        if (<#= isGridAdd #> && $<#= pageObj #>->EventCancelled && !$CurrentForm->hasValue("k_blankrow")) { // Insert failed
            $<#= pageObj #>->restoreCurrentRowFormValues(<#= rowIndex #>); // Restore form values
        }

<#
    }
#>

<#
    if (listEdit) {
#>
    <# if (inlineEdit) { #>
        if (<#= isEdit #>) {
            if ($<#= pageObj #>->checkInlineEditKey() && $<#= pageObj #>->EditRowCount == 0) { // Inline edit
                <#= rowType #> = ROWTYPE_EDIT; // Render edit
    <# if (checkConcurrentUpdate) { #>
                if (!$<#= pageObj #>->EventCancelled) {
                    $<#= pageObj #>->HashValue = $<#= pageObj #>->getRowHash($<#= pageObj #>->Recordset); // Get hash value for record
                }
    <# } #>
            }
        }
    <# } #>
    <# if (gridEdit) { #>
        if (<#= isGridEdit #>) { // Grid edit
            if ($<#= pageObj #>->EventCancelled) {
                $<#= pageObj #>->restoreCurrentRowFormValues(<#= rowIndex #>); // Restore form values
            }
            if ($<#= pageObj #>->RowAction == "insert") {
                <#= rowType #> = ROWTYPE_ADD; // Render add
            } else {
                <#= rowType #> = ROWTYPE_EDIT; // Render edit
            }
        <# if (checkConcurrentUpdate) { #>
            if (!$<#= pageObj #>->EventCancelled) {
                $<#= pageObj #>->HashValue = $<#= pageObj #>->getRowHash($<#= pageObj #>->Recordset); // Get hash value for record
            }
        <# } #>
        }
    <# } #>

<# if (inlineEdit) { #>
        if (<#= isEdit #> && <#= rowType #> == ROWTYPE_EDIT && $<#= pageObj #>->EventCancelled) { // Update failed
            $CurrentForm->Index = 1;
            $<#= pageObj #>->restoreFormValues(); // Restore form values
        }
<# } #>
<# if (gridEdit) { #>
        if (<#= isGridEdit #> && (<#= rowType #> == ROWTYPE_EDIT || <#= rowType #> == ROWTYPE_ADD) && $<#= pageObj #>->EventCancelled) { // Update failed
            $<#= pageObj #>->restoreCurrentRowFormValues(<#= rowIndex #>); // Restore form values
        }
<# } #>

        if (<#= rowType #> == ROWTYPE_EDIT) { // Edit row
            $<#= pageObj #>->EditRowCount++;
        }

<# if (ctrlId == "grid") { #>
        if (<#= isConfirm #>) { // Confirm row
            $<#= pageObj #>->restoreCurrentRowFormValues(<#= rowIndex #>); // Restore form values
        }
<# } #>

<#
    }
#>

        // Set up row id / data-rowindex
        $<#= pageObj #>->RowAttrs->merge(["data-rowindex" => <#= rowCnt #>, "id" => "r" . <#= rowCnt #> . "_<#= tblVar #>", "data-rowtype" => <#= rowType #>]);

        // Render row
        $<#= pageObj #>->renderRow();

        // Render list options
        $<#= pageObj #>->renderListOptions();

<# if (UseCustomTemplate) { #>
        // Save row and cell attributes
        $<#= pageObj #>->Attrs[<#= rowCnt #>] = ["row_attrs" => <#= rowAttributes #>, "cell_attrs" => []];
        $<#= pageObj #>->Attrs[<#= rowCnt #>]["cell_attrs"] = $<#= pageObj #>->fieldCellAttributes();
<# } #>

<# if (ctrlId == "grid" || ctrlId == "list" && gridAddOrEdit) { #>
        // Skip delete row / empty row for confirm page
        if ($<#= pageObj #>->RowAction != "delete" && $<#= pageObj #>->RowAction != "insertdelete" && !($<#= pageObj #>->RowAction == "insert" && <#= isConfirm #> && $<#= pageObj #>->emptyRow())) {
<# } #>
?>
