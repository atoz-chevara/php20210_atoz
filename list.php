<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>
    // Class variables
    public $ListOptions; // List options
    public $ExportOptions; // Export options
    public $SearchOptions; // Search options
    public $OtherOptions; // Other options
    public $FilterOptions; // Filter options
    public $ImportOptions; // Import options
    public $ListActions; // List actions
    public $SelectedCount = 0;
    public $SelectedIndex = 0;

<# if (ctrlId == "grid") { #>
    public $ShowOtherOptions = false;
<# } #>

    public $DisplayRecords = <#= recPerPage #>;
    public $StartRecord;
    public $StopRecord;
    public $TotalRecords = 0;
    public $RecordRange = 10;
    public $PageSizes = "<#= recPerPageList #>"; // Page sizes (comma separated)
    public $DefaultSearchWhere = ""; // Default search WHERE clause
    public $SearchWhere = ""; // Search WHERE clause
    public $SearchPanelClass = "ew-search-panel collapse<#= PROJ.SearchPanelCollapsed ? "" : " show" #>"; // Search Panel class
    public $SearchRowCount = 0; // For extended search
    public $SearchColumnCount = 0; // For extended search
    public $SearchFieldsPerRow = <#= extSearchFldPerRow #>; // For extended search
    public $RecordCount = 0; // Record count
    public $EditRowCount;
    public $StartRowCount = 1;
    public $RowCount = 0;
    public $Attrs = []; // Row attributes and cell attributes
    public $RowIndex = 0; // Row index
    public $KeyCount = 0; // Key count
    public $RowAction = ""; // Row action
    public $MultiColumnClass = "<#= multiColumnClass #>";
    public $MultiColumnEditClass = "w-100";
    public $DbMasterFilter = ""; // Master filter
    public $DbDetailFilter = ""; // Detail filter
    public $MasterRecordExists;
    public $MultiSelectKey;
    public $Command;
    public $RestoreSearch = false;
    public $HashValue; // Hash value
    public $DetailPages;
    public $OldRecordset;

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        <# if (UseCustomTemplate) { #>
        $this->AllowAddDeleteRow = false; // Do not allow add/delete row
        <# } #>

        // Search filters
        $srchAdvanced = ""; // Advanced search filter
        $srchBasic = ""; // Basic search filter
        $filter = "";

        // Get command
        $this->Command = strtolower(Get("cmd"));

        if ($this->isPageRequest()) { // Validate request
            <# if (ctrlId == "list") { #>
            // Process list action first
            if ($this->processListAction()) { // Ajax request
                $this->terminate();
                return;
            }
            <# } #>

            // Set up records per page
            $this->setupDisplayRecords();

            // Handle reset command
            $this->resetCmd();

            <# if (ctrlId == "list") { #>

            // Set up Breadcrumb
            if (!$this->isExport()) {
                $this->setupBreadcrumb();
            }

            <# if (listAddOrEdit || isImport) { #>
            // Check QueryString parameters
            if (Get("action") !== null) {
                $this->CurrentAction = Get("action");

                <# if (listAddOrEdit) { #>
                // Clear inline mode
                if ($this->isCancel()) {
                    $this->clearInlineMode();
                }
                <# } #>

                <# if (gridEdit) { #>
                // Switch to grid edit mode
                if ($this->isGridEdit()) {
                    $this->gridEditMode();
                }
                <# } #>

                <# if (inlineEdit) { #>
                // Switch to inline edit mode
                if ($this->isEdit()) {
                    $this->inlineEditMode();
                }
                <# } #>

                <# if (inlineAdd || inlineCopy) { #>
                // Switch to inline add mode
                if ($this->isAdd() || $this->isCopy()) {
                    $this->inlineAddMode();
                }
                <# } #>

                <# if (gridAdd) { #>
                // Switch to grid add mode
                if ($this->isGridAdd()) {
                    $this->gridAddMode();
                }
                <# } #>
            } else {
                if (Post("action") !== null) {
                    $this->CurrentAction = Post("action"); // Get action

                    <# if (isImport) { #>
                    // Process import
                    if ($this->isImport()) {
                        $this->import(Post(Config("API_FILE_TOKEN_NAME")));
                        $this->terminate();
                        return;
                    }
                    <# } #>

                    <# if (gridEdit) { #>
                    // Grid Update
                    if (($this->isGridUpdate() || $this->isGridOverwrite()) && Session(SESSION_INLINE_MODE) == "gridedit") {
                        if ($this->validateGridForm()) {
                            $gridUpdate = $this->gridUpdate();
                        } else {
                            $gridUpdate = false;
                        }
                        if ($gridUpdate) {
                            <# if (TABLE.TblGridEditReturnPage == "_GRIDADD") { #>
                            $this->gridAddMode();
                            <# } else if (TABLE.TblGridEditReturnPage == "_GRIDEDIT") { #>
                            $this->gridEditMode();
                            <# } else if (!IsEmpty(TABLE.TblGridEditReturnPage)) { #>
                            $this->terminate(<#= TABLE.TblGridEditReturnPage #>);
                            return;
                            <# } #>
                        } else {
                            $this->EventCancelled = true;
                            $this->gridEditMode(); // Stay in Grid edit mode
                        }
                    }
                    <# } #>

                    <# if (inlineEdit) { #>
                    // Inline Update
                    if (($this->isUpdate() || $this->isOverwrite()) && Session(SESSION_INLINE_MODE) == "edit") {
                        $this->setKey(Post($this->OldKeyName));
                        $this->inlineUpdate();
                    }
                    <# } #>

                    <# if (inlineAdd || inlineCopy) { #>
                    // Insert Inline
                    if ($this->isInsert() && Session(SESSION_INLINE_MODE) == "add") {
                        $this->setKey(Post($this->OldKeyName));
                        $this->inlineInsert();
                    }
                    <# } #>

                    <# if (gridAdd) { #>
                    // Grid Insert
                    if ($this->isGridInsert() && Session(SESSION_INLINE_MODE) == "gridadd") {
                        if ($this->validateGridForm()) {
                            $gridInsert = $this->gridInsert();
                        } else {
                            $gridInsert = false;
                        }
                        if ($gridInsert) {
                            <# if (TABLE.TblGridAddReturnPage == "_GRIDADD") { #>
                            $this->gridAddMode();
                            <# } else if (TABLE.TblGridAddReturnPage == "_GRIDEDIT") { #>
                            $this->gridEditMode();
                            <# } else if (!IsEmpty(TABLE.TblGridAddReturnPage)) { #>
                            $this->terminate(<#= TABLE.TblGridAddReturnPage #>);
                            return;
                            <# } #>
                        } else {
                            $this->EventCancelled = true;
                            $this->gridAddMode(); // Stay in Grid add mode
                        }
                    }
                    <# } #>

            <# if (gridEdit) { #>
                } elseif (Session(SESSION_INLINE_MODE) == "gridedit") { // Previously in grid edit mode
                    if (Get(Config("TABLE_START_REC")) !== null || Get(Config("TABLE_PAGE_NO")) !== null) { // Stay in grid edit mode if paging
                        $this->gridEditMode();
                    } else { // Reset grid edit
                        $this->clearInlineMode();
                    }
            <# } #>
                }
            }
            <# } #>

            <# } #>

            // Hide list options
            if ($this->isExport()) {
                $this->ListOptions->hideAllOptions(["sequence"]);
                $this->ListOptions->UseDropDownButton = false; // Disable drop down button
                $this->ListOptions->UseButtonGroup = false; // Disable button group
            } elseif ($this->isGridAdd() || $this->isGridEdit()) {
                $this->ListOptions->hideAllOptions();
                $this->ListOptions->UseDropDownButton = false; // Disable drop down button
                $this->ListOptions->UseButtonGroup = false; // Disable button group
            }

            <# if (ctrlId == "list") { #>
            // Hide options
            if ($this->isExport() || $this->CurrentAction) {
                $this->ExportOptions->hideAllOptions();
                $this->FilterOptions->hideAllOptions();
                $this->ImportOptions->hideAllOptions();
            }

            // Hide other options
            if ($this->isExport()) {
                $this->OtherOptions->hideAllOptions();
            }
            <# } #>

            <# if (gridAddOrEdit) { #>
            // Show grid delete link for grid add / grid edit
            if ($this->AllowAddDeleteRow) {
                if ($this->isGridAdd() || $this->isGridEdit()) {
                    $item = $this->ListOptions["griddelete"];
                    if ($item) {
                        $item->Visible = true;
                    }
                }
            }
            <# } #>

            <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch || useBasicSearch)) { #>

            // Get default search criteria
            <# if (useBasicSearch) { #>
            AddFilter($this->DefaultSearchWhere, $this->basicSearchWhere(true));
            <# } #>
            <# if (useExtendedBasicSearch || useAdvancedSearch) { #>
            AddFilter($this->DefaultSearchWhere, $this->advancedSearchWhere(true));
            <# } #>

            <# if (ctrlId == "list" && useBasicSearch) { #>
            // Get basic search values
            $this->loadBasicSearchValues();
            <# } #>

            <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch)) { #>
            // Get and validate search values for advanced search
            $this->loadSearchValues(); // Get search values
            <# } #>

            <# if (ctrlId == "list" && (useBasicSearch || useAdvancedSearch || useExtendedBasicSearch)) { #>
            // Process filter list
            if ($this->processFilterList()) {
                $this->terminate();
                return;
            }
            <# } #>

            <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch)) { #>
            if (!$this->validateSearch()) {
                // Nothing to do
            }
            <# } #>

            // Restore search parms from Session if not searching / reset / export
            if (($this->isExport() || $this->Command != "search" && $this->Command != "reset" && $this->Command != "resetall") && $this->Command != "json" && $this->checkSearchParms()) {
                $this->restoreSearchParms();
            }

            <# if (ServerScriptExist("Table", "Recordset_SearchValidated")) { #>
            // Call Recordset SearchValidated event
            $this->recordsetSearchValidated();
            <# } #>

            <# } #>

            // Set up sorting order
            $this->setupSortOrder();

            <# if (ctrlId == "list" && useBasicSearch) { #>
            // Get basic search criteria
            if (!$this->hasInvalidFields()) {
                $srchBasic = $this->basicSearchWhere();
            }
            <# } #>

            <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch)) { #>
            // Get search criteria for advanced search
            if (!$this->hasInvalidFields()) {
                $srchAdvanced = $this->advancedSearchWhere();
            }
            <# } #>
        }

        // Restore display records
        if ($this->Command != "json" && $this->getRecordsPerPage() != "") {
            $this->DisplayRecords = $this->getRecordsPerPage(); // Restore from Session
        } else {
            $this->DisplayRecords = <#= recPerPage #>; // Load default
            $this->setRecordsPerPage($this->DisplayRecords); // Save default to Session
        }

        // Load Sorting Order
        if ($this->Command != "json") {
            $this->loadSortOrder();
        }

        <# if (ctrlId == "list" && (useBasicSearch || useExtendedBasicSearch || useAdvancedSearch)) { #>

        // Load search default if no existing search criteria
        if (!$this->checkSearchParms()) {
        <# if (ctrlId == "list" && useBasicSearch) { #>
            // Load basic search from default
            $this->BasicSearch->loadDefault();
            if ($this->BasicSearch->Keyword != "") {
                $srchBasic = $this->basicSearchWhere();
            }
        <# } #>

        <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch)) { #>
            // Load advanced search from default
            if ($this->loadAdvancedSearchDefault()) {
                $srchAdvanced = $this->advancedSearchWhere();
            }
        <# } #>
        }

        <# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch)) { #>
        // Restore search settings from Session
        if (!$this->hasInvalidFields()) {
            $this->loadAdvancedSearch();
        }
        <# } #>

        // Build search criteria
        AddFilter($this->SearchWhere, $srchAdvanced);
        AddFilter($this->SearchWhere, $srchBasic);

        <# if (ServerScriptExist("Table", "Recordset_Searching")) { #>
        // Call Recordset_Searching event
        $this->recordsetSearching($this->SearchWhere);
        <# } #>

        // Save search criteria
        if ($this->Command == "search" && !$this->RestoreSearch) {
            $this->setSearchWhere($this->SearchWhere); // Save to Session
            $this->StartRecord = 1; // Reset start record counter
            $this->setStartRecordNumber($this->StartRecord);
        } elseif ($this->Command != "json") {
            $this->SearchWhere = $this->getSearchWhere();
        }

        <# } #>

        // Build filter
        $filter = "";

    <# if (hasUserTable) { #>
        if (!$Security->canList()) {
            $filter = "(0=1)"; // Filter all records
        }
    <# } #>

    <# if (masterTables.length > 0) { #>
        // Restore master/detail filter
        $this->DbMasterFilter = $this->getMasterFilter(); // Restore master filter
        $this->DbDetailFilter = $this->getDetailFilter(); // Restore detail filter
    <# } #>

    <# if (masterTableHasUserIdFld) { #>
        // Add master User ID filter
        if ($Security->currentUserID() != "" && !$Security->isAdmin()) { // Non system admin
            <#
            for (let md of masterTables) {
                let masterTable = GetTableObject(md.MasterTable),
                    masterTblVar = masterTable.TblVar;
            #>
                if ($this->getCurrentMasterTable() == "<#= masterTblVar #>") {
                    $this->DbMasterFilter = $this->addMasterUserIDFilter($this->DbMasterFilter, "<#= masterTblVar #>"); // Add master User ID filter
                }
                <#
            }
            #>
        }
    <# } #>

        AddFilter($filter, $this->DbDetailFilter);
        AddFilter($filter, $this->SearchWhere);

    <# if (showBlankListPage) { #>
        if ($filter == "") {
            $filter = "0=101";
            $this->SearchWhere = $filter;
        }
    <# } #>

    <#
    if (masterTables.length > 0) {
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar,
                masterListPage = GetRouteUrl("list", masterTable);
    #>
        // Load master record
        if ($this->CurrentMode != "add" && $this->getMasterFilter() != "" && $this->getCurrentMasterTable() == "<#= masterTblVar #>") {
            $masterTbl = Container("<#= masterTblVar #>");
            $rsmaster = $masterTbl->loadRs($this->DbMasterFilter)->fetch(\PDO::FETCH_ASSOC);
            $this->MasterRecordExists = $rsmaster !== false;
            if (!$this->MasterRecordExists) {
                $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record found
                $this->terminate("<#= masterListPage #>"); // Return to master page
                return;
            } else {
                $masterTbl->loadListRowValues($rsmaster);
                $masterTbl->RowType = ROWTYPE_MASTER; // Master row
                $masterTbl->renderListRow();
            }
        }
    <#
        } // MasterDetail
    }
    #>

        // Set up filter
        if ($this->Command == "json") {
            $this->UseSessionForListSql = false; // Do not use session for ListSQL
            $this->CurrentFilter = $filter;
        } else {
            $this->setSessionWhere($filter);
            $this->CurrentFilter = "";
        }

    <# if (exportSelectedOnly && ctrlId == "list") { #>
        // Export selected records
        if ($this->isExport()) {
            $this->CurrentFilter = $this->buildExportSelectedFilter();
        }
    <# } #>

    <# if (ctrlId == "list" && listExport && (exportHtml || exportEmail || exportCsv || exportWord || exportExcel || exportXml || exportPdf)) { #>
        // Export data only
        if (!$this->CustomExport && in_array($this->Export, array_keys(Config("EXPORT_CLASSES")))) {
            $this->exportData();
            $this->terminate();
            return;
        }
    <# } #>

        if ($this->isGridAdd()) {
        <# if (ctrlId == "grid") { #>

            if ($this->CurrentMode == "copy") {
                $this->TotalRecords = $this->listRecordCount();
                $this->StartRecord = 1;
                $this->DisplayRecords = $this->TotalRecords;
                $this->Recordset = $this->loadRecordset($this->StartRecord - 1, $this->DisplayRecords);
            } else {
                $this->CurrentFilter = "0=1";
                $this->StartRecord = 1;
                $this->DisplayRecords = $this->GridAddRowCount;
            }

        <# } else { #>

            $this->CurrentFilter = "0=1";
            $this->StartRecord = 1;
            $this->DisplayRecords = $this->GridAddRowCount;

        <# } #>

            $this->TotalRecords = $this->DisplayRecords;
            $this->StopRecord = $this->DisplayRecords;
        } else {
            $this->TotalRecords = $this->listRecordCount();
            $this->StartRecord = 1;

        <# if (ctrlId == "list") { #>

            if ($this->DisplayRecords <= 0 || ($this->isExport() && $this->ExportAll)) { // Display all records
                $this->DisplayRecords = $this->TotalRecords;
            }
            if (!($this->isExport() && $this->ExportAll)) { // Set up start record position
                $this->setupStartRecord();
            }

        <# } else { #>

            $this->DisplayRecords = $this->TotalRecords; // Display all records

        <# } #>

            $this->Recordset = $this->loadRecordset($this->StartRecord - 1, $this->DisplayRecords);

        <# if (ctrlId == "list") { #>
            // Set no record found message
            if (!$this->CurrentAction && $this->TotalRecords == 0) {
                <# if (hasUserTable) { #>
                if (!$Security->canList()) {
                    $this->setWarningMessage(DeniedMessage());
                }
                <# } #>
                if ($this->SearchWhere == "0=101") {
                    $this->setWarningMessage($Language->phrase("EnterSearchCriteria"));
                } else {
                    $this->setWarningMessage($Language->phrase("NoRecord"));
                }
            }
        <# } #>

        <# if (TABLE.TblAuditTrail && ctrlId == "list" && (useBasicSearch || useExtendedBasicSearch || useAdvancedSearch)) { #>
            // Audit trail on search
            if ($this->AuditTrailOnSearch && $this->Command == "search" && !$this->RestoreSearch) {
                $searchParm = ServerVar("QUERY_STRING");
                $searchSql = $this->getSessionWhere();
                $this->writeAuditTrailOnSearch($searchParm, $searchSql);
            }
        <# } #>
        }

    <# if (ctrlId == "list") { #>
        // Search options
        $this->setupSearchOptions();

        // Set up search panel class
        if ($this->SearchWhere != "") {
            AppendClass($this->SearchPanelClass, "show");
        }

    <# } #>

        // Normal return
        if (IsApi()) {
            $rows = $this->getRecordsFromRecordset($this->Recordset);
            $this->Recordset->close();
            WriteJson(["success" => true, $this->TableVar => $rows, "totalRecordCount" => $this->TotalRecords]);
            $this->terminate(true);
            return;
        }

        // Set up pager
        $this->Pager = new <#= pagerClass #>($this->StartRecord, $this->getRecordsPerPage(), $this->TotalRecords, $this->PageSizes, $this->RecordRange, $this->AutoHidePager, $this->AutoHidePageSizeSelector);

<## Page run end #>
<#= include('shared/page-run-end.php') #>
    }

    // Set up number of records displayed per page
    protected function setupDisplayRecords()
    {
        $wrk = Get(Config("TABLE_REC_PER_PAGE"), "");
        if ($wrk != "") {
            if (is_numeric($wrk)) {
                $this->DisplayRecords = (int)$wrk;
            } else {
                if (SameText($wrk, "all")) { // Display all records
                    $this->DisplayRecords = -1;
                } else {
                    $this->DisplayRecords = <#= recPerPage #>; // Non-numeric, load default
                }
            }
            $this->setRecordsPerPage($this->DisplayRecords); // Save to Session
            // Reset start position
            $this->StartRecord = 1;
            $this->setStartRecordNumber($this->StartRecord);
        }
    }

<# if (listAddOrEdit) { #>

    // Exit inline mode
    protected function clearInlineMode()
    {
    <#
        for (let f of currentFields) {
            if (IsFloatFormatField(f)) { // Check if adSingle/adDouble/adNumeric/adCurrency
                let fldParm = f.FldParm;
    #>
        $this-><#= fldParm #>->FormValue = ""; // Clear form value
    <#
            }
        }
    #>
        $this->LastAction = $this->CurrentAction; // Save last action
        $this->CurrentAction = ""; // Clear action
        $_SESSION[SESSION_INLINE_MODE] = ""; // Clear inline mode
    }

<# } #>

<# if (gridAdd) { #>

    // Switch to Grid Add mode
    protected function gridAddMode()
    {
        $this->CurrentAction = "gridadd";
        $_SESSION[SESSION_INLINE_MODE] = "gridadd";
        $this->hideFieldsForAddEdit();
    }

<# } #>

<# if (gridEdit) { #>

    // Switch to Grid Edit mode
    protected function gridEditMode()
    {
        $this->CurrentAction = "gridedit";
        $_SESSION[SESSION_INLINE_MODE] = "gridedit";
        $this->hideFieldsForAddEdit();
    }

<# } #>

<# if (inlineEdit) { #>

    // Switch to Inline Edit mode
    protected function inlineEditMode()
    {
        global $Security, $Language;
        <# if (hasUserTable) { #>
        if (!$Security->canEdit()) {
            return false; // Edit not allowed
        }
        <# } #>
        $inlineEdit = true;
        <#
        for (let kf of keyFields) {
            let fldParm = kf.FldParm;
        #>
        if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
            $this-><#= fldParm #>->setQueryStringValue($keyValue);
        } else {
            $inlineEdit = false;
        }
        <#
        } // KeyField
        #>
        if ($inlineEdit) {
            if ($this->loadRow()) {
            <#
                if (hasUserIdFld) {
            #>

                    // Check if valid User ID
                    if (!$this->showOptionLink("edit")) {
                        $userIdMsg = $Language->phrase("NoEditPermission");
                        $this->setFailureMessage($userIdMsg);
                        $this->clearInlineMode(); // Clear inline edit mode
                        return false;
                    }

            <#
                }
            #>
                $this->OldKey = $this->getKey(true); // Get from CurrentValue
                $this->setKey($this->OldKey); // Set to OldValue
                $_SESSION[SESSION_INLINE_MODE] = "edit"; // Enable inline edit
            }
        }
        return true;
    }

    // Perform update to Inline Edit record
    protected function inlineUpdate()
    {
        global $Language, $CurrentForm;
        $CurrentForm->Index = 1;

        $this->loadFormValues(); // Get form values

        // Validate form
        $inlineUpdate = true;
        if (!$this->validateForm()) {
            $inlineUpdate = false; // Form error, reset action
        } else {
            <# if (checkConcurrentUpdate) { #>
            // Overwrite record, just reload hash value
            if ($this->isOverwrite()) {
                $this->loadRowHash();
            }
            <# } #>
            $inlineUpdate = false;
            $this->SendEmail = true; // Send email on update success
            $inlineUpdate = $this->editRow(); // Update record
        }

        if ($inlineUpdate) { // Update success
            if ($this->getSuccessMessage() == "") {
                $this->setSuccessMessage($Language->phrase("UpdateSuccess")); // Set up success message
            }
            $this->clearInlineMode(); // Clear inline edit mode
        } else {
            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("UpdateFailed")); // Set update failed message
            }
            $this->EventCancelled = true; // Cancel event
            $this->CurrentAction = "edit"; // Stay in edit mode
        }
    }

    // Check Inline Edit key
    public function checkInlineEditKey()
    {
    <#
        for (let kf of keyFields) {
            let fldParm = kf.FldParm;
    #>
        if (!SameString($this-><#= fldParm #>->OldValue, $this-><#= fldParm #>->CurrentValue)) {
            return false;
        }
    <#
    } // KeyField
    #>
        return true;
    }

<# } #>

<# if (inlineAdd || inlineCopy) { #>

    // Switch to Inline Add mode
    protected function inlineAddMode()
    {
        global $Security, $Language;
        <# if (hasUserTable) { #>
        if (!$Security->canAdd()) {
            return false; // Add not allowed
        }
        <# } #>

        <# if (inlineCopy) { #>

        if ($this->isCopy()) {
            <#
            for (let kf of keyFields) {
                let fldParm = kf.FldParm;
            #>
            if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                $this-><#= fldParm #>->setQueryStringValue($keyValue);
            } else {
                $this->CurrentAction = "add";
            }
            <#
            } // KeyField
            #>
            $this->OldKey = $this->getKey(true); // Get from CurrentValue
        } else {
            $this->OldKey = ""; // Clear old record key
        }
        $this->setKey($this->OldKey); // Set to OldValue

        <# if (hasUserIdFld) { #>

        // Check if valid User ID
        if ($this->loadRow() && !$this->showOptionLink("add")) {
            $userIdMsg = $Language->phrase("NoAddPermission");
            $this->setFailureMessage($userIdMsg);
            $this->clearInlineMode(); // Clear inline edit mode
            return false;
        }

        <# } #>

        <# } else { #>
        $this->CurrentAction = "add";
        <# } #>
        $_SESSION[SESSION_INLINE_MODE] = "add"; // Enable inline add

        return true;
    }

    // Perform update to Inline Add/Copy record
    protected function inlineInsert()
    {
        global $Language, $CurrentForm;

        $this->loadOldRecord(); // Load old record

        $CurrentForm->Index = 0;

        $this->loadFormValues(); // Get form values

        // Validate form
        if (!$this->validateForm()) {
            $this->EventCancelled = true; // Set event cancelled
            $this->CurrentAction = "add"; // Stay in add mode
            return;
        }

        $this->SendEmail = true; // Send email on add success
        if ($this->addRow($this->OldRecordset)) { // Add record
            if ($this->getSuccessMessage() == "") {
                $this->setSuccessMessage($Language->phrase("AddSuccess")); // Set up add success message
            }
            $this->clearInlineMode(); // Clear inline add mode
        } else { // Add failed
            $this->EventCancelled = true; // Set event cancelled
            $this->CurrentAction = "add"; // Stay in add mode
        }
    }

<# } #>

<# if (gridEdit) { #>

    // Perform update to grid
    public function gridUpdate()
    {
        global $Language, $CurrentForm;

        $gridUpdate = true;

        // Get old recordset
        $this->CurrentFilter = $this->buildKeyFilter();
        if ($this->CurrentFilter == "") {
            $this->CurrentFilter = "0=1";
        }
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        if ($rs = $conn->executeQuery($sql)) {
            $rsold = $rs->fetchAll();
            $rs->closeCursor();
        }

        <# if (ServerScriptExist("Table", "Grid_Updating")) { #>
        // Call Grid Updating event
        if (!$this->gridUpdating($rsold)) {
            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("GridEditCancelled")); // Set grid edit cancelled message
            }
            return false;
        }
        <# } #>

        <# if (ctrlId == "list") { #>
        // Begin transaction
        $conn->beginTransaction();
        <# } #>

        <# if (TABLE.TblAuditTrail) { #>
        if ($this->AuditTrailOnEdit) {
            $this->writeAuditTrailDummy($Language->phrase("BatchUpdateBegin")); // Batch update begin
        }
        <# } #>

        $key = "";

        // Update row index and get row key
        $CurrentForm->Index = -1;
        $rowcnt = strval($CurrentForm->getValue($this->FormKeyCountName));
        if ($rowcnt == "" || !is_numeric($rowcnt)) {
            $rowcnt = 0;
        }

        // Update all rows based on key
        for ($rowindex = 1; $rowindex <= $rowcnt; $rowindex++) {
            $CurrentForm->Index = $rowindex;
            $this->setKey($CurrentForm->getValue($this->OldKeyName));
            $rowaction = strval($CurrentForm->getValue($this->FormActionName));

            // Load all values and keys
            if ($rowaction != "insertdelete") { // Skip insert then deleted rows
                $this->loadFormValues(); // Get form values

                if ($rowaction == "" || $rowaction == "edit" || $rowaction == "delete") {
                    $gridUpdate = $this->OldKey != ""; // Key must not be empty
                } else {
                    $gridUpdate = true;
                }

                // Skip empty row
                if ($rowaction == "insert" && $this->emptyRow()) {
                    // No action required

                // Validate form and insert/update/delete record
                } elseif ($gridUpdate) {
                    if ($rowaction == "delete") {
                        $this->CurrentFilter = $this->getRecordFilter();
                        $gridUpdate = $this->deleteRows(); // Delete this row
                    //} elseif (!$this->validateForm()) { // Already done in validateGridForm
                    //    $gridUpdate = false; // Form error, reset action
                    } else {
                        if ($rowaction == "insert") {
                            $gridUpdate = $this->addRow(); // Insert this row
                        } else {
                            if ($this->OldKey != "") {
                                <# if (checkConcurrentUpdate) { #>
                                // Overwrite record, just reload hash value
                                if ($this->isGridOverwrite()) {
                                    $this->loadRowHash();
                                }
                                <# } #>

                                $this->SendEmail = false; // Do not send email on update success
                                $gridUpdate = $this->editRow(); // Update this row
                            }
                        } // End update
                    }
                }

                if ($gridUpdate) {
                    if ($key != "") {
                        $key .= ", ";
                    }
                    $key .= $this->OldKey;
                } else {
                    break;
                }
            }
        }

        if ($gridUpdate) {
            <# if (ctrlId == "list") { #>
            $conn->commit(); // Commit transaction
            <# } #>

            // Get new records
            $rsnew = $conn->fetchAll($sql);

            <# if (ServerScriptExist("Table", "Grid_Updated")) { #>
            // Call Grid_Updated event
            $this->gridUpdated($rsold, $rsnew);
            <# } #>

            <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnEdit) {
                $this->writeAuditTrailDummy($Language->phrase("BatchUpdateSuccess")); // Batch update success
            }
            <# } #>

            <# if (ctrlId == "list") { #>
            if ($this->getSuccessMessage() == "") {
                $this->setSuccessMessage($Language->phrase("UpdateSuccess")); // Set up update success message
            }
            <# } #>

            $this->clearInlineMode(); // Clear inline edit mode

            <# if (TABLE.TblSendMailOnEdit) { #>
            // Send notify email
            $table = '<#= SingleQuote(TABLE.TblName) #>';
            $subject = $table . " " . $Language->phrase("RecordUpdated");
            $action = $Language->phrase("ActionUpdatedGridEdit");

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

            // Set up error message
            if (!$emailSent) {
                $this->setFailureMessage($email->SendErrDescription);
            }

            <# } #>
        } else {
            <# if (ctrlId == "list") { #>
            $conn->rollback(); // Rollback transaction
            <# } #>

            <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnEdit) {
                $this->writeAuditTrailDummy($Language->phrase("BatchUpdateRollback")); // Batch update rollback
            }
            <# } #>
            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("UpdateFailed")); // Set update failed message
            }
        }

        return $gridUpdate;
    }

<# } #>

    // Build filter for all keys
    protected function buildKeyFilter()
    {
        global $CurrentForm;

        $wrkFilter = "";

        // Update row index and get row key
        $rowindex = 1;
        $CurrentForm->Index = $rowindex;
        $thisKey = strval($CurrentForm->getValue($this->OldKeyName));

        while ($thisKey != "") {
            $this->setKey($thisKey);
            if ($this->OldKey != "") {
                $filter = $this->getRecordFilter();

                if ($wrkFilter != "") {
                    $wrkFilter .= " OR ";
                }
                $wrkFilter .= $filter;
            } else {
                $wrkFilter = "0=1";
                break;
            }

            // Update row index and get row key
            $rowindex++; // Next row
            $CurrentForm->Index = $rowindex;
            $thisKey = strval($CurrentForm->getValue($this->OldKeyName));
        }

        return $wrkFilter;
    }

<# if (gridAdd) { #>

    // Perform Grid Add
    public function gridInsert()
    {
        global $Language, $CurrentForm;

        $rowindex = 1;
        $gridInsert = false;
        $conn = $this->getConnection();

        <# if (ServerScriptExist("Table", "Grid_Inserting")) { #>
        // Call Grid Inserting event
        if (!$this->gridInserting()) {
            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("GridAddCancelled")); // Set grid add cancelled message
            }
            return false;
        }
        <# } #>

        <# if (ctrlId == "list") { #>
        // Begin transaction
        $conn->beginTransaction();
        <# } #>

        // Init key filter
        $wrkfilter = "";

        $addcnt = 0;

        <# if (TABLE.TblAuditTrail) { #>
        if ($this->AuditTrailOnAdd) {
            $this->writeAuditTrailDummy($Language->phrase("BatchInsertBegin")); // Batch insert begin
        }
        <# } #>

        $key = "";

        // Get row count
        $CurrentForm->Index = -1;
        $rowcnt = strval($CurrentForm->getValue($this->FormKeyCountName));
        if ($rowcnt == "" || !is_numeric($rowcnt)) {
            $rowcnt = 0;
        }

        // Insert all rows
        for ($rowindex = 1; $rowindex <= $rowcnt; $rowindex++) {
            // Load current row values
            $CurrentForm->Index = $rowindex;

            $rowaction = strval($CurrentForm->getValue($this->FormActionName));
            if ($rowaction != "" && $rowaction != "insert") {
                continue; // Skip
            }

            if ($rowaction == "insert") {
                $this->OldKey = strval($CurrentForm->getValue($this->OldKeyName));
                $this->loadOldRecord(); // Load old record
            }

            $this->loadFormValues(); // Get form values

            if (!$this->emptyRow()) {
                $addcnt++;
                $this->SendEmail = false; // Do not send email on insert success

                // Validate form // Already done in validateGridForm
                //if (!$this->validateForm()) {
                //    $gridInsert = false; // Form error, reset action
                //} else {
                    $gridInsert = $this->addRow($this->OldRecordset); // Insert this row
                //}

                if ($gridInsert) {
    <#
        for (let kf of keyFields) {
            let fldParm = kf.FldParm;
    #>
                    if ($key != "") {
                        $key .= Config("COMPOSITE_KEY_SEPARATOR");
                    }
                    $key .= $this-><#= fldParm #>->CurrentValue;
    <#
        } // KeyField
    #>

                    // Add filter for this record
                    $filter = $this->getRecordFilter();
                    if ($wrkfilter != "") {
                        $wrkfilter .= " OR ";
                    }
                    $wrkfilter .= $filter;
                } else {
                    break;
                }
            }
        }

        if ($addcnt == 0) { // No record inserted
            <# if (ctrlId == "list") { #>
            $this->setFailureMessage($Language->phrase("NoAddRecord"));
            $gridInsert = false;
            <# } else { #>
            $this->clearInlineMode(); // Clear grid add mode and return
            return true;
            <# } #>
        }

        if ($gridInsert) {
            <# if (ctrlId == "list") { #>
            $conn->commit(); // Commit transaction
            <# } #>

            // Get new records
            $this->CurrentFilter = $wrkfilter;
            $sql = $this->getCurrentSql();
            $rsnew = $conn->fetchAll($sql);

            <# if (ServerScriptExist("Table", "Grid_Inserted")) { #>
            // Call Grid_Inserted event
            $this->gridInserted($rsnew);
            <# } #>

            <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnAdd) {
                $this->writeAuditTrailDummy($Language->phrase("BatchInsertSuccess")); // Batch insert success
            }
            <# } #>

            <# if (ctrlId == "list") { #>
            if ($this->getSuccessMessage() == "") {
                $this->setSuccessMessage($Language->phrase("InsertSuccess")); // Set up insert success message
            }
            <# } #>

            $this->clearInlineMode(); // Clear grid add mode

            <# if (TABLE.TblSendMailOnAdd) { #>
            // Send notify email
            $table = '<#= SingleQuote(TABLE.TblName) #>';
            $subject = $table . " " . $Language->phrase("RecordInserted");
            $action = $Language->phrase("ActionInsertedGridAdd");

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
            $args["rsnew"] = &$rsnew;
            $emailSent = false;
            if ($this->emailSending($email, $args)) {
                $emailSent = $email->send();
            }
            <# } else { #>
            $emailSent = $email->send();
            <# } #>

            if (!$emailSent) {
                $this->setFailureMessage($email->SendErrDescription);
            }

            <# } #>
        } else {
            <# if (ctrlId == "list") { #>
            $conn->rollback(); // Rollback transaction
            <# } #>

            <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnAdd) {
                $this->writeAuditTrailDummy($Language->phrase("BatchInsertRollback")); // Batch insert rollback
            }
            <# } #>

            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("InsertFailed")); // Set insert failed message
            }
        }

        return $gridInsert;
    }

<# } #>

<# if (gridAddOrEdit) { #>

    // Check if empty row
    public function emptyRow()
    {
        global $CurrentForm;
    <#
        for (let f of currentFields) {
            let fldParm = f.FldParm, fldVar = f.FldVar,
            oldFldVar = "o_" + fldParm;
            // Skip AutoIncrement fields, AutoUpdate fields and date fields with default value
            if (!f.FldAutoIncrement && IsEmpty(f.FldAutoUpdateValue)) {
                if (f.FldHtmlTag == "FILE") { // P6
    #>
        if (!EmptyValue($this-><#= fldParm #>->Upload->Value)) {
            return false;
        }
    <#
                } else if (IsBooleanField(TABLE, f)) {
    #>
        if ($CurrentForm->hasValue("<#= fldVar #>") && $CurrentForm->hasValue("<#= oldFldVar #>") && ConvertToBool($this-><#= fldParm #>->CurrentValue) != ConvertToBool($this-><#= fldParm #>->OldValue)) {
            return false;
        }
    <#
                } else {
    #>
        if ($CurrentForm->hasValue("<#= fldVar #>") && $CurrentForm->hasValue("<#= oldFldVar #>") && $this-><#= fldParm #>->CurrentValue != $this-><#= fldParm #>->OldValue) {
            return false;
        }
    <#
                }
            }
        } // Field
    #>
        return true;
    }

    // Validate grid form
    public function validateGridForm()
    {
        global $CurrentForm;
        // Get row count
        $CurrentForm->Index = -1;
        $rowcnt = strval($CurrentForm->getValue($this->FormKeyCountName));
        if ($rowcnt == "" || !is_numeric($rowcnt)) {
            $rowcnt = 0;
        }

        // Validate all records
        for ($rowindex = 1; $rowindex <= $rowcnt; $rowindex++) {
            // Load current row values
            $CurrentForm->Index = $rowindex;

            $rowaction = strval($CurrentForm->getValue($this->FormActionName));
            if ($rowaction != "delete" && $rowaction != "insertdelete") {
                $this->loadFormValues(); // Get form values

                if ($rowaction == "insert" && $this->emptyRow()) {
                    // Ignore
                } elseif (!$this->validateForm()) {
                    return false;
                }
            }
        }
        return true;
    }

    // Get all form values of the grid
    public function getGridFormValues()
    {
        global $CurrentForm;
        // Get row count
        $CurrentForm->Index = -1;
        $rowcnt = strval($CurrentForm->getValue($this->FormKeyCountName));
        if ($rowcnt == "" || !is_numeric($rowcnt)) {
            $rowcnt = 0;
        }
        $rows = [];

        // Loop through all records
        for ($rowindex = 1; $rowindex <= $rowcnt; $rowindex++) {
            // Load current row values
            $CurrentForm->Index = $rowindex;

            $rowaction = strval($CurrentForm->getValue($this->FormActionName));
            if ($rowaction != "delete" && $rowaction != "insertdelete") {
                $this->loadFormValues(); // Get form values

                if ($rowaction == "insert" && $this->emptyRow()) {
                    // Ignore
                } else {
                    $rows[] = $this->getFieldValues("FormValue"); // Return row as array
                }
            }
        }
        return $rows; // Return as array of array
    }

    // Restore form values for current row
    public function restoreCurrentRowFormValues($idx)
    {
        global $CurrentForm;

        // Get row based on current index
        $CurrentForm->Index = $idx;
        $rowaction = strval($CurrentForm->getValue($this->FormActionName));
        $this->loadFormValues(); // Load form values
        // Set up invalid status correctly
        $this->resetFormError();
        if ($rowaction == "insert" && $this->emptyRow()) {
            // Ignore
        } else {
            $this->validateForm();
        }
    }

    // Reset form status
    public function resetFormError()
    {
    <#
        for (let f of currentFields) {
            FIELD = f;
            let fldParm = f.FldParm;
    #>
        $this-><#= fldParm #>->clearErrorMessage();
    <#
        } // Field
    #>
    }

<# } #>

<# if (ctrlId == "list" && (useBasicSearch || useExtendedBasicSearch || useAdvancedSearch)) { #>

    // Get list of filters
    public function getFilterList()
    {
        global $UserProfile;

        // Initialize
        $filterList = "";
        $savedFilterList = "";

        <# if (hasUserTable && !IsEmpty(DB.SecUserProfileFld)) { #>
        // Load server side filters
        if (Config("SEARCH_FILTER_OPTION") == "Server" && isset($UserProfile)) {
            $savedFilterList = $UserProfile->getSearchFilters(CurrentUserName(), "<#= formNameSearch #>");
        }
        <# } #>

    <#
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldParm = f.FldParm, fldName = f.FldName;
    #>
        $filterList = Concat($filterList, $this-><#= fldParm #>->AdvancedSearch->toJson(), ","); // Field <#= fldName #>
    <#
            }
        } // AllField
    #>

        <# if (useBasicSearch) { #>

        if ($this->BasicSearch->Keyword != "") {
            $wrk = "\"" . Config("TABLE_BASIC_SEARCH") . "\":\"" . JsEncode($this->BasicSearch->Keyword) . "\",\"" . Config("TABLE_BASIC_SEARCH_TYPE") . "\":\"" . JsEncode($this->BasicSearch->Type) . "\"";
            $filterList = Concat($filterList, $wrk, ",");
        }

        <# } #>

        // Return filter list in JSON
        if ($filterList != "") {
            $filterList = "\"data\":{" . $filterList . "}";
        }
        if ($savedFilterList != "") {
            $filterList = Concat($filterList, "\"filters\":" . $savedFilterList, ",");
        }
        return ($filterList != "") ? "{" . $filterList . "}" : "null";
    }

    // Process filter list
    protected function processFilterList()
    {
        global $UserProfile;
        if (Post("ajax") == "savefilters") { // Save filter request (Ajax)
            $filters = Post("filters");
            $UserProfile->setSearchFilters(CurrentUserName(), "<#= formNameSearch #>", $filters);
            WriteJson([["success" => true]]); // Success
            return true;
        } elseif (Post("cmd") == "resetfilter") {
            $this->restoreFilterList();
        }
        return false;
    }

    // Restore list of filters
    protected function restoreFilterList()
    {

        // Return if not reset filter
        if (Post("cmd") !== "resetfilter") {
            return false;
        }

        $filter = json_decode(Post("filter"), true);
        $this->Command = "search";

    <#
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldName = f.FldName, fldParm = f.FldParm;
    #>
        // Field <#= fldName #>
        $this-><#= fldParm #>->AdvancedSearch->SearchValue = @$filter["x_<#= fldParm #>"];
        $this-><#= fldParm #>->AdvancedSearch->SearchOperator = @$filter["z_<#= fldParm #>"];
        $this-><#= fldParm #>->AdvancedSearch->SearchCondition = @$filter["v_<#= fldParm #>"];
        $this-><#= fldParm #>->AdvancedSearch->SearchValue2 = @$filter["y_<#= fldParm #>"];
        $this-><#= fldParm #>->AdvancedSearch->SearchOperator2 = @$filter["w_<#= fldParm #>"];
        $this-><#= fldParm #>->AdvancedSearch->save();
    <#
            }
        } // AllField
    #>

        <# if (useBasicSearch) { #>

        $this->BasicSearch->setKeyword(@$filter[Config("TABLE_BASIC_SEARCH")]);
        $this->BasicSearch->setType(@$filter[Config("TABLE_BASIC_SEARCH_TYPE")]);

        <# } #>
    }

 <# } #>

<# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch)) { #>

    // Advanced search WHERE clause based on QueryString
    protected function advancedSearchWhere($default = false)
    {
        global $Security;
        $where = "";

        <# if (hasUserTable) { #>
        if (!$Security->canSearch()) {
            return "";
        }
        <# } #>

        <#
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldName = f.FldName, fldParm = f.FldParm, multiSelect = Code.false;
                // Multi-Select field
                if (f.FldHtmlTag == "CHECKBOX" && !IsBooleanField(TABLE, f) && GetFieldType(f.FldType) == 3 ||
                    f.FldHtmlTag == "SELECT" && f.FldSelectMultiple)
                    multiSelect = Code.true;
    #>
        $this->buildSearchSql($where, $this-><#= fldParm #>, $default, <#= multiSelect #>); // <#= fldName #>
    <#
            }
        } // AllField
    #>

        // Set up search parm
        if (!$default && $where != "" && in_array($this->Command, ["", "reset", "resetall"])) {
            $this->Command = "search";
        }
        if (!$default && $this->Command == "search") {
        <#
            for (let f of allFields) {
                if ((f.FldSearch || f.FldExtendedBasicSearch) &&
                !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                    let fldName = f.FldName, fldParm = f.FldParm;
        #>
            $this-><#= fldParm #>->AdvancedSearch->save(); // <#= fldName #>
        <#
                }
            } // AllField
        #>
        }

        return $where;
    }

    // Build search SQL
    protected function buildSearchSql(&$where, &$fld, $default, $multiValue)
    {
        $fldParm = $fld->Param;
        $fldVal = ($default) ? $fld->AdvancedSearch->SearchValueDefault : $fld->AdvancedSearch->SearchValue;
        $fldOpr = ($default) ? $fld->AdvancedSearch->SearchOperatorDefault : $fld->AdvancedSearch->SearchOperator;
        $fldCond = ($default) ? $fld->AdvancedSearch->SearchConditionDefault : $fld->AdvancedSearch->SearchCondition;
        $fldVal2 = ($default) ? $fld->AdvancedSearch->SearchValue2Default : $fld->AdvancedSearch->SearchValue2;
        $fldOpr2 = ($default) ? $fld->AdvancedSearch->SearchOperator2Default : $fld->AdvancedSearch->SearchOperator2;
        $wrk = "";
        if (is_array($fldVal)) {
            $fldVal = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $fldVal);
        }
        if (is_array($fldVal2)) {
            $fldVal2 = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $fldVal2);
        }
        $fldOpr = strtoupper(trim($fldOpr));
        if ($fldOpr == "") {
            $fldOpr = "=";
        }
        $fldOpr2 = strtoupper(trim($fldOpr2));
        if ($fldOpr2 == "") {
            $fldOpr2 = "=";
        }

        if (Config("SEARCH_MULTI_VALUE_OPTION") == 1 || !IsMultiSearchOperator($fldOpr)) {
            $multiValue = false;
        }

        if ($multiValue) {
            $wrk1 = ($fldVal != "") ? GetMultiSearchSql($fld, $fldOpr, $fldVal, $this->Dbid) : ""; // Field value 1
            $wrk2 = ($fldVal2 != "") ? GetMultiSearchSql($fld, $fldOpr2, $fldVal2, $this->Dbid) : ""; // Field value 2
            $wrk = $wrk1; // Build final SQL
            if ($wrk2 != "") {
                $wrk = ($wrk != "") ? "($wrk) $fldCond ($wrk2)" : $wrk2;
            }
        } else {
            $fldVal = $this->convertSearchValue($fld, $fldVal);
            $fldVal2 = $this->convertSearchValue($fld, $fldVal2);
            $wrk = GetSearchSql($fld, $fldVal, $fldOpr, $fldCond, $fldVal2, $fldOpr2, $this->Dbid);
        }
        AddFilter($where, $wrk);
    }

    // Convert search value
    protected function convertSearchValue(&$fld, $fldVal)
    {
        if ($fldVal == Config("NULL_VALUE") || $fldVal == Config("NOT_NULL_VALUE")) {
            return $fldVal;
        }
        $value = $fldVal;
        if ($fld->isBoolean()) {
            if ($fldVal != "") {
                $value = (SameText($fldVal, "1") || SameText($fldVal, "y") || SameText($fldVal, "t")) ? $fld->TrueValue : $fld->FalseValue;
            }
        } elseif ($fld->DataType == DATATYPE_DATE || $fld->DataType == DATATYPE_TIME) {
            if ($fldVal != "") {
                $value = UnFormatDateTime($fldVal, $fld->DateTimeFormat);
            }
        }
        return $value;
    }

<# } #>

<# if (ctrlId == "list" && useBasicSearch) { #>

    // Return basic search SQL
    protected function basicSearchSql($arKeywords, $type)
    {
        $where = "";

    <#
        for (let f of allFields) {
            if (f.FldBasicSearch) {
                let fldParm = f.FldParm;
    #>
        $this->buildBasicSearchSql($where, $this-><#= fldParm #>, $arKeywords, $type);
    <#
            }
        } // AllField
    #>

        return $where;
    }

    // Build basic search SQL
    protected function buildBasicSearchSql(&$where, &$fld, $arKeywords, $type)
    {
        $defCond = ($type == "OR") ? "OR" : "AND";
        $arSql = []; // Array for SQL parts
        $arCond = []; // Array for search conditions
        $cnt = count($arKeywords);
        $j = 0; // Number of SQL parts
        for ($i = 0; $i < $cnt; $i++) {
            $keyword = $arKeywords[$i];
            $keyword = trim($keyword);
            if (Config("BASIC_SEARCH_IGNORE_PATTERN") != "") {
                $keyword = preg_replace(Config("BASIC_SEARCH_IGNORE_PATTERN"), "\\", $keyword);
                $ar = explode("\\", $keyword);
            } else {
                $ar = [$keyword];
            }
            foreach ($ar as $keyword) {
                if ($keyword != "") {
                    $wrk = "";
                    if ($keyword == "OR" && $type == "") {
                        if ($j > 0) {
                            $arCond[$j - 1] = "OR";
                        }
                    } elseif ($keyword == Config("NULL_VALUE")) {
                        $wrk = $fld->Expression . " IS NULL";
                    } elseif ($keyword == Config("NOT_NULL_VALUE")) {
                        $wrk = $fld->Expression . " IS NOT NULL";
                    } elseif ($fld->IsVirtual && $fld->Visible) {
                        $wrk = $fld->VirtualExpression . Like(QuotedValue("%" . $keyword . "%", DATATYPE_STRING, $this->Dbid), $this->Dbid);
                    } elseif ($fld->DataType != DATATYPE_NUMBER || is_numeric($keyword)) {
                        $wrk = $fld->BasicSearchExpression . Like(QuotedValue("%" . $keyword . "%", DATATYPE_STRING, $this->Dbid), $this->Dbid);
                    }
                    if ($wrk != "") {
                        $arSql[$j] = $wrk;
                        $arCond[$j] = $defCond;
                        $j += 1;
                    }
                }
            }
        }
        $cnt = count($arSql);
        $quoted = false;
        $sql = "";
        if ($cnt > 0) {
            for ($i = 0; $i < $cnt - 1; $i++) {
                if ($arCond[$i] == "OR") {
                    if (!$quoted) {
                        $sql .= "(";
                    }
                    $quoted = true;
                }
                $sql .= $arSql[$i];
                if ($quoted && $arCond[$i] != "OR") {
                    $sql .= ")";
                    $quoted = false;
                }
                $sql .= " " . $arCond[$i] . " ";
            }
            $sql .= $arSql[$cnt - 1];
            if ($quoted) {
                $sql .= ")";
            }
        }
        if ($sql != "") {
            if ($where != "") {
                $where .= " OR ";
            }
            $where .= "(" . $sql . ")";
        }
    }


    // Return basic search WHERE clause based on search keyword and type
    protected function basicSearchWhere($default = false)
    {
        global $Security;
        $searchStr = "";

        <# if (hasUserTable) { #>
        if (!$Security->canSearch()) {
            return "";
        }
        <# } #>

        $searchKeyword = ($default) ? $this->BasicSearch->KeywordDefault : $this->BasicSearch->Keyword;
        $searchType = ($default) ? $this->BasicSearch->TypeDefault : $this->BasicSearch->Type;

        // Get search SQL
        if ($searchKeyword != "") {
            $ar = $this->BasicSearch->keywordList($default);
            // Search keyword in any fields
            if (($searchType == "OR" || $searchType == "AND") && $this->BasicSearch->BasicSearchAnyFields) {
                foreach ($ar as $keyword) {
                    if ($keyword != "") {
                        if ($searchStr != "") {
                            $searchStr .= " " . $searchType . " ";
                        }
                        $searchStr .= "(" . $this->basicSearchSql([$keyword], $searchType) . ")";
                    }
                }
            } else {
                $searchStr = $this->basicSearchSql($ar, $searchType);
            }
            if (!$default && in_array($this->Command, ["", "reset", "resetall"])) {
                $this->Command = "search";
            }
        }

        if (!$default && $this->Command == "search") {
            $this->BasicSearch->setKeyword($searchKeyword);
            $this->BasicSearch->setType($searchType);
        }

        return $searchStr;
    }

<# } #>

<# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch || useBasicSearch)) { #>

    // Check if search parm exists
    protected function checkSearchParms()
    {
    <# if (ctrlId == "list" && useBasicSearch) { #>
        // Check basic search
        if ($this->BasicSearch->issetSession()) {
            return true;
        }
    <# } #>

    <#
        if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch)) {
            for (let f of allFields) {
                if (
                    (f.FldSearch || f.FldExtendedBasicSearch) &&
                    !(f.FldHtmlTag == "FILE" && IsBinaryField(f))
                ) {
                    let fldParm = f.FldParm, fldObj = "this->" + fldParm;
    #>
        if ($<#= fldObj #>->AdvancedSearch->issetSession()) {
            return true;
        }
    <#
                }
            } // AllField
        }
    #>

        return false;
    }

    // Clear all search parameters
    protected function resetSearchParms()
    {
        // Clear search WHERE clause
        $this->SearchWhere = "";
        $this->setSearchWhere($this->SearchWhere);

        <# if (ctrlId == "list" && useBasicSearch) { #>
        // Clear basic search parameters
        $this->resetBasicSearchParms();
        <# } #>

        <# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch)) { #>
        // Clear advanced search parameters
        $this->resetAdvancedSearchParms();
        <# } #>
    }

    // Load advanced search default values
    protected function loadAdvancedSearchDefault()
    {
        <#
        let gencnt = 0;
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) && (!IsEmpty(f.FldSearchDefault) || !IsEmpty(f.FldSearchDefault2)) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldParm = f.FldParm;
    #>
                $this-><#= fldParm #>->AdvancedSearch->loadDefault();
                <#
                gencnt += 1;
            }
        } // AllField
    #>

        <# if (gencnt > 0) { #>
        return true;
        <# } else { #>
        return false;
        <# } #>
    }

<# } #>

<# if (ctrlId == "list" && useBasicSearch) { #>

    // Clear all basic search parameters
    protected function resetBasicSearchParms()
    {
        $this->BasicSearch->unsetSession();
    }

    <# } #>

    <# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch)) { #>

    // Clear all advanced search parameters
    protected function resetAdvancedSearchParms()
    {
        <#
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldParm = f.FldParm, fldObj = "this->" + fldParm;
    #>
                $<#= fldObj #>->AdvancedSearch->unsetSession();
                <#
            }
        } // AllField
    #>
    }

<# } #>

<# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch || useBasicSearch)) { #>

    // Restore all search parameters
    protected function restoreSearchParms()
    {

        $this->RestoreSearch = true;

        <# if (ctrlId == "list" && useBasicSearch) { #>
        // Restore basic search values
        $this->BasicSearch->load();
        <# } #>

        <# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch)) { #>
        // Restore advanced search values
        <#
        for (let f of allFields) {
            if ((f.FldSearch || f.FldExtendedBasicSearch) &&
            !(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                let fldParm = f.FldParm;
        #>
                $this-><#= fldParm #>->AdvancedSearch->load();
                <#
            }
        } // AllField
        #>
        <# } #>
    }

<# } #>

    // Set up sort parameters
    protected function setupSortOrder()
    {

        <# if (sortType == 2) { #>
        // Check for Ctrl pressed
        $ctrl = Get("ctrl") !== null;
        <# } #>

        // Check for "order" parameter
        if (Get("order") !== null) {
            $this->CurrentOrder = Get("order");
            $this->CurrentOrderType = Get("ordertype", "");
        <#
            for (let f of currentFields) {
                if (!IsBinaryField(f)) {
                    let fldName = f.FldName, fldParm = f.FldParm;
                    if (sortType == 1) { // Single column Sort
        #>
            $this->updateSort($this-><#= fldParm #>); // <#= fldName #>
        <#
                    } else if (sortType == 2) { // Multi Column Sort
        #>
            $this->updateSort($this-><#= fldParm #>, $ctrl); // <#= fldName #>
        <#
                    }
                }
            } // Field
        #>
            $this->setStartRecordNumber(1); // Reset start position
        }
    }

    // Load sort order parameters
    protected function loadSortOrder()
    {
        $orderBy = $this->getSessionOrderBy(); // Get ORDER BY from Session
        if ($orderBy == "") {
            $this->DefaultSort = "<#= Code.quote(defaultOrderBy) #>";
            if ($this->getSqlOrderBy() != "") {
                $useDefaultSort = true;
            <#
                let fldSorts = {};
                if (!IsEmpty(defaultOrderBy)) {
                        let orderByFlds = defaultOrderBy.split(",");
                    for (let orderByFld of orderByFlds) {
                        let fldExpr = orderByFld.trim(), sort = "ASC";
                        if (fldExpr.toUpperCase().endsWith(" ASC")) {
                            sort = "ASC";
                            fldExpr = fldExpr.substr(0, fldExpr.length - 3).trim();
                        } else if (fldExpr.toUpperCase().endsWith(" DESC")) {
                            sort = "DESC";
                            fldExpr = fldExpr.substr(0, fldExpr.length - 4).trim();
                        }
                        for (let f of allFields) {
                            let fld = FieldSqlName(f, tblDbId), fldParm = f.FldParm;
                            if (!IsBinaryField(f) && fld == fldExpr) {
                                fldSorts[fldParm] = sort;
            #>
                if ($this-><#= fldParm #>->getSort() != "") {
                    $useDefaultSort = false;
                }
            <#
                                break;
                            }
                        } // Field
                    } // OrderField
                }
            #>
                if ($useDefaultSort) {
            <#
                for (let fldParm in fldSorts) {
            #>
                    $this-><#= fldParm #>->setSort("<#= fldSorts[fldParm] #>");
            <#
                }
            #>
                    $orderBy = $this->getSqlOrderBy();
                    $this->setSessionOrderBy($orderBy);
                } else {
                    $this->setSessionOrderBy("");
                }
            }
        }
    }

    // Reset command
    // - cmd=reset (Reset search parameters)
    // - cmd=resetall (Reset search and master/detail parameters)
    // - cmd=resetsort (Reset sort parameters)
    protected function resetCmd()
    {

        // Check if reset command
        if (StartsString("reset", $this->Command)) {
            <# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch || useBasicSearch)) { #>
            // Reset search criteria
            if ($this->Command == "reset" || $this->Command == "resetall") {
                $this->resetSearchParms();
            }
            <# } #>

            <# if (masterTables.length > 0) { #>
            // Reset master/detail keys
            if ($this->Command == "resetall") {
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
                    } // MasterDetailField
                } // MasterDetail
            #>
            }
            <# } #>

            // Reset (clear) sorting order
            if ($this->Command == "resetsort") {
                $orderBy = "";
                $this->setSessionOrderBy($orderBy);
                <# if (useVirtualLookup) { #>
                $this->setSessionOrderByList($orderBy);
                <# } #>
                <# if (sortType > 0) { #>
                <#
                for (let f of allFields) {
                    if (!IsBinaryField(f)) {
                        let fldParm = f.FldParm;
                #>
                $this-><#= fldParm #>->setSort("");
                <#
                    }
                } // Field
                #>
                <# } #>
            }

            // Reset start position
            $this->StartRecord = 1;
            $this->setStartRecordNumber($this->StartRecord);
        }
    }

<#
    // Set up view link visibility
    let viewVisible = SecurityCheck("View", isSecurityEnabled, isSecurityEnabled);
    if (viewVisible == "") {
        viewVisible = Code.true;
    }

    // Set up edit link visibility
    let editVisible = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled);
    if (editVisible == "") {
        editVisible = Code.true;
    }

    // Set up copy link visibility
    let copyVisible = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled);
    if (copyVisible == "") {
        copyVisible = Code.true;
    }

    // Set up delete link visibility
    let deleteVisible = SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled),
    deleteConfirm = "";
    if (deleteVisible == "") {
        deleteVisible = Code.true;
    }
    if (inlineDelete) {
        deleteConfirm = ` onclick="return ew.confirmDelete(this);"`;
    }

    // Set up user permission visibility
        let userPermissionVisible = "$Security->isAdmin()";

    // Set up multi checkbox visibility
        let checkboxVisible = Code.false;
        if (exportSelectedOnly) {
            checkboxVisible = Code.true;
        } else {
            if (TABLE.TblDelete && multiDelete) {
                checkboxVisible = isSecurityEnabled ? SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled) : Code.true;
            }
            if (multiUpdate) {
                let wrkVisible = isSecurityEnabled ? SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled) : Code.true;
                checkboxVisible = checkboxVisible == Code.false ? wrkVisible : BuildCondition(checkboxVisible, "||", wrkVisible);
            }
        }
        if (checkboxVisible == "") {
            checkboxVisible = Code.true;
        }
#>

    // Set up list options
    protected function setupListOptions()
    {
        global $Security, $Language;

        <# if (ctrlId == "grid" || ctrlId == "list" && gridAddOrEdit) { #>

        // "griddelete"
        if ($this->AllowAddDeleteRow) {
            $item = &$this->ListOptions->add("griddelete");
            $item->CssClass = "text-nowrap";
            $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
            $item->Visible = false; // Default hidden
        }

        <# } #>

        // Add group option item
        $item = &$this->ListOptions->add($this->ListOptions->GroupOptionName);
        $item->Body = "";
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        $item->Visible = false;

        <# if (TABLE.TblView) { #>
        // "view"
        $item = &$this->ListOptions->add("view");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= viewVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        <# } #>

        <# if (ctrlId == "list" && (TABLE.TblEdit || inlineEdit) || ctrlId == "grid" && TABLE.TblEdit) { #>
        // "edit"
        $item = &$this->ListOptions->add("edit");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= editVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        <# } #>

        <# if (ctrlId == "list" && (TABLE.TblCopy && TABLE.TblAdd || inlineCopy || inlineAdd) || ctrlId == "grid" && TABLE.TblCopy && TABLE.TblAdd) { #>
        <#
        if (ctrlId == "list" && !TABLE.TblCopy && !inlineCopy) {
            if (copyVisible == Code.true) {
                copyVisible = "";
            } else {
                copyVisible += " && ";
            }
            copyVisible += "$this->isAdd()";
        }
    #>
        // "copy"
        $item = &$this->ListOptions->add("copy");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= copyVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        <# } #>

        <# if (TABLE.TblDelete && !multiDelete) { #>
        // "delete"
        $item = &$this->ListOptions->add("delete");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= deleteVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        <# } #>

        <# if (ctrlId == "list") { #>

        <# if (detailTables.length > 0) { #>

        <# // Detail links
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar,
                detailPageObj = GetPageObject("grid", detailTable),
                detailTblName = detailTable.TblName,
                detailVisible = SecurityCheck("Detail", isSecurityEnabled, isSecurityEnabled, detailTable),
                detailPrefix = (detailTable.TblType == "REPORT") ? "detailreport" : "detail";
            if (detailVisible == "") {
                detailVisible = Code.true;
            }
        #>
        // "<#= detailPrefix #>_<#= detailTblVar #>"
        $item = &$this->ListOptions->add("<#= detailPrefix #>_<#= detailTblVar #>");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= detailVisible #> && !$this->ShowMultipleDetails;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        $item->ShowInButtonGroup = false;
        <#
        } // MasterDetail
        #>
        // Multiple details
        if ($this->ShowMultipleDetails) {
            $item = &$this->ListOptions->add("details");
            $item->CssClass = "text-nowrap";
            $item->Visible = $this->ShowMultipleDetails;
            $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
            $item->ShowInButtonGroup = false;
        }

        // Set up detail pages
        $pages = new SubPages();
        <#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar;
            if (detailTable.TblType != "REPORT") {
        #>
        $pages->add("<#= Quote(detailTblVar) #>");
        <#
            }
        }
        #>
        $this->DetailPages = $pages;

        <# } #>

        <# if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl) { #>
        // "userpermission"
        $item = &$this->ListOptions->add("userpermission");
        $item->CssClass = "text-nowrap";
        $item->Visible = <#= userPermissionVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        $item->ButtonGroupName = "userpermission"; // Use own group
        <# } #>

        // List actions
        $item = &$this->ListOptions->add("listactions");
        $item->CssClass = "text-nowrap";
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        $item->Visible = false;
        $item->ShowInButtonGroup = false;
        $item->ShowInDropDown = false;

        // "checkbox"
        $item = &$this->ListOptions->add("checkbox");
        $item->Visible = <#= checkboxVisible #>;
        $item->OnLeft = <#= Code.bool(linkOnLeft) #>;
        $item->Header = "<div class=\"custom-control custom-checkbox d-inline-block\"><input type=\"checkbox\" name=\"key\" id=\"key\" class=\"custom-control-input\" onclick=\"ew.selectAllKey(this);\"><label class=\"custom-control-label\" for=\"key\"></label></div>";
        <# if (linkOnLeft) { #>
        $item->moveTo(0);
        <# } #>
        $item->ShowInDropDown = false;
        $item->ShowInButtonGroup = false;

        <# } #>

        <# if (TABLE.TblDisplayRowCount) { #>
        // "sequence"
        $item = &$this->ListOptions->add("sequence");
        $item->CssClass = "text-nowrap";
        $item->Visible = true;
        $item->OnLeft = true; // Always on left
        $item->ShowInDropDown = false;
        $item->ShowInButtonGroup = false;
        <# } #>

        // Drop down button for ListOptions
        $this->ListOptions->UseDropDownButton = <#= Code.bool(useDropDownForListOptions) #>;
        $this->ListOptions->DropDownButtonPhrase = $Language->phrase("ButtonListOptions");
        <# if (ctrlId == "list") { #>
        $this->ListOptions->UseButtonGroup = <#= Code.bool(useButtonsForLinks) #>;
        <# } else { #>
        $this->ListOptions->UseButtonGroup = false;
        <# } #>
        <# if (PROJ.UseDropdownForMobile) { #>
        if ($this->ListOptions->UseButtonGroup && IsMobile()) {
            $this->ListOptions->UseDropDownButton = true;
        }
        <# } #>
        //$this->ListOptions->ButtonClass = ""; // Class for button group

        <# if (ServerScriptExist("Table", "ListOptions_Load")) { #>
        // Call ListOptions_Load event
        $this->listOptionsLoad();
        <# } #>

        <# if (ctrlId == "list") { #>
        $this->setupListOptionsExt();
        <# } #>

        $item = $this->ListOptions[$this->ListOptions->GroupOptionName];
        $item->Visible = $this->ListOptions->groupOptionVisible();
    }

<#
    // Set up view link visibility
    viewVisible = SecurityCheck("View", isSecurityEnabled, isSecurityEnabled);
    if (hasUserIdFld) {
        viewVisible = BuildCondition(viewVisible, "&&", '$this->showOptionLink("view")');
    }
    global.masterViewVisible = viewVisible;
    if (viewVisible == "") {
        viewVisible = Code.true;
    }

    // Set up edit link visibility
    editVisible = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled);
    if (hasUserIdFld) {
        editVisible = BuildCondition(editVisible, "&&", '$this->showOptionLink("edit")');
    }
    global.masterEditVisible = editVisible;
    //editVisible = BuildCondition(editVisible, "&&", "$oListOpt->Visible");
    if (editVisible == "") {
        editVisible = Code.true;
    }

    // Set up copy link visibility
    copyVisible = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled);
    if (hasUserIdFld) {
        copyVisible = BuildCondition(copyVisible, "&&", '$this->showOptionLink("add")');
    }
    global.masterCopyVisible = copyVisible;
    //copyVisible = BuildCondition(copyVisible, "&&", "$oListOpt->Visible");
    if (copyVisible == "") {
        copyVisible = Code.true;
    }

    // Set up delete link visibility
    deleteVisible = SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled);
    if (hasUserIdFld) {
        deleteVisible = BuildCondition(deleteVisible, "&&", '$this->showOptionLink("delete")');
    }
    //deleteVisible = BuildCondition(deleteVisible, "&&", "$oListOpt->Visible");
    if (deleteVisible == "") {
        deleteVisible = Code.true;
    }

    // Set up user permission visibility
    userPermissionVisible = "$Security->isAdmin()";

    // Set up multi checkbox visibility
    if (exportSelectedOnly) {
        checkboxVisible = "";
    } else {
        checkboxVisible = "";
        if (TABLE.TblDelete && multiDelete) {
            let wrkVisible = SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled);
            if (hasUserIdFld) {
                wrkVisible = BuildCondition(wrkVisible, "&&", '$this->showOptionLink("delete")');
            }
            checkboxVisible = BuildCondition(checkboxVisible, "||", wrkVisible);
        }
        if (multiUpdate) {
            let wrkVisible = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled);
            if (hasUserIdFld) {
                wrkVisible = BuildCondition(wrkVisible, "&&", '$this->showOptionLink("edit")');
            }
            checkboxVisible = BuildCondition(checkboxVisible, "||", wrkVisible);
        }
    }
    //checkboxVisible = BuildCondition(checkboxVisible, "&&", "$oListOpt->Visible");
    if (checkboxVisible == "") {
        checkboxVisible = Code.false;
    }
#>

    // Render list options
    public function renderListOptions()
    {
        global $Security, $Language, $CurrentForm;

        $this->ListOptions->loadDefault();

        <# if (ServerScriptExist("Table", "ListOptions_Rendering")) { #>
        // Call ListOptions_Rendering event
        $this->listOptionsRendering();
        <# } #>

        <# if (ctrlId == "grid" || ctrlId == "list" && listAddOrEdit) { #>

        // Set up row action and key
        if ($CurrentForm && is_numeric($this->RowIndex) && $this->RowType != "view") {
            $CurrentForm->Index = $this->RowIndex;
            $actionName = str_replace("k_", "k" . $this->RowIndex . "_", $this->FormActionName);
            $oldKeyName = str_replace("k_", "k" . $this->RowIndex . "_", $this->OldKeyName);
            $blankRowName = str_replace("k_", "k" . $this->RowIndex . "_", $this->FormBlankRowName);
            if ($this->RowAction != "") {
                $this->MultiSelectKey .= "<input type=\"hidden\" name=\"" . $actionName . "\" id=\"" . $actionName . "\" value=\"" . $this->RowAction . "\">";
            }

            $oldKey = $this->getKey(false); // Get from OldValue
            if ($oldKeyName != "" && $oldKey != "") {
                $this->MultiSelectKey .= "<input type=\"hidden\" name=\"" . $oldKeyName . "\" id=\"" . $oldKeyName . "\" value=\"" . HtmlEncode($oldKey) . "\">";
            }

            if ($this->RowAction == "insert" && $this->isConfirm() && $this->emptyRow()) {
                $this->MultiSelectKey .= "<input type=\"hidden\" name=\"" . $blankRowName . "\" id=\"" . $blankRowName . "\" value=\"1\">";
            }
        }

        <# } #>

        <# if (ctrlId == "grid" || ctrlId == "list" && gridAddOrEdit) { #>

        // "delete"
        if ($this->AllowAddDeleteRow) {
            <# if (ctrlId == "list") { #>
            if ($this->isGridAdd() || $this->isGridEdit()) {
            <# } else { #>
            if ($this->CurrentMode == "add" || $this->CurrentMode == "copy" || $this->CurrentMode == "edit") {
            <# } #>
                $options = &$this->ListOptions;
                $options->UseButtonGroup = true; // Use button group for grid delete button
                $opt = $options["griddelete"];
                <#
                if (!TABLE.TblDelete || hasUserTable) {
                    let cond = TABLE.TblDelete ? "!$Security->canDelete() && " : "";
                #>
                if (<#= cond #>is_numeric($this->RowIndex) && ($this->RowAction == "" || $this->RowAction == "edit")) { // Do not allow delete existing record
                    $opt->Body = "&nbsp;";
                } else {
                    $opt->Body = "<a class=\"ew-grid-link ew-grid-delete\" title=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" onclick=\"return ew.deleteGridRow(this, " . $this->RowIndex . ");\">" . <#= deleteLinkCaption #> . "</a>";
                }
                <# } else { #>
                $opt->Body = "<a class=\"ew-grid-link ew-grid-delete\" title=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" onclick=\"return ew.deleteGridRow(this, " . $this->RowIndex . ");\">" . <#= deleteLinkCaption #> . "</a>";
                <# } #>
            }
        }

        <# } #>

    <# if (TABLE.TblDisplayRowCount) { #>
        // "sequence"
        $opt = $this->ListOptions["sequence"];
        $opt->Body = FormatSequenceNumber($this->RecordCount);
    <# } #>

    <# if (ctrlId == "list") { #>

        $pageUrl = $this->pageUrl();

        <# if (inlineAdd || inlineCopy) { #>
        // "copy"
        $opt = $this->ListOptions["copy"];
        if ($this->isInlineAddRow() || $this->isInlineCopyRow()) { // Inline Add/Copy
            $this->ListOptions->CustomItem = "copy"; // Show copy column only
            $cancelurl = $this->addMasterUrl($pageUrl . "action=cancel");
            $opt->Body = "<div" . (($opt->OnLeft) ? " class=\"text-right\"" : "") . ">" .
            "<a class=\"ew-grid-link ew-inline-insert\" title=\"" . HtmlTitle(<#= insertLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= insertLinkCaption #>) . "\" href=\"#\" onclick=\"<#= listFormSubmit #>\">" . <#= insertLinkCaption #> . "</a>&nbsp;" .
            "<a class=\"ew-grid-link ew-inline-cancel\" title=\"" . HtmlTitle(<#= cancelLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= cancelLinkCaption #>) . "\" href=\"" . $cancelurl . "\">" . <#= cancelLinkCaption #> . "</a>" .
            "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"insert\"></div>";
            return;
        }
        <# } #>

        <# if (inlineEdit) { #>
        // "edit"
        $opt = $this->ListOptions["edit"];
        if ($this->isInlineEditRow()) { // Inline-Edit
            $this->ListOptions->CustomItem = "edit"; // Show edit column only
            $cancelurl = $this->addMasterUrl($pageUrl . "action=cancel");
            <# if (checkConcurrentUpdate) { #>
            if ($this->UpdateConflict == "U") {
                $opt->Body = "<div" . (($opt->OnLeft) ? " class=\"text-right\"" : "") . ">" .
                "<a class=\"ew-grid-link ew-inline-reload\" title=\"" . HtmlTitle(<#= reloadLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= reloadLinkCaption #>) . "\" href=\"" . HtmlEncode(UrlAddHash(GetUrl($this->InlineEditUrl), "r" . $this->RowCount . "_" . $this->TableVar)) . "\">" .
                <#= reloadLinkCaption #> . "</a>&nbsp;" .
                "<a class=\"ew-grid-link ew-inline-overwrite\" title=\"" . HtmlTitle(<#= overwriteLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= overwriteLinkCaption #>) . "\" href=\"#\" onclick=\"<#= listFormInlineSubmit #>\">" . <#= overwriteLinkCaption #> . "</a>&nbsp;" .
                "<a class=\"ew-grid-link ew-inline-cancel\" title=\"" . HtmlTitle(<#= conflictCancelLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= conflictCancelLinkCaption #>) . "\" href=\"" . $cancelurl . "\">" . <#= conflictCancelLinkCaption #> . "</a>" .
                "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"overwrite\"></div>";
            } else {
                <# } #>
                $opt->Body = "<div" . (($opt->OnLeft) ? " class=\"text-right\"" : "") . ">" .
                "<a class=\"ew-grid-link ew-inline-update\" title=\"" . HtmlTitle(<#= updateLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= updateLinkCaption #>) . "\" href=\"#\" onclick=\"<#= listFormInlineSubmit #>\">" . <#= updateLinkCaption #> . "</a>&nbsp;" .
                "<a class=\"ew-grid-link ew-inline-cancel\" title=\"" . HtmlTitle(<#= cancelLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= cancelLinkCaption #>) . "\" href=\"" . $cancelurl . "\">" . <#= cancelLinkCaption #> . "</a>" .
                "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"update\"></div>";
                <# if (checkConcurrentUpdate) { #>
            }
            $opt->Body .= "<input type=\"hidden\" name=\"k" . $this->RowIndex . "_hash\" id=\"k" . $this->RowIndex . "_hash\" value=\"" . $this->HashValue . "\">";
            <# } #>
            $opt->Body .= "<input type=\"hidden\" name=\"k" . $this->RowIndex . "_key\" id=\"k" . $this->RowIndex . "_key\" value=\"" . HtmlEncode(<#= multiSelectKey #>) . "\">";
            return;
        }
        <# } #>

        <# } #>

        if ($this->CurrentMode == "view") { // View mode

            <# if (TABLE.TblView) { #>
            // "view"
            $opt = $this->ListOptions["view"];
            $viewcaption = HtmlTitle(<#= viewLinkCaption #>);
            if (<#= viewVisible #>) {
            <# if (useModalView) { #>
                if (IsMobile()) {
                    $opt->Body = "<a class=\"ew-row-link ew-view\" title=\"" . $viewcaption . "\" data-caption=\"" . $viewcaption . "\" href=\"" . HtmlEncode(GetUrl($this->ViewUrl)) . "\">" . <#= viewLinkCaption #> . "</a>";
                } else {
                    $opt->Body = "<a class=\"ew-row-link ew-view\" title=\"" . $viewcaption . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $viewcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,url:'" . HtmlEncode(GetUrl($this->ViewUrl)) . "',btn:null});\">" . <#= viewLinkCaption #> . "</a>";
                }
            <# } else { #>
                $opt->Body = "<a class=\"ew-row-link ew-view\" title=\"" . $viewcaption . "\" data-caption=\"" . $viewcaption . "\" href=\"" . HtmlEncode(GetUrl($this->ViewUrl)) . "\">" . <#= viewLinkCaption #> . "</a>";
            <# } #>
            } else {
                $opt->Body = "";
            }
            <# } #>

            <# if (ctrlId == "list" && (TABLE.TblEdit || inlineEdit) || ctrlId == "grid" && TABLE.TblEdit) { #>
            // "edit"
            $opt = $this->ListOptions["edit"];
            $editcaption = HtmlTitle(<#= editLinkCaption #>);
            if (<#= editVisible #>) {
            <# if (TABLE.TblEdit) { #>
            <# if (useModalEdit) { #>
                if (IsMobile()) {
                    $opt->Body = "<a class=\"ew-row-link ew-edit\" title=\"" . $editcaption . "\" data-caption=\"" . $editcaption . "\" href=\"" . HtmlEncode(GetUrl($this->EditUrl)) . "\">" . <#= editLinkCaption #> . "</a>";
                } else {
                    $opt->Body = "<a class=\"ew-row-link ew-edit\" title=\"" . $editcaption . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $editcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'SaveBtn',url:'" . HtmlEncode(GetUrl($this->EditUrl)) . "'});\">" . <#= editLinkCaption #> . "</a>";
                }
                <# } else { #>
                $opt->Body = "<a class=\"ew-row-link ew-edit\" title=\"" . HtmlTitle(<#= editLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= editLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->EditUrl)) . "\">" . <#= editLinkCaption #> . "</a>";
                <# } #>
            <# } #>
            <# if (ctrlId == "list" && inlineEdit) { #>
                $opt->Body .= "<a class=\"ew-row-link ew-inline-edit\" title=\"" . HtmlTitle(<#= inlineEditLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= inlineEditLinkCaption #>) . "\" href=\"" . HtmlEncode(UrlAddHash(GetUrl($this->InlineEditUrl), "r" . $this->RowCount . "_" . $this->TableVar)) . "\">" . <#= inlineEditLinkCaption #> . "</a>";
            <# } #>
            } else {
                $opt->Body = "";
            }
            <# } #>

            <# if (ctrlId == "list" && (TABLE.TblCopy && TABLE.TblAdd || inlineCopy && inlineAdd) || ctrlId == "grid" && TABLE.TblCopy && TABLE.TblAdd) { #>
            // "copy"
            $opt = $this->ListOptions["copy"];
            $copycaption = HtmlTitle(<#= copyLinkCaption #>);
            if (<#= copyVisible #>) {
            <# if (TABLE.TblCopy && TABLE.TblAdd) { #>
                <# if (useModalAdd) { #>
                if (IsMobile()) {
                    $opt->Body = "<a class=\"ew-row-link ew-copy\" title=\"" . $copycaption . "\" data-caption=\"" . $copycaption . "\" href=\"" . HtmlEncode(GetUrl($this->CopyUrl)) . "\">" . <#= copyLinkCaption #> . "</a>";
                } else {
                    $opt->Body = "<a class=\"ew-row-link ew-copy\" title=\"" . $copycaption . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $copycaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'AddBtn',url:'" . HtmlEncode(GetUrl($this->CopyUrl)) . "'});\">" . <#= copyLinkCaption #> . "</a>";
                }
                <# } else { #>
                $opt->Body = "<a class=\"ew-row-link ew-copy\" title=\"" . $copycaption . "\" data-caption=\"" . $copycaption . "\" href=\"" . HtmlEncode(GetUrl($this->CopyUrl)) . "\">" . <#= copyLinkCaption #> . "</a>";
                <# } #>
            <# } #>
            <# if (ctrlId == "list" && inlineCopy && inlineAdd) { #>
                $opt->Body .= "<a class=\"ew-row-link ew-inline-copy\" title=\"" . HtmlTitle(<#= inlineCopyLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= inlineCopyLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->InlineCopyUrl)) . "\">" . <#= inlineCopyLinkCaption #> . "</a>";
            <# } #>
            } else {
                $opt->Body = "";
            }
            <# } #>

            <# if (TABLE.TblDelete && !multiDelete) { #>
            // "delete"
            $opt = $this->ListOptions["delete"];
            if (<#= deleteVisible #>) {
            $opt->Body = "<a class=\"ew-row-link ew-delete\"" . "<#= Quote(deleteConfirm) #>" . " title=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" data-caption=\"" . HtmlTitle(<#= deleteLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->DeleteUrl)) . "\">" . <#= deleteLinkCaption #> . "</a>";
            } else {
                $opt->Body = "";
            }
            <# } #>

        } // End View mode

    <# if (ctrlId == "list") { #>

        // Set up list action buttons
        $opt = $this->ListOptions["listactions"];
        if ($opt && !$this->isExport() && !$this->CurrentAction) {
            $body = "";
            $links = [];
            foreach ($this->ListActions->Items as $listaction) {
                if ($listaction->Select == ACTION_SINGLE && $listaction->Allow) {
                    $action = $listaction->Action;
                    $caption = $listaction->Caption;
                    $icon = ($listaction->Icon != "") ? "<i class=\"" . HtmlEncode(str_replace(" ew-icon", "", $listaction->Icon)) . "\" data-caption=\"" . HtmlTitle($caption) . "\"></i> " : "";
                    $links[] = "<li><a class=\"dropdown-item ew-action ew-list-action\" data-action=\"" . HtmlEncode($action) . "\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"#\" onclick=\"return ew.submitAction(event,jQuery.extend({key:" . $this->keyToJson(true) . "}," . $listaction->toJson(true) . "));\">" . $icon . $listaction->Caption . "</a></li>";
                    if (count($links) == 1) { // Single button
                        $body = "<a class=\"ew-action ew-list-action\" data-action=\"" . HtmlEncode($action) . "\" title=\"" . HtmlTitle($caption) . "\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"#\" onclick=\"return ew.submitAction(event,jQuery.extend({key:" . $this->keyToJson(true) . "}," . $listaction->toJson(true) . "));\">" . $icon . $listaction->Caption . "</a>";
                    }
                }
            }
            if (count($links) > 1) { // More than one buttons, use dropdown
                $body = "<button class=\"dropdown-toggle btn btn-default ew-actions\" title=\"" . HtmlTitle($Language->phrase("ListActionButton")) . "\" data-toggle=\"dropdown\">" . $Language->phrase("ListActionButton") . "</button>";
                $content = "";
                foreach ($links as $link) {
                    $content .= "<li>" . $link . "</li>";
                }
                $body .= "<ul class=\"dropdown-menu" . ($opt->OnLeft ? "" : " dropdown-menu-right") . "\">" . $content . "</ul>";
                $body = "<div class=\"btn-group btn-group-sm\">" . $body . "</div>";
            }
            if (count($links) > 0) {
                $opt->Body = $body;
                $opt->Visible = true;
            }
        }

        <# if (detailTables.length > 0) { // Detail links #>

        $detailViewTblVar = "";
        $detailCopyTblVar = "";
        $detailEditTblVar = "";
        <#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar,
                detailPageObj = GetPageObject("grid", detailTable),
                detailVisible = SecurityCheck("Detail", isSecurityEnabled, isSecurityEnabled, detailTable),
                detailUrl = detailTable.TblType == "REPORT" ? GetRouteUrl(detailTable.TblReportType, detailTable) : GetRouteUrl("list", detailTable),
                qry = "";
            if (hasUserIdFld) {
                if (!IsEmpty(detailVisible))
                    detailVisible += " && ";
                detailVisible += "$this->showOptionLink()";
            }
            if (detailVisible == "")
                detailVisible = Code.true;
            detailUrl += '?" . Config("TABLE_SHOW_MASTER") . "=' + tblVar;
            for (let rel of md.Relations) {
                let masterField = GetFieldObject(TABLE, rel.MasterField),
                    masterFldParm = masterField.FldParm,
                    masterFldObj = "$this->" + masterFldParm,
                    cv = masterFldObj + "->CurrentValue",
                    suffix = "";
                if (GetFieldType(masterField.FldType) == 2)
                    suffix = ", " + masterField.FldDtFormat;
                qry += `&" . GetForeignKeyUrl("fk_${masterFldParm}", ${cv}${suffix}) . "`;
            } // MasterDetailField
            detailUrl += qry;
            let detailLink = ctrlId == "view" ? viewPageDetailLinkCaption : detailLinkCaption;

            // Set up detail view link visibility
            let detailViewVisible = SecurityCheck("DetailView", isSecurityEnabled, isSecurityEnabled);
            detailViewVisible = BuildCondition(masterViewVisible, "&&", detailViewVisible);
            if (!IsEmpty(detailViewVisible))
                detailViewVisible = " && " + detailViewVisible;

            // Set up detail edit link visibility
            let detailEditVisible = SecurityCheck("DetailEdit", isSecurityEnabled, isSecurityEnabled);
            detailEditVisible = BuildCondition(masterEditVisible, "&&", detailEditVisible);
            if (!IsEmpty(detailEditVisible))
                detailEditVisible = " && " + detailEditVisible;

            // Set up detail copy link visibility
            let detailCopyVisible = SecurityCheck("DetailAdd", isSecurityEnabled, isSecurityEnabled);
            detailCopyVisible = BuildCondition(masterCopyVisible, "&&", detailCopyVisible);
            if (!IsEmpty(detailCopyVisible))
                detailCopyVisible = " && " + detailCopyVisible;
            let isDetailCopy = isDetailAdd && (TABLE.TblAdd && TABLE.TblCopy);

            let detailPrefix = detailTable.TblType == "REPORT" ? "detailreport" : "detail";
        #>
        // "<#= detailPrefix #>_<#= detailTblVar #>"
        $opt = $this->ListOptions["<#= detailPrefix #>_<#= detailTblVar #>"];
        if (<#= detailVisible #>) {
            $body = <#= detailLink #> . $Language->TablePhrase("<#= detailTblVar #>", "TblCaption");
            <# if (showDetailCount && detailTable.TblType != "REPORT") { #>
            $body .= "&nbsp;" . str_replace("%c", Container("<#= detailTblVar #>")->Count, $Language->phrase("DetailCount"));
            <# } #>
            <# if (detailTable.TblType == "REPORT") { #>
            $body = "<a class=\"btn btn-default ew-row-link\" href=\"" . HtmlEncode("<#= detailUrl #>") . "\">" . $body . "</a>";
            <# } else if (detailPageObj) { #>
            $body = "<a class=\"btn btn-default ew-row-link ew-detail\" data-action=\"list\" href=\"" . HtmlEncode("<#= detailUrl #>") . "\">" . $body . "</a>";
            $links = "";
            $detailPage = Container("<#= detailPageObj #>");
        <# if (TABLE.TblView && isDetailView) { #>
            if ($detailPage->DetailView<#= detailViewVisible #>) {
                $caption = <#= masterDetailViewLinkCaption #>;
                $url = $this->getViewUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>");
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-view\" data-action=\"view\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"" . HtmlEncode($url) . "\">" . HtmlImageAndText($caption) . "</a></li>";
                if ($detailViewTblVar != "") {
                    $detailViewTblVar .= ",";
                }
                $detailViewTblVar .= "<#= detailTblVar #>";
            }
            <# } #>
            <# if (TABLE.TblEdit && isDetailEdit) { #>
            if ($detailPage->DetailEdit<#= detailEditVisible #>) {
                $caption = <#= masterDetailEditLinkCaption #>;
                $url = $this->getEditUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>");
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-edit\" data-action=\"edit\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"" . HtmlEncode($url) . "\">" . HtmlImageAndText($caption) . "</a></li>";
                if ($detailEditTblVar != "") {
                    $detailEditTblVar .= ",";
                }
                $detailEditTblVar .= "<#= detailTblVar #>";
            }
            <# } #>
            <# if (isDetailCopy) { #>
            if ($detailPage->DetailAdd<#= detailCopyVisible #>) {
                $caption = <#= masterDetailCopyLinkCaption #>;
                $url = $this->getCopyUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>");
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-copy\" data-action=\"add\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"" . HtmlEncode($url) . "\">" . HtmlImageAndText($caption) . "</a></li>";
                if ($detailCopyTblVar != "") {
                    $detailCopyTblVar .= ",";
                }
                $detailCopyTblVar .= "<#= detailTblVar #>";
            }
            <# } #>
            if ($links != "") {
                $body .= "<button class=\"dropdown-toggle btn btn-default ew-detail\" data-toggle=\"dropdown\"></button>";
                $body .= "<ul class=\"dropdown-menu\">" . $links . "</ul>";
            }
        <# } #>
            $body = "<div class=\"btn-group btn-group-sm ew-btn-group\">" . $body . "</div>";
            $opt->Body = $body;
            if ($this->ShowMultipleDetails) {
                $opt->Visible = false;
            }
        }
        <#
        } // MasterDetail
        #>
        if ($this->ShowMultipleDetails) {
            $body = "<div class=\"btn-group btn-group-sm ew-btn-group\">";
            $links = "";
            if ($detailViewTblVar != "") {
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-view\" data-action=\"view\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailViewLink")) . "\" href=\"" . HtmlEncode($this->getViewUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailViewTblVar)) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailViewLink")) . "</a></li>";
            }
            if ($detailEditTblVar != "") {
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-edit\" data-action=\"edit\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailEditLink")) . "\" href=\"" . HtmlEncode($this->getEditUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailEditTblVar)) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailEditLink")) . "</a></li>";
            }
            if ($detailCopyTblVar != "") {
                $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-copy\" data-action=\"add\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailCopyLink")) . "\" href=\"" . HtmlEncode($this->GetCopyUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailCopyTblVar)) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailCopyLink")) . "</a></li>";
            }
            if ($links != "") {
                $body .= "<button class=\"dropdown-toggle btn btn-default ew-master-detail\" title=\"" . HtmlTitle($Language->phrase("MultipleMasterDetails")) . "\" data-toggle=\"dropdown\">" . $Language->phrase("MultipleMasterDetails") . "</button>";
                $body .= "<ul class=\"dropdown-menu ew-menu\">" . $links . "</ul>";
            }
            $body .= "</div>";
            // Multiple details
            $opt = $this->ListOptions["details"];
            $opt->Body = $body;
        }

                    <# } #>

                    <#
        if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl) {
            let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
                fldParm = userLevelIdField.FldParm,
                cv = "this->" + fldParm + "->CurrentValue";
        #>
        // "userpermission"
        $opt = $this->ListOptions["userpermission"];
        if ($Security->hasUserLevelID($<#= cv #>) || $<#= cv #> < 0 && $<#= cv #> != -2) {
            $opt->Body = "-";
        } else {
            $opt->Body = "<a class=\"ew-row-link ew-user-permission\" title=\"" . HtmlTitle($Language->phrase("Permission")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("Permission")) . "\" href=\"" . HtmlEncode(<#= userPrivPageQuoted #>) . "\">" . $Language->phrase("Permission") . "</a>";
        }
        <#
        }
        #>

        // "checkbox"
        $opt = $this->ListOptions["checkbox"];
        <#
        if (!IsEmpty(multiSelectKey)) {
            let multiClick = recPerRow < 1 ? ` onclick="ew.clickMultiCheckbox(event);"` : "";
        #>
        $opt->Body = "<div class=\"custom-control custom-checkbox d-inline-block\"><input type=\"checkbox\" id=\"key_m_" . $this->RowCount . "\" name=\"key_m[]\" class=\"custom-control-input ew-multi-select\" value=\"" . HtmlEncode(<#= multiSelectKey #>) . "\"<#= Quote(multiClick) #>><label class=\"custom-control-label\" for=\"key_m_" . $this->RowCount . "\"></label></div>";
        <#
        }
        #>

    <# } #>

    <# if (gridEdit && checkConcurrentUpdate) { #>
        <# if (ctrlId == "grid") { #>
        if ($this->CurrentMode == "edit" && is_numeric($this->RowIndex) && $this->RowAction != "delete") {
        <# } else { #>
        if ($this->isGridEdit() && is_numeric($this->RowIndex)) {
        <# } #>
            $this->MultiSelectKey .= "<input type=\"hidden\" name=\"k" . $this->RowIndex . "_hash\" id=\"k" . $this->RowIndex . "_hash\" value=\"" . $this->HashValue . "\">";
        }
    <# } #>

        $this->renderListOptionsExt();

        <# if (ServerScriptExist("Table", "ListOptions_Rendered")) { #>
        // Call ListOptions_Rendered event
        $this->listOptionsRendered();
        <# } #>
    }

<#
    let addSecChkWrk = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled);
        if (!IsEmpty(addSecChkWrk)) {
            addSecChkWrk = " && " + addSecChkWrk;
        }
#>

    // Set up other options
    protected function setupOtherOptions()
    {
        global $Language, $Security;

        <# if (ctrlId == "grid") { #>

        $option = $this->OtherOptions["addedit"];
        $option->UseDropDownButton = false;
        $option->DropDownButtonPhrase = $Language->phrase("ButtonAddEdit");
        $option->UseButtonGroup = true;
        //$option->ButtonClass = ""; // Class for button group
        $item = &$option->add($option->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;

        <# if (TABLE.TblAdd) { #>
        // Add
        if ($this->CurrentMode == "view") { // Check view mode
            $item = &$option->add("add");
            $addcaption = HtmlTitle($Language->phrase("AddLink"));
            $this->AddUrl = $this->getAddUrl();
            <# if (useModalAdd) { #>
            if (IsMobile()) {
                $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"" . HtmlEncode(GetUrl($this->AddUrl)) . "\">" . $Language->phrase("AddLink") . "</a>";
            } else {
                $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $addcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'AddBtn',url:'" . HtmlEncode(GetUrl($this->AddUrl)) . "'});\">" . $Language->phrase("AddLink") . "</a>";
            }
            <# } else { #>
            $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"" . HtmlEncode(GetUrl($this->AddUrl)) . "\">" . $Language->phrase("AddLink") . "</a>";
            <# } #>
            $item->Visible = $this->AddUrl != ""<#= addSecChkWrk #>;
        }
        <# } #>

        <# } else if (ctrlId == "list") { #>

        $options = &$this->OtherOptions;

        <# if (TABLE.TblAdd || inlineAdd || gridAdd) { #>

        $option = $options["addedit"];

        <# if (TABLE.TblAdd) { #>
        // Add
        $item = &$option->add("add");
        $addcaption = HtmlTitle($Language->phrase("AddLink"));
        <# if (useModalAdd) { #>
        if (IsMobile()) {
            $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"" . HtmlEncode(GetUrl($this->AddUrl)) . "\">" . $Language->phrase("AddLink") . "</a>";
        } else {
            $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $addcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'AddBtn',url:'" . HtmlEncode(GetUrl($this->AddUrl)) . "'});\">" . $Language->phrase("AddLink") . "</a>";
        }
        <# } else { #>
        $item->Body = "<a class=\"ew-add-edit ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"" . HtmlEncode(GetUrl($this->AddUrl)) . "\">" . $Language->phrase("AddLink") . "</a>";
        <# } #>
        $item->Visible = $this->AddUrl != ""<#= addSecChkWrk #>;
        <# } #>

        <# if (inlineAdd) { #>
        // Inline Add
        $item = &$option->add("inlineadd");
        $item->Body = "<a class=\"ew-add-edit ew-inline-add\" title=\"" . HtmlTitle($Language->phrase("InlineAddLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("InlineAddLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->InlineAddUrl)) . "\">" . $Language->phrase("InlineAddLink") . "</a>";
        $item->Visible = $this->InlineAddUrl != ""<#= addSecChkWrk #>;
        <# } #>

        <# if (gridAdd) { #>
        $item = &$option->add("gridadd");
        $item->Body = "<a class=\"ew-add-edit ew-grid-add\" title=\"" . HtmlTitle($Language->phrase("GridAddLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridAddLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->GridAddUrl)) . "\">" . $Language->phrase("GridAddLink") . "</a>";
        $item->Visible = $this->GridAddUrl != ""<#= addSecChkWrk #>;
        <# } #>

        <# if (TABLE.TblAdd && isDetailAdd) { #>
        $option = $options["detail"];
        $detailTableLink = "";
        <#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar,
                detailPageObj = GetPageObject("grid", detailTable);
            if (detailTable.TblType != "REPORT") {
                // Set up add link visibility
                let detailAddVisible = SecurityCheck("DetailAdd", isSecurityEnabled, isSecurityEnabled);
                if (!IsEmpty(detailAddVisible)) {
                    detailAddVisible = " && " + detailAddVisible;
                }
        #>
                $item = &$option->add("detailadd_<#= detailTblVar #>");
                $url = $this->getAddUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>");
                $detailPage = Container("<#= detailPageObj #>");
                $caption = $Language->phrase("Add") . "&nbsp;" . $this->tableCaption() . "/" . $detailPage->tableCaption();
                $item->Body = "<a class=\"ew-detail-add-group ew-detail-add\" title=\"" . HtmlTitle($caption) . "\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"" . HtmlEncode(GetUrl($url)) . "\">" . $caption . "</a>";
                $item->Visible = ($detailPage->DetailAdd<#= detailAddVisible #><#= addSecChkWrk #>);
                if ($item->Visible) {
                    if ($detailTableLink != "") {
                        $detailTableLink .= ",";
                    }
                    $detailTableLink .= "<#= detailTblVar #>";
                }
                <#
            }
        } // MasterDetail
        #>
        // Add multiple details
        if ($this->ShowMultipleDetails) {
            $item = &$option->add("detailsadd");
            $url = $this->getAddUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailTableLink);
            $caption = $Language->phrase("AddMasterDetailLink");
            $item->Body = "<a class=\"ew-detail-add-group ew-detail-add\" title=\"" . HtmlTitle($caption) . "\" data-caption=\"" . HtmlTitle($caption) . "\" href=\"" . HtmlEncode(GetUrl($url)) . "\">" . $caption . "</a>";
            $item->Visible = $detailTableLink != ""<#= addSecChkWrk #>;
            // Hide single master/detail items
            $ar = explode(",", $detailTableLink);
            $cnt = count($ar);
            for ($i = 0; $i < $cnt; $i++) {
                if ($item = $option["detailadd_" . $ar[$i]]) {
                    $item->Visible = false;
                }
            }
        }
        <# } #>

        <# } #>

        <# if (gridEdit) { #>

        <#
        let editSecChkWrk = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled);
        if (!IsEmpty(editSecChkWrk)) {
            editSecChkWrk = " && " + editSecChkWrk;
        }
        #>
        // Add grid edit
        $option = $options["addedit"];
        $item = &$option->add("gridedit");
        $item->Body = "<a class=\"ew-add-edit ew-grid-edit\" title=\"" . HtmlTitle($Language->phrase("GridEditLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridEditLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->GridEditUrl)) . "\">" . $Language->phrase("GridEditLink") . "</a>";
        $item->Visible = $this->GridEditUrl != ""<#= editSecChkWrk #>;

        <# } #>

        $option = $options["action"];

        <# if (TABLE.TblDelete && multiDelete) { #>

        <#
        let deleteJs;
        if (inlineDelete) {
            deleteJs = "return ew.submitAction(event, {f:" + jsFormName + ", url:'\" . GetUrl($this->MultiDeleteUrl) . \"', data:{action:'delete'}, msg:ew.language.phrase('DeleteConfirmMsg')});";
        } else {
            deleteJs = "return ew.submitAction(event, {f:" + jsFormName + ", url:'\" . GetUrl($this->MultiDeleteUrl) . \"', data:{action:'show'}});";
        }
        let deleteSecChkWrk = SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled);
        if (IsEmpty(deleteSecChkWrk)) {
            deleteSecChkWrk = Code.true;
        }
        #>
        // Add multi delete
        $item = &$option->add("multidelete");
        $item->Body = "<a class=\"ew-action ew-multi-delete\" title=\"" . HtmlTitle($Language->phrase("DeleteSelectedLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("DeleteSelectedLink")) . "\" href=\"#\" onclick=\"<#= deleteJs #>return false;\">" . $Language->phrase("DeleteSelectedLink") . "</a>";
        $item->Visible = <#= deleteSecChkWrk #>;

        <# } #>

        <# if (multiUpdate) { #>

        <#
        let updateJs;
        if (useModalUpdate) {
            updateJs = "return ew.modalDialogShow({lnk:this,btn:'UpdateBtn',f:" + jsFormName + ",url:'\" . GetUrl($this->MultiUpdateUrl) . \"'});";
        } else {
            updateJs = "return ew.submitAction(event, {f:" + jsFormName + ",url:'\" . GetUrl($this->MultiUpdateUrl) . \"'});";
        }
        let updateSecChkWrk = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled);
        if (IsEmpty(updateSecChkWrk)) {
            updateSecChkWrk = Code.true;
        }
        #>
        // Add multi update
        $item = &$option->add("multiupdate");
        $item->Body = "<a class=\"ew-action ew-multi-update\" title=\"" . HtmlTitle($Language->phrase("UpdateSelectedLink")) . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . HtmlTitle($Language->phrase("UpdateSelectedLink")) . "\" href=\"#\" onclick=\"<#= updateJs #>return false;\">" . $Language->phrase("UpdateSelectedLink") . "</a>";
        $item->Visible = <#= updateSecChkWrk #>;

        <# } #>

        // Set up options default
        foreach ($options as $option) {
            $option->UseDropDownButton = <#= Code.bool(useDropDownForAction) #>;
            $option->UseButtonGroup = true;
            //$option->ButtonClass = ""; // Class for button group
            $item = &$option->add($option->GroupOptionName);
            $item->Body = "";
            $item->Visible = false;
        }
        $options["addedit"]->DropDownButtonPhrase = $Language->phrase("ButtonAddEdit");
        $options["detail"]->DropDownButtonPhrase = $Language->phrase("ButtonDetails");
        $options["action"]->DropDownButtonPhrase = $Language->phrase("ButtonActions");

        // Filter button
        $item = &$this->FilterOptions->add("savecurrentfilter");
        $item->Body = "<a class=\"ew-save-filter\" data-form=\"<#= formNameSearch #>\" href=\"#\" onclick=\"return false;\">" . $Language->phrase("SaveCurrentFilter") . "</a>";
        $item->Visible = <#= Code.bool(useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) #>;
        $item = &$this->FilterOptions->add("deletefilter");
        $item->Body = "<a class=\"ew-delete-filter\" data-form=\"<#= formNameSearch #>\" href=\"#\" onclick=\"return false;\">" . $Language->phrase("DeleteFilter") . "</a>";
        $item->Visible = <#= Code.bool(useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) #>;
        $this->FilterOptions->UseDropDownButton = true;
        $this->FilterOptions->UseButtonGroup = !$this->FilterOptions->UseDropDownButton;
        $this->FilterOptions->DropDownButtonPhrase = $Language->phrase("Filters");

        // Add group option item
        $item = &$this->FilterOptions->add($this->FilterOptions->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;

        <# } #>
    }

    // Render other options
    public function renderOtherOptions()
    {
        global $Language, $Security;

        $options = &$this->OtherOptions;

        <# if (ctrlId == "grid") { #>

        <#
        if (!TABLE.TblAdd) {
            addSecChkWrk = Code.false;
        } else {
            addSecChkWrk = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled);
            if (IsEmpty(addSecChkWrk)) {
                addSecChkWrk = Code.true;
            }
        }
        #>
        <# if (recPerRow < 1) { // Single Column #>
        if (($this->CurrentMode == "add" || $this->CurrentMode == "copy" || $this->CurrentMode == "edit") && !$this->isConfirm()) { // Check add/copy/edit mode
            if ($this->AllowAddDeleteRow) {
                $option = $options["addedit"];
                $option->UseDropDownButton = false;
                $item = &$option->add("addblankrow");
                $item->Body = "<a class=\"ew-add-edit ew-add-blank-row\" title=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" href=\"#\" onclick=\"return ew.addGridRow(this);\">" . $Language->phrase("AddBlankRow") . "</a>";
                $item->Visible = <#= addSecChkWrk #>;
                $this->ShowOtherOptions = $item->Visible;
            }
        }
        if ($this->CurrentMode == "view") { // Check view mode
            $option = $options["addedit"];
            $item = $option["add"];
            $this->ShowOtherOptions = $item && $item->Visible;
        }
        <# } #>

        <# } else if (ctrlId == "list") { #>

        <#
            let nonGridAddEditCode = `$option = $options["action"];
// Set up list action buttons
foreach ($this->ListActions->Items as $listaction) {
    if ($listaction->Select == ACTION_MULTIPLE) {
        $item = &$option->add("custom_" . $listaction->Action);
        $caption = $listaction->Caption;
        $icon = ($listaction->Icon != "") ? '<i class=\"' . HtmlEncode($listaction->Icon) . '" data-caption="' . HtmlEncode($caption) . '"></i>' . $caption : $caption;
        $item->Body = '<a class="ew-action ew-list-action" title="' . HtmlEncode($caption) . '" data-caption="' . HtmlEncode($caption) . '" href="#" onclick="return ew.submitAction(event,jQuery.extend({f:${jsFormName}},' . $listaction->toJson(true) . '));">' . $icon . '</a>';
        $item->Visible = $listaction->Allow;
    }
}

// Hide grid edit and other options
if ($this->TotalRecords <= 0) {
    $option = $options["addedit"];
    $item = $option["gridedit"];
    if ($item) {
        $item->Visible = false;
    }
    $option = $options["action"];
    $option->hideAllOptions();
}`;
        #>

        <# if (!gridAddOrEdit) { #>
        <#= nonGridAddEditCode #>
        <# } else { #>
        if (!$this->isGridAdd() && !$this->isGridEdit()) { // Not grid add/edit mode
            <#= nonGridAddEditCode #>
            <#
            if (!TABLE.TblAdd) {
                addSecChkWrk = Code.false;
            } else {
                addSecChkWrk = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled);
                if (IsEmpty(addSecChkWrk)) {
                    addSecChkWrk = Code.true;
                }
            }
            #>
        } else { // Grid add/edit mode
            // Hide all options first
            foreach ($options as $option) {
                $option->hideAllOptions();
            }

            $pageUrl = $this->pageUrl();

            <# if (gridAdd) { #>
            // Grid-Add
            if ($this->isGridAdd()) {
                <# if (recPerRow < 1) { // Single Column #>
                if ($this->AllowAddDeleteRow) {
                    // Add add blank row
                    $option = $options["addedit"];
                    $option->UseDropDownButton = false;
                    $item = &$option->add("addblankrow");
                    $item->Body = "<a class=\"ew-add-edit ew-add-blank-row\" title=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" href=\"#\" onclick=\"return ew.addGridRow(this);\">" . $Language->phrase("AddBlankRow") . "</a>";
                    $item->Visible = <#= addSecChkWrk #>;
                }
                <# } #>
                $option = $options["action"];
                $option->UseDropDownButton = false;
                // Add grid insert
                $item = &$option->add("gridinsert");
                $item->Body = "<a class=\"ew-action ew-grid-insert\" title=\"" . HtmlTitle($Language->phrase("GridInsertLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridInsertLink")) . "\" href=\"#\" onclick=\"<#= listFormGridSubmit #>\">" . $Language->phrase("GridInsertLink") . "</a>";
                // Add grid cancel
                $item = &$option->add("gridcancel");
                $cancelurl = $this->addMasterUrl($pageUrl . "action=cancel");
                $item->Body = "<a class=\"ew-action ew-grid-cancel\" title=\"" . HtmlTitle($Language->phrase("GridCancelLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridCancelLink")) . "\" href=\"" . $cancelurl . "\">" . $Language->phrase("GridCancelLink") . "</a>";
            }
            <# } #>

            <# if (gridEdit) { #>
            // Grid-Edit
            if ($this->isGridEdit()) {
                <# if (recPerRow < 1) { // Single Column #>
                if ($this->AllowAddDeleteRow) {
                    // Add add blank row
                    $option = $options["addedit"];
                    $option->UseDropDownButton = false;
                    $item = &$option->add("addblankrow");
                    $item->Body = "<a class=\"ew-add-edit ew-add-blank-row\" title=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("AddBlankRow")) . "\" href=\"#\" onclick=\"return ew.addGridRow(this);\">" . $Language->phrase("AddBlankRow") . "</a>";
                    $item->Visible = <#= addSecChkWrk #>;
                }
                <# } #>
                $option = $options["action"];
                $option->UseDropDownButton = false;
                <# if (checkConcurrentUpdate) { #>
                if ($this->UpdateConflict == "U") { // Record already updated by other user
                    $item = &$option->add("reload");
                    $item->Body = "<a class=\"ew-action ew-grid-reload\" title=\"" . HtmlTitle($Language->phrase("ReloadLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("ReloadLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->GridEditUrl)) . "\">" . $Language->phrase("ReloadLink") . "</a>";
                    $item = &$option->add("overwrite");
                    $item->Body = "<a class=\"ew-action ew-grid-overwrite\" title=\"" . HtmlTitle($Language->phrase("OverwriteLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("OverwriteLink")) . "\" href=\"#\" onclick=\"<#= listFormGridSubmit #>\">" . $Language->phrase("OverwriteLink") . "</a>";
                    $item = &$option->add("cancel");
                    $cancelurl = $this->addMasterUrl($pageUrl . "action=cancel");
                    $item->Body = "<a class=\"ew-action ew-grid-cancel\" title=\"" . HtmlTitle($Language->phrase("ConflictCancelLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("ConflictCancelLink")) . "\" href=\"" . $cancelurl . "\">" . $Language->phrase("ConflictCancelLink") . "</a>";
                } else {
                    <# } #>
                    $item = &$option->add("gridsave");
                    $item->Body = "<a class=\"ew-action ew-grid-save\" title=\"" . HtmlTitle($Language->phrase("GridSaveLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridSaveLink")) . "\" href=\"#\" onclick=\"<#= listFormGridSubmit #>\">" . $Language->phrase("GridSaveLink") . "</a>";
                    $item = &$option->add("gridcancel");
                    $cancelurl = $this->addMasterUrl($pageUrl . "action=cancel");
                    $item->Body = "<a class=\"ew-action ew-grid-cancel\" title=\"" . HtmlTitle($Language->phrase("GridCancelLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("GridCancelLink")) . "\" href=\"" . $cancelurl . "\">" . $Language->phrase("GridCancelLink") . "</a>";
                    <# if (checkConcurrentUpdate) { #>
                }
                <# } #>
            }
            <# } #>
        }

        <# } #>

    <# } #>
    }

    <# if (ctrlId == "list") { #>

    // Process list action
    protected function processListAction()
    {
        global $Language, $Security;
        <# if (hasUserProfile && TABLE.TblName == PROJ.SecTbl) { #>
        global $UserProfile;
        <# } #>
        $userlist = "";
        $user = "";

        $filter = $this->getFilterFromRecordKeys();
        $userAction = Post("useraction", "");
        if ($filter != "" && $userAction != "") {
            // Check permission first
            $actionCaption = $userAction;
            if (array_key_exists($userAction, $this->ListActions->Items)) {
                $actionCaption = $this->ListActions[$userAction]->Caption;
                if (!$this->ListActions[$userAction]->Allow) {
                    $errmsg = str_replace('%s', $actionCaption, $Language->phrase("CustomActionNotAllowed"));
                    if (Post("ajax") == $userAction) { // Ajax
                        echo "<p class=\"text-danger\">" . $errmsg . "</p>";
                        return true;
                    } else {
                        $this->setFailureMessage($errmsg);
                        return false;
                    }
                }
            }

            $this->CurrentFilter = $filter;
            $sql = $this->getCurrentSql();
            $conn = $this->getConnection();
            $rs = LoadRecordset($sql, $conn, \PDO::FETCH_ASSOC);
            $this->CurrentAction = $userAction;

            // Call row action event
            if ($rs) {
                $conn->beginTransaction();
                $this->SelectedCount = $rs->recordCount();
                $this->SelectedIndex = 0;
                while (!$rs->EOF) {
                    $this->SelectedIndex++;

                    $row = $rs->fields;

                    <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>

                    $user = GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $row);
                    if ($userlist != "") {
                        $userlist .= ",";
                    }
                    $userlist .= $user;
                    if ($userAction == "resendregisteremail") {
                        <# if (resendRegisterEmail) { #>
                        $processed = $this->sendRegisterEmail($row);
                        <# } else { #>
                        $processed = false;
                        <# } #>
                    } elseif ($userAction == "resetconcurrentuser") {
                        <# if (resetConcurrentUser) { #>
                        $processed = $UserProfile->resetConcurrentUser($user);
                        <# } else { #>
                        $processed = false;
                        <# } #>
                    } elseif ($userAction == "resetloginretry") {
                        <# if (resetLoginRetry) { #>
                        $processed = $UserProfile->resetLoginRetry($user);
                        <# } else { #>
                        $processed = false;
                        <# } #>
                    } elseif ($userAction == "setpasswordexpired") {
                        <# if (setPasswordExpired) { #>
                        $processed = $UserProfile->setPasswordExpired($user);
                        <# } else { #>
                        $processed = false;
                        <# } #>
                    } else {
                        <# if (ServerScriptExist("Table", "Row_CustomAction")) { #>
                        $processed = $this->rowCustomAction($userAction, $row);
                        <# } else { #>
                        $processed = true;
                        <# } #>
                     }

                    <# } else { #>

                    <# if (ServerScriptExist("Table", "Row_CustomAction")) { #>
                    $processed = $this->rowCustomAction($userAction, $row);
                    <# } else { #>
                    $processed = true;
                    <# } #>

                    <# } #>

                    if (!$processed) {
                        break;
                    }

                    $rs->moveNext();
                }

                if ($processed) {
                    $conn->commit(); // Commit the changes

                    <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>

                    <# if (resendRegisterEmail) { #>
                    if ($userAction == "resendregisteremail") {
                        $this->setSuccessMessage(str_replace('%u', $userlist, $Language->phrase("ResendRegisterEmailSuccess")));
                    }
                    <# } #>

                    <# if (resetConcurrentUser) { #>
                    if ($userAction == "resetconcurrentuser") {
                        $this->setSuccessMessage(str_replace('%u', $userlist, $Language->phrase("ResetConcurrentUserSuccess")));
                    }
                    <# } #>

                    <# if (resetLoginRetry) { #>
                    if ($userAction == "resetloginretry") {
                        $this->setSuccessMessage(str_replace('%u', $userlist, $Language->phrase("ResetLoginRetrySuccess")));
                    }
                    <# } #>

                    <# if (setPasswordExpired) { #>
                    if ($userAction == "setpasswordexpired") {
                        $this->setSuccessMessage(str_replace('%u', $userlist, $Language->phrase("SetPasswordExpiredSuccess")));
                    }
                    <# } #>

                    <# } #>

                    if ($this->getSuccessMessage() == "" && !ob_get_length()) { // No output
                        $this->setSuccessMessage(str_replace('%s', $actionCaption, $Language->phrase("CustomActionCompleted"))); // Set up success message
                    }
                } else {
                    $conn->rollback(); // Rollback changes

                    <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>

                    <# if (resendRegisterEmail) { #>
                    if ($userAction == "resendregisteremail") {
                        $this->setFailureMessage(str_replace('%u', $user, $Language->phrase("ResendRegisterEmailFailure")));
                    }
                    <# } #>

                    <# if (resetConcurrentUser) { #>
                    if ($userAction == "resetconcurrentuser") {
                        $this->setFailureMessage(str_replace('%u', $user, $Language->phrase("ResetConcurrentUserFailure")));
                    }
                    <# } #>

                    <# if (resetLoginRetry) { #>
                    if ($userAction == "resetloginretry") {
                        $this->setFailureMessage(str_replace('%u', $user, $Language->phrase("ResetLoginRetryFailure")));
                    }
                    <# } #>

                    <# if (setPasswordExpired) { #>
                    if ($userAction == "setpasswordexpired") {
                        $this->setFailureMessage(str_replace('%u', $user, $Language->phrase("SetPasswordExpiredFailure")));
                    }
                    <# } #>

                    <# } #>

                    // Set up error message
                    if ($this->getSuccessMessage() != "" || $this->getFailureMessage() != "") {
                        // Use the message, do nothing
                    } elseif ($this->CancelMessage != "") {
                        $this->setFailureMessage($this->CancelMessage);
                        $this->CancelMessage = "";
                    } else {
                        $this->setFailureMessage(str_replace('%s', $actionCaption, $Language->phrase("CustomActionFailed")));
                    }
                }
            }
            if ($rs) {
                $rs->close();
            }
            $this->CurrentAction = ""; // Clear action

            if (Post("ajax") == $userAction) { // Ajax
                if ($this->getSuccessMessage() != "") {
                    echo "<p class=\"text-success\">" . $this->getSuccessMessage() . "</p>";
                    $this->clearSuccessMessage(); // Clear message
                }
                if ($this->getFailureMessage() != "") {
                    echo "<p class=\"text-danger\">" . $this->getFailureMessage() . "</p>";
                    $this->clearFailureMessage(); // Clear message
                }
                return true;
            }
        }
        return false; // Not ajax request
    }

<# } #>

<# if (ctrlId == "list") { #>

    <# if (recPerRow >= 1) { // Multi-Column Layout #>

    // Get multi column CSS class for record DIV
    public function getMultiColumnClass()
    {
        if ($this->isGridAdd() || $this->isGridEdit() || $this->isInlineActionRow()) {
            return "p-3 " . $this->MultiColumnEditClass; // Occupy a whole row
        }
        return $this->MultiColumnClass; // Occupy a column only
    }

    <# } #>

<# } #>

<## setupListOptionsExt function #>
<#= include('shared/setup-list-options.php') #>

<## renderListOptionsExt function #>
<#= include('shared/render-list-options.php') #>

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>
    <#= GetServerScript("Table", "Form_CustomValidate") #>
    <#= GetServerScript("Table", "ListOptions_Load") #>
    <#= GetServerScript("Table", "ListOptions_Rendering") #>
    <#= GetServerScript("Table", "ListOptions_Rendered") #>
<# if (ctrlId == "list") { #>
    <#= GetServerScript("Table", "Row_CustomAction") #>
    <#= GetServerScript("Table", "Page_Exporting") #>
    <#= GetServerScript("Table", "Row_Export") #>
    <#= GetServerScript("Table", "Page_Exported") #>
    <#= GetServerScript("Table", "Page_Importing") #>
    <#= GetServerScript("Table", "Row_Import") #>
    <#= GetServerScript("Table", "Page_Imported") #>
<# } #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>