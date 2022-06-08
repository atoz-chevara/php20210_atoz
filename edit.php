<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $FormClassName = "ew-horizontal ew-form ew-edit-form";
    public $IsModal = false;
    public $IsMobileOrModal = false;
    public $DbMasterFilter;
    public $DbDetailFilter;
    public $HashValue; // Hash Value
    public $DisplayRecords = 1;
    public $StartRecord;
    public $StopRecord;
    public $TotalRecords = 0;
    public $RecordRange = 10;
    public $RecordCount;

<# if (useMultiPage) { #>
    public $MultiPages; // Multi pages object
<# } #>

<# if (showMultiPageForDetails) { #>
    public $DetailPages; // Detail pages object
<# } #>

<## Captcha variables #>
<#= include('shared/captcha-var.php') #>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $SkipHeaderFooter;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }
        $this->IsMobileOrModal = IsMobile() || $this->IsModal;

        $this->FormClassName = "ew-form ew-edit-form ew-horizontal";

    <# if (detailEditPaging) { #>
        // Load record by position
        $loadByPosition = false;
    <# } #>

        $loaded = false;
        $postBack = false;

        // Set up current action and primary key
        if (IsApi()) {

            // Load key values
            $loaded = true;
    <#
        keyFields.forEach((kf, i) => {
            let fldParm = kf.FldParm;
    #>
            if (($keyValue = Get("<#= fldParm #>") ?? Key(<#= i #>) ?? Route(<#= i + 2 #>)) !== null) {
                $this-><#= fldParm #>->setQueryStringValue($keyValue);
                $this-><#= fldParm #>->setOldValue($this-><#= fldParm #>->QueryStringValue);
            } elseif (Post("<#= fldParm #>") !== null) {
                $this-><#= fldParm #>->setFormValue(Post("<#= fldParm #>"));
                $this-><#= fldParm #>->setOldValue($this-><#= fldParm #>->FormValue);
            } else {
                $loaded = false; // Unable to load key
            }
    <#
        }); // Field
    #>

            // Load record
            if ($loaded) {
                $loaded = $this->loadRow();
            }
            if (!$loaded) {
                $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                $this->terminate();
                return;
            }

            $this->CurrentAction = "update"; // Update record directly
            $this->OldKey = $this->getKey(true); // Get from CurrentValue
            $postBack = true;

        } else {

            if (Post("action") !== null) {

                $this->CurrentAction = Post("action"); // Get action code
                if (!$this->isShow()) { // Not reload record, handle as postback
                    $postBack = true;
                }

                // Get key from Form
                $this->setKey(Post($this->OldKeyName), $this->isShow());

            } else {

                $this->CurrentAction = "show"; // Default action is display

                // Load key from QueryString
                $loadByQuery = false;
        <#
            keyFields.forEach((kf, i) => {
                let fldParm = kf.FldParm;
        #>
                if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                    $this-><#= fldParm #>->setQueryStringValue($keyValue);
                    $loadByQuery = true;
                } else {
                    $this-><#= fldParm #>->CurrentValue = null;
                }
        <#
            }); // KeyField
        #>
        <# if (detailEditPaging) { #>
                if (!$loadByQuery) {
                    $loadByPosition = true;
                }
        <# } #>

            }

    <# if (masterTables.length > 0) { #>
            // Set up master detail parameters
            $this->setupMasterParms();
    <# } #>

            // Load recordset
            if ($this->isShow()) {

    <# if (detailEditPaging) { #>

                $this->StartRecord = 1; // Initialize start position
                if ($rs = $this->loadRecordset()) { // Load records
                    $this->TotalRecords = $rs->recordCount(); // Get record count
                }
                if ($this->TotalRecords <= 0) { // No record found
                    if ($this->getSuccessMessage() == "" && $this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                    }
                    $this->terminate("<#= listPage #>"); // Return to list page
                    return;
                } elseif ($loadByPosition) { // Load record by position
                    $this->setupStartRecord(); // Set up start record position
                    // Point to current record
                    if ($this->StartRecord <= $this->TotalRecords) {
                        $rs->move($this->StartRecord - 1);
                        $loaded = true;
                    }
                } else { // Match key values
        <#
            let checkCond = keyFields.map(kf => "$this->" + kf.FldParm + "->CurrentValue != null").join(" && "),
                matchCond = keyFields.map(kf => "SameString($this->" + kf.FldParm + "->CurrentValue, $rs->fields['" + SingleQuote(kf.FldName) + "'])").join(" && ");
        #>
                    if (<#= checkCond #>) {
                        while (!$rs->EOF) {
                            if (<#= matchCond #>) {
                                $this->setStartRecordNumber($this->StartRecord); // Save record position
                                $loaded = true;
                                break;
                            } else {
                                $this->StartRecord++;
                                $rs->moveNext();
                            }
                        }
                    }
                }

    <# } #>

    <# if (detailEditPaging) { #>

                // Load current row values
                if ($loaded) {
                    $this->loadRowValues($rs);
                }

    <# } else { #>

                // Load current record
                $loaded = $this->loadRow();

    <# } #>
                $this->OldKey = $loaded ? $this->getKey(true) : ""; // Get from CurrentValue

            }
        }

        // Process form if post back
        if ($postBack) {

            $this->loadFormValues(); // Get form values

    <# if (checkConcurrentUpdate) { #>
            // Overwrite record, reload hash value
            if ($this->isOverwrite()) {
                $this->loadRowHash();
    <# if (editConfirm) { #>
                $this->CurrentAction = "confirm";
    <# } else { #>
                $this->CurrentAction = "update";
    <# } #>
            }
    <# } #>

    <# if (isDetailEdit && detailTables.length > 0) { #>
            // Set up detail parameters
            $this->setupDetailParms();
    <# } #>

        }

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

        // Validate form if post back
        if ($postBack) {
            if (!$this->validateForm()) {
                $this->EventCancelled = true; // Event cancelled
                $this->restoreFormValues();
                if (IsApi()) {
                    $this->terminate();
                    return;
                } else {
                    $this->CurrentAction = ""; // Form error, reset action
                }
            }
        }

        // Perform current action
        switch ($this->CurrentAction) {
            case "show": // Get a record to display

    <# if (detailEditPaging) { #>

                if (!$loaded) {
                    if ($this->getSuccessMessage() == "" && $this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                    }
                    $this->terminate("<#= listPage #>"); // Return to list page
                    return;
                } else {
        <# if (checkConcurrentUpdate) { #>
                    $this->HashValue = $this->getRowHash($rs); // Get hash value for record
        <# } #>
                }

    <# } else { #>

                if (!$loaded) { // Load record based on key
                    if ($this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("NoRecord")); // No record found
                    }
                    $this->terminate("<#= listPage #>"); // No matching record, return to list
                    return;
                }

    <# } #>

    <# if (isDetailEdit && detailTables.length > 0) { #>
                // Set up detail parameters
                $this->setupDetailParms();
    <# } #>

                break;

            case "update": // Update

    <# if (!IsEmpty(TABLE.TblEditReturnPage)) { #>
                $returnUrl = <#= editReturnPage #>;
    <# } else { #>
        <# if (isDetailEdit && detailTables.length > 0) { #>
                if ($this->getCurrentDetailTable() != "") { // Master/detail edit
            <# if (isDetailView) { #>
                    $returnUrl = $this->getViewUrl(Config("TABLE_SHOW_DETAIL") . "=" . $this->getCurrentDetailTable()); // Master/Detail view page
            <# } else { #>
                    $returnUrl = "<#= listPage #>"; // Master list page
            <# } #>
                } else {
                    $returnUrl = <#= editReturnPage #>;
                }
        <# } else { #>
                $returnUrl = <#= editReturnPage #>;
        <# } #>
    <# } #>

    <# if (!isCustomEditReturnPage) { #>
                if (GetPageName($returnUrl) == "<#= listPage #>") {
                    $returnUrl = $this->addMasterUrl($returnUrl); // List page, return to List page with correct master key if necessary
                }
    <# } #>

                $this->SendEmail = true; // Send email on update success
                if ($this->editRow()) { // Update record based on key
                    if ($this->getSuccessMessage() == "") {
                        $this->setSuccessMessage($Language->phrase("UpdateSuccess")); // Update success
                    }
                    if (IsApi()) {
                        $this->terminate(true);
                        return;
                    } else {
                        $this->terminate($returnUrl); // Return to caller
                        return;
                    }
                } elseif (IsApi()) { // API request, return
                    $this->terminate();
                    return;
                } elseif ($this->getFailureMessage() == $Language->phrase("NoRecord")) {
                    $this->terminate($returnUrl); // Return to caller
                    return;
                } else {
                    $this->EventCancelled = true; // Event cancelled
                    $this->restoreFormValues(); // Restore form values if update failed

    <# if (isDetailEdit && detailTables.length > 0) { #>
                    // Set up detail parameters
                    $this->setupDetailParms();
    <# } #>

                }

        }

        // Set up Breadcrumb
        $this->setupBreadcrumb();

        // Render the record
        <# if (editConfirm) { #>
        if ($this->isConfirm()) { // Confirm page
            $this->RowType = ROWTYPE_VIEW; // Render as View
        } else {
            $this->RowType = ROWTYPE_EDIT; // Render as Edit
        }
        <# } else { #>
        $this->RowType = ROWTYPE_EDIT; // Render as Edit
        <# } #>

        $this->resetAttributes();
        $this->renderRow();

        <# if (detailEditPaging) { #>
        $this->Pager = new <#= pagerClass #>($this->StartRecord, $this->DisplayRecords, $this->TotalRecords, "", $this->RecordRange, $this->AutoHidePager);
        <# } #>

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Table", "Form_CustomValidate") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
