<#
    let lvl = groupIndex + 1,
        groupField = groupFields[groupIndex],
        lvlGrpFldObj = Code.fldObj(groupField);
#>
<?php
    $<#= pageObj #>->getSummaryValues($<#= lvlGrpFldObj #>->Records); // Get crosstab summaries from records
    $<#= pageObj #>->resetAttributes();
    $<#= pageObj #>->RowType = ROWTYPE_TOTAL;
    $<#= pageObj #>->RowTotalType = ROWTOTAL_GROUP;
    $<#= pageObj #>->RowTotalSubType = ROWTOTAL_FOOTER;
    $<#= pageObj #>->RowGroupLevel = <#= lvl #>;
    $<#= pageObj #>->renderRow();
?>

    <!-- Summary <#= groupField.FldName #> (level <#= lvl #>) -->
    <tr<?= $<#= pageObj #>->rowAttributes(); ?>>
<#
    for (let j = 0; j < groupIndex; j++) {
        let gf = groupFields[j],
            fldParm = gf.FldParm,
            grpFldObj = Code.fldObj(gf);
#>
        <td data-field="<#= fldParm #>"<?= $<#= grpFldObj #>->cellAttributes() ?>>&nbsp;</td>
<#
    }

    let rowPrefix = "", rowSuffix = "";
    if (IsFieldDrillDown(groupField)) {
        rowPrefix = `"<a" . $${lvlGrpFldObj}->linkAttributes() . ">" . `;
        rowSuffix = ` . "</a>"`;
    }
#>
        <td colspan="<?= ($<#= pageObj #>->GroupColumnCount - <#= lvl - 1 #>) ?>"<?= $<#= lvlGrpFldObj #>->cellAttributes() ?>><?= str_replace(["%v", "%c"], [<#= rowPrefix #>$<#= lvlGrpFldObj #>->GroupViewValue<#= rowSuffix #>, $<#= lvlGrpFldObj #>->caption()], $Language->phrase("CtbSumHead")) ?></td>

<!-- Dynamic columns begin -->
<?php
    $cntcol = count($<#= pageObj #>->SummaryViewValues);
for ($iy = 1; $iy <= $cntcol; $iy++) {
    $colShow = ($iy <= $<#= pageObj #>->ColumnCount) ? $<#= pageObj #>->Columns[$iy]->Visible : true;
    $colDesc = ($iy <= $<#= pageObj #>->ColumnCount) ? $<#= pageObj #>->Columns[$iy]->Caption : $Language->phrase("Summary");
    if ($colShow) {
        ?>
        <!-- <?= $colDesc; ?> -->
        <td<?= $<#= pageObj #>->summaryCellAttributes($iy-1) ?>><?= $<#= pageObj #>->renderSummaryFields($iy-1) ?></td>
        <?php
    }
}
?>
<!-- Dynamic columns end -->
    </tr>
