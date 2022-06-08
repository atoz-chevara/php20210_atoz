<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Common report config #>
<#= include('shared/config-report.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    // Options
    public $HideOptions = <#= Code.bool(!showReport && !showCharts) #>;
    public $ExportOptions; // Export options
    public $SearchOptions; // Search options
    public $FilterOptions; // Filter options

    // Records
    public $GroupRecords = [];
    public $DetailRecords = [];
    public $DetailRecordCount = 0;

    // Paging variables
    public $RecordIndex = 0; // Record index
    public $RecordCount = 0; // Record count (start from 1 for each group)
    public $StartGroup = 0; // Start group
    public $StopGroup = 0; // Stop group
    public $TotalGroups = 0; // Total groups
    public $GroupCount = 0; // Group count
    public $DisplayGroups = <#= groupPerPage #>; // Groups per page
    public $GroupRange = 10;
    public $PageSizes = "<#= groupPerPageList #>"; // Page sizes (comma separated)

    public $DefaultSearchWhere = ""; // Default search WHERE clause
    public $SearchWhere = "";
    public $SearchPanelClass = "ew-search-panel collapse<#= PROJ.SearchPanelCollapsed ? "" : " show" #>"; // Search Panel class
    public $SearchRowCount = 0; // For extended search
    public $SearchColumnCount = 0; // For extended search
    public $SearchFieldsPerRow = <#= extSearchFldPerRow #>; // For extended search
    public $PageFirstGroupFilter = "";
    public $UserIDFilter = "";
    public $DrillDownList = "";

    public $DbMasterFilter = ""; // Master filter
    public $DbDetailFilter = ""; // Detail filter
    public $SearchCommand = false;

    public $ShowHeader;
    public $GroupColumnCount = 0;

    public $ColumnSpan;

    public $TopContentClass = "<#= reportTopContainerClass #>";
    public $LeftContentClass = "<#= reportLeftContainerClass #>";
    public $CenterContentClass = "<#= reportCenterContainerClass #>";
    public $RightContentClass = "<#= reportRightContainerClass #>";
    public $BottomContentClass = "<#= reportBottomContainerClass #>";

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $ExportFileName, $Language, $Security, $UserProfile,
            $Security, $DrillDownInPanel, $Breadcrumb,
            $DashboardReport, $CustomExportType, $ReportExportType;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

    <# if (hasUserIdFld) { #>
        // Set up User ID
        $filter = "";
        $filter = $this->applyUserIDFilters($filter);
        $this->UserIDFilter = $filter;
        $this->Filter = $this->UserIDFilter;
    <# } #>

    <# if (parmFields.length > 0) { #>
        // Handle drill down
        $drillDownFilter = $this->getDrillDownFilter();
        $DrillDownInPanel = $this->DrillDownInPanel;
        if ($this->DrillDown) {
            AddFilter($this->Filter, $drillDownFilter);
        }
    <# } #>

    <# if (!IsEmpty(groupPerPageList)) { #>
        // Set up groups per page dynamically
        $this->setupDisplayGroups();
    <# } #>

        // Set up Breadcrumb
        if (!$this->isExport() && !$DashboardReport) {
            $this->setupBreadcrumb();
        }

        // Get sort
        $this->Sort = $this->getSort();

    <# if (dynamicSortCharts.length) { #>
        // Get chart sort
        $this->getChartSort();
    <# } #>

    <# if (useExtendedBasicSearch) { #>
        // Check if search command
        $this->SearchCommand = (Get("cmd", "") == "search");
    <# } #>

    <# if ((useExtendedBasicSearch || showYearSelection) && hasUserTable && !IsEmpty(DB.SecUserProfileFld)) { #>
        // Process filter list
        if ($this->processFilterList()) {
            $this->terminate();
            return;
        }
    <# } #>

    <# if (ServerScriptExist("Table", "Page_FilterLoad")) { #>
        // Load custom filters
        $this->pageFilterLoad();
    <# } #>

    <# if (useExtendedBasicSearch || showYearSelection) { #>
        // Restore filter list
        $this->restoreFilterList();
    <# } #>

        // Extended filter
        $extendedFilter = "";


    <# if (showYearSelection) { #>
    <#
        let fldExpr = FieldSqlName(columnField, tblDbId),
            dateExpr = DbGroupSql("y", 0, tblDbId).replace(/%s/g, fldExpr);
    #>
        // Add year filter
        $year = $this->getYearSelection();
        if ($year != "") {
            AddFilter($this->SearchWhere, "<#= Quote(dateExpr) #> = " . $year);
        }
    <# } #>

    <# if (useExtendedBasicSearch) { #>

        // Build extended filter
        $extendedFilter = $this->getExtendedFilter();
        AddFilter($this->SearchWhere, $extendedFilter);

    <# } #>

    <# if (!useExtendedBasicSearch && !showYearSelection) { #>
        // No filter
        $this->FilterOptions["savecurrentfilter"]->Visible = false;
        $this->FilterOptions["deletefilter"]->Visible = false;
    <# } #>

    <# if (ServerScriptExist("Table", "Page_Selecting")) { #>
        // Call Page Selecting event
        $this->pageSelecting($this->SearchWhere);
    <# } #>

        // Load columns to array
        $this->getColumns();

    <# if (TABLE.TblShowBlankListPage) { #>
        // Requires search criteria
        if (($this->SearchWhere == "") && !$this->DrillDown) {
            $this->SearchWhere = "0=101";
        }
    <# } #>

        // Search options
        $this->setupSearchOptions();

        // Set up search panel class
        if ($this->SearchWhere != "") {
            AppendClass($this->SearchPanelClass, "show");
        }

        // Update filter
        AddFilter($this->Filter, $this->SearchWhere);

    <# if (masterTables.length > 0) { #>
        // Set up master detail parameters
        $this->setupMasterParms();

        // Reset master/detail keys
        if (SameText(Get("cmd"), "resetall")) {
            $this->setCurrentMasterTable(""); // Clear master table
            $this->DbMasterFilter = "";
            $this->DbDetailFilter = "";
        <#
            for (let md of masterTables) {
                for (let rel of md.Relations) {
                    let detailField = GetFieldObject(TABLE, rel.DetailField),
                        detailFldParm = detailField.FldParm;
        #>
            $this-><#= detailFldParm #>->setSessionValue("");
        <#
                }
            } // MasterDetail
        #>
        }

        // Add detail filter
        AddFilter($this->Filter, $this->DbDetailFilter);
    <# } #>

        // Get total group count
        $sql = $this->buildReportSql($this->getSqlSelectGroup(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), "", "", $this->Filter, "");
        $this->TotalGroups = $this->getRecordCount($sql);

        if ($this->DisplayGroups <= 0 || $this->DrillDown || $DashboardReport) { // Display all groups
            $this->DisplayGroups = $this->TotalGroups;
        }
        $this->StartGroup = 1;

        // Show header
        $this->ShowHeader = ($this->TotalGroups > 0);

        // Set up start position if not export all
        if ($this->ExportAll && $this->isExport()) {
            $this->DisplayGroups = $this->TotalGroups;
        } else {
            $this->setupStartGroup();
        }

        // Set no record found message
        if ($this->TotalGroups == 0) {
            <# if (isUserLevel) { #>
            if ($Security->canList()) {
            <# } #>
                if ($this->SearchWhere == "0=101") {
                    $this->setWarningMessage($Language->phrase("EnterSearchCriteria"));
                } else {
                    $this->setWarningMessage($Language->phrase("NoRecord"));
                }
            <# if (isUserLevel) { #>
            } else {
                $this->setWarningMessage(DeniedMessage());
            }
             <# } #>
        }

        // Hide export options if export/dashboard report/hide options
        if ($this->isExport() || $DashboardReport || $this->HideOptions) {
            $this->ExportOptions->hideAllOptions();
        }

        // Hide search/filter options if export/drilldown/dashboard report/hide options
        if ($this->isExport() || $this->DrillDown || $DashboardReport || $this->HideOptions) {
            $this->SearchOptions->hideAllOptions();
            $this->FilterOptions->hideAllOptions();
        }

        // Get group records
        if ($this->TotalGroups > 0) {
            $grpSort = UpdateSortFields($this->getSqlOrderByGroup(), $this->Sort, 2); // Get grouping field only
            $sql = $this->buildReportSql($this->getSqlSelectGroup(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), "", $this->getSqlOrderByGroup(), $this->Filter, $grpSort);
            $grpRs = $sql->setFirstResult($this->StartGroup - 1)->setMaxResults($this->DisplayGroups)->execute();
            $this->GroupRecords = $grpRs->fetchAll(); // Get records of first groups
            $this->loadGroupRowValues();
            $this->GroupCount = 1;
        }

        // Init detail records
        $this->DetailRecords = [];

        // Set up column attributes
        $this-><#= columnFieldParm #>->CssClass = "<#= Quote(FieldViewClass(columnField)) #>";
        $this-><#= columnFieldParm #>->CellCssStyle = "<#= Quote(FieldStyle(columnField)) #>";

        $this->setupFieldCount();

        // Set the last group to display if not export all
        if ($this->ExportAll && $this->isExport()) {
            $this->StopGroup = $this->TotalGroups;
        } else {
            $this->StopGroup = $this->StartGroup + $this->DisplayGroups - 1;
        }

        // Stop group <= total number of groups
        if (intval($this->StopGroup) > intval($this->TotalGroups)) {
            $this->StopGroup = $this->TotalGroups;
        }

        // Navigate
        $this->RecordCount = 0;
        $this->RecordIndex = 0;

        // Set up pager
        $this->Pager = new <#= pagerClass #>($this->StartGroup, $this->getGroupPerPage(), $this->TotalGroups, $this->PageSizes, $this->GroupRange, $this->AutoHidePager, $this->AutoHidePageSizeSelector);

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Load group row values
    public function loadGroupRowValues()
    {
        $cnt = count($this->GroupRecords); // Get record count
        if ($this->GroupCount < $cnt) {
            $this-><#= firstGroupField.FldParm #>->setGroupValue($this->GroupRecords[$this->GroupCount][0]);
        } else {
            $this-><#= firstGroupField.FldParm #>->setGroupValue("");
        }
    }

    // Load row values
    public function loadRowValues($record)
    {
    <#
        for (let grpFld of groupFields) {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
    #>
        $<#= fldObj #>->setDbValue($record['<#= SingleQuote(fldName) #>']);
    <#
        }
    #>

        $cntbase = <#= summaryFieldIndex #>;
        $cnt = count($this->SummaryFields);
        for ($is = 0; $is < $cnt; $is++) {
            $smry = &$this->SummaryFields[$is];
            $cntval = count($smry->SummaryValues);
            for ($ix = 1; $ix < $cntval; $ix++) {
                if ($smry->SummaryType == "AVG") {
                    $smry->SummaryValues[$ix] = $record[$ix * 2 + $cntbase - 2];
                    $smry->SummaryValueCounts[$ix] = $record[$ix * 2 + $cntbase - 1];
                } else {
                    $smry->SummaryValues[$ix] = $record[$ix + $cntbase - 1];
                }
            }
            $cntbase += ($smry->SummaryType == "AVG") ? 2 * ($cntval - 1) : ($cntval - 1);
        }

    }

    // Get summary values from records
    public function getSummaryValues($records)
    {
        $colcnt = $this->ColumnCount;
        $cnt = count($this->SummaryFields);
        for ($is = 0; $is < $cnt; $is++) {
            $smry = &$this->SummaryFields[$is];
            $smry->SummaryGroupValues = InitArray($colcnt, null);
            $smry->SummaryGroupValueCounts = InitArray($colcnt, null);
        }
        foreach ($records as $record) {
            $cntbase = <#= summaryFieldIndex #>;
            for ($is = 0; $is < $cnt; $is++) {
                $smry = &$this->SummaryFields[$is];
                $cntval = count($smry->SummaryValues);
                for ($ix = 1; $ix < $cntval; $ix++) {
                    if ($smry->SummaryType == "AVG") {
                        $thisval = $record[$ix * 2 + $cntbase - 2];
                        $thiscnt = $record[$ix * 2 + $cntbase - 1];
                    } else {
                        $thisval = $record[$ix + $cntbase - 1];
                    }
                    $smry->SummaryGroupValues[$ix - 1] = SummaryValue($smry->SummaryGroupValues[$ix - 1], $thisval, $smry->SummaryType);
                    if ($smry->SummaryType == "AVG") {
                        $smry->SummaryGroupValueCounts[$ix - 1] += $thiscnt;
                    }

                }
                $cntbase += ($smry->SummaryType == "AVG") ? 2 * ($cntval - 1) : ($cntval - 1);
            }
        }
    }

    // Render row
    public function renderRow()
    {
        global $Security, $Language;

        $conn = $this->getConnection();

        // Set up summary values
        if ($this->RowType != ROWTYPE_SEARCH) { // Skip for search row

    <# if (TABLE.TblRowSum) { #>
            $colcnt = $this->ColumnCount + 1;
    <# } else { #>
            $colcnt = $this->ColumnCount;
    <# } #>

            $this->SummaryCellAttrs = InitArray($colcnt, null);
            $this->SummaryViewAttrs = InitArray($colcnt, null);
            $this->SummaryLinkAttrs = InitArray($colcnt, null);
            $this->SummaryCurrentValues = InitArray($colcnt, null);
            $this->SummaryViewValues = InitArray($colcnt, null);

            $cnt = count($this->SummaryFields);
            for ($is = 0; $is < $cnt; $is++) {
                $smry = &$this->SummaryFields[$is];
                $smry->SummaryViewAttrs = InitArray($colcnt, null);
                $smry->SummaryLinkAttrs = InitArray($colcnt, null);
                $smry->SummaryCurrentValues = InitArray($colcnt, null);
                $smry->SummaryViewValues = InitArray($colcnt, null);
    <# if (TABLE.TblRowSum) { #>
                $smry->SummaryRowSummary = $smry->SummaryInitValue;
                $smry->SummaryRowCount = 0;
    <# } #>
            }
        }


        if ($this->RowTotalType == ROWTOTAL_PAGE) { // Page total

            // Aggregate SQL (filter by group values)
            $firstGrpFld = &$this-><#= firstGroupField.FldParm #>;
            $firstGrpFld->getDistinctValues($this->GroupRecords);
            $where = DetailFilterSql($firstGrpFld, $this->getSqlFirstGroupField(), $firstGrpFld->DistinctValues, $this->Dbid);
            if ($this->Filter != "") {
                $where = "($this->Filter) AND ($where)";
            }
            $qb = $this->buildReportSql($this->getSqlSelectAggregate()->addSelect($this->DistinctColumnFields), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupByAggregate(), "", "", $where, "");
            $rsagg = $qb->execute()->fetch(\PDO::FETCH_NUM);

        } else if ($this->RowTotalType == ROWTOTAL_GRAND) { // Grand total

            // Aggregate SQL
            $qb = $this->buildReportSql($this->getSqlSelectAggregate()->addSelect($this->DistinctColumnFields), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupByAggregate(), "", "", $this->Filter, "");
            $rsagg = $qb->execute()->fetch(\PDO::FETCH_NUM);

        }

        if ($this->RowType != ROWTYPE_SEARCH) { // Skip for search row
            for ($i = 1; $i <= $this->ColumnCount; $i++) {
                if ($this->Columns[$i]->Visible) {

                    $cntbaseagg = <#= summaryFieldIndexAgg #>;
                    $cnt = count($this->SummaryFields);
                    for ($is = 0; $is < $cnt; $is++) {
                        $smry = &$this->SummaryFields[$is];
                        if ($this->RowType == ROWTYPE_DETAIL) { // Detail row
                            $thisval = $smry->SummaryValues[$i];
                            if ($smry->SummaryType == "AVG") {
                                $thiscnt = $smry->SummaryValueCounts[$i];
                            }
                        } elseif ($this->RowTotalType == ROWTOTAL_GROUP) { // Group total
                            $thisval = $smry->SummaryGroupValues[$i - 1];
                            if ($smry->SummaryType == "AVG") {
                                $thiscnt = $smry->SummaryGroupValueCounts[$i - 1];
                            }
                        } elseif ($this->RowTotalType == ROWTOTAL_PAGE || $this->RowTotalType == ROWTOTAL_GRAND) { // Page Total / Grand total
                            if ($smry->SummaryType == "AVG") {
                                $thisval = $rsagg[$i * 2 + $cntbaseagg - 2] ?? 0;
                                $thiscnt = $rsagg[$i * 2 + $cntbaseagg - 1] ?? 0;
                                $cntbaseagg += $this->ColumnCount * 2;
                            } else {
                                $thisval = $rsagg[$i + $cntbaseagg -1] ?? 0;
                                $cntbaseagg += $this->ColumnCount;
                            }
                        }
                        if ($smry->SummaryType == "AVG") {
                            $smry->SummaryCurrentValues[$i - 1] = ($thiscnt > 0) ? $thisval / $thiscnt : 0;
                        } else {
                            $smry->SummaryCurrentValues[$i - 1] = $thisval;
                        }
    <# if (TABLE.TblRowSum) { #>
                        $smry->SummaryRowSummary = SummaryValue($smry->SummaryRowSummary, $thisval, $smry->SummaryType);
                        if ($smry->SummaryType == "AVG") {
                            $smry->SummaryRowCount += $thiscnt;
                        }
    <# } #>
                    }

                }
            }
        }

    <# if (TABLE.TblRowSum) { #>
        if ($this->RowType != ROWTYPE_SEARCH) { // Skip for search row
            $cnt = count($this->SummaryFields);
            for ($is = 0; $is < $cnt; $is++) {
                $smry = &$this->SummaryFields[$is];
                if ($smry->SummaryType == "AVG") {
                    $smry->SummaryCurrentValues[$this->ColumnCount] = ($smry->SummaryRowCount > 0) ? $smry->SummaryRowSummary / $smry->SummaryRowCount : 0;
                } else {
                    $smry->SummaryCurrentValues[$this->ColumnCount] = $smry->SummaryRowSummary;
                }
            }
        }
    <# } #>

    <# if (ServerScriptExist("Table", "Row_Rendering")) { #>
        // Call Row_Rendering event
        $this->rowRendering();
    <# } #>

        if ($this->RowType == ROWTYPE_SEARCH) { // Search row

    <#
            for (let f of currentFields) {
                FIELD = f;
                if (f.FldExtendedBasicSearch) {
    #>
            // <#= f.FldName #>
            <#= ScriptSearch() #>
    <#
                    let isUserSelect = (f.FldSrchOpr == "USER SELECT" && GetFieldType(f.FldType) != 4);
                    if (IsTextFilter(f) && (f.FldSrchOpr == "BETWEEN" || isUserSelect || !IsEmpty(f.FldSrchOpr2))) {
    #>
            <#= ScriptSearch2() #>
    <#
                    }
                }
            }
    #>

        } elseif ($this->RowType == ROWTYPE_TOTAL) { // Summary row

    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
            if (fldName == columnDateFieldName) {
                let fldObj = "this->" + columnDateFieldParm;
    #>
            // <#= columnDateFieldName #>
            $<#= fldObj #>->GroupViewValue = $<#= fldObj #>->groupValue();
            $<#= fldObj #>->resetAttributes();
            $<#= fldObj #>->CssClass = "<#= Quote(FieldViewClass(columnField)) #>";
            $<#= fldObj #>->CellCssStyle = "<#= Quote(FieldStyle(columnField)) #>";
            $<#= fldObj #>->CellCssClass = ($this->RowGroupLevel == <#= i + 1 #>) ? "ew-rpt-grp-summary-<#= i + 1 #>" : "ew-rpt-grp-field-<#= i + 1 #>";
    <#
            } else {
                FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptGroupSummaryView() #>
    <#
            }
        });
    #>

    <#
        summaryFields.forEach((smryFld, j) => {
            let summaryField = GetFieldObject(TABLE, smryFld.name),
                fldObj = "this->" + summaryField.FldParm,
                smryFldObj = "this->SummaryFields[" + j + "]",
                fld = "$smry->SummaryCurrentValues[$i]",
                formatFld = ScriptViewFormat({ fld: summaryField, parm: fld });
            if (!IsEmpty(formatFld))
                fld = formatFld;
            let smryViewClass = FieldViewClass(summaryField),
                smryCellStyle = FieldStyle(summaryField);
    #>
            // Set up summary values
            $smry = &$<#= smryFldObj #>;
            $scvcnt = count($smry->SummaryCurrentValues);
            for ($i = 0; $i < $scvcnt; $i++) {
                $smry->SummaryViewValues[$i] = <#= fld #>;
                $smry->SummaryViewAttrs[$i]["class"] = "<#= Quote(smryViewClass) #>";
    <# if (!IsEmpty(smryCellStyle)) { #>
                $this->SummaryCellAttrs[$i]["style"] = "<#= Quote(smryCellStyle) #>";
    <# } #>
                $this->SummaryCellAttrs[$i]["class"] = ($this->RowTotalType == ROWTOTAL_GROUP) ? "ew-rpt-grp-summary-" . $this->RowGroupLevel : "";

    <# if (IsFieldDrillDown(summaryField)) { #>
    <# let checkDrillDownTable = isSecurityEnabled ? ` && AllowList(PROJECT_ID . $${smryFldObj}->DrillDownTable)` : ""; #>
                if (!$this->isExport()<#= checkDrillDownTable #>) {
                    $url = $<#= smryFldObj #>->DrillDownUrl;
    <#
            let drillTable = GetTableObject(summaryField.FldDrillTable),
                drillTblVar = drillTable.TblVar,
                drillSourceFields = summaryField.FldDrillSourceFields.trim().replace(/\|\|$/, ""),
                sourceFlds = drillSourceFields.split("||"),
                drillTargetFields = summaryField.FldDrillTargetFields.trim().replace(/\|\|$/, ""),
                targetFlds = drillTargetFields.split("||");
            if (sourceFlds.length == targetFlds.length) {
                let i = 0, mapSourceTarget = ArrayCombine(sourceFlds, targetFlds);
                for (let [sourceFld, targetFld] of mapSourceTarget.entries()) {
                    let sourceField = GetFieldObject(TABLE, sourceFld),
                        sourceFldParm = sourceField.FldParm,
                        sourceFldObj = (summaryField.FldParm == sourceFldParm) ? fldObj : "this->" + sourceFldParm,
                        columnParm = (sourceFldParm == columnFieldParm) ? ", $i + 1" : "",
                        targetField = GetFieldObject(drillTable, targetFld),
                        targetFldParm = targetField.FldParm;
                    if (sourceField.FldRowID > 1) {
    #>
                    $parm = ($this->RowTotalType == ROWTOTAL_GROUP && $this->RowGroupLevel >= <#= sourceField.FldRowID #>) ? 0 : -1;
                    $url = str_replace("=f<#= i #>", "=" . Encrypt($this->getDrillDownSql($<#= sourceFldObj #>, "<#= targetFldParm #>", $this->RowTotalType, $parm)), $url);
    <#
                    } else {
    #>
                    $url = str_replace("=f<#= i #>", "=" . Encrypt($this->getDrillDownSql($<#= sourceFldObj #>, "<#= targetFldParm #>", $this->RowTotalType<#= columnParm #>)), $url);
    <#
                    }
                    i++;
                } // End for mapSourceTarget
            }
    #>
                    $smry->SummaryLinkAttrs[$i]["title"] = JsEncodeAttribute($Language->phrase("ClickToDrillDown"));
                    $smry->SummaryLinkAttrs[$i]["class"] = "ew-drill-link";
                    $smry->SummaryLinkAttrs[$i]["onclick"] = DrillDownScript($url, '<#= tblVar #>_<#= sourceFldParm #>', $Language->tablePhrase('<#= drillTblVar #>', 'TblCaption'), $this->UseDrillDownPanel);
                    $smry->SummaryLinkAttrs[$i]["data-drilldown-placement"] = "bottom";
                }
    <# } // IsFieldDrillDown #>

            }

    <#
        }); // End for smryFld
    #>

    <#
        for (let grpFld of groupFields) {
            let fldName = grpFld.FldName;
            if (fldName != columnDateFieldName) {
                FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptSummaryViewRefer() #>
    <#
            }
        }
    #>

        } else {

    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
            if (fldName == columnDateFieldName) {
                fldObj = "this->" + columnDateFieldParm;
    #>
            // <#= columnDateFieldName #>
            $<#= fldObj #>->GroupViewValue = $<#= fldObj #>->groupValue();
            $<#= fldObj #>->resetAttributes();
            $<#= fldObj #>->CssClass = $this-><#= columnFieldParm #>->CssClass;
            $<#= fldObj #>->CellCssStyle = Concat($this-><#= columnFieldParm #>->CellCssStyle, "vertical-align: top", ";");
            $<#= fldObj #>->CellCssClass = "ew-rpt-grp-field-<#= i + 1 #>";
            if (!$<#= fldObj #>->LevelBreak) {
                $<#= fldObj #>->GroupViewValue = "&nbsp;";
            } else {
                $<#= fldObj #>->LevelBreak = false;
            }
    <#
            } else {
                FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptGroupView() #>
            if (!$<#= fldObj #>->LevelBreak) {
                $<#= fldObj #>->GroupViewValue = "&nbsp;";
            } else {
                $<#= fldObj #>->LevelBreak = false;
            }
    <#
            }
        });
    #>

    <#
        summaryFields.forEach((smryFld, j) => {
            let summaryField = GetFieldObject(TABLE, smryFld.name),
                fldObj = "this->" + summaryField.FldParm,
                smryFldObj = "this->SummaryFields[" + j + "]",
                fld = "$smry->SummaryCurrentValues[$i]",
                formatFld = ScriptViewFormat({ fld: summaryField, parm: fld });
            if (!IsEmpty(formatFld))
                fld = formatFld;
            let smryViewClass = FieldViewClass(summaryField),
                smryCellStyle = FieldStyle(summaryField);
    #>
            // Set up summary values
            $smry = &$<#= smryFldObj #>;
            $scvcnt = count($smry->SummaryCurrentValues);
            for ($i = 0; $i < $scvcnt; $i++) {
                $smry->SummaryViewValues[$i] = <#= fld #>;
                $smry->SummaryViewAttrs[$i]["class"] = "<#= Quote(smryViewClass) #>";
    <# if (!IsEmpty(smryCellStyle)) { #>
                $this->SummaryCellAttrs[$i]["style"] = "<#= Quote(smryCellStyle) #>";
    <# } #>
                $this->SummaryCellAttrs[$i]["class"] = ($this->RecordCount % 2 != 1) ? "ew-table-alt-row" : "ew-table-row";

    <# if (IsFieldDrillDown(summaryField)) { #>
    <# let checkDrillDownTable = isSecurityEnabled ? ` && AllowList(PROJECT_ID . $${fldObj}->DrillDownTable)` : ""; #>
                if (!$this->isExport()<#= checkDrillDownTable #>) {
                    $url = $<#= fldObj #>->DrillDownUrl;
    <#
            let drillTable = GetTableObject(summaryField.FldDrillTable),
                drillTblVar = drillTable.TblVar,
                drillSourceFields = summaryField.FldDrillSourceFields.trim().replace(/\|\|$/, ""),
                sourceFlds = drillSourceFields.split("||"),
                drillTargetFields = summaryField.FldDrillTargetFields.trim().replace(/\|\|$/, ""),
                targetFlds = drillTargetFields.split("||");
            if (sourceFlds.length == targetFlds.length) {
                let i = 0, mapSourceTarget = ArrayCombine(sourceFlds, targetFlds);
                for (let [sourceFld, targetFld] of mapSourceTarget.entries()) {
                    let sourceField = GetFieldObject(TABLE, sourceFld),
                        sourceFldParm = sourceField.FldParm,
                        sourceFldObj = (summaryField.FldParm == sourceFldParm) ? fldObj : "this->" + sourceFldParm,
                        columnParm = (sourceFldParm == columnFieldParm) ? ", $i + 1" : "",
                        targetField = GetFieldObject(drillTable, targetFld),
                        targetFldParm = targetField.FldParm;
    #>
                    $url = str_replace("=f<#= i #>", "=" . Encrypt($this->getDrillDownSql($<#= sourceFldObj #>, "<#= targetFldParm #>", 0<#= columnParm #>)), $url);
    <#
                    i++;
                } // End for mapSourceTarget
            }
    #>
                    $smry->SummaryLinkAttrs[$i]["title"] = JsEncodeAttribute($Language->phrase("ClickToDrillDown"));
                    $smry->SummaryLinkAttrs[$i]["class"] = "ew-drill-link";
                    $smry->SummaryLinkAttrs[$i]["onclick"] = DrillDownScript($url, '<#= tblVar #>_<#= sourceFldParm #>', $Language->tablePhrase('<#= drillTblVar #>', 'TblCaption'), $this->UseDrillDownPanel);
                    $smry->SummaryLinkAttrs[$i]["data-drilldown-placement"] = "bottom";
                }
    <# } // IsFieldDrillDown #>

            }

    <#
        }); // End for smryFld
    #>

    <#
        for (let grpFld of groupFields) {
            let fldName = grpFld.FldName;
            if (fldName != columnDateFieldName) {
                FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptViewRefer() #>
    <#
            }
        }
    #>

        }

        // Call Cell_Rendered event
    <# if (ServerScriptExist("Table", "Cell_Rendered")) { #>
        if ($this->RowType == ROWTYPE_TOTAL) { // Summary row
    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
            if (fldName == columnDateFieldName) {
                fldObj = "this->" + columnDateFieldParm;
    #>
            // <#= columnDateFieldName #>
            $this->CurrentIndex = <#= i #>; // Group Index
            $currentValue = $<#= fldObj #>->groupValue();
            $viewValue = &$<#= fldObj #>->GroupViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
            } else {
    #>
            // <#= fldName #>
            $this->CurrentIndex = <#= i #>; // Current index
            $currentValue = $<#= fldObj #>->groupValue();
            $viewValue = &$<#= fldObj #>->GroupViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
            }
        });
    #>

            // Call Cell_Rendered for Summary fields
            $cnt = count($this->SummaryFields);
            for ($is = 0; $is < $cnt; $is++) {
                $smry = &$this->SummaryFields[$is];
                $scvcnt = count($smry->SummaryCurrentValues);
                for ($i = 0; $i < $scvcnt; $i++) {
                    $this->CurrentIndex = $i;
                    $currentValue = $smry->SummaryCurrentValues[$i];
                    $viewValue = &$smry->SummaryViewValues[$i];
                    $viewAttrs = &$smry->SummaryViewAttrs[$i];
                    $cellAttrs = &$this->SummaryCellAttrs[$i];
                    $hrefValue = "";
                    $linkAttrs = &$smry->SummaryLinkAttrs[$i];
                    $this->cellRendered($smry, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
                }
            }

        } elseif ($this->RowType == ROWTYPE_DETAIL) { // Detail row

    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
    #>
            // <#= fldName #>
            $this->CurrentIndex = <#= i #>; // Group index
            $currentValue = $<#= fldObj #>->groupValue();
            $viewValue = &$<#= fldObj #>->GroupViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
        });
    #>

            $cnt = count($this->SummaryFields);
            for ($is = 0; $is < $cnt; $is++) {
                $smry = &$this->SummaryFields[$is];
                $scvcnt = count($smry->SummaryCurrentValues);
                for ($i = 0; $i < $scvcnt; $i++) {
                    $this->CurrentIndex = $i;
                    $currentValue = $smry->SummaryCurrentValues[$i];
                    $viewValue = &$smry->SummaryViewValues[$i];
                    $viewAttrs = &$smry->SummaryViewAttrs[$i];
                    $cellAttrs = &$this->SummaryCellAttrs[$i];
                    $hrefValue = "";
                    $linkAttrs = &$smry->SummaryLinkAttrs[$i];
                    $this->cellRendered($smry, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
                }

            }

        }
    <# } #>

    <# if (ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row_Rendered event
        $this->rowRendered();
    <# } #>

        $this->setupFieldCount();
    }

    // Setup field count
    protected function setupFieldCount()
    {
        $this->GroupColumnCount = 0;
    <#
        for (let grpFld of groupFields) {
            let fldObj = "this->" + grpFld.FldParm;
    #>
        if ($<#= fldObj #>->Visible) {
            $this->GroupColumnCount += 1;
        }
    <#
        }
    #>

    }

    // Get column values
    protected function getColumns()
    {
        global $Language;

        // Load column values
        $filter = "";
        AddFilter($filter, $this->Filter);
        AddFilter($filter, $this->SearchWhere);
        $this->loadColumnValues($filter);

        // Get active columns
        $this->ColumnSpan = $this->ColumnCount;

    <# if (TABLE.TblRowSum) { #>
        $this->ColumnSpan++; // Add summary column
    <# } #>

    }

    <# if (showYearSelection) { #>

    // Get year selection
    protected function getYearSelection()
    {
        // Process query string
        $year = "";
        if (Get("<#= columnDateFieldName #>") !== null) {
            $this-><#= columnDateFieldParm #>->setQueryStringValue(Get("<#= columnDateFieldName #>"));
            if (is_numeric($this-><#= columnDateFieldParm #>->QueryStringValue)) {
                $year = $this-><#= columnDateFieldParm #>->QueryStringValue;
                $this->resetPager();
            }
        }

        // Get distinct year
        $rsyear = $this->getConnection()->executeQuery($this->getSqlCrosstabYear())->fetchAll(\PDO::FETCH_NUM);
        foreach ($rsyear as $row) {
            if ($row[0] !== null) {
                $this-><#= columnDateFieldParm #>->DistinctValues[] = $row[0];
            }
        }

        // Restore from session
        if ($year == "" && $this-><#= columnDateFieldParm #>->AdvancedSearch->IssetSession()) {
            $this-><#= columnDateFieldParm #>->AdvancedSearch->load();
            $year = $this-><#= columnDateFieldParm #>->AdvancedSearch->SearchValue;
        }

        // Use first record
        if ($year == "" && count($this-><#= columnDateFieldParm #>->DistinctValues) > 0) {
            $year = $this-><#= columnDateFieldParm #>->DistinctValues[0];
        }
        $this-><#= columnDateFieldParm #>->CurrentValue = $year; // Save to CurrentValue
        $this-><#= columnDateFieldParm #>->AdvancedSearch->SearchValue = $year;
        $this-><#= columnDateFieldParm #>->AdvancedSearch->save(); // Save to session
        return $year;
    }

    <# } #>

<## Shared functions #>
<#= include('shared/shared-functions.php') #>
<#= include('shared/report-shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Table", "Form_CustomValidate") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
