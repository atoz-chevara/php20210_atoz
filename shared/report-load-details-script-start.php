<# if (groupFields.length == 0) { #>
<?php
    $<#= pageObj #>->loadRowValues($<#= pageObj #>->DetailRecords[<#= recCnt #>]);
    <#= recCnt #>++;
    <#= recIndex #>++;
?>
<# } else { #>
<?php

    // Build detail SQL
    $where = DetailFilterSql($<#= firstGroupFldObj #>, $<#= pageObj #>->getSqlFirstGroupField(), $<#= firstGroupFldObj #>->groupValue(), $<#= pageObj #>->Dbid);

    if ($<#= pageObj #>->PageFirstGroupFilter != "") {
        $<#= pageObj #>->PageFirstGroupFilter .= " OR ";
    }
    $<#= pageObj #>->PageFirstGroupFilter .= $where;

    if ($<#= pageObj #>->Filter != "") {
        $where = "($<#= pageObj #>->Filter) AND ($where)";
    }

    <# if (ctrlId == "crosstab") { #>
    $sql = $<#= pageObj #>->buildReportSql($<#= pageObj #>->getSqlSelect()->addSelect($<#= pageObj #>->DistinctColumnFields), $<#= pageObj #>->getSqlFrom(), $<#= pageObj #>->getSqlWhere(), $<#= pageObj #>->getSqlGroupBy(), "", $<#= pageObj #>->getSqlOrderBy(), $where, $<#= pageObj #>->Sort);
    <# } else { #>
    $sql = $<#= pageObj #>->buildReportSql($<#= pageObj #>->getSqlSelect(), $<#= pageObj #>->getSqlFrom(), $<#= pageObj #>->getSqlWhere(), $<#= pageObj #>->getSqlGroupBy(), $<#= pageObj #>->getSqlHaving(), $<#= pageObj #>->getSqlOrderBy(), $where, $<#= pageObj #>->Sort);
    <# } #>
    $rs = $sql->execute();
    $<#= pageObj #>->DetailRecords = $rs ? $rs->fetchAll() : [];
    $<#= pageObj #>->DetailRecordCount = count($<#= pageObj #>->DetailRecords);

    <# if (ctrlId == "summary" && groupFields.length == 1) { #>
    $<#= pageObj #>->setGroupCount($<#= pageObj #>->DetailRecordCount, <#= grpCount #>);
    <# } #>

    // Load detail records
    $<#= firstGroupFldObj #>->Records = &$<#= pageObj #>->DetailRecords;
    $<#= firstGroupFldObj #>->LevelBreak = true; // Set field level break

    <#
    let lastGrpFld = groupFields[groupFields.length - 1],
        lastGrpFldObj = Code.fldObj(lastGrpFld),
        indexes = [];
    groupFields.forEach((grpFld, i) => {
        groupIndex = i;
        let grpFldParm = grpFld.FldParm,
            grpFldObj = Code.fldObj(grpFld),
            grpFldIndex = Code.getName(pageObj, `GroupCounter[${i+1}]`);
        // Get distinct values
if (i > 0) {
    let prevGrpFld = groupFields[i-1],
        prevGrpFldObj = Code.fldObj(prevGrpFld);
    #>
    $<#= grpFldObj #>->getDistinctValues($<#= prevGrpFldObj #>->Records);
    <# if (ctrlId == "summary") { #>
    $<#= pageObj #>->setGroupCount(count($<#= grpFldObj #>->DistinctValues), <#= indexes.join(", ") #>);
    <#= grpFldIndex #> = 0; // Init group count index
    <# } #>
    foreach ($<#= grpFldObj #>->DistinctValues as $<#= grpFldParm #>) { // Load records for this distinct value
    $<#= grpFldObj #>->setGroupValue($<#= grpFldParm #>); // Set group value
    $<#= grpFldObj #>->getDistinctRecords($<#= prevGrpFldObj #>->Records, $<#= grpFldObj #>->groupValue());
    $<#= grpFldObj #>->LevelBreak = true; // Set field level break
    <#
}
if (ctrlId == "summary") { // Group header row for summary reports only
    indexes.push(grpFldIndex);
    #>
    <# if (i == 0) { // First group #>
    <#= grpFldIndex #> = $<#= pageObj #>->GroupCount;
    <# } else { // Second and subsequent groups #>
    <#= grpFldIndex #>++;
    <# } #>
    $<#= grpFldObj #>->getCnt($<#= grpFldObj #>->Records); // Get record count
    <# if (i == groupFields.length - 1) { // Last group #>
    $<#= pageObj #>->setGroupCount($<#= grpFldObj #>->Count, <#= indexes.join(", ") #>);
    <# } #>
    ?>

<## Group header rows #>
<#= include('./summary-group-header-rows.html') #>

    <?php
    <#
        }
    }); // End for grpFld
    #>
    <# if (ctrlId == "summary") { // Reset record count for summary report #>
    <#= recCnt #> = 0; // Reset record count
    <# } #>
    foreach ($<#= lastGrpFldObj #>->Records as $record) {

        <#= recCnt #>++;
        <#= recIndex #>++;

        $<#= pageObj #>->loadRowValues($record);

    <# if (ctrlId == "crosstab") { #>
        // Render row
        $<#= pageObj #>->resetAttributes();
        $<#= pageObj #>->RowType = ROWTYPE_DETAIL;
        $<#= pageObj #>->renderRow();
    <# } #>

?>
<# } #>
