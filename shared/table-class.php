<?php

namespace <#= ProjectNamespace #>;

use Doctrine\DBAL\ParameterType;

/**
 * Table class for <#= tblName #>
 */
<# if (TABLE.TblType == "REPORT") { #>
    <# if (TABLE.TblReportType == "crosstab") { #>
class <#= tblClassName #> extends CrosstabTable
    <# } else { #>
class <#= tblClassName #> extends ReportTable
    <# } #>
<# } else { #>
class <#= tblClassName #> extends DbTable
<# } #>
{
    protected $SqlFrom = "";
    protected $SqlSelect = null;
    protected $SqlSelectList = null;
    protected $SqlWhere = "";
    protected $SqlGroupBy = "";
    protected $SqlHaving = "";
    protected $SqlOrderBy = "";

    public $UseSessionForListSql = true;

    // Column CSS classes
    public $LeftColumnClass = "<#= labelClass #>";
    public $RightColumnClass = "<#= rightColumnClass #>";
    public $OffsetColumnClass = "<#= offsetClass #>";
    public $TableLeftColumnClass = "<#= tableLeftColumnClass #>";

<# if (TABLE.TblAuditTrail) { #>
    // Audit trail
    public $AuditTrailOnAdd = <#= Code.bool(auditTrailOnAdd) #>;
    public $AuditTrailOnEdit = <#= Code.bool(auditTrailOnEdit) #>;
    public $AuditTrailOnDelete = <#= Code.bool(auditTrailOnDelete) #>;
    public $AuditTrailOnView = <#= Code.bool(auditTrailOnView) #>;
    public $AuditTrailOnViewData = <#= Code.bool(auditTrailOnViewData) #>;
    public $AuditTrailOnSearch = <#= Code.bool(auditTrailOnSearch) #>;
<# } #>

<# if (TABLE.TblReportType == "summary") { #>
    public $ShowGroupHeaderAsRow = <#= Code.bool(TABLE.ShowGroupHeaderAsRow) #>;
    public $ShowCompactSummaryFooter = <#= Code.bool(TABLE.ShowCompactSummaryFooter) #>;
<# } #>

    // Export
    public $ExportDoc;

<#
    for (let c of allCharts) {
        let chartVar = c.ChartVar;
#>
    public $<#= chartVar #>;
<#
    }
#>

<# if (global.columnDateFieldParm) { #>
    public $<#= global.columnDateFieldParm #>;
<# } #>

    // Fields
<#
    for (let f of allFields) {
        let fldParm = f.FldParm;
#>
    public $<#= fldParm #>;
<#
    } // AllField

    let updateTable = fromPart;
    if (TABLE.TblType == "VIEW" && !IsEmpty(TABLE.TblSQL)) {
        updateTable = SqlPart(TABLE.TblSQL, "FROM");
        if (IsEmpty(updateTable) || /\s/.test(updateTable) || /\sAS\s/i.test(SqlPart(TABLE.TblSQL, "SELECT"))) // Safe parsing, FROM contains space => not single table, SELECT contains " AS " => alias
            updateTable = fromPart;
    }
#>

    // Page ID
    public $PageID = ""; // To be overridden by subclass

    // Constructor
    public function __construct()
    {
        global $Language, $CurrentLanguage;

        parent::__construct();

        // Language object
        $Language = Container("language");

        $this->TableVar = '<#= tblVar #>';
        $this->TableName = '<#= SingleQuote(tblName) #>';
        $this->TableType = '<#= TABLE.TblType #>';

        // Update Table
        $this->UpdateTable = "<#= Code.quote(updateTable) #>";

    <# if (TABLE.TblType == "REPORT" && ["summary", "crosstab"].includes(TABLE.TblReportType)) { #>
        $this->ReportSourceTable = '<#= srcTable.TblVar #>'; // Report source table
    <# } #>

        $this->Dbid = '<#= SingleQuote(tblDbId) #>';

        $this->ExportAll = <#= Code.bool(exportAll) #>;
    <# if (TABLE.TblType == "REPORT") { #>
        $this->ExportPageBreakCount = <#= reportPageBreakRecordCount #>; // Page break per every n record (report only)
    <# } else { #>
        $this->ExportPageBreakCount = <#= pdfPageBreakRecordCount #>; // Page break per every n record (PDF only)
    <# } #>
        $this->ExportPageOrientation = "<#= pdfPageOrientation #>"; // Page orientation (PDF only)
        $this->ExportPageSize = "<#= pdfPageSize #>"; // Page size (PDF only)
        $this->ExportExcelPageOrientation = <#= excelPageOrientation #>; // Page orientation (PhpSpreadsheet only)
        $this->ExportExcelPageSize = <#= excelPageSize #>; // Page size (PhpSpreadsheet only)
        $this->ExportWordPageOrientation = "<#= wordPageOrientation #>"; // Page orientation (PHPWord only)
        $this->ExportWordColumnWidth = <#= wordColumnWidth > 0 ? wordColumnWidth : Code.null #>; // Cell width (PHPWord only)

    <# if (TABLE.TblType != "REPORT") { #>
        $this->DetailAdd = <#= Code.bool(TABLE.TblDetailAdd) #>; // Allow detail add
        $this->DetailEdit = <#= Code.bool(TABLE.TblDetailEdit) #>; // Allow detail edit
        $this->DetailView = <#= Code.bool(TABLE.TblDetailView) #>; // Allow detail view
        $this->ShowMultipleDetails = <#= Code.bool(TABLE.TblShowMultipleDetails && detailTables.length > 1) #>; // Show multiple details
        $this->GridAddRowCount = <#= gridAddRowCount #>;
    <# } #>

    <# if (TABLE.TblType != "REPORT") { #>
        $this->AllowAddDeleteRow = true; // Allow add/delete row
    <# } #>

    <# if (!hasUserIdFld || TABLE.TblUserIDAllow) { #>
        $this->UserIDAllowSecurity = Config("DEFAULT_USER_ID_ALLOW_SECURITY"); // Default User ID allowed permissions
    <# } #>

    <# if (TABLE.TblType != "REPORT") { #>

        $this->BasicSearch = new BasicSearch($this->TableVar);

        <# if (!IsEmpty(TABLE.TblBasicSearchDefault)) { #>
        $this->BasicSearch->KeywordDefault = <#= TABLE.TblBasicSearchDefault #>;
        <# } #>

        <# if (!IsEmpty(TABLE.TblBasicSearchTypeDefault)) { #>
        $this->BasicSearch->TypeDefault = "<#= Quote(TABLE.TblBasicSearchTypeDefault) #>";
        <# } #>

    <# } #>

<#
    // Generate fields definition
    for (let f of allFields) {
        FIELD = f;
#>
<## Field class #>
<#= include('./field-class.php') #>
<#
    } // End for allFields
#>
<#
    if (TABLE.TblReportType == "crosstab" && columnDateFieldParm) { // Crosstab report
        let fldExpr = DbGroupSql("y", 0, tblDbId).replace(/%s/g, FieldSqlName(columnField, tblDbId));
#>
        // <#= columnDateFieldName #>
        $this-><#= columnDateFieldParm #> = new ReportField('<#= tblVar #>', '<#= SingleQuote(tblName) #>', '<#= "x_" + columnDateFieldParm #>', '<#= SingleQuote(columnDateFieldName) #>', '<#= SingleQuote(fldExpr) #>', '', <#= columnDateFieldType #>, -1, -1, false, '', false, false, false);
        $this-><#= columnDateFieldParm #>->Sortable = false;
        $this-><#= columnDateFieldParm #>->Caption = $Language->phrase("Year");
<#
            let drillDownUrl = FieldDrillDownUrl(columnField);
            if (drillDownUrl) {
#>
        $this-><#= columnDateFieldParm #>->DrillDownTable = "<#= Quote(columnField.FldDrillTable) #>";
        $this-><#= columnDateFieldParm #>->DrillDownUrl = "<#= drillDownUrl #>";
<#
            }
#>
        $this->Fields['<#= SingleQuote(columnDateFieldName) #>'] = &$this-><#= columnDateFieldParm #>;
<#
    }
#>
<#
    // Generate charts definition
    for (let c of allCharts) {
        CHART = c;
#>
<## Chart class #>
<#= include('./chart-class.php') #>
<#
    } // End for allCharts
#>

    }

    // Field Visibility
    public function getFieldVisibility($fldParm)
    {
        global $Security;
<#
    let extName = "FieldVisibility",
        ext = GetExtensionObject(extName);
    if (ext && ext.Enabled) {
        let extTable = GetExtensionTable(extName, TABLE.TblName);
        if (extTable) {
            let prpType = extTable.PermissionType;
            if (prpType == null) prpType = "bool";
            for (let f of allFields) {
                let fldParm = f.FldParm;
                let extField = GetExtensionField(extName, TABLE.TblName, f.FldName);
                if (extField && extField.Properties) {
                    let cnt = 0;
                    for (let prpId in extField.Properties) {
                        let prpValue = extField[prpId] || "";
                        if (prpId && prpValue) {
                            cnt += 1;
                            if (cnt == 1) {
#>
        if ($fldParm == "<#= fldParm #>") {
<#
                            }
                            let m, prpCond, prpExpr;
                            if (m = prpId.match(/^page_(\w+)/)) // Page ID found
                                prpCond = "CurrentPageID() == \"" + m[1] + "\"";
                            else if (m = prpId.match(/^action_(\w+)/)) // Action found
                                prpCond = "@$this->CurrentAction == \"" + m[1] + "\"";
                            let fldPrpType = prpType;
                            let obj = ParseJson(prpValue);
                            if (IsObject(obj))
                                fldPrpType = "complex";
                            switch (fldPrpType) {
                                case "bool":
                                    if (/^[\-\d]+$/.test(prpValue)) // Numeric
                                        prpExpr = (prpValue != "0") ? Code.true : Code.false;
                                    else
                                        prpExpr = prpValue; // Assume value is string => PHP expressio
                                    break;
                                case "userlevel":
                                    m = prpValue.trim().match(/^array\(([\s\S]*)\)$/) || prpValue.match(/^\[([\s\S]*)\]$/);
                                    prpValue = "[" + (m ? m[1] : prpValue) + "]";
                                    prpExpr = "count(array_intersect($Security->UserLevelID, " + prpValue + ")) > 0 || $Security->isAdmin()";
                                    break;
                                case "userid":
                                    m = prpValue.trim().match(/^array\(([\s\S]*)\)$/) || prpValue.match(/^\[([\s\S]*)\]$/);
                                    prpValue = "[" + (m ? m[1] : prpValue) + "]";
                                    prpExpr = "count(array_intersect($Security->UserID, " + prpValue + ")) > 0 || $Security->isAdmin()";
                                    break;
                                case "complex":
                                    let fldPrpExpr = "";
                                    if (obj.userlevel) {
                                        prpValue = IsArray(obj.userlevel) ? JSON.stringify(obj.userlevel) : "[" + obj.userlevel + "]";
                                        m = prpValue.match(/^\[([\s\S]*)\]$/);
                                        prpValue = "[" + m[1] + "]";
                                        fldPrpExpr = "(count(array_intersect($Security->UserLevelID, " + prpValue + ")) > 0 || $Security->isAdmin())";
                                    }
                                    if (obj.userid) {
                                        prpValue = IsArray(obj.userid) ? JSON.stringify(obj.userid) : "[" + obj.userid + "]";
                                        m = prpValue.match(/^\[([\s\S]*)\]$/);
                                        prpValue = "[" + m[1] + "]";
                                        if (fldPrpExpr != "") fldPrpExpr += " && ";
                                        fldPrpExpr += "(count(array_intersect($Security->UserID, " + prpValue + ")) > 0 || $Security->isAdmin())";
                                    }
                                    if (obj.bool) {
                                        prpValue = obj.bool;
                                        if (/^[\-\d]+$/.test(prpValue)) // Numeric (Value can also be a string => PHP expression)
                                            prpValue = (prpValue != "0") ? Code.true : Code.false;
                                        if (fldPrpExpr != "") fldPrpExpr += " && ";
                                        fldPrpExpr += prpValue;
                                    }
                                    if (fldPrpExpr != "")
                                        prpExpr = fldPrpExpr;
                            }
                            if (prpCond && prpExpr) {
#>
            if (<#= prpCond #>) {
                return <#= prpExpr #>;
            }
<#
                            }
                        }
                    }
                    if (cnt > 0) {
#>
        }
<#
                    }
                }
            }
        }
    }
#>
        return $this->$fldParm->Visible; // Returns original value
    }

<#
    if (TABLE.TblType != "REPORT") {
#>
    // Set left column class (must be predefined col-*-* classes of Bootstrap grid system)
    public function setLeftColumnClass($class)
    {
        if (preg_match('/^col\-(\w+)\-(\d+)$/', $class, $match)) {
            $this->LeftColumnClass = $class . " col-form-label ew-label";
            $this->RightColumnClass = "col-" . $match[1] . "-" . strval(12 - (int)$match[2]);
            $this->OffsetColumnClass = $this->RightColumnClass . " " . str_replace("col-", "offset-", $class);
            $this->TableLeftColumnClass = preg_replace('/^col-\w+-(\d+)$/', "w-col-$1", $class); // Change to w-col-*
        }
    }

    <# if (sortType == 1) { #>

    // Single column sort
    public function updateSort(&$fld)
    {
        if ($this->CurrentOrder == $fld->Name) {
            $sortField = $fld->Expression;
            $lastSort = $fld->getSort();
            if (in_array($this->CurrentOrderType, ["ASC", "DESC", "NO"])) {
                $curSort = $this->CurrentOrderType;
            } else {
                $curSort = $lastSort;
            }
            $fld->setSort($curSort);
            $orderBy = in_array($curSort, ["ASC", "DESC"]) ? $sortField . " " . $curSort : "";
            $this->setSessionOrderBy($orderBy); // Save to Session
    <# if (useVirtualLookup) { #>
            $sortFieldList = ($fld->VirtualExpression != "") ? $fld->VirtualExpression : $sortField;
            $orderBy = in_array($curSort, ["ASC", "DESC"]) ? $sortFieldList . " " . $curSort : "";
            $this->setSessionOrderByList($orderBy); // Save to Session
    <# } #>
        } else {
            $fld->setSort("");
        }
    }

    <# } else if (sortType == 2) { #>

    // Multiple column sort
    public function updateSort(&$fld, $ctrl)
    {
        if ($this->CurrentOrder == $fld->Name) {
            $sortField = $fld->Expression;
            $lastSort = $fld->getSort();
            if (in_array($this->CurrentOrderType, ["ASC", "DESC", "NO"])) {
                $curSort = $this->CurrentOrderType;
            } else {
                $curSort = $lastSort;
            }
            $fld->setSort($curSort);
            $lastOrderBy = in_array($lastSort, ["ASC", "DESC"]) ? $sortField . " " . $lastSort : "";
            $curOrderBy = in_array($curSort, ["ASC", "DESC"]) ? $sortField . " " . $curSort : "";
            if ($ctrl) {
                $orderBy = $this->getSessionOrderBy();
                $arOrderBy = !empty($orderBy) ? explode(", ", $orderBy) : [];
                if ($lastOrderBy != "" && in_array($lastOrderBy, $arOrderBy)) {
                    foreach ($arOrderBy as $key => $val) {
                        if ($val == $lastOrderBy) {
                            if ($curOrderBy == "") {
                                unset($arOrderBy[$key]);
                            } else {
                                $arOrderBy[$key] = $curOrderBy;
                            }
                        }
                    }
                } elseif ($curOrderBy != "") {
                    $arOrderBy[] = $curOrderBy;
                }
                $orderBy = implode(", ", $arOrderBy);
                $this->setSessionOrderBy($orderBy); // Save to Session
            } else {
                $this->setSessionOrderBy($curOrderBy); // Save to Session
            }
    <# if (useVirtualLookup) { #>
            $sortFieldList = ($fld->VirtualExpression != "") ? $fld->VirtualExpression : $sortField;
            $lastOrderBy = in_array($lastSort, ["ASC", "DESC"]) ? $sortFieldList . " " . $lastSort : "";
            $curOrderBy = in_array($curSort, ["ASC", "DESC"]) ? $sortFieldList . " " . $curSort : "";
            if ($ctrl) {
                $orderByList = $this->getSessionOrderByList();
                $arOrderBy = !empty($orderByList) ? explode(", ", $orderByList) : [];
                if ($lastOrderBy != "" && in_array($lastOrderBy, $arOrderBy)) {
                    foreach ($arOrderBy as $key => $val) {
                        if ($val == $lastOrderBy) {
                            if ($curOrderBy == "") {
                                unset($arOrderBy[$key]);
                            } else {
                                $arOrderBy[$key] = $curOrderBy;
                            }
                        }
                    }
                } elseif ($curOrderBy != "") {
                    $arOrderBy[] = $curOrderBy;
                }
                $orderByList = implode(", ", $arOrderBy);
                $this->setSessionOrderByList($orderByList); // Save to Session
            } else {
                $this->setSessionOrderByList($curOrderBy); // Save to Session
            }
    <# } #>
        } else {
            if (!$ctrl) {
                $fld->setSort("");
            }
        }
    }

    <# } #>

    <# if (useVirtualLookup) { #>

    // Session ORDER BY for List page
    public function getSessionOrderByList()
    {
        return Session(PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_ORDER_BY_LIST"));
    }

    public function setSessionOrderByList($v)
    {
        $_SESSION[PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_ORDER_BY_LIST")] = $v;
    }

    <# } #>

<#
    } else { // REPORT
#>

    <# if (sortType == 2) { #>
    // Multiple column sort
    protected function updateSort(&$fld, $ctrl)
    <# } else { #>
    // Single column sort
    protected function updateSort(&$fld)
    <# } #>
    {
        if ($this->CurrentOrder == $fld->Name) {
            $sortField = $fld->Expression;
            $lastSort = $fld->getSort();
            if (in_array($this->CurrentOrderType, ["ASC", "DESC", "NO"])) {
                $curSort = $this->CurrentOrderType;
            } else {
                $curSort = $lastSort;
            }
            $fld->setSort($curSort);

            $lastOrderBy = in_array($lastSort, ["ASC", "DESC"]) ? $sortField . " " . $lastSort : "";
            $curOrderBy = in_array($curSort, ["ASC", "DESC"]) ? $sortField . " " . $curSort : "";
    <# if (sortType == 2) { #>
            if ($fld->GroupingFieldId == 0) {
                if ($ctrl) {
                    $orderBy = $this->getDetailOrderBy();
                    $arOrderBy = !empty($orderBy) ? explode(", ", $orderBy) : [];
                    if ($lastOrderBy != "" && in_array($lastOrderBy, $arOrderBy)) {
                        foreach ($arOrderBy as $key => $val) {
                            if ($val == $lastOrderBy) {
                                if ($curOrderBy == "") {
                                    unset($arOrderBy[$key]);
                                } else {
                                    $arOrderBy[$key] = $curOrderBy;
                                }
                            }
                        }
                    } elseif ($curOrderBy != "") {
                        $arOrderBy[] = $curOrderBy;
                    }
                    $orderBy = implode(", ", $arOrderBy);
                    $this->setDetailOrderBy($orderBy); // Save to Session
                } else {
                    $this->setDetailOrderBy($curOrderBy); // Save to Session
                }
            }
    <# } else { #>
            if ($fld->GroupingFieldId == 0) {
                $this->setDetailOrderBy($curOrderBy); // Save to Session
            }
    <# } #>

        } else {
    <# if (sortType == 2) { #>
            if ($fld->GroupingFieldId == 0 && !$ctrl) {
                $fld->setSort("");
            }
    <# } else { #>
            if ($fld->GroupingFieldId == 0) {
                $fld->setSort("");
            }
    <# } #>
        }
    }

    // Get Sort SQL
    protected function sortSql()
    {
        $dtlSortSql = $this->getDetailOrderBy(); // Get ORDER BY for detail fields from session
        $argrps = [];
        foreach ($this->Fields as $fld) {
            if (in_array($fld->getSort(), ["ASC", "DESC"])) {
                $fldsql = $fld->Expression;
                if ($fld->GroupingFieldId > 0) {
                    if ($fld->GroupSql != "") {
                        $argrps[$fld->GroupingFieldId] = str_replace("%s", $fldsql, $fld->GroupSql) . " " . $fld->getSort();
                    } else {
                        $argrps[$fld->GroupingFieldId] = $fldsql . " " . $fld->getSort();
                    }
                }
            }
        }
        $sortSql = "";
        foreach ($argrps as $grp) {
            if ($sortSql != "") {
                $sortSql .= ", ";
            }
            $sortSql .= $grp;
        }
        if ($dtlSortSql != "") {
            if ($sortSql != "") {
                $sortSql .= ", ";
            }
            $sortSql .= $dtlSortSql;
        }
        return $sortSql;
    }

<#
    }
#>

<#
    if (masterTables.length > 0) {
#>
    // Current master table name
    public function getCurrentMasterTable()
    {
        return Session(PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_MASTER_TABLE"));
    }
    public function setCurrentMasterTable($v)
    {
        $_SESSION[PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_MASTER_TABLE")] = $v;
    }

    // Session master WHERE clause
    public function getMasterFilter()
    {
        // Master filter
        $masterFilter = "";
    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
    #>
        if ($this->getCurrentMasterTable() == "<#= masterTblVar #>") {
    <#
            let dbId = GetDbId(masterTable.TblName); // Get master dbid
            md.Relations.forEach((rel , j) => {
                let masterField = GetFieldObject(masterTable, rel.MasterField),
                    masterFld = FieldSqlName(masterField, dbId),
                    masterFldTypeName = GetFieldTypeName(masterField.FldType),
                    detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFldParm = detailField.FldParm,
                    cond = j >= 1 ? " AND " : "";
    #>
            if ($this-><#= detailFldParm #>->getSessionValue() != "") {
                $masterFilter .= "<#= cond #>" . GetForeignKeySql("<#= Code.quote(masterFld) #>", $this-><#= detailFldParm #>->getSessionValue(), <#= masterFldTypeName #>, "<#= Quote(dbId) #>");
            } else {
                return "";
            }
    <#
            }); // MasterDetailField
    #>
        }
    <#
        } // MasterDetail
    #>
        return $masterFilter;
    }

    // Session detail WHERE clause
    public function getDetailFilter()
    {
        // Detail filter
        $detailFilter = "";
    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
    #>
        if ($this->getCurrentMasterTable() == "<#= masterTblVar #>") {
    <#
            let dbId = GetDbId(masterTable.TblName); // Get dbid
            md.Relations.forEach((rel , j) => {
                let detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFld = FieldSqlName(detailField, dbId),
                    detailFldTypeName = GetFieldTypeName(detailField.FldType),
                    detailFldParm = detailField.FldParm,
                    cond = j >= 1 ? " AND " : "";
    #>
            if ($this-><#= detailFldParm #>->getSessionValue() != "") {
                $detailFilter .= "<#= cond #>" . GetForeignKeySql("<#= Code.quote(detailFld) #>", $this-><#= detailFldParm #>->getSessionValue(), <#= detailFldTypeName #>, "<#= Quote(dbId) #>");
            } else {
                return "";
            }
    <#
            }); // MasterDetailField
    #>
        }
    <#
        } // MasterDetail
    #>
        return $detailFilter;
    }

    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar,
                masterFilter = "",
                detailFilter = "",
                dbId = GetDbId(masterTable.TblName);
            for (let rel of md.Relations) {
                let masterField = GetFieldObject(masterTable, rel.MasterField),
                    masterFld = FieldSqlName(masterField, dbId),
                    masterFldParm = masterField.FldParm,
                    masterFldQuoteS = masterField.FldQuoteS,
                    masterFldQuoteE = masterField.FldQuoteE;
                if (!IsEmpty(masterFilter))
                    masterFilter += " AND ";
                masterFilter += masterFld + "=" + masterFldQuoteS + "@" + masterFldParm + "@" + masterFldQuoteE;
                let detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFld = FieldSqlName(detailField, tblDbId),
                    detailFldParm = detailField.FldParm,
                    detailFldQuoteS = detailField.FldQuoteS,
                    detailFldQuoteE = detailField.FldQuoteE;
                if (!IsEmpty(detailFilter))
                    detailFilter += " AND ";
                detailFilter += detailFld + "=" + detailFldQuoteS + "@" + detailFldParm + "@" + detailFldQuoteE;
            } // MasterDetailField
    #>
    // Master filter
    public function sqlMasterFilter_<#= masterTblVar #>()
    {
        return "<#= Code.quote(masterFilter) #>";
    }
    // Detail filter
    public function sqlDetailFilter_<#= masterTblVar #>()
    {
        return "<#= Code.quote(detailFilter) #>";
    }
    <#
        } // MasterDetail
    #>

<#
    }
#>

<#
    if (detailTables.length > 0) {
#>
    // Current detail table name
    public function getCurrentDetailTable()
    {
        return Session(PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_DETAIL_TABLE"));
    }
    public function setCurrentDetailTable($v)
    {
        $_SESSION[PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_DETAIL_TABLE")] = $v;
    }

    // Get detail url
    public function getDetailUrl()
    {
        // Detail url
        $detailUrl = "";
    <#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar;
            if (detailTable.TblType != "REPORT") {
    #>
        if ($this->getCurrentDetailTable() == "<#= detailTblVar #>") {
            $detailUrl = Container("<#= detailTblVar #>")->getListUrl() . "?" . Config("TABLE_SHOW_MASTER") . "=" . $this->TableVar;
    <#
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(TABLE, rel.MasterField),
                        masterFldParm = masterField.FldParm,
                        detailField = GetFieldObject(detailTable, rel.DetailField),
                        detailFldParm = detailField.FldParm,
                        suffix = "";
                        if (GetFieldType(masterField.FldType) == 2) { // Date
                            suffix = ", " + masterField.FldDtFormat;
                        }
    #>
            $detailUrl .= "&" . GetForeignKeyUrl("fk_<#= Quote(masterFldParm) #>", $this-><#= masterFldParm #>->CurrentValue<#= suffix #>);
    <#
                } // MasterDetailField
    #>
        }
    <#
            }
        } // MasterDetail
    #>
        if ($detailUrl == "") {
            $detailUrl = "<#= listPage #>";
        }
        return $detailUrl;
    }
<#
    }
#>

<#
    // Report SQL
    if (TABLE.TblType == "REPORT") {

        if (groupFields.length > 0) {

            // Report group level SQL: SELECT DISTINCT [Group-By FIELDS] FROM [TABLE/VIEW] ORDER BY [Group-By FIELDS]
            let isCustomView = srcTable.TblType == "CUSTOMVIEW";
            let groupSelectPart = groupFields.map(f =>
                    isCustomView && !IsEmpty(f.FldSourceName) && f.FldSourceName != f.FldName ?
                    f.FldSourceName + (f.FldAlias ? " AS " + f.FldName : "") :
                    QuotedName(f.FldName, tblDbId)
                ).join(",");
            let groupOrderByPart = groupFields.map(f =>
                    FieldSqlName(f, tblDbId) + (!IsEmpty(f.FldOrder) ? " " + f.FldOrder : "")
                ).join(",");
#>

    <# if (["summary", "crosstab"].includes(TABLE.TblReportType)) { #>

    // Table Level Group SQL
    private $sqlFirstGroupField = "";
    private $sqlSelectGroup = null;
    private $sqlOrderByGroup = "";

    // First Group Field
    public function getSqlFirstGroupField($alias = false)
    {
        if ($this->sqlFirstGroupField != "") {
            return $this->sqlFirstGroupField;
        }
        $firstGroupField = &$this-><#= firstGroupField.FldParm #>;
        $expr = $firstGroupField->Expression;
        if ($firstGroupField->GroupSql != "") {
            $expr = str_replace("%s", $firstGroupField->Expression, $firstGroupField->GroupSql);
            if ($alias) {
                $expr .= " AS " . QuotedName($firstGroupField->getGroupName(), $this->Dbid);
            }
        }
        return $expr;
    }

    public function setSqlFirstGroupField($v)
    {
        $this->sqlFirstGroupField = $v;
    }

    // Select Group
    public function getSqlSelectGroup()
    {
        return $this->sqlSelectGroup ?? $this->getQueryBuilder()->select($this->getSqlFirstGroupField(true))->distinct();
    }

    public function setSqlSelectGroup($v)
    {
        $this->sqlSelectGroup = $v;
    }

    // Order By Group
    public function getSqlOrderByGroup()
    {
        if ($this->sqlOrderByGroup != "") {
            return $this->sqlOrderByGroup;
        }
        return $this->getSqlFirstGroupField() . " <#= (firstGroupField.FldOrder || "").trim().toUpperCase() == "DESC" ? "DESC" : "ASC" #>";
    }

    public function setSqlOrderByGroup($v)
    {
        $this->sqlOrderByGroup = $v;
    }

    <# } #>

    <#
        } // groupFields.length > 0
    #>

    <# if (TABLE.TblReportType == "crosstab") { #>

    <#
        // Crosstab report
        let distinctSelect = DistinctColumnField(),
            distinctWhere = wherePart,
            distinctOrderBy = distinctSelect,
            srcTableFilter = srcTable.TblFilter.trim() || '""',
            yearSql = "",
            columnCaptions = '""', columnNames = "", columnValues = "";
        if (!IsEmpty(distinctSelect))
            distinctWhere = distinctWhere.trim();
        if (IsEmpty(columnField.FldOrder))
            distinctOrderBy = "";
        else
            distinctOrderBy += " " + columnField.FldOrder;
        if (showYearSelection)
            yearSql = CrosstabYearSql();
        if (columnDateType == "q") {
            columnNames = "Qtr1,Qtr2,Qtr3,Qtr4";
            columnValues = "1,2,3,4"; // Column values
            columnCaptions = columnNames.split(",").map(id => Code.languagePhrase(id)).join(` . "," . `);
        } else if (columnDateType == "m") {
            columnNames = "MonthJan,MonthFeb,MonthMar,MonthApr,MonthMay,MonthJun,MonthJul,MonthAug,MonthSep,MonthOct,MonthNov,MonthDec";
            columnValues = "1,2,3,4,5,6,7,8,9,10,11,12"; // Column values
            columnCaptions = columnNames.split(",").map(id => Code.languagePhrase(id)).join(` . "," . `);
        }
    #>

    // Crosstab properties
    private $sqlSelectAggregate = null;
    private $sqlGroupByAggregate = "";

    // Select Aggregate
    public function getSqlSelectAggregate()
    {
        return $this->sqlSelectAggregate ?? $this->getQueryBuilder()->select("<#= Code.quote(selectAggPart) #>");
    }

    public function setSqlSelectAggregate($v)
    {
        $this->sqlSelectAggregate = $v;
    }

    // Group By Aggregate
    public function getSqlGroupByAggregate()
    {
        return ($this->sqlGroupByAggregate != "") ? $this->sqlGroupByAggregate : "<#= Code.quote(groupByAggPart) #>";
    }

    public function setSqlGroupByAggregate($v)
    {
        $this->sqlGroupByAggregate = $v;
    }

    // Table level SQL
    private $columnField = "";
    private $columnDateType = "";
    private $columnCaptions = "";
    private $columnNames = "";
    private $columnValues = "";
    <# if (!IsEmpty(distinctSelect)) { #>
    private $sqlDistinctSelect = null;
    private $sqlDistinctWhere = "";
    private $sqlDistinctOrderBy = "";
    <# } #>
    <# if (showYearSelection) { #>
    private $sqlCrosstabYear = "";
    <# } #>

    public $Columns;
    public $ColumnCount;
    public $Col;
    public $DistinctColumnFields = "";
    private $columnLoaded = false;

    // Column field
    public function getColumnField()
    {
        return ($this->columnField != "") ? $this->columnField : "<#= Code.quote(FieldSqlName(columnField, tblDbId)) #>";
    }

    public function setColumnField($v)
    {
        $this->columnField = $v;
    }

    // Column date type
    public function getColumnDateType()
    {
        return ($this->columnDateType != "") ? $this->columnDateType : "<#= columnDateType #>";
    }

    public function setColumnDateType($v)
    {
        $this->columnDateType = $v;
    }

    // Column captions
    public function getColumnCaptions()
    {
        global $Language;
        return ($this->columnCaptions != "") ? $this->columnCaptions : <#= columnCaptions #>;
    }

    public function setColumnCaptions($v)
    {
        $this->columnCaptions = $v;
    }

    // Column names
    public function getColumnNames()
    {
        return ($this->columnNames != "") ? $this->columnNames : "<#= columnNames #>";
    }

    public function setColumnNames($v)
    {
        $this->columnNames = $v;
    }

    // Column values
    public function getColumnValues()
    {
        return ($this->columnValues != "") ? $this->columnValues : "<#= columnValues #>";
    }

    public function setColumnValues($v)
    {
        $this->columnValues = $v;
    }

    <# if (!IsEmpty(distinctSelect)) { #>

    // Select Distinct
    public function getSqlDistinctSelect()
    {
        return $this->sqlDistinctSelect ?? $this->getQueryBuilder()->select("<#= Code.quote(distinctSelect) #>")->distinct();
    }

    public function setSqlDistinctSelect($v)
    {
        $this->sqlDistinctSelect = $v;
    }

    // Distinct Where
    public function getSqlDistinctWhere()
    {
        $where = ($this->sqlDistinctWhere != "") ? $this->sqlDistinctWhere : <#= distinctWhere #>;
        $filter = <#= srcTableFilter #>;
        AddFilter($where, $filter);
        return $where;
    }

    public function setSqlDistinctWhere($v)
    {
        $this->sqlDistinctWhere = $v;
    }

    // Distinct Order By
    public function getSqlDistinctOrderBy()
    {
        return ($this->sqlDistinctOrderBy != "") ? $this->sqlDistinctOrderBy : "<#= Code.quote(distinctOrderBy) #>";
    }

    public function setSqlDistinctOrderBy($v)
    {
        $this->sqlDistinctOrderBy = $v;
    }

    <# } #>

    <# if (showYearSelection) { #>

    // Crosstab Year
    public function getSqlCrosstabYear()
    {
        return ($this->sqlCrosstabYear != "") ? $this->sqlCrosstabYear : "<#= Code.quote(yearSql) #>";
    }

    public function setSqlCrosstabYear($v)
    {
        $this->sqlCrosstabYear = $v;
    }

    <# } #>

    // Load column values
    public function loadColumnValues($filter = "")
    {

        global $Language;

        // Data already loaded, return
        if ($this->columnLoaded) {
            return;
        }

        $conn = $this->getConnection();

    <# if (["q", "m"].includes(columnDateType)) { #>

        $arColumnCaptions = explode(",", $this->getColumnCaptions());
        $arColumnNames = explode(",", $this->getColumnNames());
        $arColumnValues = explode(",", $this->getColumnValues());

        // Get distinct column count
        $this->ColumnCount = count($arColumnNames);

    <# } else { #>

        // Build SQL
        $sql = $this->buildReportSql($this->getSqlDistinctSelect(), $this->getSqlFrom(), $this->getSqlDistinctWhere(), "", "", $this->getSqlDistinctOrderBy(), $filter, "");

        // Load columns
        $rscol = $conn->executeQuery($sql)->fetchAll(\PDO::FETCH_NUM);

        // Get distinct column count
        $this->ColumnCount = count($rscol);
/* Uncomment to show phrase
        if ($this->ColumnCount == 0) {
            echo "<p>" . $Language->phrase("NoDistinctColVals") . $sql . "</p>";
            exit();
        }
*/
    <# } #>

        $this->Columns = Init2DArray($this->ColumnCount + 1, <#= groupFields.length + 1 #>, null);

    <# if (["q", "m"].includes(columnDateType)) { #>

        for ($colcnt = 1; $colcnt <= $this->ColumnCount; $colcnt++)
            $this->Columns[$colcnt] = new CrosstabColumn($arColumnValues[$colcnt - 1], $arColumnCaptions[$colcnt - 1], true);

    <# } else { #>

        $colcnt = 0;
        foreach ($rscol as $row) {
            if ($row[0] === null) {
                $wrkValue = Config("NULL_VALUE");
                $wrkCaption = $Language->phrase("NullLabel");
            } elseif (strval($row[0]) == "") {
                $wrkValue = EMPTY_VALUE;
                $wrkCaption = $Language->phrase("EmptyLabel");
            } else {
                $wrkValue = $row[0];
                $wrkCaption = $row[0];
            }
            $colcnt++;
            $this->Columns[$colcnt] = new CrosstabColumn($wrkValue, $wrkCaption, true);
        }

    <# } #>

        // 1st dimension = no of groups (level 0 used for grand total)
        // 2nd dimension = no of distinct values
        $groupCount = <#= groupFields.length #>;
    <#
        summaryFields.forEach((smryFld, i) => {
            let summaryField = GetFieldObject(TABLE, smryFld.name),
                fldName = summaryField.FldName,
                fldVar = summaryField.FldVar,
                summaryType = smryFld.type,
                fld = FieldSqlName(summaryField, tblDbId),
                summaryCaption = "",
                summaryInitValue = "0";
            switch (summaryType) {
                case "AVG": summaryCaption = `$Language->phrase("RptAvg")`; break;
                case "COUNT": summaryCaption = `$Language->phrase("RptCnt")`; break;
                case "MAX": summaryCaption = `$Language->phrase("RptMax")`; summaryInitValue = Code.null; break;
                case "MIN": summaryCaption = `$Language->phrase("RptMin")`; summaryInitValue = Code.null; break;
                case "SUM": summaryCaption = `$Language->phrase("RptSum")`; break;
            }
    #>
        $this->SummaryFields[<#= i #>] = new SummaryField('<#= SingleQuote(fldVar) #>', '<#= SingleQuote(fldName) #>', '<#= SingleQuote(fld) #>', '<#= SingleQuote(summaryType) #>');
        $this->SummaryFields[<#= i #>]->SummaryCaption = <#= summaryCaption #>;
        $this->SummaryFields[<#= i #>]->SummaryValues = InitArray($this->ColumnCount + 1, null);
        $this->SummaryFields[<#= i #>]->SummaryValueCounts = InitArray($this->ColumnCount + 1, null);
        $this->SummaryFields[<#= i #>]->SummaryInitValue = <#= summaryInitValue #>;
    <#
        });
    #>

    <# if (["q", "m"].includes(columnDateType)) { #>

        // Update crosstab SQL
        $sqlFlds = "";
        $cnt = count($this->SummaryFields);
        for ($is = 0; $is < $cnt; $is++) {
            $smry = &$this->SummaryFields[$is];
            for ($i = 0; $i < $this->ColumnCount; $i++) {
                $fld = CrosstabFieldExpression($smry->SummaryType, $smry->Expression,
                    $this->getColumnField(), $this->getColumnDateType(), $arColumnValues[$i], "", $arColumnNames[$i] . $is, $this->Dbid);
                if ($sqlFlds != "") {
                    $sqlFlds .= ", ";
                }
                $sqlFlds .= $fld;
            }
        }

    <# } else { #>

        // Update crosstab SQL
        $sqlFlds = "";
        $cnt = count($this->SummaryFields);
        for ($is = 0; $is < $cnt; $is++) {
            $smry = &$this->SummaryFields[$is];
            for ($i = 1; $i <= $this->ColumnCount; $i++) {
                $fld = CrosstabFieldExpression($smry->SummaryType, $smry->Expression, $this->getColumnField(), $this->getColumnDateType(), $this->Columns[$i]->Value, "<#= Quote(columnField.FldQuoteS) #>", "C" . $is . $i, $this->Dbid);
                if ($sqlFlds != "") {
                    $sqlFlds .= ", ";
                }
                $sqlFlds .= $fld;
            }
        }

    <# } #>

        $this->DistinctColumnFields = $sqlFlds ?: "NULL"; // In case ColumnCount = 0

        $this->columnLoaded = true;

    }

    <# } else if (TABLE.TblReportType == "summary") { #>

    // Summary properties
    private $sqlSelectAggregate = null;
    private $sqlAggregatePrefix = "";
    private $sqlAggregateSuffix = "";
    private $sqlSelectCount = null;

    // Select Aggregate
    public function getSqlSelectAggregate()
    {
        return $this->sqlSelectAggregate ?? $this->getQueryBuilder()->select("<#= Code.quote(selectAggPart) #>");
    }

    public function setSqlSelectAggregate($v)
    {
        $this->sqlSelectAggregate = $v;
    }

    // Aggregate Prefix
    public function getSqlAggregatePrefix()
    {
        return ($this->sqlAggregatePrefix != "") ? $this->sqlAggregatePrefix : "<#= Code.quote(aggPrefixPart) #>";
    }

    public function setSqlAggregatePrefix($v)
    {
        $this->sqlAggregatePrefix = $v;
    }

    // Aggregate Suffix
    public function getSqlAggregateSuffix()
    {
        return ($this->sqlAggregateSuffix != "") ? $this->sqlAggregateSuffix : "<#= Code.quote(aggSuffixPart) #>";
    }

    public function setSqlAggregateSuffix($v)
    {
        $this->sqlAggregateSuffix = $v;
    }

    // Select Count
    public function getSqlSelectCount()
    {
        return $this->sqlSelectCount ?? $this->getQueryBuilder()->select("COUNT(*)");
    }

    public function setSqlSelectCount($v)
    {
        $this->sqlSelectCount = $v;
    }

    <# } #>

    // Render for lookup
    public function renderLookup()
    {

    <#
        for (let f of allFields) {
            if (IsExtendedFilter(f)) {
                let fldParm = f.FldParm,
                    fldObj = "this->" + fldParm;
                if (IsTextFilter(f)) { // Text filter
                    if (GetFieldType(f.FldType) == 2) { // Format date
                        let fldDtFormat = f.FldDtFormat || 0;
    #>
        $<#= fldObj #>->ViewValue = FormatDateTime($<#= fldObj #>->CurrentValue, <#= fldDtFormat #>);
    <#
                    } else {
    #>
        $<#= fldObj #>->ViewValue = $<#= fldObj #>->CurrentValue;
    <#
                    }
                } else { // Non-text filter
                    let currentValue = Code.getName(fldObj, "CurrentValue"), dropDownType = '""', fldDtFormat = 0;
                    if (IsDateFilter(f)) {
                        dropDownType = Code.getName(fldObj, "DateFilter");
                        fldDtFormat = f.FldDtFormat;
                    } else if (IsDetailGroupTypeField(TABLE, f)) {
                        dropDownType = '"' + f.FldGroupByType + '"';
                        fldDtFormat = f.FldDtFormat;
                    } else if (GetFieldType(f.FldType) == 2) {
                        dropDownType = '"date"';
                        fldDtFormat = f.FldDtFormat;
                    } else if (IsBooleanField(TABLE, f)) {
                        dropDownType = '"boolean"';
                    }
    #>
        $<#= fldObj #>->ViewValue = GetDropDownDisplayValue(<#= currentValue #>, <#= dropDownType #>, <#= fldDtFormat #>);
    <#
                }
            }
        } // End for
    #>

    }

<#
    } // TABLE.TblType == "REPORT"
#>

    // Table level SQL
    public function getSqlFrom() // From
    {
        return ($this->SqlFrom != "") ? $this->SqlFrom : "<#= Code.quote(fromPart) #>";
    }
    public function sqlFrom() // For backward compatibility
    {
        return $this->getSqlFrom();
    }
    public function setSqlFrom($v)
    {
        $this->SqlFrom = $v;
    }

    <# if (TABLE.TblReportType == "summary") { #>
    public function getSqlSelect() // Select
    {
        if ($this->SqlSelect) {
            return $this->SqlSelect;
        }
        $select = $this->getQueryBuilder()->select("<#= Code.quote(selectPart) #>");
    <#
        groupFields.forEach(grpFld => {
    #>
        $groupField = &$this-><#= grpFld.FldParm #>;
        if ($groupField->GroupSql != "") {
            $expr = str_replace("%s", $groupField->Expression, $groupField->GroupSql) . " AS " . QuotedName($groupField->getGroupName(), $this->Dbid);
            $select->addSelect($expr);
        }
    <#
        });
    #>
        return $select;
    }
    <# } else { #>
    public function getSqlSelect() // Select
    {
        return $this->SqlSelect ?? $this->getQueryBuilder()->select("<#= Code.quote(selectPart) #>");
    }
    <# } #>
    public function sqlSelect() // For backward compatibility
    {
        return $this->getSqlSelect();
    }
    public function setSqlSelect($v)
    {
        $this->SqlSelect = $v;
    }

    <# if (useVirtualLookup) { #>
    public function getSqlSelectList() // Select for List page
    {
        if ($this->SqlSelectList) {
            return $this->SqlSelectList;
        }
        <# if (LanguageCount > 1) { #>
        global $CurrentLanguage;
        switch ($CurrentLanguage) {
        <#
        Languages.forEach((id, index) => {
        #>
            case "<#= id #>":
                $from = "(SELECT <#= Code.quote(selectPart) #>, <#= virtualFieldList[index] #> FROM <#= Code.quote(fromPart) #>)";
                break;
        <#
        });
        #>
            default:
                $from = "(SELECT <#= Code.quote(selectPart) #>, <#= virtualFieldList[0] #> FROM <#= Code.quote(fromPart) #>)";
                break;
        }
        <# } else { #>
        $from = "(SELECT <#= Code.quote(selectPart) #>, <#= virtualFieldList[0] #> FROM <#= Code.quote(fromPart) #>)";
        <# } #>
        return $from . " <#= Code.quote(QuotedName("TMP_TABLE", tblDbId)) #>";
    }
    public function sqlSelectList() // For backward compatibility
    {
        return $this->getSqlSelectList();
    }
    public function setSqlSelectList($v)
    {
        $this->SqlSelectList = $v;
    }
    <# } #>

    public function getSqlWhere() // Where
    {
        $where = ($this->SqlWhere != "") ? $this->SqlWhere : <#= wherePart #>;
    <#
    let tblDefaultFilter = TABLE.TblFilter ? TABLE.TblFilter.trim() : "";
    if (tblDefaultFilter == "")
        tblDefaultFilter = '""';
    #>
        $this->DefaultFilter = <#= tblDefaultFilter #>;
        AddFilter($where, $this->DefaultFilter);
        return $where;
    }
    public function sqlWhere() // For backward compatibility
    {
        return $this->getSqlWhere();
    }
    public function setSqlWhere($v)
    {
        $this->SqlWhere = $v;
    }

    public function getSqlGroupBy() // Group By
    {
        return ($this->SqlGroupBy != "") ? $this->SqlGroupBy : "<#= Code.quote(groupByPart) #>";
    }
    public function sqlGroupBy() // For backward compatibility
    {
        return $this->getSqlGroupBy();
    }
    public function setSqlGroupBy($v)
    {
        $this->SqlGroupBy = $v;
    }

    public function getSqlHaving() // Having
    {
        return ($this->SqlHaving != "") ? $this->SqlHaving : "<#= Code.quote(havingPart) #>";
    }
    public function sqlHaving() // For backward compatibility
    {
        return $this->getSqlHaving();
    }
    public function setSqlHaving($v)
    {
        $this->SqlHaving = $v;
    }

    public function getSqlOrderBy() // Order By
    {
    <# if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && defaultOrderBy == "") { #>
        return ($this->SqlOrderBy != "") ? $this->SqlOrderBy : Config("USER_LEVEL_ID_FIELD");
    <# } else { #>
        return ($this->SqlOrderBy != "") ? $this->SqlOrderBy : $this->DefaultSort;
    <# } #>
    }
    public function sqlOrderBy() // For backward compatibility
    {
        return $this->getSqlOrderBy();
    }
    public function setSqlOrderBy($v)
    {
        $this->SqlOrderBy = $v;
    }

    // Apply User ID filters
    public function applyUserIDFilters($filter)
    {
        <# if (hasUserIdFld || masterTableHasUserIdFld) { #>
        global $Security;
        // Add User ID filter
        if ($Security->currentUserID() != "" && !$Security->isAdmin()) { // Non system admin
        <#
            if (hasUserIdFld) {
        #>
            $filter = $this->addUserIDFilter($filter);
        <#
            } else if (masterTableHasUserIdFld) {
                for (let md of masterTables) {
                    let masterTable = GetTableObject(md.MasterTable),
                        masterTblVar = masterTable.TblVar;
        #>
            if ($this->getCurrentMasterTable() == "<#= masterTblVar #>" || $this->getCurrentMasterTable() == "") {
                $filter = $this->addDetailUserIDFilter($filter, "<#= masterTblVar #>"); // Add detail User ID filter
            }
        <#
                } // MasterTable
            }
        #>
        }
        <# } #>
        return $filter;
    }

    // Check if User ID security allows view all
    public function userIDAllow($id = "")
    {

        $allow = $this->UserIDAllowSecurity;
        switch ($id) {
            case "add":
            case "copy":
            case "gridadd":
            case "register":
            case "addopt":
                return (($allow & 1) == 1);
            case "edit":
            case "gridedit":
            case "update":
            case "changepassword":
            case "resetpassword":
                return (($allow & 4) == 4);
            case "delete":
                return (($allow & 2) == 2);
            case "view":
                return (($allow & 32) == 32);
            case "search":
                return (($allow & 64) == 64);
            default:
                return (($allow & 8) == 8);
        }

    }

    /**
     * Get record count
     *
     * @param string|QueryBuilder $sql SQL or QueryBuilder
     * @param mixed $c Connection
     * @return int
     */
    public function getRecordCount($sql, $c = null)
    {
        $cnt = -1;
        $rs = null;
        if ($sql instanceof \Doctrine\DBAL\Query\QueryBuilder) { // Query builder
            $sqlwrk = clone $sql;
            $sqlwrk = $sqlwrk->resetQueryPart("orderBy")->getSQL();
        } else {
            $sqlwrk = $sql;
        }
        $pattern = '/^SELECT\s([\s\S]+)\sFROM\s/i';
        // Skip Custom View / SubQuery / SELECT DISTINCT / ORDER BY
        if (
            ($this->TableType == 'TABLE' || $this->TableType == 'VIEW' || $this->TableType == 'LINKTABLE') &&
            preg_match($pattern, $sqlwrk) && !preg_match('/\(\s*(SELECT[^)]+)\)/i', $sqlwrk) &&
            !preg_match('/^\s*select\s+distinct\s+/i', $sqlwrk) && !preg_match('/\s+order\s+by\s+/i', $sqlwrk)
        ) {
            $sqlwrk = "SELECT COUNT(*) FROM " . preg_replace($pattern, "", $sqlwrk);
        } else {
            $sqlwrk = "SELECT COUNT(*) FROM (" . $sqlwrk . ") COUNT_TABLE";
        }
        $conn = $c ?? $this->getConnection();
        $rs = $conn->executeQuery($sqlwrk);
        $cnt = $rs->fetchColumn();
        if ($cnt !== false) {
            return (int)$cnt;
        }

        // Unable to get count by SELECT COUNT(*), execute the SQL to get record count directly
        return ExecuteRecordCount($sql, $conn);
    }

<#
    // Table/View SQL
    if (TABLE.TblType != "REPORT") {
#>

    // Get SQL
    public function getSql($where, $orderBy = "")
    {
        return $this->buildSelectSql(
            $this->getSqlSelect(),
            $this->getSqlFrom(),
            $this->getSqlWhere(),
            $this->getSqlGroupBy(),
            $this->getSqlHaving(),
            $this->getSqlOrderBy(),
            $where,
            $orderBy
        )->getSQL();
    }

    // Table SQL
    public function getCurrentSql()
    {
        $filter = $this->CurrentFilter;
        $filter = $this->applyUserIDFilters($filter);
        $sort = $this->getSessionOrderBy();
        return $this->getSql($filter, $sort);
    }

    /**
     * Table SQL with List page filter
     *
     * @return QueryBuilder
     */
    public function getListSql()
    {
        $filter = $this->UseSessionForListSql ? $this->getSessionWhere() : "";
        AddFilter($filter, $this->CurrentFilter);
        $filter = $this->applyUserIDFilters($filter);
        <# if (ServerScriptExist("Table", "Recordset_Selecting")) { #>
        $this->recordsetSelecting($filter);
        <# } #>
<# if (useVirtualLookup) { #>
        if ($this->useVirtualFields()) {
            $select = "*";
            $from = $this->getSqlSelectList();
            $sort = $this->UseSessionForListSql ? $this->getSessionOrderByList() : "";
        } else {
            $select = $this->getSqlSelect();
            $from = $this->getSqlFrom();
            $sort = $this->UseSessionForListSql ? $this->getSessionOrderBy() : "";
        }
<# } else { #>
        $select = $this->getSqlSelect();
        $from = $this->getSqlFrom();
        $sort = $this->UseSessionForListSql ? $this->getSessionOrderBy() : "";
<# } #>
        $this->Sort = $sort;
        return $this->buildSelectSql(
            $select,
            $from,
            $this->getSqlWhere(),
            $this->getSqlGroupBy(),
            $this->getSqlHaving(),
            $this->getSqlOrderBy(),
            $filter,
            $sort
        );
    }

    // Get ORDER BY clause
    public function getOrderBy()
    {
        $orderBy = $this->getSqlOrderBy();
<# if (useVirtualLookup) { #>
        $sort = ($this->useVirtualFields()) ? $this->getSessionOrderByList() : $this->getSessionOrderBy();
<# } else { #>
        $sort = $this->getSessionOrderBy();
<# } #>
        if ($orderBy != "" && $sort != "") {
            $orderBy .= ", " . $sort;
        } elseif ($sort != "") {
            $orderBy = $sort;
        }
        return $orderBy;
    }

<# if (useVirtualLookup) { #>
    // Check if virtual fields is used in SQL
    protected function useVirtualFields()
    {
        $where = $this->UseSessionForListSql ? $this->getSessionWhere() : $this->CurrentFilter;
        $orderBy = $this->UseSessionForListSql ? $this->getSessionOrderByList() : "";
        if ($where != "") {
            $where = " " . str_replace(["(", ")"], ["", ""], $where) . " ";
        }
        if ($orderBy != "") {
            $orderBy = " " . str_replace(["(", ")"], ["", ""], $orderBy) . " ";
        }
<#
    let genBasicSearch = allFields.some(f => IsVirtualLookupField(f) && IsFieldBasicSearch(f));
    if (genBasicSearch) {
#>
        if ($this->BasicSearch->getKeyword() != "") {
            return true;
        }
<#
    }
    for (let f of allFields) {
        if (IsVirtualLookupField(f)) {
            let fldObj = "$this->" + f.FldParm;
            if (f.FldHtmlTag == "TEXT" || f.FldVirtualLookupSearch) {
#>
        if (
            <#= fldObj #>->AdvancedSearch->SearchValue != "" ||
            <#= fldObj #>->AdvancedSearch->SearchValue2 != "" ||
            ContainsString($where, " " . <#= fldObj #>->VirtualExpression . " ")
        ) {
            return true;
        }
<#
            }
#>
        if (ContainsString($orderBy, " " . <#= fldObj #>->VirtualExpression . " ")) {
            return true;
        }
<#
        }
    } // AllField
#>
        return false;
    }
<# } #>

    // Get record count based on filter (for detail record count in master table pages)
    public function loadRecordCount($filter)
    {
        $origFilter = $this->CurrentFilter;
        $this->CurrentFilter = $filter;
        <# if (ServerScriptExist("Table", "Recordset_Selecting")) { #>
        $this->recordsetSelecting($this->CurrentFilter);
        <# } #>
        $select = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlSelect() : $this->getQueryBuilder()->select("*");
        $groupBy = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlGroupBy() : "";
        $having = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlHaving() : "";
        $sql = $this->buildSelectSql($select, $this->getSqlFrom(), $this->getSqlWhere(), $groupBy, $having, "", $this->CurrentFilter, "");
        $cnt = $this->getRecordCount($sql);
        $this->CurrentFilter = $origFilter;
        return $cnt;
    }

    // Get record count (for current List page)
    public function listRecordCount()
    {
        $filter = $this->getSessionWhere();
        AddFilter($filter, $this->CurrentFilter);
        $filter = $this->applyUserIDFilters($filter);
        <# if (ServerScriptExist("Table", "Recordset_Selecting")) { #>
        $this->recordsetSelecting($filter);
        <# } #>
        $select = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlSelect() : $this->getQueryBuilder()->select("*");
        $groupBy = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlGroupBy() : "";
        $having = $this->TableType == 'CUSTOMVIEW' ? $this->getSqlHaving() : "";
<# if (useVirtualLookup) { #>
        if ($this->useVirtualFields()) {
            $sql = $this->buildSelectSql("*", $this->getSqlSelectList(), $this->getSqlWhere(), $groupBy, $having, "", $filter, "");
        } else {
            $sql = $this->buildSelectSql($select, $this->getSqlFrom(), $this->getSqlWhere(), $groupBy, $having, "", $filter, "");
        }
<# } else { #>
        $sql = $this->buildSelectSql($select, $this->getSqlFrom(), $this->getSqlWhere(), $groupBy, $having, "", $filter, "");
<# } #>
        $cnt = $this->getRecordCount($sql);
        return $cnt;
    }

    /**
     * INSERT statement
     *
     * @param mixed $rs
     * @return QueryBuilder
     */
    protected function insertSql(&$rs)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert($this->UpdateTable);
        foreach ($rs as $name => $value) {
            if (!isset($this->Fields[$name]) || $this->Fields[$name]->IsCustom) {
                continue;
            }
        <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>
            if (Config("ENCRYPTED_PASSWORD") && $name == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                $value = Config("CASE_SENSITIVE_PASSWORD") ? EncryptPassword($value) : EncryptPassword(strtolower($value));
            }
        <# } #>
            $type = GetParameterType($this->Fields[$name], $value, $this->Dbid);
            $queryBuilder->setValue($this->Fields[$name]->Expression, $queryBuilder->createPositionalParameter($value, $type));
        }
        return $queryBuilder;
    }

    // Insert
    public function insert(&$rs)
    {
        $conn = $this->getConnection();
        $success = $this->insertSql($rs)->execute();

        if ($success) {
    <#
            for (let f of allFields) {
                if (f.FldAutoIncrement) {
                    let fldParm = f.FldParm;
    #>
            // Get insert id if necessary
    <#
                    let fldDbDefault = f.FldDbDefault,
                        curVal = "";
                    if (tblIsPostgreSql && /^nextval\(/i.test(fldDbDefault)) {
                        curVal = fldDbDefault.replace(/nextval\(/gi, "currval(");
                    } else if (tblIsOracle && /\.NEXTVAL$/i.test(fldDbDefault)) {
                        curVal = fldDbDefault.replace(/\.NEXTVAL/gi, "");
                    }
    #>
        <# if (tblIsPostgreSql && curVal) { #>
            $this-><#= fldParm #>->setDbValue($conn->fetchColumn("SELECT <#= Quote(curVal) #>"));
        <# } else if (tblIsOracle && curVal) { #>
            $this-><#= fldParm #>->setDbValue($conn->lastInsertId("<#= Quote(curVal) #>"));
        <# } else { #>
            $this-><#= fldParm #>->setDbValue($conn->lastInsertId());
        <# } #>
            $rs['<#= SingleQuote(f.FldName) #>'] = $this-><#= fldParm #>->DbValue;
    <#
                }
            } // Field
    #>
    <# if (auditTrailOnAdd) { #>
            if ($this->AuditTrailOnAdd) {
                $this->writeAuditTrailOnAdd($rs);
            }
    <# } #>
        }
        return $success;
    }

    /**
     * UPDATE statement
     *
     * @param array $rs Data to be updated
     * @param string|array $where WHERE clause
     * @param string $curfilter Filter
     * @return QueryBuilder
     */
    protected function updateSql(&$rs, $where = "", $curfilter = true)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->update($this->UpdateTable);
        foreach ($rs as $name => $value) {
            if (!isset($this->Fields[$name]) || $this->Fields[$name]->IsCustom || $this->Fields[$name]->IsAutoIncrement) {
                continue;
            }
        <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>
            if (Config("ENCRYPTED_PASSWORD") && $name == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                if ($value == $this->Fields[$name]->OldValue) { // No need to update hashed password if not changed
                    continue;
                }
                $value = Config("CASE_SENSITIVE_PASSWORD") ? EncryptPassword($value) : EncryptPassword(strtolower($value));
            }
        <# } #>
            $type = GetParameterType($this->Fields[$name], $value, $this->Dbid);
            $queryBuilder->set($this->Fields[$name]->Expression, $queryBuilder->createPositionalParameter($value, $type));
        }
        $filter = ($curfilter) ? $this->CurrentFilter : "";
        if (is_array($where)) {
            $where = $this->arrayToFilter($where);
        }
        AddFilter($filter, $where);
        if ($filter != "") {
            $queryBuilder->where($filter);
        }
        return $queryBuilder;
    }

    // Update
    public function update(&$rs, $where = "", $rsold = null, $curfilter = true)
    {
    <#
        if (detailTables.length > 0) {
            for (let md of detailTables) {
                if (md.CascadeUpdate) { // Cascade update
                    let detailTable = GetTableObject(md.DetailTable),
                        detailTblVar = detailTable.TblVar;
                    if (detailTable.TblType != "REPORT") {
    #>
        // Cascade Update detail table '<#= detailTable.TblName #>'
        $cascadeUpdate = false;
        $rscascade = [];
    <#
                        // Get detail key SQL
                        let detailKeySql = "",
                            dbId = GetDbId(detailTable.TblName); // Get detail dbid
                        for (let rel of md.Relations) {
                            let masterField = GetFieldObject(TABLE, rel.MasterField),
                                masterFldName = masterField.FldName,
                                masterFldTypeName = GetFieldTypeName(masterField.FldType),
                                detailField = GetFieldObject(detailTable, rel.DetailField),
                                detailFld = FieldSqlName(detailField, dbId),
                                detailFldName = detailField.FldName,
                                masterKeyCheck = "isset($rs['" + SingleQuote(masterFldName) + "']) && $rsold['" + SingleQuote(masterFldName) + "'] != $rs['" + SingleQuote(masterFldName) + "']";
                            if (detailKeySql != "")
                                detailKeySql += ' . " AND " . ';
                            detailKeySql += "\"" + Quote(detailFld) + " = \" . QuotedValue($rsold['" + SingleQuote(masterFldName) + "'], " + masterFldTypeName + ", '" + SingleQuote(dbId) + "')";
    #>
        if ($rsold && (<#= masterKeyCheck #>)) { // Update detail field '<#= detailFldName #>'
            $cascadeUpdate = true;
            $rscascade['<#= SingleQuote(detailFldName) #>'] = $rs['<#= SingleQuote(masterFldName) #>'];
        }
    <#
                        } // MasterDetailField
    #>
        if ($cascadeUpdate) {
            $rswrk = Container("<#= detailTblVar #>")->loadRs(<#= detailKeySql #>)->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rswrk as $rsdtlold) {
                $rskey = [];
        <#
            for (let df of detailTable.Fields) {
                if (df.FldIsPrimaryKey) {
        #>
                $fldname = '<#= SingleQuote(df.FldName) #>';
                $rskey[$fldname] = $rsdtlold[$fldname];
        <#
                }
            } // Field
        #>
                $rsdtlnew = array_merge($rsdtlold, $rscascade);
                // Call Row_Updating event
                $success = Container("<#= detailTblVar #>")->rowUpdating($rsdtlold, $rsdtlnew);
                if ($success) {
                    $success = Container("<#= detailTblVar #>")->update($rscascade, $rskey, $rsdtlold);
                }
                if (!$success) {
                    return false;
                }
                // Call Row_Updated event
                Container("<#= detailTblVar #>")->rowUpdated($rsdtlold, $rsdtlnew);
            }
        }
    <#
                    }
                }
            }
        }
    #>

        // If no field is updated, execute may return 0. Treat as success
        $success = $this->updateSql($rs, $where, $curfilter)->execute();
        $success = ($success > 0) ? $success : true;

    <# if (auditTrailOnEdit) { #>
        if ($success && $this->AuditTrailOnEdit && $rsold) {
            $rsaudit = $rs;
        <#
            for (let kf of keyFields) {
        #>
            $fldname = '<#= SingleQuote(kf.FldName) #>';
            if (!array_key_exists($fldname, $rsaudit)) {
                $rsaudit[$fldname] = $rsold[$fldname];
            }
        <#
            } // KeyField
        #>
            $this->writeAuditTrailOnEdit($rsold, $rsaudit);
        }
    <# } #>
        return $success;
    }

    /**
     * DELETE statement
     *
     * @param array $rs Key values
     * @param string|array $where WHERE clause
     * @param string $curfilter Filter
     * @return QueryBuilder
     */
    protected function deleteSql(&$rs, $where = "", $curfilter = true)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->delete($this->UpdateTable);
        if (is_array($where)) {
            $where = $this->arrayToFilter($where);
        }
        if ($rs) {
    <#
        for (let kf of keyFields) {
            let fldName = kf.FldName, fldParm = kf.FldParm;
    #>
            if (array_key_exists('<#= SingleQuote(fldName) #>', $rs)) {
                AddFilter($where, QuotedName('<#= SingleQuote(fldName) #>', $this->Dbid) . '=' . QuotedValue($rs['<#= SingleQuote(fldName) #>'], $this-><#= fldParm #>->DataType, $this->Dbid));
            }
    <#
        } // KeyField
    #>
        }
        $filter = ($curfilter) ? $this->CurrentFilter : "";
        AddFilter($filter, $where);
        return $queryBuilder->where($filter != "" ? $filter : "0=1");
    }

    // Delete
    public function delete(&$rs, $where = "", $curfilter = false)
    {
        $success = true;
    <#
        if (detailTables.length > 0) {
            for (let md of detailTables) {
                if (md.CascadeDelete) { // Cascade delete
                    let detailTable = GetTableObject(md.DetailTable),
                        detailTblVar = detailTable.TblVar;
                    if (detailTable.TblType != "REPORT") {
                        // Get detail key SQL
                        let detailKeySql = "",
                            dbId = GetDbId(detailTable.TblName); // Get detail dbid
                        for (let rel of md.Relations) {
                            let masterField = GetFieldObject(TABLE, rel.MasterField),
                                masterFldName = masterField.FldName,
                                masterFldTypeName = GetFieldTypeName(masterField.FldType),
                                detailField = GetFieldObject(detailTable, rel.DetailField),
                                detailFld = FieldSqlName(detailField, dbId);
                            if (detailKeySql != "")
                                detailKeySql += ' . " AND " . ';
                            detailKeySql += "\"" + Quote(detailFld) + " = \" . QuotedValue($rs['" + SingleQuote(masterFldName) + "'], " + masterFldTypeName + ", \"" + Quote(dbId) + "\")";
                        } // MasterDetailField
    #>
        // Cascade delete detail table '<#= md.DetailTable #>'
        $dtlrows = Container("<#= detailTblVar #>")->loadRs(<#= detailKeySql #>)->fetchAll(\PDO::FETCH_ASSOC);
        // Call Row Deleting event
        foreach ($dtlrows as $dtlrow) {
            $success = Container("<#= detailTblVar #>")->rowDeleting($dtlrow);
            if (!$success) {
                break;
            }
        }
        if ($success) {
            foreach ($dtlrows as $dtlrow) {
                $success = Container("<#= detailTblVar #>")->delete($dtlrow); // Delete
                if (!$success) {
                    break;
                }
            }
        }
        // Call Row Deleted event
        if ($success) {
            foreach ($dtlrows as $dtlrow) {
                Container("<#= detailTblVar #>")->rowDeleted($dtlrow);
            }
        }
    <#
                    }
                }
            }
        }
    #>

        if ($success) {
            $success = $this->deleteSql($rs, $where, $curfilter)->execute();
        }
    <# if (auditTrailOnDelete) { #>
        if ($success && $this->AuditTrailOnDelete) {
            $this->writeAuditTrailOnDelete($rs);
        }
    <# } #>
        return $success;

    }

    // Load DbValue from recordset or array
    protected function loadDbValues($row)
    {
        if (!is_array($row)) {
            return;
        }
        <#
            for (let f of allFields) {
                let fldName = f.FldName, fldParm = f.FldParm,
                    fld = "$row['" + SingleQuote(fldName) + "']";
                if (f.FldHtmlTag == "FILE") {
        #>
        $this-><#= fldParm #>->Upload->DbValue = <#= fld #>;
        <#
                } else {
        #>
        $this-><#= fldParm #>->DbValue = <#= GetFieldValue(TABLE, fld, f.FldType) #>;
        <#
                }
            }
        #>
    }

    // Delete uploaded files
    public function deleteUploadedFiles($row)
    {
        $this->loadDbValues($row);
    <#
        for (let f of allFields) {
            let fldName = f.FldName,
                fldParm = f.FldParm;
            if (f.FldHtmlTag == "FILE" && !IsBinaryField(f)) {
    #>
    <# if (!IsEmpty(f.FldUploadPath)) { #>
        $this-><#= fldParm #>->OldUploadPath = <#= f.FldUploadPath #>;
    <# } #>
    <# if (!f.FldUploadMultiple) { // Not multiple upload #>
        $oldFiles = EmptyValue($row['<#= SingleQuote(fldName) #>']) ? [] : [$row['<#= SingleQuote(fldName) #>']];
    <# } else { #>
        $oldFiles = EmptyValue($row['<#= SingleQuote(fldName) #>']) ? [] : explode(Config("MULTIPLE_UPLOAD_SEPARATOR"), $row['<#= SingleQuote(fldName) #>']);
    <# } #>
        foreach ($oldFiles as $oldFile) {
            if (file_exists($this-><#= fldParm #>->oldPhysicalUploadPath() . $oldFile)) {
                @unlink($this-><#= fldParm #>->oldPhysicalUploadPath() . $oldFile);
            }
        }
    <#
            }
        } // Field
    #>
    }

<#
    }
#>

<#
    let keyFilter = keyFields.map(f => `${FieldSqlName(f, tblDbId)} = ${f.FldQuoteS}@${f.FldParm}@${f.FldQuoteE}`).join(" AND ");
#>

    // Record filter WHERE clause
    protected function sqlKeyFilter()
    {
        return "<#= Code.quote(keyFilter) #>";
    }

    // Get Key
    public function getKey($current = false)
    {
        $keys = [];
    <#
        for (let kf of keyFields) {
            let fldName = kf.FldName,
                fldParm = kf.FldParm,
                fldObj = "$this->" + fldParm;
    #>
        $val = $current ? <#= fldObj #>->CurrentValue : <#= fldObj #>->OldValue;
        if (EmptyValue($val)) {
            return "";
        } else {
            $keys[] = $val;
        }
    <#
        } // KeyField
    #>
        return implode(Config("COMPOSITE_KEY_SEPARATOR"), $keys);
    }

    // Set Key
    public function setKey($key, $current = false)
    {
        $this->OldKey = strval($key);
        $keys = explode(Config("COMPOSITE_KEY_SEPARATOR"), $this->OldKey);
        if (count($keys) == <#= keyFields.length #>) {
    <#
        keyFields.forEach((kf, i) => {
            let fldName = kf.FldName,
            fldParm = kf.FldParm,
            fldObj = "$this->" + fldParm;
    #>
            if ($current) {
                <#= fldObj #>->CurrentValue = $keys[<#= i #>];
            } else {
                <#= fldObj #>->OldValue = $keys[<#= i #>];
            }
    <#
        }); // KeyField
    #>
        }
    }

    // Get record filter
    public function getRecordFilter($row = null)
    {
        $keyFilter = $this->sqlKeyFilter();
    <#
        for (let kf of keyFields) {
            let fldName = kf.FldName,
                fldParm = kf.FldParm,
                fldObj = "$this->" + fldParm;
    #>
        if (is_array($row)) {
            $val = array_key_exists('<#= Quote(fldName) #>', $row) ? $row['<#= Quote(fldName) #>'] : null;
        } else {
            $val = <#= fldObj #>->OldValue !== null ? <#= fldObj #>->OldValue : <#= fldObj #>->CurrentValue;
        }
    <#
            if (GetFieldType(kf.FldType) == 1) { // Numeric
    #>
        if (!is_numeric($val)) {
            return "0=1"; // Invalid key
        }
    <#
            }
            let prefix = "", suffix = "";
            if (GetFieldType(kf.FldType) == 2) { // Date
                prefix = "UnFormatDateTime(";
                suffix = ", " + kf.FldDtFormat + ")";
            }
    #>
        if ($val === null) {
            return "0=1"; // Invalid key
        } else {
            $keyFilter = str_replace("@<#= fldParm #>@", AdjustSql(<#= prefix #>$val<#= suffix #>, $this->Dbid), $keyFilter); // Replace key value
        }
    <#
        } // KeyField
    #>
        return $keyFilter;
    }

    // Return page URL
    public function getReturnUrl()
    {
        $referUrl = ReferUrl();
        $referPageName = ReferPageName();
        $name = PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_RETURN_URL");
        // Get referer URL automatically
        if ($referUrl != "" && $referPageName != CurrentPageName() && $referPageName != "<#= loginPage #>") { // Referer not same page or login page
            $_SESSION[$name] = $referUrl; // Save to Session
        }
        return $_SESSION[$name] ?? GetUrl("<#= listPage #>");
    }

    // Set return page URL
    public function setReturnUrl($v)
    {
        $_SESSION[PROJECT_NAME . "_" . $this->TableVar . "_" . Config("TABLE_RETURN_URL")] = $v;
    }

    // Get modal caption
    public function getModalCaption($pageName)
    {
        global $Language;
        if ($pageName == "<#= viewPage #>") {
            return $Language->phrase("View");
        } elseif ($pageName == "<#= editPage #>") {
            return $Language->phrase("Edit");
        } elseif ($pageName == "<#= addPage #>") {
            return $Language->phrase("Add");
        } else {
            return "";
        }
    }

    // API page name
    public function getApiPageName($action)
    {
        switch (strtolower($action)) {
            case Config("API_VIEW_ACTION"):
                return "<#= viewPageName #>";
            case Config("API_ADD_ACTION"):
                return "<#= addPageName #>";
            case Config("API_EDIT_ACTION"):
                return "<#= editPageName #>";
            case Config("API_DELETE_ACTION"):
                return "<#= deletePageName #>";
            case Config("API_LIST_ACTION"):
                return "<#= listPageName #>";
            default:
                return "";
        }
    }

    // List URL
    public function getListUrl()
    {
        return "<#= listPage #>";
    }

    // View URL
    public function getViewUrl($parm = "")
    {
        if ($parm != "") {
            $url = $this->keyUrl("<#= viewPage #>", $this->getUrlParm($parm));
        } else {
            $url = $this->keyUrl("<#= viewPage #>", $this->getUrlParm(Config("TABLE_SHOW_DETAIL") . "="));
        }
        return $this->addMasterUrl($url);
    }

    // Add URL
    public function getAddUrl($parm = "")
    {
        if ($parm != "") {
            $url = "<#= addPage #>?" . $this->getUrlParm($parm);
        } else {
            $url = "<#= addPage #>";
        }
        return $this->addMasterUrl($url);
    }

    // Edit URL
    public function getEditUrl($parm = "")
    {
    <# if (isDetailEdit && detailTables.length > 0) { #>
        if ($parm != "") {
            $url = $this->keyUrl("<#= editPage #>", $this->getUrlParm($parm));
        } else {
            $url = $this->keyUrl("<#= editPage #>", $this->getUrlParm(Config("TABLE_SHOW_DETAIL") . "="));
        }
    <# } else { #>
        $url = $this->keyUrl("<#= editPage #>", $this->getUrlParm($parm));
    <# } #>
        return $this->addMasterUrl($url);
    }

    // Inline edit URL
    public function getInlineEditUrl()
    {
        $url = $this->keyUrl(CurrentPageName(), $this->getUrlParm("action=edit"));
        return $this->addMasterUrl($url);
    }

    // Copy URL
    public function getCopyUrl($parm = "")
    {
    <# if (isDetailAdd && detailTables.length > 0) { #>
        if ($parm != "") {
            $url = $this->keyUrl("<#= addPage #>", $this->getUrlParm($parm));
        } else {
            $url = $this->keyUrl("<#= addPage #>", $this->getUrlParm(Config("TABLE_SHOW_DETAIL") . "="));
        }
    <# } else { #>
        $url = $this->keyUrl("<#= addPage #>", $this->getUrlParm($parm));
    <# } #>
        return $this->addMasterUrl($url);
    }

    // Inline copy URL
    public function getInlineCopyUrl()
    {
        $url = $this->keyUrl(CurrentPageName(), $this->getUrlParm("action=copy"));
        return $this->addMasterUrl($url);
    }

    // Delete URL
    public function getDeleteUrl()
    {
        return $this->keyUrl("<#= deletePage #>", $this->getUrlParm());
    }

    // Add master url
    public function addMasterUrl($url)
    {
    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
    #>
        if ($this->getCurrentMasterTable() == "<#= masterTblVar #>" && !ContainsString($url, Config("TABLE_SHOW_MASTER") . "=")) {
            $url .= (ContainsString($url, "?") ? "&" : "?") . Config("TABLE_SHOW_MASTER") . "=" . $this->getCurrentMasterTable();
    <#
            for (let rel of md.Relations) {
                let masterField = GetFieldObject(masterTable, rel.MasterField),
                    masterFldParm = masterField.FldParm,
                    detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFldParm = detailField.FldParm,
                    suffix = "";
                if (GetFieldType(detailField.FldType) == 2) { // Date
                    suffix = ", " + detailField.FldDtFormat;
                }
    #>
            $url .= "&" . GetForeignKeyUrl("fk_<#= Quote(masterFldParm) #>", $this-><#= detailFldParm #>->CurrentValue ?? $this-><#= detailFldParm #>->getSessionValue()<#= suffix #>);
    <#
            } // MasterDetailField
    #>
        }
    <#
        } // MasterDetail
    #>
        return $url;
    }

    public function keyToJson($htmlEncode = false)
    {
        $json = "";
<#
    keyFields.forEach((f, i) => {
        let	fldParm = f.FldParm,
            prefix = (i == 0) ? "" : ",";
        if (f.FldHtmlTag == "FILE" && !IsBinaryField(f)) { // Upload to folder
#>
        $json .= "<#= prefix #><#= fldParm #>:" . JsonEncode($this-><#= fldParm #>->Upload->DbValue, "<#= GetFieldJsonTypeName(f.FldType) #>");
<#
        } else {
#>
        $json .= "<#= prefix #><#= fldParm #>:" . JsonEncode($this-><#= fldParm #>->CurrentValue, "<#= GetFieldJsonTypeName(f.FldType) #>");
<#
        }
    }); // KeyField
#>
        $json = "{" . $json . "}";
        if ($htmlEncode) {
            $json = HtmlEncode($json);
        }
        return $json;
    }

    // Add key value to URL
    public function keyUrl($url, $parm = "")
    {
<#
    keyFields.forEach((f, i) => {
//		fldKeyVal = fldParm + "->CurrentValue";
//		if (GetFieldType(f.FldType) != 1)
//			fldKeyVal = "urlencode(" + fldKeyVal + ")";
        let fldParm = f.FldParm,
            concat = i > 0 ? "&" : "";
        if (f.FldHtmlTag == "FILE" && !IsBinaryField(f)) { // Upload to folder (P501)
#>
        if (!EmptyValue($this-><#= fldParm #>->Upload->DbValue)) {
            $url .= "/" . rawurlencode($this-><#= fldParm #>->Upload->DbValue);
<#
        } else {
#>
        if ($this-><#= fldParm #>->CurrentValue !== null) {
            $url .= "/" . rawurlencode($this-><#= fldParm #>->CurrentValue);
<#
        }
#>
        } else {
            return "javascript:ew.alert(ew.language.phrase('InvalidRecord'));";
        }
<#
    }); // KeyField
#>
        if ($parm != "") {
            $url .= "?" . $parm;
        }
        return $url;
    }

    // Render sort
    public function renderSort($fld)
    {
        $classId = $fld->TableVar . "_" . $fld->Param;
        $scriptId = str_replace("%id%", $classId, "<#= CustomScriptId("%id%", "header") #>");
        $scriptStart = $this->UseCustomTemplate ? "<template id=\"" . $scriptId . "\">" : "";
        $scriptEnd = $this->UseCustomTemplate ? "</template>" : "";
    <# if (sortType == 0) { #>
        $jsSort = "";
    <# } else { #>
        $jsSort = " class=\"ew-pointer\" onclick=\"ew.sort(event, '" . $this->sortUrl($fld) . "', <#= sortType #>);\"";
    <# } #>
        if ($this->sortUrl($fld) == "") {
            $html = <<<NOSORTHTML
{$scriptStart}<div class="ew-table-header-caption">{$fld->caption()}</div>{$scriptEnd}
NOSORTHTML;
        } else {
            if ($fld->getSort() == "ASC") {
                $sortIcon = '<i class="fas fa-sort-up"></i>';
            } elseif ($fld->getSort() == "DESC") {
                $sortIcon = '<i class="fas fa-sort-down"></i>';
            } else {
                $sortIcon = '';
            }
            $html = <<<SORTHTML
{$scriptStart}<div{$jsSort}><div class="ew-table-header-btn"><span class="ew-table-header-caption">{$fld->caption()}</span><span class="ew-table-header-sort">{$sortIcon}</span></div></div>{$scriptEnd}
SORTHTML;
        }
        return $html;
    }

    // Sort URL
    public function sortUrl($fld)
    {
        <# if (TABLE.TblType == "REPORT" && ["summary", "crosstab"].includes(TABLE.TblReportType)) { #>
        global $DashboardReport;
        <# } #>
<# if (sortType == 0) { #>
        return "";
<# } else { #>
        if (
            $this->CurrentAction || $this->isExport() ||
        <# if (TABLE.TblType == "REPORT" && ["summary", "crosstab"].includes(TABLE.TblReportType)) { #>
            $this->DrillDown || $DashboardReport ||
        <# } #>
        <# if (tblIsMySql || tblIsPostgreSql) { #>
            in_array($fld->Type, [128, 204, 205])
        ) { // Unsortable data type
        <# } else { #>
            in_array($fld->Type, [141, 201, 203, 128, 204, 205])
        ) { // Unsortable data type
        <# } #>
                return "";
        } elseif ($fld->Sortable) {
            $urlParm = $this->getUrlParm("order=" . urlencode($fld->Name) . "&amp;ordertype=" . $fld->getNextSort());
            return $this->addMasterUrl(CurrentPageName() . "?" . $urlParm);
        } else {
            return "";
        }
<# } #>
    }

    // Get record keys from Post/Get/Session
    public function getRecordKeys()
    {
        $arKeys = [];
        $arKey = [];
        if (Param("key_m") !== null) {
            $arKeys = Param("key_m");
            $cnt = count($arKeys);
    <# if (keyFields.length > 1) { #>
            for ($i = 0; $i < $cnt; $i++) {
                $arKeys[$i] = explode(Config("COMPOSITE_KEY_SEPARATOR"), $arKeys[$i]);
            }
    <# } #>
        } else {
    <#
        keyFields.forEach((f, i) => {
            let fldParm = f.FldParm;
    #>
    <# if (keyFields.length > 1) { #>
            if (($keyValue = Param("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                $arKey[] = $keyValue;
            } elseif (IsApi() && (($keyValue = Key(<#= i #>) ?? Route(<#= i + 2 #>)) !== null)) {
                $arKey[] = $keyValue;
            } else {
                $arKeys = null; // Do not setup
            }
    <# } else { #>
            if (($keyValue = Param("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                $arKeys[] = $keyValue;
            } elseif (IsApi() && (($keyValue = Key(<#= i #>) ?? Route(<#= i + 2 #>)) !== null)) {
                $arKeys[] = $keyValue;
            } else {
                $arKeys = null; // Do not setup
            }
    <# } #>
    <#
        }); // KeyField
        if (keyFields.length > 1) {
    #>
            if (is_array($arKeys)) {
                $arKeys[] = $arKey;
            }
    <#
        }
    #>
            //return $arKeys; // Do not return yet, so the values will also be checked by the following code
        }
        // Check keys
        $ar = [];
        if (is_array($arKeys)) {
            foreach ($arKeys as $key) {
    <# if (keyFields.length > 1) { #>
                if (!is_array($key) || count($key) != <#= keyFields.length #>) {
                    continue; // Just skip so other keys will still work
                }
    <# } #>
    <#
        keyFields.forEach((f, i) => {
            let isNumericKey = (GetFieldType(f.FldType) == 1);
            if (isNumericKey) {
    #>
    <# if (keyFields.length > 1) { #>
                if (!is_numeric($key[<#= i #>])) { // <#= f.FldName #>
    <# } else { #>
                if (!is_numeric($key)) {
    <# } #>
                    continue;
                }
    <#
            }
        }); // KeyField
    #>
                $ar[] = $key;
            }
        }
        return $ar;
    }

    // Get filter from record keys
    public function getFilterFromRecordKeys($setCurrent = true)
    {
        $arKeys = $this->getRecordKeys();
        $keyFilter = "";
        foreach ($arKeys as $key) {
            if ($keyFilter != "") {
                $keyFilter .= " OR ";
            }
    <#
        keyFields.forEach((f, i) => {
            let fldParm = f.FldParm,
                keyStr = keyFields.length > 1 ? "$key[" + i + "]" : "$key";
    #>
            if ($setCurrent) {
                $this-><#= fldParm #>->CurrentValue = <#= keyStr #>;
            } else {
                $this-><#= fldParm #>->OldValue = <#= keyStr #>;
            }
    <#
        }); // KeyField
    #>
            $keyFilter .= "(" . $this->getRecordFilter() . ")";
        }
        return $keyFilter;
    }

    // Load recordset based on filter
    public function &loadRs($filter)
    {
        $sql = $this->getSql($filter); // Set up filter (WHERE Clause)
        $conn = $this->getConnection();
        $stmt = $conn->executeQuery($sql);
        return $stmt;
    }

    <# if (TABLE.TblType != "REPORT") { #>

    // Load row values from record
    public function loadListRowValues(&$rs)
    {
        if (is_array($rs)) {
            $row = $rs;
        } elseif ($rs && property_exists($rs, "fields")) { // Recordset
            $row = $rs->fields;
        } else {
            return;
        }
    <#
        for (let f of allFields) {
            let fldParm = f.FldParm, fldName = f.FldName;
            if (GetFieldType(f.FldType) == 4) { // Boolean Fields
    #>
        $this-><#= fldParm #>->setDbValue(ConvertToBool($row['<#= SingleQuote(fldName) #>']) ? "1" : "0");
    <#
            } else if (f.FldHtmlTag == "FILE") {
    #>
        $this-><#= fldParm #>->Upload->DbValue = $row['<#= SingleQuote(fldName) #>'];
        <# if (!IsBinaryField(f)) { #>
        $this-><#= fldParm #>->setDbValue($this-><#= fldParm #>->Upload->DbValue);
        <# } else { #>
        if (is_resource($this-><#= fldParm #>->Upload->DbValue) && get_resource_type($this-><#= fldParm #>->Upload->DbValue) == "stream") { // Byte array
            $this-><#= fldParm #>->Upload->DbValue = stream_get_contents($this-><#= fldParm #>->Upload->DbValue);
        }
        <# } #>
    <#
            } else {
    #>
        $this-><#= fldParm #>->setDbValue($row['<#= SingleQuote(fldName) #>']);
    <#
            }
        } // AllField
    #>
    }

    // Render list row values
    public function renderListRow()
    {
        global $Security, $CurrentLanguage, $Language;

    <# if (TABLE.TblType != "REPORT" && ServerScriptExist("Table", "Row_Rendering")) { #>
        // Call Row Rendering event
        $this->rowRendering();
    <# } #>

        // Common render codes
    <#
        for (let f of allFields) {
            FIELD = f;
    #>
        // <#= f.FldName #>
        <#= ScriptCommon() #>
    <#
        } // AllField
    #>

    <#
        for (let f of allFields) {
            FIELD = f;
    #>
        // <#= f.FldName #>
        <#= ScriptView() #>
    <#
        } // AllField
    #>

    <#
        for (let f of allFields) {
            FIELD = f;
    #>
        // <#= f.FldName #>
        <#= ScriptViewRefer() #>
    <#
        } // AllField
    #>

    <# if (TABLE.TblType != "REPORT" && ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row Rendered event
        $this->rowRendered();
    <# } #>

    <# if (TABLE.TblType != "REPORT") { #>
        // Save data for Custom Template
        $this->Rows[] = $this->customTemplateFieldValues();
    <# } #>

    }

    // Render edit row values
    public function renderEditRow()
    {
        global $Security, $CurrentLanguage, $Language;

    <# if (TABLE.TblType != "REPORT" && ServerScriptExist("Table", "Row_Rendering")) { #>
        // Call Row Rendering event
        $this->rowRendering();
    <# } #>

    <#
        for (let f of allFields) {
            FIELD = f;
    #>
        // <#= f.FldName #>
        <#= ScriptEdit() #>
    <#
        } // AllField
    #>

    <# if (TABLE.TblType != "REPORT" && ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row Rendered event
        $this->rowRendered();
    <# } #>

    }

    // Aggregate list row values
    public function aggregateListRowValues()
    {
    <#
        for (let f of allFields) {
            let fldObj = "$this->" + f.FldParm;
            if (f.FldAggregate == "COUNT" || f.FldAggregate == "AVERAGE") {
    #>
            <#= fldObj #>->Count++; // Increment count
    <#
            }
            if (f.FldAggregate == "AVERAGE" || f.FldAggregate == "TOTAL") {
    #>
            if (is_numeric(<#= fldObj #>->CurrentValue)) {
                <#= fldObj #>->Total += <#= fldObj #>->CurrentValue; // Accumulate total
            }
    <#
            }
        } // AllField
    #>
    }

    // Aggregate list row (for rendering)
    public function aggregateListRow()
    {

    <#
        for (let f of allFields) {
            if (!IsEmpty(f.FldAggregate)) {
                FIELD = f;
    #>
            <#= ScriptAggregate() #>
    <#
            }
        } //AllField
    #>

    <# if (TABLE.TblType != "REPORT" && ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row Rendered event
        $this->rowRendered();
    <# } #>

    }

    // Export data in HTML/CSV/Word/Excel/Email/PDF format
    public function exportDocument($doc, $recordset, $startRec = 1, $stopRec = 1, $exportPageType = "")
    {
        if (!$recordset || !$doc) {
            return;
        }

        if (!$doc->ExportCustom) {

            // Write header
            $doc->exportTableHeader();
            if ($doc->Horizontal) { // Horizontal format, write header
                $doc->beginExportRow();

                if ($exportPageType == "view") {

    <#
        for (let f of allFields) {
            if (f.FldView) {
                let fldObj = "$this->" + f.FldParm;
    #>
                    $doc->exportCaption(<#= fldObj #>);
    <#
            }
        } // AllField
    #>

                } else {

    <#
        for (let f of allFields) {
            if (f.FldExport) {
                let fldObj = "$this->" + f.FldParm;
    #>
                    $doc->exportCaption(<#= fldObj #>);
    <#
            }
        } // AllField
    #>

                }

                $doc->endExportRow();

            }

        }

        // Move to first record
        $recCnt = $startRec - 1;
        $stopRec = ($stopRec > 0) ? $stopRec : PHP_INT_MAX;

        while (!$recordset->EOF && $recCnt < $stopRec) {
            $row = $recordset->fields;
            $recCnt++;
            if ($recCnt >= $startRec) {
                $rowCnt = $recCnt - $startRec + 1;

                // Page break
                if ($this->ExportPageBreakCount > 0) {
                    if ($rowCnt > 1 && ($rowCnt - 1) % $this->ExportPageBreakCount == 0) {
                        $doc->exportPageBreak();
                    }
                }

                $this->loadListRowValues($row);

        <# if (IsAggregate()) { #>
                $this->aggregateListRowValues(); // Aggregate row values
        <# } #>

                // Render row
                $this->RowType = ROWTYPE_VIEW; // Render view
                $this->resetAttributes();
                $this->renderListRow();

                if (!$doc->ExportCustom) {

                    $doc->beginExportRow($rowCnt); // Allow CSS styles if enabled

                    if ($exportPageType == "view") {

    <#
        for (let f of allFields) {
            if (f.FldView) {
                let fldObj = "$this->" + f.FldParm;
    #>
                        $doc->exportField(<#= fldObj #>);
    <#
            }
        } // AllField
    #>

                    } else {

    <#
        for (let f of allFields) {
            if (f.FldExport) {
                let fldObj = "$this->" + f.FldParm;
    #>
                        $doc->exportField(<#= fldObj #>);
    <#
            }
        } // AllField
    #>

                    }

                    $doc->endExportRow($rowCnt);
                }
            }

    <# if (ServerScriptExist(eventCtrlType, "Row_Export")) { #>
            // Call Row Export server event
            if ($doc->ExportCustom) {
                $this->rowExport($row);
            }
    <# } #>

            $recordset->moveNext();
        }

        <# if (IsAggregate()) { #>
        // Export aggregates (horizontal format only)
        if ($doc->Horizontal) {
            $this->RowType = ROWTYPE_AGGREGATE;
            $this->resetAttributes();
            $this->aggregateListRow();
            if (!$doc->ExportCustom) {
                $doc->beginExportRow(-1);
    <#
        for (let f of allFields) {
            if (f.FldExport) {
                let fldObj = "$this->" + f.FldParm;
    #>
                $doc->exportAggregate(<#= fldObj #>, '<#= SingleQuote(f.FldAggregate) #>');
    <#
            }
        } // AllField
    #>
                $doc->endExportRow();
            }
        }
        <# } #>

        if (!$doc->ExportCustom) {
            $doc->exportTableFooter();
        }

    }

    <# } #>

<#
    if (hasUserIdFld) {
        let userIdField = GetFieldObject(TABLE, TABLE.TblUserIDFld),
            userIdFld = FieldSqlName(userIdField, tblDbId);
#>

    <#
        if (TABLE.TblName == secTable.TblName) {

            userIdField = GetFieldObject(TABLE, DB.SecuUserIDFld);
            userIdFld = FieldSqlName(userIdField, tblDbId);
            let userIdFldDataType = GetFieldTypeName(userIdField.FldType),
                parentUserIdFld = "";

            if (hasParentUserId) {
                let parentUserIdField = GetFieldObject(TABLE, DB.SecuParentUserIDFld);
                parentUserIdFld = FieldSqlName(parentUserIdField, tblDbId);
            }

            let fromPart = secTable.TblType == "CUSTOMVIEW" ? SqlPart(secTable.TblCustomSQL, "FROM") : SqlTableName(secTable, tblDbId);
    #>

    // User ID filter
    public function getUserIDFilter($userId)
    {
        $userIdFilter = '<#= SingleQuote(userIdFld) #> = ' . QuotedValue($userId, <#= userIdFldDataType #>, Config("USER_TABLE_DBID"));
    <# if (hasParentUserId) { #>
        $parentUserIdFilter = '<#= SingleQuote(userIdFld) #> IN (SELECT <#= SingleQuote(userIdFld) #> FROM ' . "<#= Code.quote(fromPart) #>" . ' WHERE <#= SingleQuote(parentUserIdFld) #> = ' . QuotedValue($userId, <#= userIdFldDataType #>, Config("USER_TABLE_DBID")) . ')';
        $userIdFilter = "($userIdFilter) OR ($parentUserIdFilter)";
    <# } #>
        return $userIdFilter;
    }

    <#
        }
    #>

    // Add User ID filter
    public function addUserIDFilter($filter = "")
    {
        global $Security;
        $filterWrk = "";
        $id = (CurrentPageID() == "list") ? $this->CurrentAction : CurrentPageID();
        if (!$this->userIDAllow($id) && !$Security->isAdmin()) {
            $filterWrk = $Security->userIdList();
            if ($filterWrk != "") {
                $filterWrk = '<#= SingleQuote(userIdFld) #> IN (' . $filterWrk . ')';
            }
        }

    <# if (ServerScriptExist("Table", "UserID_Filtering")) { #>
        // Call User ID Filtering event
        $this->userIdFiltering($filterWrk);
    <# } #>

        AddFilter($filter, $filterWrk);
        return $filter;
    }

    <#
        if (hasParentUserId && PROJ.SecTbl == TABLE.TblName) { // User table with Parent User ID
    #>

    // Add Parent User ID filter
    public function addParentUserIDFilter($userId)
    {
        global $Security;
        if (!$Security->isAdmin()) {
            $result = $Security->parentUserIDList($userId);
            if ($result != "") {
                $result = '<#= SingleQuote(userIdFld) #> IN (' . $result . ')';
            }
            return $result;
        }
        return "";
    }

    <#
        }
    #>

    // User ID subquery
    public function getUserIDSubquery(&$fld, &$masterfld)
    {
        global $UserTable;
        $wrk = "";
        $sql = "SELECT " . $masterfld->Expression . " FROM <#= Code.quote(fromPart) #>";
        $filter = $this->addUserIDFilter("");
        if ($filter != "") {
            $sql .= " WHERE " . $filter;
        }

        // List all values
        if ($rs = Conn($UserTable->Dbid)->executeQuery($sql)->fetchAll(\PDO::FETCH_NUM)) {
            foreach ($rs as $row) {
                if ($wrk != "") {
                    $wrk .= ",";
                }
                $wrk .= QuotedValue($row[0], $masterfld->DataType, Config("USER_TABLE_DBID"));
            }
        }

        if ($wrk != "") {
            $wrk = $fld->Expression . " IN (" . $wrk . ")";
        } else { // No User ID value found
            $wrk = "0=1";
        }
        return $wrk;
    }
<#
    }
#>

<#
    if (masterTableHasUserIdFld) {
#>

    // Add master User ID filter
    public function addMasterUserIDFilter($filter, $currentMasterTable)
    {
        $filterWrk = $filter;
    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
            if (hasUserId && !IsEmpty(masterTable.TblUserIDFld)) {
                if (masterTblVar == TABLE.TblVar) { // Check if master = detail
    #>
        if ($currentMasterTable == "<#= masterTblVar #>" && $this->getCurrentMasterTable() != "") {
    <#
                } else {
    #>
        if ($currentMasterTable == "<#= masterTblVar #>") {
    <#
                }
    #>
            $filterWrk = Container("<#= masterTblVar #>")->addUserIDFilter($filterWrk);
        }
    <#
            }
        } // MasterDetail
    #>
        return $filterWrk;
    }

    // Add detail User ID filter
    public function addDetailUserIDFilter($filter, $currentMasterTable)
    {
        $filterWrk = $filter;
    <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
            if (hasUserId && !IsEmpty(masterTable.TblUserIDFld)) {
                if (masterTblVar == TABLE.TblVar) { // Check if master = detail
    #>
        if ($currentMasterTable == "<#= masterTblVar #>" && $this->getCurrentMasterTable() != "") {
    <#
                } else {
    #>
        if ($currentMasterTable == "<#= masterTblVar #>") {
    <#
                }
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(masterTable, rel.MasterField),
                        masterFldParm = masterField.FldParm,
                        detailField = GetFieldObject(TABLE, rel.DetailField),
                        detailFldParm = detailField.FldParm;
    #>
            $mastertable = Container("<#= masterTblVar #>");
            if (!$mastertable->userIdAllow()) {
                $subqueryWrk = $mastertable->getUserIDSubquery($this-><#= detailFldParm #>, $mastertable-><#= masterFldParm #>);
                AddFilter($filterWrk, $subqueryWrk);
            }
    <#
                } // MasterDetailField
    #>
        }
    <#
            }
        } // MasterDetail
    #>
        return $filterWrk;
    }
<#
    }
#>

    <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl && PROJ.SecRegisterEmail && !IsEmpty(PROJ.SecEmailFld)) { #>
    // Send register email
    public function sendRegisterEmail($row)
    {

    <# if (hasUserProfile && MultiLanguage) { #>
        // Get user language
        global $UserProfile;
        $userName = GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $row);
        $langId = $UserProfile->getLanguageId($userName);
        $email = $this->prepareRegisterEmail($row, $langId);
    <# } else { #>
        $email = $this->prepareRegisterEmail($row);
    <# } #>

    <# if (ServerScriptExist("Table", "Email_Sending")) { #>
        $args = [];
        $args["rs"] = $row;
        $emailSent = false;
        if ($this->emailSending($email, $args)) { // Use Email_Sending server event of user table
            $emailSent = $email->send();
        }
    <# } else { #>
        $emailSent = $email->send();
    <# } #>
        return $emailSent;
    }

    // Prepare register email
    public function prepareRegisterEmail($row = null, $langId = "")
    {
        global $CurrentForm;
        $email = new Email();
        $email->load(Config("EMAIL_REGISTER_TEMPLATE"), $langId);
    <#
        let emailField = GetFieldObject(TABLE, PROJ.SecEmailFld),
            emailFldName = emailField.FldName,
            emailFldParm = emailField.FldParm,
            emailFldObj = "$this->" + emailFldParm;
    #>
        $receiverEmail = $row === null ? <#= emailFldObj #>->CurrentValue : GetUserInfo(Config("USER_EMAIL_FIELD_NAME"), $row);
        if ($receiverEmail == "") { // Send to recipient directly
            $receiverEmail = Config("RECIPIENT_EMAIL");
            $bccEmail = "";
        } else { // Bcc recipient
            $bccEmail = Config("RECIPIENT_EMAIL");
        }
        $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
        $email->replaceRecipient($receiverEmail); // Replace Recipient
        if ($bccEmail != "") // Add Bcc
            $email->addBcc($bccEmail);
    <#
        for (let f of allFields) {
            if (f.FldRegister) {
                let fldName = f.FldName,
                    fldParm = f.FldParm;
    #>
        $email->replaceContent('<!--FieldCaption_<#= SingleQuote(fldName) #>-->', $this-><#= fldParm #>->caption());
        $email->replaceContent('<!--<#= SingleQuote(fldName) #>-->', $row === null ? strval($this-><#= fldParm #>->FormValue) : GetUserInfo('<#= SingleQuote(fldName) #>', $row));
    <#
            }
        } // Field

        if (PROJ.SecRegisterActivate) {
            let loginField = GetFieldObject(TABLE, PROJ.SecLoginIDFld),
                loginIdFldName = loginField.FldName,
                loginIdFldParm = loginField.FldParm,
                loginIdFldObj = "$this->" + loginIdFldParm,
                passwordField = GetFieldObject(TABLE, PROJ.SecPasswdFld),
                passwordFldName = passwordField.FldName,
                passwordFldParm = passwordField.FldParm,
                passwordFldVar = passwordField.FldVar,
                passwordFldObj = "$this->" + passwordFldParm;
    #>
        $loginID = $row === null ? <#= loginIdFldObj #>->CurrentValue : GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $row);
        $password = $row === null ? ($CurrentForm->hasValue("<#= passwordFldName #>") ? $CurrentForm->getValue("<#= passwordFldName #>") : $CurrentForm->getValue("<#= passwordFldVar #>")) : GetUserInfo(Config("LOGIN_PASSWORD_FIELD_NAME"), $row); // Use raw password post value
        $activateLink = FullUrl("<#= registerPage #>", "activate")
            . "?action=confirm&user=" . rawurlencode($loginID) . "&activatetoken=" . Encrypt($receiverEmail) . "," . Encrypt($loginID) . "," . Encrypt($password);
        $email->replaceContent("<!--ActivateLink-->", $activateLink);
        $email->Content = preg_replace('/<!--\s*register_activate_link[\s\S]*?-->/i', '', $email->Content); // Remove comments
    <#
        } else {
    #>
        $email->Content = preg_replace('/<!--\s*register_activate_link_begin[\s\S]*?-->[\s\S]*?<!--\s*register_activate_link_end[\s\S]*?-->/i', '', $email->Content); // Remove activate link block
    <#
        }
    #>
        return $email;
    }
    <# } #>


    <#
        hasFileField = allFields.some(f => f.FldHtmlTag == "FILE");
    #>

    // Get file data
    public function getFileData($fldparm, $key, $resize, $width = 0, $height = 0, $plugins = [])
    {

    <# if (hasFileField) { #>
        $width = ($width > 0) ? $width : Config("THUMBNAIL_DEFAULT_WIDTH");
        $height = ($height > 0) ? $height : Config("THUMBNAIL_DEFAULT_HEIGHT");

        // Set up field name / file name field / file type field
        $fldName = "";
        $fileNameFld = "";
        $fileTypeFld = "";
    <#
        let cond = "if";
        for (let f of allFields) {
            if (f.FldHtmlTag == "FILE") {
                let fldName = f.FldName,
                    fldParm = f.FldParm;
    #>
        <#= cond #> ($fldparm == '<#= SingleQuote(fldParm) #>') {
            $fldName = "<#= Quote(fldName) #>";
        <# if (!IsEmpty(f.FileNameFld)) { #>
            $fileNameFld = "<#= Quote(f.FileNameFld) #>";
        <# } #>
        <# if (!IsEmpty(f.FileTypeFld)) { #>
            $fileTypeFld = "<#= Quote(f.FileTypeFld) #>";
        <# } #>
    <#
                cond = "} elseif";
            }
        } // Field
    #>
        } else {
            return false; // Incorrect field
        }

        // Set up key values
        $ar = explode(Config("COMPOSITE_KEY_SEPARATOR"), $key);
        if (count($ar) == <#= keyFields.length #>) {
    <#
        keyFields.forEach((kf, i) => {
            let fldParm = kf.FldParm;
    #>
            $this-><#= fldParm #>->CurrentValue = $ar[<#= i #>];
    <#
        }); // KeyField
    #>
        } else {
            return false; // Incorrect key
        }

        // Set up filter (WHERE Clause)
        $filter = $this->getRecordFilter();
<# if (ctrlType != "report") { #>
        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
<# } else { #>
        $sql = $this->buildReportSql($this->getSqlSelect(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $filter, "");
<# } #>
        $conn = $this->getConnection();
        $dbtype = GetConnectionType($this->Dbid);

        if ($row = $conn->fetchAssoc($sql)) {
            $val = $row[$fldName];

            if (!EmptyValue($val)) {
                $fld = $this->Fields[$fldName];

                // Binary data
                if ($fld->DataType == DATATYPE_BLOB) {

                    if ($dbtype != "MYSQL") {
                        if (is_resource($val) && get_resource_type($val) == "stream") { // Byte array
                            $val = stream_get_contents($val);
                        }
                    }

                    if ($resize) {
                        ResizeBinary($val, $width, $height, 100, $plugins);
                    }

                    // Write file type
                    if ($fileTypeFld != "" && !EmptyValue($row[$fileTypeFld])) {
                        AddHeader("Content-type", $row[$fileTypeFld]);
                    } else {
                        AddHeader("Content-type", ContentType($val));
                    }

                    // Write file name
                    $downloadPdf = !Config("EMBED_PDF") && Config("DOWNLOAD_PDF_FILE");
                    if ($fileNameFld != "" && !EmptyValue($row[$fileNameFld])) {
                        $fileName = $row[$fileNameFld];
                        $pathinfo = pathinfo($fileName);
                        $ext = strtolower(@$pathinfo["extension"]);
                        $isPdf = SameText($ext, "pdf");
                        if ($downloadPdf || !$isPdf) { // Skip header if not download PDF
                            AddHeader("Content-Disposition", "attachment; filename=\"" . $fileName . "\"");
                        }
                    } else {
                        $ext = ContentExtension($val);
                        $isPdf = SameText($ext, ".pdf");
                        if ($isPdf && $downloadPdf) { // Add header if download PDF
                            AddHeader("Content-Disposition", "attachment; filename=\"" . $fileName . "\"");
                        }
                    }

                    // Write file data
                    if (
                        StartsString("PK", $val) &&
                        ContainsString($val, "[Content_Types].xml") &&
                        ContainsString($val, "_rels") &&
                        ContainsString($val, "docProps")
                    ) { // Fix Office 2007 documents
                        if (!EndsString("\0\0\0", $val)) { // Not ends with 3 or 4 \0
                            $val .= "\0\0\0\0";
                        }
                    }

                    // Clear any debug message
                    if (ob_get_length()) {
                        ob_end_clean();
                    }

                    // Write binary data
                    Write($val);

                // Upload to folder
                } else {

                    if ($fld->UploadMultiple) {
                        $files = explode(Config("MULTIPLE_UPLOAD_SEPARATOR"), $val);
                    } else {
                        $files = [$val];
                    }
                    $data = [];
                    $ar = [];
                    foreach ($files as $file) {
                        if (!EmptyValue($file)) {
                            if (Config("ENCRYPT_FILE_PATH")) {
                                $ar[$file] = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                    "/" . $this->TableVar . "/" . Encrypt($fld->physicalUploadPath() . $file)));
                            } else {
                                $ar[$file] = FullUrl($fld->hrefPath() . $file);
                            }
                        }
                    }
                    $data[$fld->Param] = $ar;

                    WriteJson($data);

                }

            }
            return true;
        }

        return false;

    <# } else { #>

        // No binary fields
        return false;

    <# } #>

    }

<# if (TABLE.TblAuditTrail) { #>

    // Write Audit Trail start/end for grid update
    public function writeAuditTrailDummy($typ)
    {
        $table = '<#= SingleQuote(TABLE.TblName) #>';

    <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
    <# } else { #>
        $usr = CurrentUserName();
    <# } #>

        WriteAuditLog($usr, $typ, $table, "", "", "", "");
    }

    <# if (auditTrailOnAdd) { #>

    // Write Audit Trail (add page)
    public function writeAuditTrailOnAdd(&$rs)
    {
        global $Language;

        if (!$this->AuditTrailOnAdd) {
            return;
        }

        $table = '<#= SingleQuote(TABLE.TblName) #>';

        // Get key value
        $key = "";
        <#
            for (let kf of keyFields) {
                let fldName = kf.FldName;
        #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rs['<#= SingleQuote(fldName) #>'];
        <#
            } // KeyField
        #>

        // Write Audit Trail
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>

        foreach (array_keys($rs) as $fldname) {
            if (array_key_exists($fldname, $this->Fields) && $this->Fields[$fldname]->DataType != DATATYPE_BLOB) { // Ignore BLOB fields
                if ($this->Fields[$fldname]->HtmlTag == "PASSWORD") {
                    $newvalue = $Language->phrase("PasswordMask"); // Password Field
                } elseif ($this->Fields[$fldname]->DataType == DATATYPE_MEMO) {
                    if (Config("AUDIT_TRAIL_TO_DATABASE")) {
                        $newvalue = $rs[$fldname];
                    } else {
                        $newvalue = "[MEMO]"; // Memo Field
                    }
                } elseif ($this->Fields[$fldname]->DataType == DATATYPE_XML) {
                    $newvalue = "[XML]"; // XML Field
                } else {
                    $newvalue = $rs[$fldname];
                }
                <# if (TABLE.TblName == PROJ.SecTbl) { #>
                if ($fldname == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                    $newvalue = $Language->phrase("PasswordMask");
                }
                <# } #>
                WriteAuditLog($usr, "A", $table, $fldname, $key, "", $newvalue);
            }
        }
    }

    <# } #>

    <# if (auditTrailOnEdit) { #>

    // Write Audit Trail (edit page)
    public function writeAuditTrailOnEdit(&$rsold, &$rsnew)
    {
        global $Language;

        if (!$this->AuditTrailOnEdit) {
            return;
        }

        $table = '<#= SingleQuote(TABLE.TblName) #>';

        // Get key value
        $key = "";
        <#
            for (let kf of keyFields) {
                let fldName = kf.FldName;
        #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rsold['<#= SingleQuote(fldName) #>'];
        <#
            } // KeyField
        #>

        // Write Audit Trail
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>

        foreach (array_keys($rsnew) as $fldname) {
            if (array_key_exists($fldname, $this->Fields) && array_key_exists($fldname, $rsold) && $this->Fields[$fldname]->DataType != DATATYPE_BLOB) { // Ignore BLOB fields
                if ($this->Fields[$fldname]->DataType == DATATYPE_DATE) { // DateTime field
                    $modified = (FormatDateTime($rsold[$fldname], 0) != FormatDateTime($rsnew[$fldname], 0));
                } else {
                    $modified = !CompareValue($rsold[$fldname], $rsnew[$fldname]);
                }
                if ($modified) {
                    if ($this->Fields[$fldname]->HtmlTag == "PASSWORD") { // Password Field
                        $oldvalue = $Language->phrase("PasswordMask");
                        $newvalue = $Language->phrase("PasswordMask");
                    } elseif ($this->Fields[$fldname]->DataType == DATATYPE_MEMO) { // Memo field
                        if (Config("AUDIT_TRAIL_TO_DATABASE")) {
                            $oldvalue = $rsold[$fldname];
                            $newvalue = $rsnew[$fldname];
                        } else {
                            $oldvalue = "[MEMO]";
                            $newvalue = "[MEMO]";
                        }
                    } elseif ($this->Fields[$fldname]->DataType == DATATYPE_XML) { // XML field
                        $oldvalue = "[XML]";
                        $newvalue = "[XML]";
                    } else {
                        $oldvalue = $rsold[$fldname];
                        $newvalue = $rsnew[$fldname];
                    }
                    <# if (TABLE.TblName == PROJ.SecTbl) { #>
                    if ($fldname == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                        $oldvalue = $Language->phrase("PasswordMask");
                        $newvalue = $Language->phrase("PasswordMask");
                    }
                    <# } #>
                    WriteAuditLog($usr, "U", $table, $fldname, $key, $oldvalue, $newvalue);
                }
            }
        }
    }

    <# } #>

    <# if (auditTrailOnDelete) { #>

    // Write Audit Trail (delete page)
    public function writeAuditTrailOnDelete(&$rs)
    {
        global $Language;

        if (!$this->AuditTrailOnDelete) {
            return;
        }

        $table = '<#= SingleQuote(TABLE.TblName) #>';

        // Get key value
        $key = "";
        <#
            for (let kf of keyFields) {
                let fldName = kf.FldName;
        #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rs['<#= SingleQuote(fldName) #>'];
        <#
            } // KeyField
        #>

        // Write Audit Trail
        <# if (useUserIdForAuditTrail) { #>
        $curUser = CurrentUserID();
        <# } else { #>
        $curUser = CurrentUserName();
        <# } #>

        foreach (array_keys($rs) as $fldname) {
            if (array_key_exists($fldname, $this->Fields) && $this->Fields[$fldname]->DataType != DATATYPE_BLOB) { // Ignore BLOB fields
                if ($this->Fields[$fldname]->HtmlTag == "PASSWORD") {
                    $oldvalue = $Language->phrase("PasswordMask"); // Password Field
                } elseif ($this->Fields[$fldname]->DataType == DATATYPE_MEMO) {
                    if (Config("AUDIT_TRAIL_TO_DATABASE")) {
                        $oldvalue = $rs[$fldname];
                    } else {
                        $oldvalue = "[MEMO]"; // Memo field
                    }
                } elseif ($this->Fields[$fldname]->DataType == DATATYPE_XML) {
                    $oldvalue = "[XML]"; // XML field
                } else {
                    $oldvalue = $rs[$fldname];
                }
                <# if (TABLE.TblName == PROJ.SecTbl) { #>
                if ($fldname == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                    $oldvalue = $Language->phrase("PasswordMask");
                }
                <# } #>
                WriteAuditLog($curUser, "D", $table, $fldname, $key, $oldvalue, "");
            }
        }
    }

    <# } #>

    <# if (auditTrailOnView) { #>

    // Write Audit Trail (view page)
    public function writeAuditTrailOnView(&$rs)
    {
        global $Language;

        if (!$this->AuditTrailOnView) {
            return;
        }

        $table = '<#= SingleQuote(TABLE.TblName) #>';

        // Get key value
        $key = "";
        <#
            for (let kf of keyFields) {
                let fldName = kf.FldName;
        #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rs['<#= SingleQuote(fldName) #>'];
        <#
            } // KeyField
        #>

        // Write Audit Trail
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>

        if ($this->AuditTrailOnViewData) { // Write all data
            foreach (array_keys($rs) as $fldname) {
                if (array_key_exists($fldname, $this->Fields) && $this->Fields[$fldname]->DataType != DATATYPE_BLOB) { // Ignore BLOB fields
                    if ($this->Fields[$fldname]->HtmlTag == "PASSWORD") {
                        $oldvalue = $Language->phrase("PasswordMask"); // Password Field
                    } elseif ($this->Fields[$fldname]->DataType == DATATYPE_MEMO) {
                        if (Config("AUDIT_TRAIL_TO_DATABASE")) {
                            $oldvalue = $rs[$fldname];
                        } else {
                            $oldvalue = "[MEMO]"; // Memo Field
                        }
                    } elseif ($this->Fields[$fldname]->DataType == DATATYPE_XML) {
                        $oldvalue = "[XML]"; // XML Field
                    } else {
                        $oldvalue = $rs[$fldname];
                    }
                    <# if (TABLE.TblName == PROJ.SecTbl) { #>
                    if ($fldname == Config("LOGIN_PASSWORD_FIELD_NAME")) {
                        $oldvalue = $Language->phrase("PasswordMask");
                    }
                    <# } #>
                    WriteAuditLog($usr, "V", $table, $fldname, $key, $oldvalue, "");
                }
            }
        } else { // Write record id only
            WriteAuditLog($usr, "V", $table, "", $key, "", "");
        }
    }

    <# } #>

    <# if (auditTrailOnSearch) { #>

    // Write Audit Trail (search)
    public function writeAuditTrailOnSearch($searchparm, $searchsql)
    {
        global $Language;

        if (!$this->AuditTrailOnSearch) {
            return;
        }

        $table = '<#= SingleQuote(TABLE.TblName) #>';

        // Write Audit Trail
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>

        WriteAuditLog($usr, "search", $table, "", "", $searchsql, $searchparm);

    }

    <# } #>

<# } #>

<# if (TABLE.TblSendMailOnAdd) { #>

    // Send email after add success
    public function sendEmailOnAdd(&$rs)
    {
        global $Language;

        $table = '<#= SingleQuote(TABLE.TblName) #>';
        $subject = $table . " " . $Language->phrase("RecordInserted");
        $action = $Language->phrase("ActionInserted");

        // Get key value
        $key = "";
    <#
        for (let kf of keyFields) {
            let fldName = kf.FldName;
    #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rs['<#= SingleQuote(fldName) #>'];
    <#
        } // KeyField
    #>

        $email = new Email();
        $email->load(Config("EMAIL_NOTIFY_TEMPLATE"));
        $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
        $email->replaceRecipient(Config("RECIPIENT_EMAIL")); // Replace Recipient
        $email->replaceSubject($subject); // Replace Subject
        $email->replaceContent("<!--table-->", $table);
        $email->replaceContent("<!--key-->", $key);
        $email->replaceContent("<!--action-->", $action);

    <# if (ServerScriptExist("Table", "Email_Sending")) { #>
        $args = ["rsnew" => $rs];
        $emailSent = false;
        if ($this->emailSending($email, $args)) {
            $emailSent = $email->send();
        }
    <# } else { #>
        $emailSent = $email->send();
    <# } #>

        // Send email failed
        if (!$emailSent) {
            $this->setFailureMessage($email->SendErrDescription);
        }

    }

<# } #>

<# if (TABLE.TblSendMailOnEdit) { #>

    // Send email after update success
    public function sendEmailOnEdit(&$rsold, &$rsnew)
    {
        global $Language;

        $table = '<#= SingleQuote(TABLE.TblName) #>';
        $subject = $table . " ". $Language->phrase("RecordUpdated");
        $action = $Language->phrase("ActionUpdated");

        // Get key value
        $key = "";
    <#
        for (let kf of keyFields) {
            let fldName = kf.FldName;
    #>
        if ($key != "") {
            $key .= Config("COMPOSITE_KEY_SEPARATOR");
        }
        $key .= $rsold['<#= SingleQuote(fldName) #>'];
    <#
        } // KeyField
    #>

        $email = new Email();
        $email->load(Config("EMAIL_NOTIFY_TEMPLATE"));
        $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
        $email->replaceRecipient(Config("RECIPIENT_EMAIL")); // Replace Recipient
        $email->replaceSubject($subject); // Replace Subject
        $email->replaceContent("<!--table-->", $table);
        $email->replaceContent("<!--key-->", $key);
        $email->replaceContent("<!--action-->", $action);

    <# if (ServerScriptExist("Table", "Email_Sending")) { #>
        $args = [];
        $args["rsold"] = &$rsold;
        $args["rsnew"] = &$rsnew;
        $emailSent = false;
        if ($this->emailSending($email, $args)) {
            $emailSent = $email->send();
        }
    <# } else { #>
        $emailSent = $email->send();
    <# } #>

        // Send email failed
        if (!$emailSent) {
            $this->setFailureMessage($email->SendErrDescription);
        }

    }

<# } #>

<# if (TABLE.TblType != "REPORT") { #>
    // Table level events
    <#= GetServerScript("Table", "Recordset_Selecting") #>
    <#= GetServerScript("Table", "Recordset_Selected") #>
    <#= GetServerScript("Table", "Recordset_SearchValidated") #>
    <#= GetServerScript("Table", "Recordset_Searching") #>
    <#= GetServerScript("Table", "Row_Selecting") #>
    <#= GetServerScript("Table", "Row_Selected") #>
    <#= GetServerScript("Table", "Row_Inserting") #>
    <#= GetServerScript("Table", "Row_Inserted") #>
    <#= GetServerScript("Table", "Row_Updating") #>
    <#= GetServerScript("Table", "Row_Updated") #>
    <#= GetServerScript("Table", "Row_UpdateConflict") #>
    <#= GetServerScript("Table", "Grid_Inserting") #>
    <#= GetServerScript("Table", "Grid_Inserted") #>
    <#= GetServerScript("Table", "Grid_Updating") #>
    <#= GetServerScript("Table", "Grid_Updated") #>
    <#= GetServerScript("Table", "Row_Deleting") #>
    <#= GetServerScript("Table", "Row_Deleted") #>
<# } #>

    <#= GetServerScript("Table", "Email_Sending") #>
    <#= GetServerScript("Table", "Lookup_Selecting") #>
    <#= GetServerScript("Table", "Row_Rendering") #>
    <#= GetServerScript("Table", "Row_Rendered") #>
    <#= GetServerScript("Table", "UserID_Filtering") #>

}
