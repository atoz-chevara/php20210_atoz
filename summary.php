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
    public $GroupCounter = []; // Group counter
    public $DisplayGroups = <#= groupPerPage #>; // Groups per page
    public $GroupRange = 10;
    public $PageSizes = "<#= groupPerPageList #>"; // Page sizes (comma separated)

<# if (showSummaryView) { #>
    public $LastGroupCount = 0; // Last group count
<# } #>

    public $PageFirstGroupFilter = "";
    public $UserIDFilter = "";
    public $DefaultSearchWhere = ""; // Default search WHERE clause
    public $SearchWhere = "";
    public $SearchPanelClass = "ew-search-panel collapse<#= PROJ.SearchPanelCollapsed ? "" : " show" #>"; // Search Panel class
    public $SearchRowCount = 0; // For extended search
    public $SearchColumnCount = 0; // For extended search
    public $SearchFieldsPerRow = <#= extSearchFldPerRow #>; // For extended search
    public $DrillDownList = "";

    public $DbMasterFilter = ""; // Master filter
    public $DbDetailFilter = ""; // Detail filter
    public $SearchCommand = false;

    public $ShowHeader;
    public $GroupColumnCount = 0;
    public $SubGroupColumnCount = 0;
    public $DetailColumnCount = 0;

    public $TotalCount;
    public $PageTotalCount;

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

        // Set field visibility for detail fields
    <#
        for (let dtlFld of detailFields) {
    #>
        $this-><#= dtlFld.FldParm #>->setVisibility();
    <#
        } // Field
    #>

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

    <# if (useExtendedBasicSearch) { #>
        // Check if search command
        $this->SearchCommand = (Get("cmd", "") == "search");
    <# } #>

    <# if (ServerScriptExist("Table", "Page_FilterLoad")) { #>
        // Load custom filters
        $this->pageFilterLoad();
    <# } #>

    <# if (useExtendedBasicSearch && hasUserTable && !IsEmpty(DB.SecUserProfileFld)) { #>
        // Process filter list
        if ($this->processFilterList()) {
            $this->terminate();
            return;
        }
    <# } #>

        // Extended filter
        $extendedFilter = "";

    <# if (useExtendedBasicSearch) { #>
        // Restore filter list
        $this->restoreFilterList();
    <# } #>

    <# if (useExtendedBasicSearch) { #>

        // Build extended filter
        $extendedFilter = $this->getExtendedFilter();
        AddFilter($this->SearchWhere, $extendedFilter);

    <# } #>

    <# if (!useExtendedBasicSearch) { #>
        // No filter
        $this->FilterOptions["savecurrentfilter"]->Visible = false;
        $this->FilterOptions["deletefilter"]->Visible = false;
    <# } #>

    <# if (ServerScriptExist("Table", "Page_Selecting")) { #>
        // Call Page Selecting event
        $this->pageSelecting($this->SearchWhere);
    <# } #>

    <# if (TABLE.TblShowBlankListPage) { #>
        // Requires search criteria
        if (($this->SearchWhere == "") && !$this->DrillDown) {
            $this->SearchWhere = "0=101";
        }
    <# } #>

        // Set up search panel class
        if ($this->SearchWhere != "") {
            AppendClass($this->SearchPanelClass, "show");
        }

        // Get sort
        $this->Sort = $this->getSort();

        // Search options
        $this->setupSearchOptions();

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

    <# if (dynamicSortCharts.length) { #>
        // Get chart sort
        $this->getChartSort();
    <# } #>

    <# if (groupFields.length) { #>
        // Get total group count
        $sql = $this->buildReportSql($this->getSqlSelectGroup(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, "");
        $this->TotalGroups = $this->getRecordCount($sql);
    <# } else { #>
        // Get total count
        $sql = $this->buildReportSql($this->getSqlSelect(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, "");
        $this->TotalGroups = $this->getRecordCount($sql);
    <# } #>

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

    <# if (groupFields.length > 0) { #>

        // Get group records
        if ($this->TotalGroups > 0) {
            $grpSort = UpdateSortFields($this->getSqlOrderByGroup(), $this->Sort, 2); // Get grouping field only
            $sql = $this->buildReportSql($this->getSqlSelectGroup(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), $this->getSqlOrderByGroup(), $this->Filter, $grpSort);
            $grpRs = $sql->setFirstResult($this->StartGroup - 1)->setMaxResults($this->DisplayGroups)->execute();
            $this->GroupRecords = $grpRs->fetchAll(); // Get records of first grouping field
            $this->loadGroupRowValues();
            $this->GroupCount = 1;
        }

        // Init detail records
        $this->DetailRecords = [];

    <# } else { #>

        // Get current page records
        if ($this->TotalGroups > 0) {
            $sql = $this->buildReportSql($this->getSqlSelect(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, $this->Sort);
            $rs = $sql->setFirstResult($this->StartGroup - 1)->setMaxResults($this->DisplayGroups)->execute();
            $this->DetailRecords = $rs->fetchAll(); // Get records
            $this->GroupCount = 1;
        }

    <# } #>

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

        $this->RecordCount = 0;
        $this->RecordIndex = 0;

    <# if (groupFields.length == 0) { #>
        $this->setGroupCount($this->StopGroup - $this->StartGroup + 1, 1);
    <# } #>

        // Set up pager
        $this->Pager = new <#= pagerClass #>($this->StartGroup, $this->getGroupPerPage(), $this->TotalGroups, $this->PageSizes, $this->GroupRange, $this->AutoHidePager, $this->AutoHidePageSizeSelector);

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<# if (groupFields.length > 0) { #>

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

<# } #>

    // Load row values
    public function loadRowValues($record)
    {
        $data = [];
    <#
        for (let f of allFields) {
            if (!IsBinaryField(f) && f.FldType != 201 && f.FldType != 203) { // Blob / adLongVarChar / adLongVarWChar
                let fldParm = f.FldParm, fldName = f.FldName;
    #>
        $data["<#= fldParm #>"] = $record['<#= SingleQuote(fldName) #>'];
    <#
            }
        }
    #>
        $this->Rows[] = $data;

    <#
        for (let f of allFields) {
            let fldName = f.FldName,
                fldObj = "this->" + f.FldParm;
            if (groupFields.length > 0 && fldName == firstGroupField.FldName) {
    #>
        $<#= fldObj #>->setDbValue(GroupValue($<#= fldObj #>, $record['<#= SingleQuote(fldName) #>']));
    <#
            } else {
                if (f.FldHtmlTag == "FILE") {
    #>
        $<#= fldObj #>->Upload->DbValue = $record['<#= SingleQuote(fldName) #>'];
    <#
                    if (IsBinaryField(f)) {
    #>
        $<#= fldObj #>->setDbValue($<#= fldObj #>->Upload->DbValue);
    <#
                    }
                } else {
    #>
        $<#= fldObj #>->setDbValue($record['<#= SingleQuote(fldName) #>']);
    <#
                }
            }
        }
    #>
    }


    // Render row
    public function renderRow()
    {
        global $Security, $Language, $Language;

        $conn = $this->getConnection();

        if ($this->RowType == ROWTYPE_TOTAL && $this->RowTotalSubType == ROWTOTAL_FOOTER && $this->RowTotalType == ROWTOTAL_PAGE) { // Get Page total

    <# if (groupFields.length > 0) { #>

            // Build detail SQL
            $firstGrpFld = &$this-><#= firstGroupField.FldParm #>;
            $firstGrpFld->getDistinctValues($this->GroupRecords);
            $where = DetailFilterSql($firstGrpFld, $this->getSqlFirstGroupField(), $firstGrpFld->DistinctValues, $this->Dbid);
            if ($this->Filter != "") {
                $where = "($this->Filter) AND ($where)";
            }
            $sql = $this->buildReportSql($this->getSqlSelect(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), $this->getSqlOrderBy(), $where, $this->Sort);
            $rs = $sql->execute();
            $records = $rs ? $rs->fetchAll() : [];

    <# } else { #>

            $records = &$this->DetailRecords;

    <# } #>

    <#
        detailFields.forEach((dtlFld, i) => {
            let fldParm = dtlFld.FldParm,
                smryTypes = dtlFld.SummaryTypes;
            for (let smryType of smryTypes) {
    #>
            $this-><#= fldParm #>->get<#= smryType #>($records);
    <#
            }
        });
    #>
            $this->PageTotalCount = count($records);

        } elseif ($this->RowType == ROWTYPE_TOTAL && $this->RowTotalSubType == ROWTOTAL_FOOTER && $this->RowTotalType == ROWTOTAL_GRAND) { // Get Grand total

            $hasCount = false;
            $hasSummary = false;

            // Get total count from SQL directly
            $sql = $this->buildReportSql($this->getSqlSelectCount(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, "");
            $rstot = $conn->executeQuery($sql);
            if ($rstot && $cnt = $rstot->fetchColumn()) {
                $rstot->closeCursor();
                $hasCount = true;
            } else {
                $cnt = 0;
            }
            $this->TotalCount = $cnt;

    <# if (hasSummaryFields) { #>

            // Get total from SQL directly
            $sql = $this->buildReportSql($this->getSqlSelectAggregate(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, "");
            $sql = $this->getSqlAggregatePrefix() . $sql . $this->getSqlAggregateSuffix();
            $rsagg = $conn->fetchAssoc($sql);
            if ($rsagg) {
    <#
        detailFields.forEach((dtlFld, i) => {
            let fldParm = dtlFld.FldParm,
                parm = fldParm.toLowerCase(),
                sumFld = "sum_" + parm,
                avgFld = "avg_" + parm,
                minFld = "min_" + parm,
                maxFld = "max_" + parm,
                cntFld = "cnt_" + parm;
    #>
                $this-><#= fldParm #>->Count = $this->TotalCount;
    <#
            if (dtlFld.FldRptAggSum) { // SUM
    #>
                $this-><#= fldParm #>->SumValue = $rsagg["<#= sumFld #>"];
    <#
            }
            if (dtlFld.FldRptAggAvg) { // AVG
    #>
                $this-><#= fldParm #>->AvgValue = $rsagg["<#= avgFld #>"];
    <#
            }
            if (dtlFld.FldRptAggMin) { // MIN
    #>
                $this-><#= fldParm #>->MinValue = $rsagg["<#= minFld #>"];
    <#
            }
            if (dtlFld.FldRptAggMax) { // MAX
    #>
                $this-><#= fldParm #>->MaxValue = $rsagg["<#= maxFld #>"];
    <#
            }
            if (dtlFld.FldRptAggCnt) { // CNT
    #>
                $this-><#= fldParm #>->CntValue = $rsagg["<#= cntFld #>"];
    <#
            }
        });
    #>
                $hasSummary = true;
            }

    <# } else { #>

            $hasSummary = true;

    <# } #>

            // Accumulate grand summary from detail records
            if (!$hasCount || !$hasSummary) {

                $sql = $this->buildReportSql($this->getSqlSelect(), $this->getSqlFrom(), $this->getSqlWhere(), $this->getSqlGroupBy(), $this->getSqlHaving(), "", $this->Filter, "");
                $rs = $sql->execute();
                $this->DetailRecords = $rs ? $rs->fetchAll() : [];
    <#
        detailFields.forEach((dtlFld, i) => {
            let fldParm = dtlFld.FldParm,
                smryTypes = dtlFld.SummaryTypes;
            for (let smryType of smryTypes) {
    #>
                $this-><#= fldParm #>->get<#= smryType #>($this->DetailRecords);
    <#
            }
        });
    #>

            }

        }

    <# if (ServerScriptExist("Table", "Row_Rendering")) { #>
        // Call Row_Rendering event
        $this->rowRendering();
    <# } #>

    <#
        for (let fld of groupFields.concat(detailFields)) {
            let fldName = fld.FldName;
            FIELD = fld;
    #>
        // <#= fldName #>
        <#= ScriptCommon() #>
    <#
        }
    #>

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

        } elseif ($this->RowType == ROWTYPE_TOTAL && !($this->RowTotalType == ROWTOTAL_GROUP && $this->RowTotalSubType == ROWTOTAL_HEADER)) { // Summary row

    <# if (!showSummaryView) { #>
            $this->RowAttrs->prependClass(($this->RowTotalType == ROWTOTAL_PAGE || $this->RowTotalType == ROWTOTAL_GRAND) ? "ew-rpt-grp-aggregate" : ""); // Set up row class
    <# } #>

    <#
        groupFields.forEach((grpFld, i) => {
            let lvl = i + 1,
                fldObj = "this->" + grpFld.FldParm;
            if (i == 0) {
    #>
            if ($this->RowTotalType == ROWTOTAL_GROUP) {
                $this->RowAttrs["data-group"] = $<#= fldObj #>->groupValue(); // Set up group attribute
            }
    <#
            } else {
    #>
            if ($this->RowTotalType == ROWTOTAL_GROUP && $this->RowGroupLevel >= <#= lvl #>) {
                $this->RowAttrs["data-group-<#= lvl #>"] = $<#= fldObj #>->groupValue(); // Set up group attribute <#= lvl #>
            }
    <#
            }
        });
    #>

    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
            FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptGroupSummaryView() #>
            $<#= fldObj #>->GroupViewValue = DisplayGroupValue($<#= fldObj #>, $<#= fldObj #>->GroupViewValue);
        <# if (showSummaryView) { #>
            if (!$<#= fldObj #>->LevelBreak) {
                $<#= fldObj #>->GroupViewValue = "&nbsp;";
            } else {
                $<#= fldObj #>->LevelBreak = false;
            }
        <# } else if (!showDetails) { #>
            if (!$<#= fldObj #>->LevelBreak) {
                 if ($<#= fldObj #>->ShowCompactSummaryFooter) {
                    $<#= fldObj #>->GroupViewValue = "&nbsp;";
                 }
            } else
                $<#= fldObj #>->LevelBreak = false;
        <# } #>
    <#
        });

        detailFields.forEach((dtlFld, i) => {
            let fldName = dtlFld.FldName,
                fldObj = "this->" + dtlFld.FldParm,
                smryTypes = dtlFld.SummaryTypes;
            FIELD = dtlFld;
            for (let smryType of smryTypes) {
    #>
            // <#= fldName #>
            <#= ScriptSummaryView({ id: smryType }) #>
    <#
            }
            if (smryTypes.length > 0) {
                if (showSummaryView) {
    #>
            $<#= fldObj #>->CellAttrs["class"] = ($this->RowTotalType == ROWTOTAL_PAGE || $this->RowTotalType == ROWTOTAL_GRAND) ? "ew-rpt-grp-aggregate" : (($this->RowGroupLevel < <#= groupFields.length #>) ? "ew-rpt-grp-summary-" . $this->RowGroupLevel : (($this->LastGroupCount % 2 != 1) ? "ew-table-alt-row" : "ew-table-row"));
    <#
                } else {
    #>
            $<#= fldObj #>->CellAttrs["class"] = ($this->RowTotalType == ROWTOTAL_PAGE || $this->RowTotalType == ROWTOTAL_GRAND) ? "ew-rpt-grp-aggregate" : "ew-rpt-grp-summary-" . $this->RowGroupLevel;
    <#
                }
            }
        });
    #>

    <#
        for (let fld of groupFields.concat(detailFields)) {
            let fldName = fld.FldName;
            FIELD = fld;
    #>
            // <#= fldName #>
            <#= ScriptSummaryViewRefer() #>
    <#
        }
    #>

        } else {

            if ($this->RowTotalType == ROWTOTAL_GROUP && $this->RowTotalSubType == ROWTOTAL_HEADER) {
    <#
        groupFields.forEach((grpFld, i) => {
            let lvl = i + 1,
                fldObj = "this->" + grpFld.FldParm;
            if (i == 0) {
    #>
                $this->RowAttrs["data-group"] = $<#= fldObj #>->groupValue(); // Set up group attribute
    <#
            } else {
    #>
                if ($this->RowGroupLevel >= <#= lvl #>) {
                    $this->RowAttrs["data-group-<#= lvl #>"] = $<#= fldObj #>->groupValue(); // Set up group attribute <#= lvl #>
                }
    <#
            }
        });
    #>
            } else {
    <#
        groupFields.forEach((grpFld, i) => {
            let lvl = i + 1,
                fldObj = "this->" + grpFld.FldParm;
            if (i == 0) {
    #>
                $this->RowAttrs["data-group"] = $<#= fldObj #>->groupValue(); // Set up group attribute
    <#
            } else {
    #>
                $this->RowAttrs["data-group-<#= lvl #>"] = $<#= fldObj #>->groupValue(); // Set up group attribute <#= lvl #>
    <#
            }
        });
    #>
            }

    <#
        groupFields.forEach((grpFld, i) => {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
            FIELD = grpFld;
    #>
            // <#= fldName #>
            <#= ScriptGroupView() #>
            $<#= fldObj #>->GroupViewValue = DisplayGroupValue($<#= fldObj #>, $<#= fldObj #>->GroupViewValue);
            if (!$<#= fldObj #>->LevelBreak) {
                $<#= fldObj #>->GroupViewValue = "&nbsp;";
            } else {
                $<#= fldObj #>->LevelBreak = false;
            }
    <#
        });

        for (let dtlFld of detailFields) {
            let fldName = dtlFld.FldName;
            FIELD = dtlFld;
    #>
            // <#= fldName #>
            <#= ScriptView() #>
    <#
        }
    #>

    <#
        for (let fld of groupFields.concat(detailFields)) {
            let fldName = fld.FldName;
            FIELD = fld;
    #>
            // <#= fldName #>
            <#= ScriptViewRefer() #>
    <#
        }
    #>

        }

    <# if (ServerScriptExist("Table", "Cell_Rendered")) { #>

        // Call Cell_Rendered event
        if ($this->RowType == ROWTYPE_TOTAL) { // Summary row
    <#
        for (let grpFld of groupFields) {
            let fldName = grpFld.FldName,
                fldObj = "this->" + grpFld.FldParm;
    #>
            // <#= fldName #>
            $currentValue = $<#= fldObj #>->GroupViewValue;
            $viewValue = &$<#= fldObj #>->GroupViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
        }

        detailFields.forEach((dtlFld, i) => {
            let fldName = dtlFld.FldName,
                fldObj = "this->" + dtlFld.FldParm,
                smryTypes = dtlFld.SummaryTypes;
            for (let smryType of smryTypes) {
    #>
            // <#= fldName #>
            $currentValue = $<#= fldObj #>-><#= SummaryValueName(smryType) #>;
            $viewValue = &$<#= fldObj #>-><#= SummaryViewValueName(smryType) #>;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
            }
        });
    #>

        } else {

    <#
        for (let fld of groupFields) {
            let fldName = fld.FldName,
                fldObj = "this->" + fld.FldParm;
    #>
            // <#= fldName #>
            $currentValue = $<#= fldObj #>->groupValue();
            $viewValue = &$<#= fldObj #>->GroupViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
        }
    #>

    <#
        for (let fld of detailFields) {
            let fldName = fld.FldName,
                fldObj = "this->" + fld.FldParm;
    #>
            // <#= fldName #>
            $currentValue = $<#= fldObj #>->CurrentValue;
            $viewValue = &$<#= fldObj #>->ViewValue;
            $viewAttrs = &$<#= fldObj #>->ViewAttrs;
            $cellAttrs = &$<#= fldObj #>->CellAttrs;
            $hrefValue = &$<#= fldObj #>->HrefValue;
            $linkAttrs = &$<#= fldObj #>->LinkAttrs;
            $this->cellRendered($<#= fldObj #>, $currentValue, $viewValue, $viewAttrs, $cellAttrs, $hrefValue, $linkAttrs);
    <#
        }
    #>

        }

    <# } #>

    <# if (ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row_Rendered event
        $this->rowRendered();
    <# } #>

        $this->setupFieldCount();
    }

    private $groupCounts = [];

    // Get group count
    public function getGroupCount(...$args)
    {
        $key = "";
        foreach ($args as $arg) {
            if ($key != "") {
                $key .= "_";
            }
            $key .= strval($arg);
        }
        if ($key == "") {
            return -1;
        } elseif ($key == "0") { // Number of first level groups
            $i = 1;
            while (isset($this->groupCounts[strval($i)])) {
                $i++;
            }
            return $i - 1;
        }
        return isset($this->groupCounts[$key]) ? $this->groupCounts[$key] : -1;
    }

    // Set group count
    public function setGroupCount($value, ...$args)
    {
        $key = "";
        foreach ($args as $arg) {
            if ($key != "") {
                $key .= "_";
            }
            $key .= strval($arg);
        }
        if ($key == "") {
            return;
        }
        $this->groupCounts[$key] = $value;
    }

    // Setup field count
    protected function setupFieldCount()
    {
        $this->GroupColumnCount = 0;
        $this->SubGroupColumnCount = 0;
        $this->DetailColumnCount = 0;
    <#
        groupFields.forEach((grpFld, i) => {
            let fldObj = "this->" + grpFld.FldParm;
            if (i == 0) {
    #>
        if ($<#= fldObj #>->Visible) {
            $this->GroupColumnCount += 1;
        }
    <#
            } else {
    #>
        if ($<#= fldObj #>->Visible) {
            $this->GroupColumnCount += 1;
            $this->SubGroupColumnCount += 1;
        }
    <#
            }
        });

        for (let dtlFld of detailFields) {
            let fldObj = "this->" + dtlFld.FldParm;
    #>
        if ($<#= fldObj #>->Visible) {
            $this->DetailColumnCount += 1;
        }
    <#
        }
    #>

    }

<## Shared functions #>
<#= include('shared/shared-functions.php') #>
<#= include('shared/report-shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Table", "Form_CustomValidate") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
