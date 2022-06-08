<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $FormClassName = "ew-horizontal ew-form ew-add-form";
    public $IsModal = false;
    public $IsMobileOrModal = false;
    public $DbMasterFilter = "";
    public $DbDetailFilter = "";
    public $StartRecord;
    public $Priv = 0;

    public $OldRecordset;
    public $CopyRecord;

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

        $this->FormClassName = "ew-form ew-add-form ew-horizontal";

        $postBack = false;

        // Set up current action
        if (IsApi()) {

            $this->CurrentAction = "insert"; // Add record directly
            $postBack = true;

        } elseif (Post("action") !== null) {

            $this->CurrentAction = Post("action"); // Get form action
            $this->setKey(Post($this->OldKeyName));
            $postBack = true;

        } else { // Not post back

    <# if (keyFields.length == 0) { #>
            $this->CopyRecord = false;
    <# } else { #>
            // Load key values from QueryString
    <#
        for (let kf of keyFields) {
            let fldParm = kf.FldParm;
    #>
            if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                $this-><#= fldParm #>->setQueryStringValue($keyValue);
            }
    <#
        } // KeyField
    #>
            $this->OldKey = $this->getKey(true); // Get from CurrentValue
            $this->CopyRecord = !EmptyValue($this->OldKey);
    <# } #>

            if ($this->CopyRecord) {
                $this->CurrentAction = "copy"; // Copy record
            } else {
                $this->CurrentAction = "show"; // Display blank record
            }

        }

        // Load old record / default values
        $loaded = $this->loadOldRecord();

    <# if (masterTables.length > 0) { #>
        // Set up master/detail parameters
        // NOTE: must be after loadOldRecord to prevent master key values overwritten
        $this->setupMasterParms();
    <# } #>

        // Load form values
        if ($postBack) {

            $this->loadFormValues(); // Load form values

    <# if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl) { #>
            // Load values for user privileges
            $allowAdd = (int)Post("x__AllowAdd");
            $allowEdit = (int)Post("x__AllowEdit");
            $allowDelete = (int)Post("x__AllowDelete");
            $allowList = (int)Post("x__AllowList");
            $allowView = (int)Post("x__AllowView");
            $allowSearch = (int)Post("x__AllowSearch");
            $allowLookup = (int)Post("x__AllowLookup");
            $allowImport = (int)Post("x__AllowImport");
            $allowAdmin = IsSysAdmin() ? (int)Post("x__AllowAdmin") : 0;
            $this->Priv = $allowAdd + $allowEdit + $allowDelete + $allowList + $allowView + $allowSearch + $allowLookup + $allowImport + $allowAdmin;
    <# } #>
        }

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

    <# if (isDetailAdd && detailTables.length > 0) { #>
        // Set up detail parameters
        $this->setupDetailParms();
    <# } #>

        // Validate form if post back
        if ($postBack) {
            if (!$this->validateForm()) {
                $this->EventCancelled = true; // Event cancelled
                $this->restoreFormValues(); // Restore form values
                if (IsApi()) {
                    $this->terminate();
                    return;
                } else {
                    $this->CurrentAction = "show"; // Form error, reset action
                }
            }
        }

        // Perform current action
        switch ($this->CurrentAction) {

            case "copy": // Copy an existing record

                if (!$loaded) { // Record not loaded
                    if ($this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("NoRecord")); // No record found
                    }
                    $this->terminate("<#= listPage #>"); // No matching record, return to list
                    return;
                }

    <# if (isDetailAdd && detailTables.length > 0) { #>
                // Set up detail parameters
                $this->setupDetailParms();
    <# } #>

                break;

            case "insert": // Add new record

                $this->SendEmail = true; // Send email on add success
                if ($this->addRow($this->OldRecordset)) { // Add successful
                    if ($this->getSuccessMessage() == "" && Post("addopt") != "1") { // Skip success message for addopt (done in JavaScript)
                        $this->setSuccessMessage($Language->phrase("AddSuccess")); // Set up success message
                    }

    <# if (!IsEmpty(TABLE.TblAddReturnPage)) { #>
                    $returnUrl = <#= addReturnPage #>;
    <# } else { #>
        <# if (isDetailAdd && detailTables.length > 0) { #>
                    if ($this->getCurrentDetailTable() != "") { // Master/detail add
                        $returnUrl = $this->getDetailUrl();
                    } else {
                        $returnUrl = <#= addReturnPage #>;
                    }
        <# } else { #>
                    $returnUrl = <#= addReturnPage #>;
        <# } #>
    <# } #>

    <# if (!isCustomAddReturnPage) { #>
                    if (GetPageName($returnUrl) == "<#= listPage #>") {
                        $returnUrl = $this->addMasterUrl($returnUrl); // List page, return to List page with correct master key if necessary
                    } elseif (GetPageName($returnUrl) == "<#= viewPage #>") {
                        $returnUrl = $this->getViewUrl(); // View page, return to View page with keyurl directly
                    }
    <# } #>

                    if (IsApi()) { // Return to caller
                        $this->terminate(true);
                        return;
                    } else {
                        $this->terminate($returnUrl);
                        return;
                    }
                } elseif (IsApi()) { // API request, return
                    $this->terminate();
                    return;
                } else {
                    $this->EventCancelled = true; // Event cancelled
                    $this->restoreFormValues(); // Add failed, restore form values

    <# if (isDetailAdd && detailTables.length > 0) { #>
                    // Set up detail parameters
                    $this->setupDetailParms();
    <# } #>

                }

        }

        // Set up Breadcrumb
        $this->setupBreadcrumb();

        // Render row based on row type
    <# if (addConfirm) { #>
        if ($this->isConfirm()) { // Confirm page
            $this->RowType = ROWTYPE_VIEW; // Render view type
        } else {
            $this->RowType = ROWTYPE_ADD; // Render add type
        }
    <# } else { #>
        $this->RowType = ROWTYPE_ADD; // Render add type
    <# } #>

        // Render row
        $this->resetAttributes();
        $this->renderRow();

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
