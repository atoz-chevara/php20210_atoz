<# if (["add", "edit", "update", "search", "view", "login", "change_password", "reset_password", "register"].includes(ctrlId)) { #>
        // Is modal
        $this->IsModal = Param("modal") == "1";
<# } #>

<# if (checkConcurrentUser && (ctrlType == "table" || ctrlId == "change_password")) { #>
        // Update last accessed time
        if (!$UserProfile->isValidUser(CurrentUserName(), session_id())) {
            Write($Language->phrase("UserProfileCorrupted"));
            $this->terminate();
            return;
        }
<# } #>

<# if (["add", "addopt", "register", "edit", "update", "search"].includes(ctrlId) || ctrlId == "list" && listAddOrEdit) { #>
        // Create form object
        $CurrentForm = new HttpForm();
<# } #>

<#
    global.exportStart = "";
    global.exportEnd = "";
    if (ctrlType == "table" || ["summary", "crosstab"].includes(ctrlId)) {
        if (["list", "summary", "crosstab"].includes(ctrlId) && listExport || ctrlId == "view" && viewExport) {
            global.exportStart = `<?php if (!${isExport}) { ?>`;
            global.exportEnd = Code.end;
#>

        // Get export parameters
        $custom = "";
        if (Param("export") !== null) {
            $this->Export = Param("export");
            $custom = Param("custom", "");
    <# if (ctrlType == "table") { #>
        } elseif (IsPost()) {
            if (Post("exporttype") !== null) {
                $this->Export = Post("exporttype");
            }
            $custom = Post("custom", "");
        } elseif (Get("cmd") == "json") {
            $this->Export = Get("cmd");
        } else {
            $this->setExportReturnUrl(CurrentUrl());
    <# } #>
        }

    <# if (ctrlType == "table" || ["summary", "crosstab"].includes(ctrlId)) { #>
        $ExportFileName = $this->TableVar; // Get export file, used in header
    <# } #>

    <# if (ctrlId == "view") { #>
    <#
        for (let f of keyFields) {
            let fldParm = f.FldParm;
    #>
        if (Get("<#= fldParm #>") !== null) {
            if ($ExportFileName != "") {
                $ExportFileName .= "_";
            }
            $ExportFileName .= Get("<#= fldParm #>");
        }
    <#
        }
    #>
    <# } #>

        // Get custom export parameters
        if ($this->isExport() && $custom != "") {
            $this->CustomExport = $this->Export;
            $this->Export = "print";
        }
        $CustomExportType = $this->CustomExport;

        $ExportType = $this->Export; // Get export parameter, used in header
    <# if (["summary", "crosstab"].includes(ctrlId)) { #>
        $ReportExportType = $ExportType; // Report export type, used in header
    <# } #>

    <# if (UseCustomTemplate) { #>

        // Custom export (post back from ew.applyTemplate), export and terminate page
        if (Post("customexport") !== null) {
            $this->CustomExport = Post("customexport");
            $this->Export = $this->CustomExport;
            $this->terminate();
            return;
        }

    <# } #>

        // Update Export URLs
        <# if (ctrlType == "table") { #>
        if (Config("USE_PHPEXCEL")) {
            $this->ExportExcelCustom = false;
        }
        if (Config("USE_PHPWORD")) {
            $this->ExportWordCustom = false;
        }
        <# } #>
        if ($this->ExportExcelCustom) {
            $this->ExportExcelUrl .= "&amp;custom=1";
        }
        if ($this->ExportWordCustom) {
            $this->ExportWordUrl .= "&amp;custom=1";
        }
        if ($this->ExportPdfCustom) {
            $this->ExportPdfUrl .= "&amp;custom=1";
        }

<#
        }
    } else if (ctrlId == "custom") {
#>
        if (Get("export") !== null) {
            $ExportType = Get("export"); // Get export parameter, used in header
        }
<#
    }
#>

<# if (isExtendPageClass && ctrlId != "grid") { #>
        $this->CurrentAction = Param("action"); // Set up current action
<# } #>

<#
    if (["list", "grid"].includes(ctrlId)) {
#>
        // Get grid add count
        $gridaddcnt = Get(Config("TABLE_GRID_ADD_ROW_COUNT"), "");
        if (is_numeric($gridaddcnt) && $gridaddcnt > 0) {
            $this->GridAddRowCount = $gridaddcnt;
        }

<#
    }

    if (["list", "grid", "preview"].includes(ctrlId)) {
#>
        // Set up list options
        $this->setupListOptions();
<#
    }

    if (["list", "summary", "crosstab"].includes(ctrlId) && listExport || ctrlId == "view" && viewExport) {
#>
        // Setup export options
        $this->setupExportOptions();
<#
    }

    if (ctrlId == "list" && isImport) {
#>
        // Setup import options
        $this->setupImportOptions();
<#
    }
#>

<# if (CONTROL.CtrlSkipHeaderFooter) { #>
        global $OldSkipHeaderFooter, $SkipHeaderFooter;
        $OldSkipHeaderFooter = $SkipHeaderFooter;
        $SkipHeaderFooter = true;
<# } #>

<#
    // Hide non-updatable fields for add/edit
    if (ctrlType == "table") {
        for (let f of allFields) {
            let fldParm = f.FldParm;
            if (ctrlId != "view" && !currentFields.some(f2 => f2.FldName == f.FldName || ctrlId == "list" && f2.FldHrefFld == f.FldName) && !IsFileRelatedField(TABLE, f)) { // Not selected field / Not list page HREF field / file related field, default not visible
#>
        $this-><#= fldParm #>->Visible = false;
<#
            } else {
#>
        $this-><#= fldParm #>->setVisibility();
<#
            }
        } // Field
#>
        $this->hideFieldsForAddEdit();
<#
    }
#>

<#
    // Clear Required flags for read only / primary key field
    if (ctrlType == "table" && ["edit", "update"].includes(ctrlId)) {
        for (let f of currentFields) {
            if (IsRequiredField(f) && (f.FldHtmlTagReadOnly || ctrlId == "update" && f.FldIsPrimaryKey)) {
#>
        $this-><#= f.FldParm #>->Required = false;
<#
            }
        }
    }
#>

<#
    // Clear lookup cache for non list/grid pages
    if (ctrlType == "table" && !["list", "grid"].includes(ctrlId)) {
#>
        // Do not use lookup cache
        $this->setUseLookupCache(false);
<#
    }
#>

<# if (useMultiPage) { #>
        // Set up multi page object
        $this->setupMultiPages();
<# } #>

<# if (showMultiPageForDetails && ["add", "edit", "view"].includes(ctrlId)) { #>
        // Set up detail page object
        $this->setupDetailPages();
<# } #>

<# if (ctrlId != "error" && ServerScriptExist("Global", "Page_Loading")) { #>
        // Global Page Loading event (in userfn*.php)
        Page_Loading();
<# } #>

<#
    if (!["error", "privacy", "personal_data"].includes(ctrlId)) {
        if (ServerScriptExist(eventCtrlType, "Page_Load")) {
#>
        // Page Load event
        if (method_exists($this, "pageLoad")) {
            $this->pageLoad();
        }
<#
        }
    }
#>

<# if (["list", "grid", "summary", "crosstab", "preview"].includes(ctrlId)) { #>

    <# if (["list", "grid"].includes(ctrlId) && masterTables.length > 0) { #>
        // Set up master detail parameters
        $this->setupMasterParms();
    <# } #>

        // Setup other options
        $this->setupOtherOptions();

    <# if (ctrlId == "list") { #>

    <# if (hasUserTable && TABLE.TblName == PROJ.SecTbl) { #>
        <# if (resendRegisterEmail) { #>
        $this->ListActions->add("resendregisteremail", $Language->phrase("ResendRegisterEmailBtn"), IsAdmin(), ACTION_AJAX, ACTION_SINGLE);
        <# } #>
        <# if (resetConcurrentUser) { #>
        $this->ListActions->add("resetconcurrentuser", $Language->phrase("ResetConcurrentUserBtn"), IsAdmin(), ACTION_AJAX, ACTION_SINGLE);
        <# } #>
        <# if (resetLoginRetry) { #>
        $this->ListActions->add("resetloginretry", $Language->phrase("ResetLoginRetryBtn"), IsAdmin(), ACTION_AJAX, ACTION_SINGLE);
        <# } #>
        <# if (setPasswordExpired) { #>
        $this->ListActions->add("setpasswordexpired", $Language->phrase("SetPasswordExpiredBtn"), IsAdmin(), ACTION_AJAX, ACTION_SINGLE);
        <# } #>
    <# } #>

        // Set up custom action (compatible with old version)
        foreach ($this->CustomActions as $name => $action) {
            $this->ListActions->add($name, $action);
        }

        // Show checkbox column if multiple action
        foreach ($this->ListActions->Items as $listaction) {
            if ($listaction->Select == ACTION_MULTIPLE && $listaction->Allow) {
                $this->ListOptions["checkbox"]->Visible = true;
                break;
            }
        }

    <# } #>

<# } #>

<#	if (ctrlType == "table") { #>
        // Set up lookup cache
<#
        for (let f of allFields) {
            if (IsLinkTableField(f) && IsLookupField(f) && !IsSelfLookupField(f, TABLE)) {
#>
        $this->setupLookupOptions($this-><#= f.FldParm #>);
<#
            }
        } // Field
#>
<# } #>

<# if (["summary", "crosstab"].includes(ctrlId)) { #>
        // Set up table class
        if ($this->isExport("word") || $this->isExport("excel") || $this->isExport("pdf")) {
            $this->ReportTableClass = "ew-table";
        } else {
            $this->ReportTableClass = "table ew-table";
        }

    <# if (UseCustomTemplate) { #>
        // Hide main table for custom layout
        if ($this->isExport() || $this->UseCustomTemplate) {
            $this->ReportTableStyle = ' style="display: none;"';
        }
    <# } #>
<# } #>
