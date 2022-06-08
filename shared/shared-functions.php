<#
    let allFileFields = allFields.filter(f => f.FldHtmlTag == "FILE"); // All upload fields
    let currentFileFields = currentFields.filter(f => f.FldHtmlTag == "FILE"); // Upload fields
    let currentFileTextFields = currentFileFields.filter(f => !IsBinaryField(f)); // Non binary upload fields

if (hasFileField && ctrlId == "list" && listAddOrEdit || ["grid", "add", "addopt", "edit", "update", "register"].includes(ctrlId)) { // Upload Field Exists
#>

    // Get upload files
    protected function getUploadFiles()
    {
        global $CurrentForm, $Language;

        <#
        for (let f of currentFileFields) {
            let fldParm = f.FldParm,
                updateFldVar = "u_" + fldParm;
        #>

        $this-><#= fldParm #>->Upload->Index = $CurrentForm->Index;
        $this-><#= fldParm #>->Upload->uploadFile();

        <#
            if (!IsEmpty(f.FileNameFld)) {
                let fileNameField = GetFieldObject(TABLE, f.FileNameFld),
                    fileNameFldParm = fileNameField.FldParm;
        #>
        $this-><#= fileNameFldParm #>->CurrentValue = $this-><#= fldParm #>->Upload->FileName;
        <#
            }
            if (!IsEmpty(f.FileTypeFld)) {
                let fileTypeField = GetFieldObject(TABLE, f.FileTypeFld),
                    fileTypeFldParm = fileTypeField.FldParm;
        #>
        $this-><#= fileTypeFldParm #>->CurrentValue = $this-><#= fldParm #>->Upload->ContentType;
        <#
            }
            if (!IsEmpty(f.FileSizeFld)) {
                let fileSizeField = GetFieldObject(TABLE, f.FileSizeFld),
                    fileSizeFldParm = fileSizeField.FldParm;
        #>
        $this-><#= fileSizeFldParm #>->CurrentValue = $this-><#= fldParm #>->Upload->FileSize;
        <#
            }
            if (!IsEmpty(f.ImageWidthFld) && !IsEmpty(f.ImageHeightFld)) {
                let imageWidthField = GetFieldObject(TABLE, f.ImageWidthFld),
                    imageWidthFldParm = imageWidthField.FldParm,
                    imageHeightField = GetFieldObject(TABLE, f.ImageHeightFld),
                    imageHeightFldParm = imageHeightField.FldParm;
        #>
        $this-><#= imageWidthFldParm #>->CurrentValue = $this-><#= fldParm #>->Upload->ImageWidth;
        $this-><#= imageHeightFldParm #>->CurrentValue = $this-><#= fldParm #>->Upload->ImageHeight;
        <#
            }
        #>
        <# if (ctrlId == "update") { #>
        $this-><#= fldParm #>->MultiUpdate = $CurrentForm->getValue("<#= updateFldVar #>");
        <# } #>
        <#
        }
        #>
    }
    <#
}
#>

<# if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "addopt", "register"].includes(ctrlId)) { #>

    // Load default values
    protected function loadDefaultValues()
    {
        <#
            for (let f of allFields) {
                FIELD = f;
        #>
        <#= ScriptEditDefaultValue() #>
        <#
                if (f.FldHtmlTag == "FILE") {
                    if (ctrlId == "grid") {
        #>
        $this-><#= f.FldParm #>->Upload->Index = $this->RowIndex;
        <#
                    }
                }
            }
        #>
    }

<# } #>

<# if (ctrlId == "list" && useBasicSearch) { #>
    // Load basic search values
    protected function loadBasicSearchValues()
    {
        $this->BasicSearch->setKeyword(Get(Config("TABLE_BASIC_SEARCH"), ""), false);
        if ($this->BasicSearch->Keyword != "" && $this->Command == "") {
            $this->Command = "search";
        }
        $this->BasicSearch->setType(Get(Config("TABLE_BASIC_SEARCH_TYPE"), ""), false);
    }
<# } #>


<# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch) || ctrlId == "search") { #>

    // Load search values for validation
    protected function loadSearchValues()
    {
        // Load search values
        $hasValue = false;
    <#
        let method = ctrlId == "search" ? "post" : "get";

        for (let f of allFields) {
            //if (IsFieldExtendedSearch(f) || IsFieldAdvancedSearch(f)) {
            if (IsFieldExtendedSearch(f) || f.FldSearch) {
                let fldParm = f.FldParm, fldName = f.FldName;

                if (!(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
    #>
    <# if (ctrlId == "list") { #>
        // <#= fldName #>
        if (!$this->isAddOrEdit() && $this-><#= fldParm #>->AdvancedSearch-><#= method #>()) {
            $hasValue = true;
            if (($this-><#= fldParm #>->AdvancedSearch->SearchValue != "" || $this-><#= fldParm #>->AdvancedSearch->SearchValue2 != "") && $this->Command == "") {
                $this->Command = "search";
            }
        }
    <# } else { #>
        if ($this-><#= fldParm #>->AdvancedSearch-><#= method #>()) {
            $hasValue = true;
        }
    <# } #>
    <#
                }

                if (f.FldHtmlTag == "SELECT" && f.FldSelectMultiple || f.FldHtmlTag == "CHECKBOX") {
    #>
        if (is_array($this-><#= fldParm #>->AdvancedSearch->SearchValue)) {
            $this-><#= fldParm #>->AdvancedSearch->SearchValue = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $this-><#= fldParm #>->AdvancedSearch->SearchValue);
        }
        if (is_array($this-><#= fldParm #>->AdvancedSearch->SearchValue2)) {
            $this-><#= fldParm #>->AdvancedSearch->SearchValue2 = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $this-><#= fldParm #>->AdvancedSearch->SearchValue2);
        }
    <#
                }
            }
        }
    #>
        return $hasValue;

    }

<# } #>

<# if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "addopt", "edit", "update", "register"].includes(ctrlId)) { #>
    // Load form values
    protected function loadFormValues()
    {
        // Load from form
        global $CurrentForm;

        <# if (ctrlId == "grid") { #>
        $CurrentForm->FormName = $this->FormName;
        <# } #>

        <#
        for (let f of currentFields) {
            if (f.FldHtmlTag != "FILE") {
                let fldName = f.FldName, fldParm = f.FldParm, fldVar = f.FldVar;
                if (["action", "t", "modal"].includes(fldName)) { // Avoid clashing default variables
                    fldName = "_" + fldName;
                }
        #>
        // Check field name '<#= fldName #>' first before field var '<#= fldVar #>'
        $val = $CurrentForm->hasValue("<#= fldName #>") ? $CurrentForm->getValue("<#= fldName #>") : $CurrentForm->getValue("<#= fldVar #>");
        <#
            // Handle autoincrement fields
            if (f.FldAutoIncrement) {
                if (["add", "addopt", "register"].includes(ctrlId)) {
                    // Skip
                } else if (["grid", "list"].includes(ctrlId)) {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey && !$this->isGridAdd() && !$this->isAdd()) {
            $this-><#= fldParm #>->setFormValue($val);
        }
        <#
                    } else {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey) {
            $this-><#= fldParm #>->setFormValue($val);
        }
        <#
                }
            } else {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey) {
        <#
                if (ctrlId == "addopt") {
        #>
            $this-><#= fldParm #>->setFormValue(ConvertFromUtf8($val));
        <#
                } else {
        #>
            if (IsApi() && $val === null) {
                $this-><#= fldParm #>->Visible = false; // Disable update for API request
            } else {
                $this-><#= fldParm #>->setFormValue($val);
            }
        <#
                    }
                    if (!IsCrosstabYearField(TABLE, f) && [2, 7].includes(GetFieldType(f.FldType))) {
                        let fldDtFormat = f.FldDtFormat;
        #>
            $this-><#= fldParm #>->CurrentValue = UnFormatDateTime($this-><#= fldParm #>->CurrentValue, <#= fldDtFormat #>);
        <#
                    }
        #>
        }
        <#
                    if (ctrlId == "update") {
                        let updateFldVar = "u_" + fldParm;
        #>
        $this-><#= fldParm #>->MultiUpdate = $CurrentForm->getValue("<#= updateFldVar #>");
        <#
                    }

                    if (ctrlId == "register" && fldName == PROJ.SecPasswdFld) {
                        let cPwdFldVar = "c_" + fldParm;
        #>
        // Note: ConfirmValue will be compared with FormValue
        if (Config("ENCRYPTED_PASSWORD")) { // Encrypted password, use raw value
            $this-><#= fldParm #>->ConfirmValue = $CurrentForm->getValue("<#= cPwdFldVar #>");
        } else {
            $this-><#= fldParm #>->ConfirmValue = RemoveXss($CurrentForm->getValue("<#= cPwdFldVar #>"));
        }
        <#
                    }

                    if (ctrlId == "list" && gridAdd || ctrlId == "grid" || ["list", "edit"].includes(ctrlId) && f.FldIsPrimaryKey && !f.FldAutoIncrement && !f.FldHtmlTagReadOnly) {
                        let oldFldVar = "o_" + fldParm;
        #>
        if ($CurrentForm->hasValue("<#= oldFldVar #>")) {
            $this-><#= fldParm #>->setOldValue($CurrentForm->getValue("<#= oldFldVar #>"));
        }
        <#
                    }
                }
            }
        }
        #>

        <#
        // Load hidden primary key fields
        for (let f of keyFields) {
            let fldName = f.FldName, fldVar = f.FldVar, fldParm = f.FldParm;
            if (!currentFields.some(f2 => f2.FldName == fldName)) {
        #>
        // Check field name '<#= fldName #>' first before field var '<#= fldVar #>'
        $val = $CurrentForm->hasValue("<#= fldName #>") ? $CurrentForm->getValue("<#= fldName #>") : $CurrentForm->getValue("<#= fldVar #>");
        <#

        // Handle autoincrement fields
        if (f.FldAutoIncrement) {
            if (["add", "addopt", "register"].includes(ctrlId)) {
                // Skip
            } else if (["grid", "list"].includes(ctrlId)) {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey && !$this->isGridAdd() && !$this->isAdd()) {
            $this-><#= fldParm #>->setFormValue($val);
        }
        <#
            } else {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey) {
            $this-><#= fldParm #>->setFormValue($val);
        }
        <#
            }
        } else {
            if (ctrlId == "addopt") {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey) {
            $this-><#= fldParm #>->setFormValue(ConvertFromUtf8($val));
        }
        <#
                } else {
        #>
        if (!$this-><#= fldParm #>->IsDetailKey) {
            $this-><#= fldParm #>->setFormValue($val);
        }
        <#
                        }
                    }
                }
            }
        #>

        <# if (hasFileField) { #>
        <#
        for (let f of allFileFields) {
            if (!IsBinaryField(f)) {
                let fldParm = f.FldParm;
            if (!IsEmpty(f.FldUploadPath)) {
        #>
		$this-><#= fldParm #>->OldUploadPath = <#= f.FldUploadPath #>;
		$this-><#= fldParm #>->UploadPath = $this-><#= fldParm #>->OldUploadPath;
        <#
                }
            }
        } // Field
        #>
        $this->getUploadFiles(); // Get upload files
        <# } #>

<# if (checkConcurrentUpdate && (ctrlId == "edit" || ctrlId == "list" && listEdit || ctrlId == "grid")) { #>
        if (!$this->isOverwrite()) {
            $this->HashValue = $CurrentForm->getValue("k_hash");
        }
<# } #>
    }

    // Restore form values
    public function restoreFormValues()
    {
        global $CurrentForm;

        <#
        // Restore hidden primary key fields
        for (let f of keyFields) {
            if (!currentFields.some(f2 => f2.FldName == f.FldName)) {
                let fldParm = f.FldParm;

                // Handle autoincrement fields
                if (f.FldAutoIncrement) {
                    if (["add", "addopt", "register"].includes(ctrlId)) {
                        // Skip
                    } else if (["grid", "list"].includes(ctrlId)) {
        #>
        if (!$this->isGridAdd() && !$this->isAdd()) {
            $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
        }
        <#
                    } else {
        #>
        $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
        <#
                    }
                } else {
                    if (ctrlId == "addopt") {
           #>
        $this-><#= fldParm #>->CurrentValue = ConvertToUtf8($this-><#= fldParm #>->FormValue);
        <#
                    } else {
        #>
                        $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
        <#
                    }
                }
            }
        }
        #>

        <#
        for (let f of currentFields) {
            if (f.FldHtmlTag == "FILE") {
            } else if (!IsFileRelatedField(TABLE, f)) {
                let fldParm = f.FldParm;

                // Handle autoincrement fields
                if (f.FldAutoIncrement) {
                    if (["add", "addopt", "register"].includes(ctrlId)) {
                        // Skip
                    } else if (["grid", "list"].includes(ctrlId)) {
        #>
        if (!$this->isGridAdd() && !$this->isAdd()) {
            $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
        }
        <#
                } else {
        #>
        $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
        <#
                }
            } else {
                if (ctrlId == "addopt") {
    #>
        $this-><#= fldParm #>->CurrentValue = ConvertToUtf8($this-><#= fldParm #>->FormValue);
    <#
                } else {
    #>
        $this-><#= fldParm #>->CurrentValue = $this-><#= fldParm #>->FormValue;
    <#
                }
                if (!IsCrosstabYearField(TABLE, f) && [2, 7].includes(GetFieldType(f.FldType))) {
                    let fldDtFormat = f.FldDtFormat;
    #>
        $this-><#= fldParm #>->CurrentValue = UnFormatDateTime($this-><#= fldParm #>->CurrentValue, <#= fldDtFormat #>);
    <#
                }
            }
        }
    } // Field
    #>

    <# if (checkConcurrentUpdate && (ctrlId == "edit" || ctrlId == "list" && listEdit || ctrlId == "grid")) { #>
        if (!$this->isOverwrite()) {
            $this->HashValue = $CurrentForm->getValue("k_hash");
        }
    <# } #>

    <# if (detailTables.length > 0 && (ctrlId == "add" && isDetailAdd && addConfirm || ctrlId == "edit" && isDetailEdit && editConfirm)) { #>
        $this->resetDetailParms();
    <# } #>
    }

<#
    }
#>

<#
if (["list", "grid", "delete", "update"].includes(ctrlId) ||
        ctrlId == "view" && detailViewPaging ||
        ctrlId == "view" && viewExport && (exportHtml || exportEmail || exportCsv || exportWord || exportExcel || exportXml || exportPdf) ||
        ctrlId == "edit" && detailEditPaging) {
#>

    // Load recordset
    public function loadRecordset($offset = -1, $rowcnt = -1)
    {
        // Load List page SQL (QueryBuilder)
        $sql = $this->getListSql();

        // Load recordset
        if ($offset > -1) {
            $sql->setFirstResult($offset);
        }
        if ($rowcnt > 0) {
            $sql->setMaxResults($rowcnt);
        }
        $stmt = $sql->execute();

        $rs = new Recordset($stmt, $sql);

        <# if (ServerScriptExist("Table", "Recordset_Selected")) { #>
        // Call Recordset Selected event
        $this->recordsetSelected($rs);
        <# } #>

        return $rs;
    }
    <#
}
#>

<# if (["list", "grid", "view", "edit", "update", "add", "addopt", "register", "delete"].includes(ctrlId)) { #>
    /**
     * Load row based on key values
     *
     * @return void
     */
    public function loadRow()
    {
        global $Security, $Language;

        $filter = $this->getRecordFilter();

        <# if (ServerScriptExist("Table", "Row_Selecting")) { #>
        // Call Row Selecting event
        $this->rowSelecting($filter);
        <# } #>

        // Load SQL based on filter
        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        $res = false;
        $row = $conn->fetchAssoc($sql);
        if ($row) {
            $res = true;
            $this->loadRowValues($row); // Load row values

            <# if (checkConcurrentUpdate && ["grid", "edit"].includes(ctrlId) || ctrlId == "list" && listEdit) { #>
            if (!$this->EventCancelled) {
                $this->HashValue = $this->getRowHash($row); // Get hash value for record
            }
            <# } #>
        }

    <#
    if (hasUserIdFld) {
        if (["add", "edit"].includes(ctrlId)) {
    #>

        // Check if valid User ID
        if ($res) {
            $res = $this->showOptionLink("<#= ctrlId #>");
            if (!$res) {
                $userIdMsg = DeniedMessage();
                $this->setFailureMessage($userIdMsg);
            }
        }

    <#
        }
    }
    #>
        return $res;
    }

    /**
     * Load row values from recordset or record
     *
     * @param Recordset|array $rs Record
     * @return void
     */
    public function loadRowValues($rs = null)
    {
        if (is_array($rs)) {
            $row = $rs;
        } elseif ($rs && property_exists($rs, "fields")) { // Recordset
            $row = $rs->fields;
        } else {
            $row = $this->newRow();
        }

        <# if (ServerScriptExist("Table", "Row_Selected")) { #>
        // Call Row Selected event
        $this->rowSelected($row);
        <# } #>

        if (!$rs) {
            return;
        }

        <# if (TABLE.TblAuditTrail && ctrlId == "view") { #>
        if ($this->AuditTrailOnView) {
            $this->writeAuditTrailOnView($row);
        }
        <# } #>

    <#
    for (let f of allFields) {
        let fldName = f.FldName,
            fldParm = f.FldParm,
            fld = "$row['" + SingleQuote(fldName) + "']";
        if (f.FldHtmlTag == "FILE") {
    #>
        $this-><#= fldParm #>->Upload->DbValue = <#= fld #>;
        <# if (!IsBinaryField(f)) { #>
        $this-><#= fldParm #>->setDbValue($this-><#= fldParm #>->Upload->DbValue);
        <# } else { #>
        if (is_resource($this-><#= fldParm #>->Upload->DbValue) && get_resource_type($this-><#= fldParm #>->Upload->DbValue) == "stream") { // Byte array
            $this-><#= fldParm #>->Upload->DbValue = stream_get_contents($this-><#= fldParm #>->Upload->DbValue);
        }
        <# } #>
        <# if (ctrlId == "grid") { #>
        $this-><#= fldParm #>->Upload->Index = $this->RowIndex;
        <# } #>
    <#
        } else {
    #>
        $this-><#= fldParm #>->setDbValue(<#= GetFieldValue(TABLE, fld, f.FldType) #>);
    <#
        if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && f.FldName == DB.UserLevelIdFld) { // User Level field
    #>
        $this-><#= fldParm #>->CurrentValue = (int)$this-><#= fldParm #>->CurrentValue;
    <#
            }
            if (IsVirtualLookupField(f)) {
                let virtualFldName = VirtualLookupFieldName(f, tblDbId);
    #>
        if (array_key_exists('<#= virtualFldName #>', $row)) {
            $this-><#= fldParm #>->VirtualValue = $row['<#= virtualFldName #>']; // Set up virtual field value
        } else {
            $this-><#= fldParm #>->VirtualValue = ""; // Clear value
        }
    <#
            }
        }
    }
    #>

    <#
    if (showDetailCount && ["list", "view"].includes(ctrlId)) {
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailPageObj = GetPageObject("grid", detailTable),
                detailTblVar = detailTable.TblVar;
            if (detailPageObj && detailTable.TblType != "REPORT") {
    #>
        $detailTbl = Container("<#= detailTblVar #>");
        $detailFilter = $detailTbl->sqlDetailFilter_<#= tblVar #>();
    <#
                let dbId = GetDbId(detailTable.TblName); // Get detail dbid
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(TABLE, rel.MasterField),
                        masterFldParm = masterField.FldParm,
                        detailField = GetFieldObject(detailTable, rel.DetailField),
                        detailFldParm = detailField.FldParm;
    #>
        $detailFilter = str_replace("@<#= detailFldParm #>@", AdjustSql($this-><#= masterFldParm #>->DbValue, "<#= Quote(dbId) #>"), $detailFilter);
    <#
                } // MasterDetailField
    #>
        $detailTbl->setCurrentMasterTable("<#= tblVar #>");
        $detailFilter = $detailTbl->applyUserIDFilters($detailFilter);
        $detailTbl->Count = $detailTbl->loadRecordCount($detailFilter);
    <#
            }
        } // MasterDetail
    }
    #>
    }

    // Return a row with default values
    protected function newRow()
    {
    <#
    let hasDefaultValue = false;
    if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "addopt", "register"].includes(ctrlId)) {
        hasDefaultValue = true;
    #>
        $this->loadDefaultValues();
    <#
    }
    #>

        $row = [];
    <#
    for (let f of allFields) {
        let fldName = f.FldName, fldParm = f.FldParm,
            fldCurrentValue = Code.null;
        if (hasDefaultValue) {
            fldCurrentValue = f.FldHtmlTag == "FILE" ? "$this->" + fldParm + "->Upload->DbValue" : "$this->" + fldParm + "->CurrentValue";
        }
    #>
        $row['<#= SingleQuote(fldName) #>'] = <#= fldCurrentValue #>;
    <#
    }
    #>
        return $row;
    }
<# } #>

<# if (["list", "grid", "edit", "add"].includes(ctrlId)) { #>
    // Load old record
    protected function loadOldRecord()
    {
    <# if (keyFields.length == 0) { #>

        return false;

    <# } else { #>

        // Load old record
        $this->OldRecordset = null;
        $validKey = $this->OldKey != "";
        if ($validKey) {
            $this->CurrentFilter = $this->getRecordFilter();
            $sql = $this->getCurrentSql();
            $conn = $this->getConnection();
            $this->OldRecordset = LoadRecordset($sql, $conn);
        }
        $this->loadRowValues($this->OldRecordset); // Load row values

        return $validKey;

    <# } #>

    }
<# } #>

<# if (["list", "grid", "view", "edit", "update", "add", "addopt", "register", "delete", "search"].includes(ctrlId)) { #>

    // Render row values based on field settings
    public function renderRow()
    {
        global $Security, $Language, $CurrentLanguage;

        // Initialize URLs
        <# if (ctrlId == "view") { #>
        $this->AddUrl = $this->getAddUrl();
        $this->EditUrl = $this->getEditUrl();
        $this->CopyUrl = $this->getCopyUrl();
        $this->DeleteUrl = $this->getDeleteUrl();
        $this->ListUrl = $this->getListUrl();
        $this->setupOtherOptions();
        <# } else if (ctrlId == "list") { #>
        $this->ViewUrl = $this->getViewUrl();
        $this->EditUrl = $this->getEditUrl();
        $this->InlineEditUrl = $this->getInlineEditUrl();
        $this->CopyUrl = $this->getCopyUrl();
        $this->InlineCopyUrl = $this->getInlineCopyUrl();
        $this->DeleteUrl = $this->getDeleteUrl();
        <# } else if (ctrlId == "grid") { #>
        $this->ViewUrl = $this->getViewUrl();
        $this->EditUrl = $this->getEditUrl();
        $this->CopyUrl = $this->getCopyUrl();
        $this->DeleteUrl = $this->getDeleteUrl();
        <# } #>

        <#
        for (let f of currentFields) {
            if (IsFloatFormatField(f)) { // Check if adSingle/adDouble/adNumeric/adCurrency
                let fldParm = f.FldParm;
        #>
        // Convert decimal values if posted back
        if ($this-><#= fldParm #>->FormValue == $this-><#= fldParm #>->CurrentValue && is_numeric(ConvertToFloatString($this-><#= fldParm #>->CurrentValue))) {
            $this-><#= fldParm #>->CurrentValue = ConvertToFloatString($this-><#= fldParm #>->CurrentValue);
        }
        <#
                }
            }
        #>

        <# if (ServerScriptExist("Table", "Row_Rendering")) { #>
        // Call Row_Rendering event
        $this->rowRendering();
        <# } #>

        // Common render codes for all row types
        <#
            for (let f of allFields) {
                FIELD = f;
        #>
        // <#= f.FldName #>
        <#= ScriptCommon() #>
        <#
            }
        #>

        <# if (["list", "grid"].includes(ctrlId) && IsAggregate()) { #>
        // Accumulate aggregate value
        if ($this->RowType != ROWTYPE_AGGREGATEINIT && $this->RowType != ROWTYPE_AGGREGATE) {
        <#
        for (let f of allFields) {
            let fldParm = f.FldParm;
            if (f.FldAggregate == "COUNT" || f.FldAggregate == "AVERAGE") {
        #>
            $this-><#= fldParm #>->Count++; // Increment count
        <#
            }
            if (f.FldAggregate == "AVERAGE" || f.FldAggregate == "TOTAL") {
        #>
            if (is_numeric($this-><#= fldParm #>->CurrentValue)) {
                $this-><#= fldParm #>->Total += $this-><#= fldParm #>->CurrentValue; // Accumulate total
            }
        <#
                }
            }
        #>
        }
        <# } #>

        if ($this->RowType == ROWTYPE_VIEW) { // View row
        <#
            for (let f of allFields) {
                if (currentFields.some(f2 => f2.FldName == f.FldName) || f.FldExport) {
                    FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptView() #>
        <#
                    }
                }
        #>

        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptViewRefer() #>
        <#
                }
        #>

        <# if (["add", "addopt", "register", "grid"].includes(ctrlId) || ctrlId == "list" && listAddOrEdit) { #>
        } elseif ($this->RowType == ROWTYPE_ADD) { // Add row
        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptAdd() #>
        <#
            }
        #>

            // Add refer script
        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptAddRefer() #>
        <#
            }
        #>

        <# } #>

        <# if (["edit", "update", "grid"].includes(ctrlId) || ctrlId == "list" && listEdit) { #>
        } elseif ($this->RowType == ROWTYPE_EDIT) { // Edit row
        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptEdit() #>
        <#
            }
        #>

            // Edit refer script
        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptEditRefer() #>
        <#
            }
        #>

        <# } #>

        <# if (ctrlId == "search" || ctrlId == "list" && useExtendedBasicSearch) { #>
        } elseif ($this->RowType == ROWTYPE_SEARCH) { // Search row
        <#
            for (let f of currentFields) {
                FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptSearch() #>
        <#
                let isUserSelect = (f.FldSrchOpr == "USER SELECT" && GetFieldType(f.FldType) != 4);
                if (f.FldSrchOpr == "BETWEEN" || isUserSelect || !IsEmpty(f.FldSrchOpr2)) {
        #>
            <#= ScriptSearch2() #>
        <#
                    }
                }
        #>

        <# } #>

        <# if (["list", "grid"].includes(ctrlId) && IsAggregate()) { #>
        } elseif ($this->RowType == ROWTYPE_AGGREGATEINIT) { // Initialize aggregate row
        <#
            for (let f of allFields) {
                let fldParm = f.FldParm;
                if (f.FldAggregate == "COUNT" || f.FldAggregate == "AVERAGE") {
        #>
                $this-><#= fldParm #>->Count = 0; // Initialize count
        <#
                }
                if (f.FldAggregate == "AVERAGE" || f.FldAggregate == "TOTAL") {
        #>
                    $this-><#= fldParm #>->Total = 0; // Initialize total
        <#
                }
            }
        #>
        } elseif ($this->RowType == ROWTYPE_AGGREGATE) { // Aggregate row
            <#
            for (let f of allFields) {
                if (!IsEmpty(f.FldAggregate)) {
                    FIELD = f;
            #>
            <#= ScriptAggregate() #>
            <#
                    }
                }
            #>

        <# } #>
        }

        <# if (["add", "addopt", "register", "edit", "update", "search", "grid"].includes(ctrlId) || ctrlId == "list" && (listAddOrEdit || useExtendedBasicSearch)) { #>
        if ($this->RowType == ROWTYPE_ADD || $this->RowType == ROWTYPE_EDIT || $this->RowType == ROWTYPE_SEARCH) { // Add/Edit/Search row
            $this->setupFieldTitles();
        }
        <# } #>

        <# if (ServerScriptExist("Table", "Row_Rendered")) { #>
        // Call Row Rendered event
        if ($this->RowType != ROWTYPE_AGGREGATEINIT) {
            $this->rowRendered();
        }
        <# } #>

        <# if (UseCustomTemplate && ["add", "edit", "list", "view", "delete"].includes(ctrlId)) { #>
        // Save data for Custom Template
        if ($this->RowType == ROWTYPE_VIEW || $this->RowType == ROWTYPE_EDIT || $this->RowType == ROWTYPE_ADD) {
            $this->Rows[] = $this->customTemplateFieldValues();
        }
        <# } #>
    }

<# } #>

<# if (ctrlId == "list" && (useAdvancedSearch || useExtendedBasicSearch) || ctrlId == "search") { #>

    // Validate search
    protected function validateSearch()
    {

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }

        <#
        for (let f of allFields) {
            if (IsValidateSearch(f) && IsValidateServer(f)) { // Skip RegExp And Custom validation if server validate not enabled
                FIELD = f;
        #>
        <#= ServerSearchValidator() #>
        <#
            }
        } // Field
        #>

        // Return validate result
        $validateSearch = !$this->hasInvalidFields();

        <# if (ServerScriptExist("Table", "Form_CustomValidate")) { #>
        // Call Form_CustomValidate event
        $formCustomError = "";
        $validateSearch = $validateSearch && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
        <# } #>

        return $validateSearch;
    }

<# } #>

<# if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "addopt", "edit", "update", "register"].includes(ctrlId)) { #>

    // Validate form
    protected function validateForm()
    {
        global $Language;

    <#
        if (ctrlId == "update") {
    #>
        $updateCnt = 0;
    <#
    for (let f of currentFields) {
        let fldParm = f.FldParm;
    #>
        if ($this-><#= fldParm #>->multiUpdateSelected()) {
            $updateCnt++;
        }
    <#
        } // Field
    #>
        if ($updateCnt == 0) {
            return false;
        }
    <#
        }
    #>

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }

    <#
        for (let f of currentFields) {
            FIELD = f;
            let fldParm = f.FldParm;
            // Required Field
    #>
        if ($this-><#= fldParm #>->Required) {
            <#= ServerReqValidator() #>
        }
    <#
            // Text validation
            if (IsValidateText(f)) {
                if (IsValidateServer(f)) { // Field validator
    #>
        <#= ServerValidator() #>
    <#
                }
                if ((TABLE.TblName == PROJ.SecTbl || ctrlId == "register") && f.FldName == PROJ.SecLoginIDFld) { // Validate username
    #>
        if (!$this-><#= fldParm #>->Raw && Config("REMOVE_XSS") && CheckUsername($this-><#= fldParm #>->FormValue)) {
            $this-><#= fldParm #>->addErrorMessage($Language->phrase("InvalidUsernameChars"));
        }
    <#
                }
                if (f.FldHtmlTag == "PASSWORD" || (TABLE.TblName == PROJ.SecTbl || ctrlId == "register") && f.FldName == PROJ.SecPasswdFld) { // Validate password
    #>
        if (!$this-><#= fldParm #>->Raw && Config("REMOVE_XSS") && CheckPassword($this-><#= fldParm #>->FormValue)) {
            $this-><#= fldParm #>->addErrorMessage($Language->phrase("InvalidPasswordChars"));
        }
    <#
                }
            }
        } // Field
    #>

    <#
    if ((ctrlId == "add" && isDetailAdd || ctrlId == "edit" && isDetailEdit) && detailTables.length > 0) {
        let detailProp = "";
        if (ctrlId == "add") {
            detailProp = "DetailAdd";
        } else if (ctrlId == "edit") {
            detailProp = "DetailEdit";
        }
    #>
        // Validate detail grid
        $detailTblVar = explode(",", $this->getCurrentDetailTable());
            <#
            for (let md of detailTables) {
                let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar,
                    detailPageObj = GetPageObject("grid", detailTable);
                if (detailPageObj && detailTable.TblType != "REPORT") {
#>
        $detailPage = Container("<#= detailPageObj #>");
        if (in_array("<#= detailTblVar #>", $detailTblVar) && $detailPage-><#= detailProp #>) {
            $detailPage->validateGridForm();
        }
        <#
                }
            } // MasterDetail
        }
    #>

        // Return validate result
        $validateForm = !$this->hasInvalidFields();

        <# if (ServerScriptExist("Table", "Form_CustomValidate")) { #>
        // Call Form_CustomValidate event
        $formCustomError = "";
        $validateForm = $validateForm && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
        <# } #>

        return $validateForm;

    }

    <# } #>

    <# if (["grid", "delete"].includes(ctrlId) || ctrlId == "list" && gridAddOrEdit) { #>

    // Delete records based on current filter
    protected function deleteRows()
    {
        global $Language, $Security;

        <# if (hasUserTable) { #>
        if (!$Security->canDelete()) {
            $this->setFailureMessage($Language->phrase("NoDeletePermission")); // No delete permission
            return false;
        }
        <# } #>

        $deleteRows = true;
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        $rows = $conn->fetchAll($sql);
        if (count($rows) == 0) {
            $this->setFailureMessage($Language->phrase("NoRecord")); // No record found
            return false;
        }

        <#
            if (detailTables.length > 0) {
                for (let md of detailTables) {
                    if (md.EnforceReferentialIntegrity && !md.CascadeDelete) { // Enforce referential integrity but not Cascade delete
                        let detailTable = GetTableObject(md.DetailTable),
                        detailTblName = detailTable.TblName,
                        detailTblVar = detailTable.TblVar;
                        if (detailTable.TblType != "REPORT") {
                            // Get detail key SQL
                            let detailKeySql = "",
                            dbId = GetDbId(detailTblName); // Get detail dbid
                            for (let rel of md.Relations) {
                                let masterField = GetFieldObject(TABLE, rel.MasterField),
                                masterFldName = masterField.FldName,
                                masterFldTypeName = GetFieldTypeName(masterField.FldType),
                                detailField = GetFieldObject(detailTable, rel.DetailField),
                                detailFld = FieldSqlName(detailField, dbId);
                                if (detailKeySql != "") {
                                    detailKeySql += ' . " AND " . ';
                                }
                                detailKeySql += "\"" + Quote(detailFld) + " = \" . QuotedValue($row['" + SingleQuote(masterFldName) + "'], " + masterFldTypeName + ", '" + SingleQuote(dbId) + "')";
                            } // MasterDetailField
        #>
        foreach ($rows as $row) {
            $rsdetail = Container("<#= detailTblVar #>")->loadRs(<#= detailKeySql #>)->fetch();
            if ($rsdetail !== false) {
                $relatedRecordMsg = str_replace("%t", "<#= Quote(detailTblName) #>", $Language->phrase("RelatedRecordExists"));
                $this->setFailureMessage($relatedRecordMsg);
                return false;
            }
        }
        <#
                        }
                    }
                }
            }
        #>

        <# if (ctrlId == "delete") { #>
        $conn->beginTransaction();
        <# } #>

        <# if (auditTrailOnDelete) { #>
        if ($this->AuditTrailOnDelete) {
            $this->writeAuditTrailDummy($Language->phrase("BatchDeleteBegin")); // Batch delete begin
        }
        <# } #>

        // Clone old rows
        $rsold = $rows;

        <# if (ServerScriptExist("Table", "Row_Deleting")) { #>
        // Call row deleting event
        if ($deleteRows) {
            foreach ($rsold as $row) {
                $deleteRows = $this->rowDeleting($row);
                if (!$deleteRows) {
                    break;
                }
            }
        }
        <# } #>

        if ($deleteRows) {
            $key = "";
            foreach ($rsold as $row) {
                $thisKey = "";
            <#
                for (let f of keyFields) {
                    let fldName = f.FldName;
            #>
                if ($thisKey != "") {
                    $thisKey .= Config("COMPOSITE_KEY_SEPARATOR");
                }
                $thisKey .= $row['<#= SingleQuote(fldName) #>'];
            <#
                }
            #>
                if (Config("DELETE_UPLOADED_FILES")) { // Delete old files
                    $this->deleteUploadedFiles($row);
                }
        <#
            if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && !IsEmpty(DB.UserLevelIdFld)) {
                let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
                userLevelIdFldVar = userLevelIdField.FldVar;
        #>
                $<#= userLevelIdFldVar #> = $row['<#= SingleQuote(DB.UserLevelIdFld) #>']; // Get User Level id
        <#
            }
        #>
                $deleteRows = $this->delete($row); // Delete

                if ($deleteRows === false) {
                    break;
                }

                if ($key != "") {
                    $key .= ", ";
                }
                $key .= $thisKey;

        <#
            if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && !IsEmpty(DB.UserLevelIdFld)) {
                let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
                userLevelIdFldVar = userLevelIdField.FldVar;
        #>
                if ($<#= userLevelIdFldVar #> != null) {
                    $conn->executeUpdate("DELETE FROM " . Config("USER_LEVEL_PRIV_TABLE") . " WHERE " . Config("USER_LEVEL_PRIV_USER_LEVEL_ID_FIELD") . " = " . $<#= userLevelIdFldVar #>); // Delete user rights as well
                }
        <#
            }
        #>
            }
        }
        if (!$deleteRows) {
            // Set up error message
            if ($this->getSuccessMessage() != "" || $this->getFailureMessage() != "") {
                // Use the message, do nothing
            } elseif ($this->CancelMessage != "") {
                $this->setFailureMessage($this->CancelMessage);
                $this->CancelMessage = "";
            } else {
                $this->setFailureMessage($Language->phrase("DeleteCancelled"));
            }
        }

        <# if (ctrlId == "delete") { #>
        if ($deleteRows) {
            $conn->commit(); // Commit the changes

            <# if (auditTrailOnDelete) { #>
            if ($this->AuditTrailOnDelete) {
                $this->writeAuditTrailDummy($Language->phrase("BatchDeleteSuccess")); // Batch delete success
            }
            <# } #>

            <# if (TABLE.TblSendMailOnDelete) { #>
            $table = '<#= SingleQuote(TABLE.TblName) #>';
            $subject = $table . " " . $Language->phrase("RecordDeleted");
            $action = $Language->phrase("ActionDeleted");
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
            $args["rs"] = &$rsold;
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
            $conn->rollback(); // Rollback changes
            <# if (auditTrailOnDelete) { #>
            if ($this->AuditTrailOnDelete) {
                $this->writeAuditTrailDummy($Language->phrase("BatchDeleteRollback")); // Batch delete rollback
            }
            <# } #>
        }
        <# } #>

        <# if (ServerScriptExist("Table", "Row_Deleted")) { #>
        // Call Row Deleted event
        if ($deleteRows) {
            foreach ($rsold as $row) {
                $this->rowDeleted($row);
            }
        }
        <# } #>

        // Write JSON for API request
        if (IsApi() && $deleteRows) {
            $row = $this->getRecordsFromRecordset($rsold);
            WriteJson(["success" => true, $this->TableVar => $row]);
        }

        return $deleteRows;
    }

<# } #>


<# if (ctrlId == "list" && listEdit || ["grid", "edit", "update"].includes(ctrlId)) { #>

    // Update record based on key values
    protected function editRow()
    {
        global $Security, $Language;

        $oldKeyFilter = $this->getRecordFilter();
        $filter = $this->applyUserIDFilters($oldKeyFilter);
        $conn = $this->getConnection();

        <#
            for (let f of allFields) {
                if ((f.FldUniqueIdx || f.FldCheckDuplicate) &&
                    !(f.FldIsPrimaryKey || f.FldAutoIncrement || f.FldHtmlTag == "FILE")) {
                    if (IsFieldList(f) || IsFieldEdit(f) || IsFieldUpdate(f)) {
                        let fldParm = f.FldParm,
                            fld = FieldSqlName(f, tblDbId);
        #>
        if ($this-><#= fldParm #>->CurrentValue != "") { // Check field with unique index
            <# if (IsFloatFormatField(f)) { #>
            $filterChk = "(<#= Quote(fld) #> = <#= Quote(f.FldQuoteS) #>" . AdjustSql(ConvertToFloatString($this-><#= fldParm #>->CurrentValue), $this->Dbid) . "<#= Quote(f.FldQuoteE) #>)";
            <# } else { #>
            $filterChk = "(<#= Quote(fld) #> = <#= Quote(f.FldQuoteS) #>" . AdjustSql($this-><#= fldParm #>->CurrentValue, $this->Dbid) . "<#= Quote(f.FldQuoteE) #>)";
            <# } #>
            $filterChk .= " AND NOT (" . $filter . ")";
            $this->CurrentFilter = $filterChk;
            $sqlChk = $this->getCurrentSql();
            $rsChk = $conn->executeQuery($sqlChk);
            if (!$rsChk) {
                return false;
            }
            if ($rsChk->fetch()) {
                $idxErrMsg = str_replace("%f", $this-><#= fldParm #>->caption(), $Language->phrase("DupIndex"));
                $idxErrMsg = str_replace("%v", $this-><#= fldParm #>->CurrentValue, $idxErrMsg);
                $this->setFailureMessage($idxErrMsg);
                $rsChk->closeCursor();
                return false;
            }
        }
        <#
                }
            }
        } // Field
        #>

        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
        $rsold = $conn->fetchAssoc($sql);
        $editRow = false;
        if (!$rsold) {
            $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
            $editRow = false; // Update Failed
        } else {
            <# if (ctrlId == "edit" && isDetailEdit && detailTables.length > 0) { #>
            // Begin transaction
            if ($this->getCurrentDetailTable() != "") {
                $conn->beginTransaction();
            }
            <# } #>

            // Save old values
            $this->loadDbValues($rsold);
    <#
        for (let f of allFileFields) {
            if (!IsBinaryField(f)) {
                let fldParm = f.FldParm;
    #>
        <# if (!IsEmpty(f.FldUploadPath)) { #>
            $this-><#= fldParm #>->OldUploadPath = <#= f.FldUploadPath #>;
            $this-><#= fldParm #>->UploadPath = $this-><#= fldParm #>->OldUploadPath;
        <# } #>
    <#
            }
        } // Field
    #>

            $rsnew = [];

        <#
            for (let f of currentFields) {
                if (!f.FldHtmlTagReadOnly && IsFieldUpdatable(f)) {
                    FIELD = f;
        #>
            // <#= f.FldName #>
            <#= ScriptUpdate() #>
        <#
                }
            } // Field
        #>

            <# if (checkConcurrentUpdate && (ctrlId == "edit" || ctrlId == "list" && listEdit || ctrlId == "grid")) { #>
            // Check hash value
            $rowHasConflict = (!IsApi() && $this->getRowHash($rsold) != $this->HashValue);
            <# if (ServerScriptExist("Table", "Row_UpdateConflict")) { #>
            // Call Row Update Conflict event
            if ($rowHasConflict) {
                $rowHasConflict = $this->rowUpdateConflict($rsold, $rsnew);
            }
            <# } #>
            if ($rowHasConflict) {
                $this->setFailureMessage($Language->phrase("RecordChangedByOtherUser"));
                $this->UpdateConflict = "U";
                return false; // Update Failed
            }
            <# } #>

            <#
            if (masterTables.length > 0) {
                for (let md of masterTables) {
                    if (md.EnforceReferentialIntegrity) { // Enforce referential integrity
                        let masterTable = GetTableObject(md.MasterTable),
                        masterTblVar = masterTable.TblVar;
                        if (masterTable.TblType != "REPORT") {
        #>
            // Check referential integrity for master table '<#= masterTable.TblName #>'
            $validMasterRecord = true;
            $masterFilter = $this->sqlMasterFilter_<#= masterTblVar #>();
        <#
                        for (let rel of md.Relations) {
                            let masterField = GetFieldObject(masterTable, rel.MasterField),
                            masterFldParm = masterField.FldParm,
                            detailField = GetFieldObject(TABLE, rel.DetailField),
                            detailFldName = detailField.FldName,
                            detailFldParm = detailField.FldParm;
        #>
            $keyValue = $rsnew['<#= SingleQuote(detailFldName) #>'] ?? $rsold['<#= SingleQuote(detailFldName) #>'];
            if (strval($keyValue) != "") {
                $masterFilter = str_replace("@<#= masterFldParm #>@", AdjustSql($keyValue), $masterFilter);
            } else {
                $validMasterRecord = false;
            }
        <#
                            } // MasterDetailField
        #>
            if ($validMasterRecord) {
                $rsmaster = Container("<#= masterTblVar #>")->loadRs($masterFilter)->fetch();
                $validMasterRecord = $rsmaster !== false;
            }
            if (!$validMasterRecord) {
                $relatedRecordMsg = str_replace("%t", "<#= Quote(masterTable.TblName) #>", $Language->phrase("RelatedRecordRequired"));
                $this->setFailureMessage($relatedRecordMsg);
                return false;
            }
        <#
                        }
                    }
                }
            }
        #>

        <#
            for (let f of currentFileFields) {
                if (!f.FldHtmlTagReadOnly) {
                    FIELD = f;
        #>
            <#= ScriptUpdateFileData({ ctlid: "update" }) #>
        <#
                }
            } // Field
        #>

            <# if (ServerScriptExist("Table", "Row_Updating")) { #>
            // Call Row Updating event
            $updateRow = $this->rowUpdating($rsold, $rsnew);
            <# } else { #>
            $updateRow = true;
            <# } #>

        <#
            let checkDuplicateKey = keyFields.some(f => !f.FldAutoIncrement);
        #>
        <# if (checkDuplicateKey) { #>
            // Check for duplicate key when key changed
            if ($updateRow) {
                $newKeyFilter = $this->getRecordFilter($rsnew);
                if ($newKeyFilter != $oldKeyFilter) {
                    $rsChk = $this->loadRs($newKeyFilter)->fetch();
                    if ($rsChk !== false) {
                        $keyErrMsg = str_replace("%f", $newKeyFilter, $Language->phrase("DupKey"));
                        $this->setFailureMessage($keyErrMsg);
                        $updateRow = false;
                    }
                }
            }
        <# } #>

            if ($updateRow) {
                if (count($rsnew) > 0) {
                    try {
                        $editRow = $this->update($rsnew, "", $rsold);
                    } catch (\Exception $e) {
                        $this->setFailureMessage($e->getMessage());
                    }
                } else {
                    $editRow = true; // No field to update
                }

                if ($editRow) {
            <#
                for (let f of currentFileFields) {
                    if (!f.FldHtmlTagReadOnly) {
                        FIELD = f;
            #>
                    <#= ScriptUpdateFile() #>
            <#
                }
                    } // Field
            #>
                }

            <#
                if (ctrlId == "edit" && isDetailEdit && detailTables.length > 0) {
            #>
                // Update detail records
                $detailTblVar = explode(",", $this->getCurrentDetailTable());
            <#
                for (let md of detailTables) {
                    let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar,
                    detailTblName = detailTable.TblName,
                    detailPageObj = GetPageObject("grid", detailTable);
                    if (detailPageObj && detailTable.TblType != "REPORT") {
            #>
                if ($editRow) {
                    $detailPage = Container("<#= detailPageObj #>");
                    if (in_array("<#= detailTblVar #>", $detailTblVar) && $detailPage->DetailEdit) {
                        <# if (hasUserTable) { #>
                        $Security->loadCurrentUserLevel($this->ProjectID . "<#= Quote(detailTblName) #>"); // Load user level of detail table
                        <# } #>
                        $editRow = $detailPage->gridUpdate();
                        <# if (hasUserTable) { #>
                        $Security->loadCurrentUserLevel($this->ProjectID . $this->TableName); // Restore user level of master table
                        <# } #>
                    }
                }
        <#
                        }
                    } // MasterDetail
                }
        #>

                <# if (ctrlId == "edit" && isDetailEdit && detailTables.length > 0) { #>
                // Commit/Rollback transaction
                if ($this->getCurrentDetailTable() != "") {
                    if ($editRow) {
                        $conn->commit(); // Commit transaction
                    } else {
                        $conn->rollback(); // Rollback transaction
                    }
                }
                <# } #>
            } else {
                if ($this->getSuccessMessage() != "" || $this->getFailureMessage() != "") {
                    // Use the message, do nothing
                } elseif ($this->CancelMessage != "") {
                    $this->setFailureMessage($this->CancelMessage);
                    $this->CancelMessage = "";
                } else {
                    $this->setFailureMessage($Language->phrase("UpdateCancelled"));
                }
                $editRow = false;
            }
        }

        <# if (ServerScriptExist("Table", "Row_Updated")) { #>
        // Call Row_Updated event
        if ($editRow) {
            $this->rowUpdated($rsold, $rsnew);
        }
        <# } #>

        <# if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl) { #>
        // Load user level information again
        if ($editRow) {
            $Security->setupUserLevel();
        }
        <# } #>

        <# if (TABLE.TblSendMailOnEdit && (ctrlId == "list" && listEdit || ["edit", "update"].includes(ctrlId))) { #>
        if ($editRow) {
            if ($this->SendEmail) {
                $this->sendEmailOnEdit($rsold, $rsnew);
            }
        }
        <# } #>

        // Clean upload path if any
        if ($editRow) {
        <#
            for (let f of currentFileFields) {
                if (!f.FldHtmlTagReadOnly) {
                    let fldParm = f.FldParm, fldName = f.FldName;
        #>
            // <#= fldName #>
            CleanUploadTempPath($this-><#= fldParm #>, $this-><#= fldParm #>->Upload->Index);
        <#
                }
            } // Field
        #>
        }

        // Write JSON for API request
        if (IsApi() && $editRow) {
            $row = $this->getRecordsFromRecordset([$rsnew], true);
            WriteJson(["success" => true, $this->TableVar => $row]);
        }

        return $editRow;

    }

<# } #>

<# if (ctrlId == "list" && isImport) { #>

    /**
     * Import file
     *
     * @param string $filetoken File token to locate the uploaded import file
     * @return bool
     */
    public function import($filetoken)
    {
        global $Security, $Language;

        <# if (hasUserTable) { #>
        if (!$Security->canImport()) {
            return false; // Import not allowed
        }
        <# } #>

        // Check if valid token
        if (EmptyValue($filetoken)) {
            return false;
        }

        // Get uploaded files by token
        $files = GetUploadedFileNames($filetoken);
        $exts = explode(",", Config("IMPORT_FILE_ALLOWED_EXT"));

        $totCnt = 0;
        $totSuccessCnt = 0;
        $totFailCnt = 0;
        $result = [Config("API_FILE_TOKEN_NAME") => $filetoken, "files" => [], "success" => false];

        // Import records
        foreach ($files as $file) {
            $res = [Config("API_FILE_TOKEN_NAME") => $filetoken, "file" => basename($file)];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Ignore log file
            if ($ext == "txt") {
                continue;
            }

            if (!in_array($ext, $exts)) {
                $res = array_merge($res, ["error" => str_replace("%e", $ext, $Language->phrase("ImportMessageInvalidFileExtension"))]);
                WriteJson($res);
                return false;
            }

            // Set up options for Page Importing event

            // Get optional data from $_POST first
            $ar = array_keys($_POST);
            $options = [];
            foreach ($ar as $key) {
                if (!in_array($key, ["action", "filetoken"])) {
                    $options[$key] = $_POST[$key];
                }
            }

            // Merge default options
            $options = array_merge(["maxExecutionTime" => $this->ImportMaxExecutionTime, "file" => $file, "activeSheet" => 0, "headerRowNumber" => 0, "headers" => [], "offset" => 0, "limit" => 0], $options);
            if ($ext == "csv") {
                $options = array_merge(["inputEncoding" => $this->ImportCsvEncoding, "delimiter" => $this->ImportCsvDelimiter, "enclosure" => $this->ImportCsvQuoteCharacter], $options);
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));

            <# if (ServerScriptExist(eventCtrlType, "Page_Importing")) { #>
            // Call Page Importing server event
            if (!$this->pageImporting($reader, $options)) {
                WriteJson($res);
                return false;
            }
            <# } #>

            // Set max execution time
            if ($options["maxExecutionTime"] > 0) {
                ini_set("max_execution_time", $options["maxExecutionTime"]);
            }

            try {
                if ($ext == "csv") {
                    if ($options["inputEncoding"] != '') {
                        $reader->setInputEncoding($options["inputEncoding"]);
                    }
                    if ($options["delimiter"] != '') {
                        $reader->setDelimiter($options["delimiter"]);
                    }
                    if ($options["enclosure"] != '') {
                        $reader->setEnclosure($options["enclosure"]);
                    }
                }
                $spreadsheet = @$reader->load($file);
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                $res = array_merge($res, ["error" => $e->getMessage()]);
                WriteJson($res);
                return false;
            }

            // Get active worksheet
            $spreadsheet->setActiveSheetIndex($options["activeSheet"]);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get row and column indexes
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Get column headers
            $headers = $options["headers"];
            $headerRow = 0;
            if (count($headers) == 0) { // Undetermined, load from header row
                $headerRow = $options["headerRowNumber"] + 1;
                $headers = $this->getImportHeaders($worksheet, $headerRow, $highestColumn);
            }
            if (count($headers) == 0) { // Unable to load header
                $res["error"] = $Language->phrase("ImportMessageNoHeaderRow");
                WriteJson($res);
                return false;
            }
            $checkValue = true; // Clear blank header values at end
            $headers = array_reverse(array_reduce(array_reverse($headers), function ($res, $name) use ($checkValue) {
                if (!EmptyValue($name) || !$checkValue) {
                    $res[] = $name;
                    $checkValue = false; // Skip further checking
                }
                return $res;
            }, []));
            foreach ($headers as $name) {
                if (!array_key_exists($name, $this->Fields)) { // Unidentified field, not header row
                    $res["error"] = str_replace('%f', $name, $Language->phrase("ImportMessageInvalidFieldName"));
                    WriteJson($res);
                    return false;
                }
            }

            $startRow = $headerRow + 1;
            $endRow = $highestRow;
            if ($options["offset"] > 0) {
                $startRow += $options["offset"];
            }
            if ($options["limit"] > 0) {
                $endRow = $startRow + $options["limit"] - 1;
                if ($endRow > $highestRow) {
                    $endRow = $highestRow;
                }
            }
            if ($endRow >= $startRow) {
                $records = $this->getImportRecords($worksheet, $startRow, $endRow, $highestColumn);
            } else {
                $records = [];
            }

            $recordCnt = count($records);
            $cnt = 0;
            $successCnt = 0;
            $failCnt = 0;
            $failList = [];
            $relLogFile = IncludeTrailingDelimiter(UploadPath(false) . Config("UPLOAD_TEMP_FOLDER_PREFIX") . $filetoken, false) . $filetoken . ".txt";
            $res = array_merge($res, ["totalCount" => $recordCnt, "count" => $cnt, "successCount" => $successCnt, "failCount" => 0]);

            // Begin transaction
            if ($this->ImportUseTransaction) {
                $conn = $this->getConnection();
                $conn->beginTransaction();
            }

            // Process records
            foreach ($records as $values) {
                $importSuccess = false;
                try {
                    if (count($values) > count($headers)) { // Make sure headers / values count matched
                        array_splice($values, count($headers));
                    }
                    $row = array_combine($headers, $values);
                    $cnt++;
                    $res["count"] = $cnt;
                    if ($this->importRow($row, $cnt)) {
                        $successCnt++;
                        $importSuccess = true;
                    } else {
                        $failCnt++;
                        $failList["row" . $cnt] = $this->getFailureMessage();
                        $this->clearFailureMessage(); // Clear error message
                    }
                } catch (\Throwable $e) {
                    $failCnt++;
                    if ($failList["row" . $cnt] == "") {
                        $failList["row" . $cnt] = $e->getMessage();
                    }
                }

                // Reset count if import fail + use transaction
                if (!$importSuccess && $this->ImportUseTransaction) {
                    $successCnt = 0;
                    $failCnt = $cnt;
                }

                // Save progress to cache
                $res["successCount"] = $successCnt;
                $res["failCount"] = $failCnt;
                SetCache($filetoken, $res);

                // No need to process further if import fail + use transaction
                if (!$importSuccess && $this->ImportUseTransaction) {
                    break;
                }
            }
            $res["failList"] = $failList;

            // Commit/Rollback transaction
            if ($this->ImportUseTransaction) {
                $conn = $this->getConnection();
                if ($failCnt > 0) { // Rollback
                    $conn->rollback();
                } else { // Commit
                    $conn->commit();
                }
            }

            $totCnt += $cnt;
            $totSuccessCnt += $successCnt;
            $totFailCnt += $failCnt;

            <# if (ServerScriptExist(eventCtrlType, "Page_Imported")) { #>
            // Call Page Imported server event
            $this->pageImported($reader, $res);
            <# } #>

            if ($totCnt > 0 && $totFailCnt == 0) { // Clean up if all records imported
                $res["success"] = true;
                $result["success"] = true;
            } else {
                $res["log"] = $relLogFile;
                $result["success"] = false;
            }

            $result["files"][] = $res;
        }

        if ($result["success"]) {
            CleanUploadTempPaths($filetoken);
        }
        WriteJson($result);
        return $result["success"];
    }

    /**
     * Get import header
     *
     * @param object $ws PhpSpreadsheet worksheet
     * @param int $rowIdx Row index for header row (1-based)
     * @param string $endColName End column Name (e.g. "F")
     * @return array
     */
    protected function getImportHeaders($ws, $rowIdx, $endColName)
    {
        $ar = $ws->rangeToArray("A" . $rowIdx . ":" . $endColName . $rowIdx);
        return $ar[0];
    }

    /**
     * Get import records
     *
     * @param object $ws PhpSpreadsheet worksheet
     * @param int $startRowIdx Start row index
     * @param int $endRowIdx End row index
     * @param string $endColName End column Name (e.g. "F")
     * @return array
     */
    protected function getImportRecords($ws, $startRowIdx, $endRowIdx, $endColName)
    {
        $ar = $ws->rangeToArray("A" . $startRowIdx . ":" . $endColName . $endRowIdx);
        return $ar;
    }

    /**
     * Import a row
     *
     * @param array $row
     * @param int $cnt
     * @return bool
     */
    protected function importRow($row, $cnt)
    {
        global $Language;

        <# if (ServerScriptExist(eventCtrlType, "Row_Import")) { #>
        // Call Row Import server event
        if (!$this->rowImport($row, $cnt)) {
            return false;
        }
        <# } #>

        // Check field values
        foreach ($row as $name => $value) {
            $fld = $this->Fields[$name];
            if (!$this->checkValue($fld, $value)) {
                $this->setFailureMessage(str_replace(["%f", "%v"], [$fld->Name, $value], $Language->phrase("ImportMessageInvalidFieldValue")));
                return false;
            }
        }

        // Insert/Update to database
        if (!$this->ImportInsertOnly && $oldrow = $this->load($row)) {
            $res = $this->update($row, "", $oldrow);
        } else {
            $res = $this->insert($row);
        }
        return $res;
    }

    /**
     * Check field value
     *
     * @param object $fld Field object
     * @param object $value
     * @return bool
     */
    protected function checkValue($fld, $value)
    {
        if ($fld->DataType == DATATYPE_NUMBER && !is_numeric($value)) {
            return false;
        } elseif ($fld->DataType == DATATYPE_DATE && !CheckDate($value)) {
            return false;
        }
        return true;
    }

    // Load row
    protected function load($row)
    {
        $filter = $this->getRecordFilter($row);
        if (!$filter) {
            return null;
        }
        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        return $conn->fetchAssoc($sql);
    }

<# } #>

<# if (checkConcurrentUpdate && ["grid", "edit"].includes(ctrlId) || ctrlId == "list" && listEdit) { #>

    // Load row hash
    protected function loadRowHash()
    {
        $filter = $this->getRecordFilter();

        // Load SQL based on filter
        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        $row = $conn->fetchAssoc($sql);
        $this->HashValue = $row ? $this->getRowHash($row) : ""; // Get hash value for record
    }

    // Get Row Hash
    public function getRowHash(&$rs)
    {
        if (!$rs) {
            return "";
        }
        $row = ($rs instanceof Recordset) ? $rs->fields : $rs;
        $hash = "";
    <#
        for (let f of currentFields) {
            if (!f.FldHtmlTagReadOnly && IsFieldUpdatable(f)) {
                let fldName = f.FldName;
    #>
        $hash .= GetFieldHash($row['<#= SingleQuote(fldName) #>']); // <#= fldName #>
    <#
            }
        } // Field
    #>
        return md5($hash);
    }
    <# } #>

    <# if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "register"].includes(ctrlId)) { #>

    // Add record
    protected function addRow($rsold = null)
    {
        global $Language, $Security;

    <#
        if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && !IsEmpty(DB.UserLevelIdFld) && !IsEmpty(DB.UserLevelNameFld)) {
            let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
            userLevelIdFldVar = userLevelIdField.FldVar,
            userLevelIdFldParm = userLevelIdField.FldParm,
            userLevelIdFldCv = "$this->" + userLevelIdFldParm + "->CurrentValue",
            userLevelNameField = GetFieldObject(TABLE, DB.UserLevelNameFld),
            userLevelNameFldVar = userLevelNameField.FldVar,
            userLevelNameFldParm = userLevelNameField.FldParm,
            userLevelNameFldCv = "$this->" + userLevelNameFldParm + "->CurrentValue";
            if (userLevelIdField.FldAutoIncrement) {
    #>
        if (trim(<#= userLevelNameFldCv #>) == "") {
            $this->setFailureMessage($Language->phrase("MissingUserLevelName"));
        } elseif (in_array(strtolower(trim(<#= userLevelNameFldCv #>)), ["anonymous", "administrator", "default"])) {
            $this->setFailureMessage($Language->phrase("UserLevelNameIncorrect"));
        }
    <#
            } else {
    #>
        if ($this-><#= userLevelIdFldParm #>->Required && trim(strval(<#= userLevelIdFldCv #>)) == "") {
            $this->setFailureMessage($Language->phrase("MissingUserLevelID"));
        } elseif (trim(<#= userLevelNameFldCv #>) == "") {
            $this->setFailureMessage($Language->phrase("MissingUserLevelName"));
        } elseif (!is_numeric(<#= userLevelIdFldCv #>)) {
            $this->setFailureMessage($Language->phrase("UserLevelIDInteger"));
        } elseif ((int)<#= userLevelIdFldCv #> < -2) {
            $this->setFailureMessage($Language->phrase("UserLevelIDIncorrect"));
        } elseif ((int)<#= userLevelIdFldCv #> == 0 && !SameText(<#= userLevelNameFldCv #>, "Default")) {
            $this->setFailureMessage($Language->phrase("UserLevelDefaultName"));
        } elseif ((int)<#= userLevelIdFldCv #> == -1 && !SameText(<#= userLevelNameFldCv #>, "Administrator")) {
            $this->setFailureMessage($Language->phrase("UserLevelAdministratorName"));
        } elseif ((int)<#= userLevelIdFldCv #> == -2 && !SameText(<#= userLevelNameFldCv #>, "Anonymous")) {
            $this->setFailureMessage($Language->phrase("UserLevelAnonymousName"));
        } elseif ((int)<#= userLevelIdFldCv #> > 0 && in_array(strtolower(trim(<#= userLevelNameFldCv #>)), ["anonymous", "administrator", "default"])) {
            $this->setFailureMessage($Language->phrase("UserLevelNameIncorrect"));
        }
    <#
            }
    #>
        if ($this->getFailureMessage() != "") {
            return false;
        }

    <#
        }
    #>

        <#
        if (hasUserIdFld) {
            let userIdField = GetFieldObject(TABLE, TABLE.TblUserIDFld),
                userIdFldParm = userIdField.FldParm;
            if (IsEmpty(userIdField.FldAutoUpdateValue)) {
    #>
        // Check if valid User ID
        $validUser = false;
        if ($Security->currentUserID() != "" && !EmptyValue($this-><#= userIdFldParm #>->CurrentValue) && !$Security->isAdmin()) { // Non system admin
            $validUser = $Security->isValidUserID($this-><#= userIdFldParm #>->CurrentValue);
            if (!$validUser) {
                $userIdMsg = str_replace("%c", CurrentUserID(), $Language->phrase("UnAuthorizedUserID"));
                $userIdMsg = str_replace("%u", $this-><#= userIdFldParm #>->CurrentValue, $userIdMsg);
                $this->setFailureMessage($userIdMsg);
                return false;
            }
        }
    <#
            }
        }
    #>

        <#
        if (hasParentUserId && PROJ.SecTbl == TABLE.TblName) {
            let parentUserIdField = GetFieldObject(TABLE, DB.SecuParentUserIDFld),
                parentUserIdFldParm = parentUserIdField.FldParm;
            if (IsEmpty(parentUserIdField.FldAutoUpdateValue)) {
    #>
        // Check if valid Parent User ID
        $validParentUser = false;
        if ($Security->currentUserID() != "" && !EmptyValue($this-><#= parentUserIdFldParm #>->CurrentValue) && !$Security->isAdmin()) { // Non system admin
            $validParentUser = $Security->isValidUserID($this-><#= parentUserIdFldParm #>->CurrentValue);
            if (!$validParentUser) {
                $parentUserIdMsg = str_replace("%c", CurrentUserID(), $Language->phrase("UnAuthorizedParentUserID"));
                $parentUserIdMsg = str_replace("%p", $this-><#= parentUserIdFldParm #>->CurrentValue, $parentUserIdMsg);
                $this->setFailureMessage($parentUserIdMsg);
                return false;
            }
        }
    <#
            }
        }
    #>

    <#
        if (masterTableHasUserIdFld) {
    #>
        // Check if valid key values for master user
        if ($Security->currentUserID() != "" && !$Security->isAdmin()) { // Non system admin
            <#
            if (masterTables.length > 0) {
                for (let md of masterTables) {
                    let masterTable = GetTableObject(md.MasterTable),
                    masterTblVar = masterTable.TblVar,
                    masterUserIdFldName = masterTable.TblUserIDFld;
                    if (!IsEmpty(masterUserIdFldName)) {
        #>
            $masterFilter = $this->sqlMasterFilter_<#= masterTblVar #>();
        <#
            let dbId = GetDbId(masterTable.TblName); // Get master dbid
            for (let rel of md.Relations) {
                let masterField = GetFieldObject(masterTable, rel.MasterField),
                masterFldParm = masterField.FldParm,
                detailField = GetFieldObject(TABLE, rel.DetailField),
                detailFldParm = detailField.FldParm;
        #>
            if (strval($this-><#= detailFldParm #>->CurrentValue) != "") {
                $masterFilter = str_replace("@<#= masterFldParm #>@", AdjustSql($this-><#= detailFldParm #>->CurrentValue, "<#= Quote(dbId) #>"), $masterFilter);
            } else {
                $masterFilter = "";
            }
        <#
                        } // MasterDetailField
        #>
            if ($masterFilter != "") {
                $rsmaster = Container("<#= masterTblVar #>")->loadRs($masterFilter)->fetch(\PDO::FETCH_ASSOC);
                $masterRecordExists = $rsmaster !== false;
                $validMasterKey = true;
                if ($masterRecordExists) {
                    $validMasterKey = $Security->isValidUserID($rsmaster['<#= SingleQuote(masterUserIdFldName) #>']);
                } elseif ($this->getCurrentMasterTable() == "<#= masterTblVar #>") {
                    $validMasterKey = false;
                }
                if (!$validMasterKey) {
                    $masterUserIdMsg = str_replace("%c", CurrentUserID(), $Language->phrase("UnAuthorizedMasterUserID"));
                    $masterUserIdMsg = str_replace("%f", $masterFilter, $masterUserIdMsg);
                    $this->setFailureMessage($masterUserIdMsg);
                    return false;
                }
            }
    <#
                    }
                } // MasterDetail
            }
    #>
        }
    <#
        }
    #>

        <# if (ctrlId == "grid") { #>
        // Set up foreign key field value from Session
        <#
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
    #>
        if ($this->getCurrentMasterTable() == "<#= masterTblVar #>") {
    <#
    for (let rel of md.Relations) {
        let detailField = GetFieldObject(TABLE, rel.DetailField),
        detailFldParm = detailField.FldParm;
    #>
            $this-><#= detailFldParm #>->CurrentValue = $this-><#= detailFldParm #>->getSessionValue();
    <#
                } // MasterDetailField
    #>
        }
    <#
        } // MasterDetail
    #>
        <# } #>

        <#
        for (let f of allFields) {
            if ((f.FldUniqueIdx || f.FldCheckDuplicate) &&
                !(f.FldAutoIncrement || f.FldHtmlTag == "FILE") &&
                (keyFields.length > 1 && !f.FldIsPrimaryKey || keyFields.length <= 1)) {
                if (IsFieldList(f) || IsFieldAdd(f) || IsFieldAddOption(f) || IsFieldRegister(f)) {
                    let fld = FieldSqlName(f, tblDbId),
                        fldParm = f.FldParm;
    #>
        if ($this-><#= fldParm #>->CurrentValue != "") { // Check field with unique index
            $filter = "(<#= Quote(fld) #> = <#= Quote(f.FldQuoteS) #>" . AdjustSql($this-><#= fldParm #>->CurrentValue, $this->Dbid) . "<#= Quote(f.FldQuoteE) #>)";
            $rsChk = $this->loadRs($filter)->fetch();
            if ($rsChk !== false) {
                $idxErrMsg = str_replace("%f", $this-><#= fldParm #>->caption(), $Language->phrase("DupIndex"));
                $idxErrMsg = str_replace("%v", $this-><#= fldParm #>->CurrentValue, $idxErrMsg);
                $this->setFailureMessage($idxErrMsg);
                return false;
            }
        }
    <#
            }
        }
        } // Field
    #>

    <#
    if (masterTables.length > 0) {
            for (let md of masterTables) {
                if (md.EnforceReferentialIntegrity) { // Enforce referential integrity
                    let masterTable = GetTableObject(md.MasterTable),
                        masterTblVar = masterTable.TblVar;
                    if (masterTable.TblType != "REPORT") {
    #>
        // Check referential integrity for master table '<#= TABLE.TblName #>'
        $validMasterRecord = true;
        $masterFilter = $this->sqlMasterFilter_<#= masterTblVar #>();
    <#
                        let dbId = GetDbId(masterTable.TblName); // Get master dbid
                        for (let rel of md.Relations) {
                            let masterField = GetFieldObject(masterTable, rel.MasterField),
                                masterFldParm = masterField.FldParm,
                                detailField = GetFieldObject(TABLE, rel.DetailField),
                                detailFldName = detailField.FldName,
                                detailFldParm = detailField.FldParm;
    #>
    <#
        if (!currentFields.some(f => f.FldName == detailFldName)) {
    #>
        if ($this-><#= detailFldParm #>->getSessionValue() != "") {
        $masterFilter = str_replace("@<#= masterFldParm #>@", AdjustSql($this-><#= detailFldParm #>->getSessionValue(), "<#= Quote(dbId) #>"), $masterFilter);
    <#
        } else {
    #>
        if (strval($this-><#= detailFldParm #>->CurrentValue) != "") {
            $masterFilter = str_replace("@<#= masterFldParm #>@", AdjustSql($this-><#= detailFldParm #>->CurrentValue, "<#= Quote(dbId) #>"), $masterFilter);
    <#
        }
    #>
        } else {
            $validMasterRecord = false;
        }
    <#
                        } // MasterDetailField
    #>
        if ($validMasterRecord) {
            $rsmaster = Container("<#= masterTblVar #>")->loadRs($masterFilter)->fetch();
            $validMasterRecord = $rsmaster !== false;
        }
        if (!$validMasterRecord) {
            $relatedRecordMsg = str_replace("%t", "<#= Quote(masterTable.TblName) #>", $Language->phrase("RelatedRecordRequired"));
            $this->setFailureMessage($relatedRecordMsg);
            return false;
        }
    <#
                    }
                }
            }
        }
    #>

        $conn = $this->getConnection();
        <# if (ctrlId == "add" && isDetailAdd && detailTables.length > 0) { #>
        // Begin transaction
        if ($this->getCurrentDetailTable() != "") {
            $conn->beginTransaction();
        }
        <# } #>

        // Load db values from rsold
        $this->loadDbValues($rsold);

        if ($rsold) {
    <#
        for (let f of allFileFields) {
            if (!IsBinaryField(f)) {
                let fldParm = f.FldParm;
    #>
            <# if (!IsEmpty(f.FldUploadPath)) { #>
            $this-><#= fldParm #>->OldUploadPath = <#= f.FldUploadPath #>;
            $this-><#= fldParm #>->UploadPath = $this-><#= fldParm #>->OldUploadPath;
            <# } #>
    <#
                }
            } // Field
    #>
        }

        $rsnew = [];

    <#
        for (let f of currentFields) {
            if (IsFieldUpdatable(f)) {
                let fldName = f.FldName;
    #>
        // <#= fldName #>
    <#
                // User Level field in register page
                if (ctrlId == "register" && fldName == DB.SecUserLevelFld) {
                    let userLevel = 0;
                    if (!IsEmpty(f.FldDefault) && !isNaN(f.FldDefault)) {
                        userLevel = f.FldDefault;
                    }
    #>
        $rsnew['<#= SingleQuote(fldName) #>'] = <#= userLevel #>; // Set default User Level
    <#
                    // Normal field
                } else {
                    FIELD = f;
    #>
        <#= ScriptInsert() #>
    <#
                }
            }
        } // Field
    #>

        <#
        // Update detail key, Parent User ID and/or User ID field(s) if not selected
        for (let f of allFields) {
            if (!currentFields.some(f2 => f2.FldName == f.FldName)) {
                if (IsDetailKeyField(TABLE, f) ||
                    hasParentUserId && PROJ.SecTbl == TABLE.TblName && f.FldName == DB.SecuParentUserIDFld ||
                    hasUserIdFld && f.FldName == TABLE.TblUserIDFld) {
                    FIELD = f;
    #>
        // <#= f.FldName #>
        <#= ScriptUpdateSpecial() #>
    <#
                }
            }
        } // Field
    #>

        <#
        for (let f of currentFileFields) {
            FIELD = f;
    #>
        <#= ScriptUpdateFileData({ ctlid: "insert" }) #>
    <#
        } // Field
    #>

        <# if (ServerScriptExist("Table", "Row_Inserting")) { #>
        // Call Row Inserting event
        $insertRow = $this->rowInserting($rsold, $rsnew);
        <# } else { #>
        $insertRow = true;
        <# } #>

    <#
        for (let f of keyFields) {
            if (!f.FldAutoIncrement) {
    #>
        // Check if key value entered
        if ($insertRow && $this->ValidateKey && strval($rsnew['<#= SingleQuote(f.FldName) #>']) == "") {
            $this->setFailureMessage($Language->phrase("InvalidKeyValue"));
            $insertRow = false;
        }
    <#
            }
        } // Field

        // Get number of non-autoincrement key fields
        let autoIncKeyCount = keyFields.filter(f => f.FldAutoIncrement).length,
            nonAutoIncKeyCount = keyFields.length - autoIncKeyCount;
        if (autoIncKeyCount == 0 && nonAutoIncKeyCount > 0) {
    #>
        // Check for duplicate key
        if ($insertRow && $this->ValidateKey) {
            $filter = $this->getRecordFilter($rsnew);
            $rsChk = $this->loadRs($filter)->fetch();
            if ($rsChk !== false) {
                $keyErrMsg = str_replace("%f", $filter, $Language->phrase("DupKey"));
                $this->setFailureMessage($keyErrMsg);
                $insertRow = false;
            }
        }
        <#
        }
    #>
        $addRow = false;
        if ($insertRow) {
            try {
                $addRow = $this->insert($rsnew);
            } catch (\Exception $e) {
                $this->setFailureMessage($e->getMessage());
            }
            if ($addRow) {
        <#
                for (let f of currentFileTextFields) {
                    FIELD = f;
        #>
                <#= ScriptUpdateFile() #>
        <#
                } // Field
        #>
            }
        } else {
            if ($this->getSuccessMessage() != "" || $this->getFailureMessage() != "") {
                // Use the message, do nothing
            } elseif ($this->CancelMessage != "") {
                $this->setFailureMessage($this->CancelMessage);
                $this->CancelMessage = "";
            } else {
                $this->setFailureMessage($Language->phrase("InsertCancelled"));
            }
            $addRow = false;
        }

        <#
        if (ctrlId == "add" && isDetailAdd && detailTables.length > 0) {
    #>
        // Add detail records
        if ($addRow) {
            $detailTblVar = explode(",", $this->getCurrentDetailTable());
    <#
            for (let md of detailTables) {
                let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar,
                    detailTblName = detailTable.TblName,
                    detailPageObj = GetPageObject("grid", detailTable);
                if (detailPageObj && detailTable.TblType != "REPORT") {
    #>
            $detailPage = Container("<#= detailPageObj #>");
            if (in_array("<#= detailTblVar #>", $detailTblVar) && $detailPage->DetailAdd) {
    <#
                            for (let rel of md.Relations) {
                                let masterField = GetFieldObject(TABLE, rel.MasterField),
                                masterFldParm = masterField.FldParm,
                                detailField = GetFieldObject(detailTable, rel.DetailField),
                                detailFldParm = detailField.FldParm;
    #>
                $detailPage-><#= detailFldParm #>->setSessionValue($this-><#= masterFldParm #>->CurrentValue); // Set master key
    <#
                            } // MasterDetailField
    #>
                <# if (hasUserTable) { #>
                $Security->loadCurrentUserLevel($this->ProjectID . "<#= Quote(detailTblName) #>"); // Load user level of detail table
                <# } #>
                $addRow = $detailPage->gridInsert();
                <# if (hasUserTable) { #>
                $Security->loadCurrentUserLevel($this->ProjectID . $this->TableName); // Restore user level of master table
                <# } #>
                if (!$addRow) {
    <#
                    for (let rel of md.Relations) {
                        let detailField = GetFieldObject(detailTable, rel.DetailField),
                            detailFldParm = detailField.FldParm;
    #>
                $detailPage-><#= detailFldParm #>->setSessionValue(""); // Clear master key if insert failed
    <#
                    } // MasterDetailField
    #>
                }
            }
    <#
                }
            } // MasterDetail
    #>
        }
    <#
        }
    #>

        <# if (ctrlId == "add" && isDetailAdd && detailTables.length > 0) { #>
        // Commit/Rollback transaction
        if ($this->getCurrentDetailTable() != "") {
            if ($addRow) {
                $conn->commit(); // Commit transaction
            } else {
                $conn->rollback(); // Rollback transaction
            }
        }
        <# } #>

        if ($addRow) {
            <# if (ServerScriptExist("Table", "Row_Inserted")) { #>
            // Call Row Inserted event
            $this->rowInserted($rsold, $rsnew);
            <# } #>

            <# if (TABLE.TblSendMailOnAdd && (ctrlId == "list" && listAdd || ["add", "register"].includes(ctrlId))) { #>
            if ($this->SendEmail) {
                $this->sendEmailOnAdd($rsnew);
            }
            <# } #>

            <# if (ctrlId == "register" && ServerScriptExist("Other", "User_Registered")) { #>
            // Call User Registered event
            $this->userRegistered($rsnew);
            <# } #>
        }

        // Clean upload path if any
        if ($addRow) {
    <#
            for (let f of currentFileFields) {
                let fldName = f.FldName, fldParm = f.FldParm;
    #>
            // <#= fldName #>
            CleanUploadTempPath($this-><#= fldParm #>, $this-><#= fldParm #>->Upload->Index);
    <#
            } // Field
    #>
        }

        <#
        if (isDynamicUserLevel && TABLE.TblName == DB.UserLevelTbl && !IsEmpty(DB.UserLevelIdFld)) {
            let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
                userLevelIdFldParm = userLevelIdField.FldParm;
    #>
        if ($addRow) {
            // Add User Level priv
            if ($this->Priv > 0) {
                $userLevelList = $GLOBALS["USER_LEVELS"];
                $userLevelPrivList = $GLOBALS["USER_LEVEL_PRIVS"];
                $tableList = $GLOBALS["USER_LEVEL_TABLES"];
                $tableNameCount = count($tableList);
                for ($i = 0; $i < $tableNameCount; $i++) {
                    $sql = "INSERT INTO " . Config("USER_LEVEL_PRIV_TABLE") . " (" .
                    Config("USER_LEVEL_PRIV_TABLE_NAME_FIELD") . ", " .
                    Config("USER_LEVEL_PRIV_USER_LEVEL_ID_FIELD") . ", " .
                    Config("USER_LEVEL_PRIV_PRIV_FIELD") . ") VALUES ('" .
                    AdjustSql($tableList[$i][4] . $tableList[$i][0], Config("USER_LEVEL_PRIV_DBID")) .
                    "', " . $this-><#= userLevelIdFldParm #>->CurrentValue . ", " . $this->Priv . ")";
                    $conn->executeUpdate($sql);
                }
            }

            // Load user level information again
            $Security->setupUserLevel();
        }
    <#
        }
    #>

        // Write JSON for API request
        if (IsApi() && $addRow) {
            $row = $this->getRecordsFromRecordset([$rsnew], true);
            WriteJson(["success" => true, $this->TableVar => $row]);
        }

        return $addRow;
    }

<# } #>

<# if (ctrlId == "list" && (useExtendedBasicSearch || useAdvancedSearch) || ctrlId == "search") { #>

    // Load advanced search
    public function loadAdvancedSearch()
    {
    <#
            for (let f of allFields) {
                //if (IsFieldExtendedSearch(f) || IsFieldAdvancedSearch(f)) {
                if (IsFieldExtendedSearch(f) || f.FldSearch) {
                    if (!(f.FldHtmlTag == "FILE" && IsBinaryField(f))) {
                        let fldParm = f.FldParm;
                        //fldOpr = "z_" + fldParm;
                        //fldVar2 = "y_" + fldParm;
                        //fldOpr2 = "w_" + fldParm;
                        //fldCond = "v_" + fldParm;
                        //fldSrchOpr = f.FldSrchOpr;
                        //fldSrchOpr2 = f.FldSrchOpr2;
                        //isUserSelect = f.FldSrchOpr == "USER SELECT" && GetFieldType(f.FldType) != 4;
    #>
        $this-><#= fldParm #>->AdvancedSearch->load();
    <#
                    }
                }
            } // Field
     #>
    }

<# } #>

<# if (exportSelectedOnly && ctrlId == "list") { #>

    // Build export filter for selected records
    protected function buildExportSelectedFilter()
    {
        global $Language;
        $wrkFilter = "";
        if ($this->isExport()) {
            $wrkFilter = $this->getFilterFromRecordKeys();
        }
        return $wrkFilter;
    }

<# } #>

<# if (["summary", "crosstab"].includes(ctrlId) || ctrlId == "list" && listExport || ctrlId == "view" && viewExport) { #>
    // Get export HTML tag
    protected function getExportTag($type, $custom = false)
    {
        global $Language;

        $pageUrl = $this->pageUrl();

        <# if (["summary", "crosstab"].includes(ctrlId)) { #>

        if (SameText($type, "excel")) {
            return '<a class="ew-export-link ew-excel" title="' . HtmlEncode($Language->phrase("ExportToExcel", true)) . '" data-caption="' . HtmlEncode($Language->phrase("ExportToExcel", true)) . '" href="#" onclick="return ew.exportWithCharts(event, \'' . $this->ExportExcelUrl . '\', \'' . session_id() . '\');">' . <#= exportToExcelCaption #> . '</a>';
        } elseif (SameText($type, "word")) {
            return '<a class="ew-export-link ew-word" title="' . HtmlEncode($Language->phrase("ExportToWord", true)) . '" data-caption="' . HtmlEncode($Language->phrase("ExportToWord", true)) . '" href="#" onclick="return ew.exportWithCharts(event, \'' . $this->ExportWordUrl . '\', \'' . session_id() . '\');">' . <#= exportToWordCaption #> . '</a>';
        } elseif (SameText($type, "pdf")) {
            return '<a class="ew-export-link ew-pdf" title="' . HtmlEncode($Language->phrase("ExportToPDF", true)) . '" data-caption="' . HtmlEncode($Language->phrase("ExportToPDF", true)) . '" href="#" onclick="return ew.exportWithCharts(event, \'' . $this->ExportPdfUrl . '\', \'' . session_id() . '\');">' . <#= exportToPdfCaption #> . '</a>';
        } elseif (SameText($type, "email")) {
            $url = $pageUrl . "export=email" . ($custom ? "&amp;custom=1" : "");
            return '<a class="ew-export-link ew-email" title="' . HtmlEncode($Language->phrase("ExportToEmail", true)) . '" data-caption="' . HtmlEncode($Language->phrase("ExportToEmail", true)) . '" id="emf_<#= tblVar #>" href="#" onclick="return ew.emailDialogShow({ lnk: \'emf_<#= tblVar #>\', hdr: ew.language.phrase(\'ExportToEmailText\'), url: \'' . $url . '\', exportid: \'' . session_id() . '\', el: this });">' . <#= exportToEmailCaption #> . '</a>';
        } elseif (SameText($type, "print")) {
            return <#= exportPrintUrl #> . <#= printerFriendlyCaption #> . "<#= exportEndTag #>";
        }


        <# } else { #>

        if (SameText($type, "excel")) {
            if ($custom) {
                return <#= customExportExcelUrl #> . <#= exportToExcelCaption #> . "<#= exportEndTag #>";
            } else {
                return <#= exportExcelUrl #> . <#= exportToExcelCaption #> . "<#= exportEndTag #>";
            }
        } elseif (SameText($type, "word")) {
            if ($custom) {
                return <#= customExportWordUrl #> . <#= exportToWordCaption #> . "<#= exportEndTag #>";
            } else {
                return <#= exportWordUrl #> . <#= exportToWordCaption #> . "<#= exportEndTag #>";
            }
        } elseif (SameText($type, "pdf")) {
            if ($custom) {
                return <#= customExportPdfUrl #> . <#= exportToPdfCaption #> . "<#= exportEndTag #>";
            } else {
                return <#= exportPdfUrl #> . <#= exportToPdfCaption #> . "<#= exportEndTag #>";
            }
        } elseif (SameText($type, "html")) {
            return <#= exportHtmlUrl #> . <#= exportToHtmlCaption #> . "<#= exportEndTag #>";
        } elseif (SameText($type, "xml")) {
            return <#= exportXmlUrl #> . <#= exportToXmlCaption #> . "<#= exportEndTag #>";
        } elseif (SameText($type, "csv")) {
            return <#= exportCsvUrl #> . <#= exportToCsvCaption #> . "<#= exportEndTag #>";
        } elseif (SameText($type, "email")) {
            $url = $custom ? ",url:'" . $pageUrl . "export=email&amp;custom=1'" : "";
            <# if (ctrlId == "list") { #>
            return '<button id="emf_<#= tblVar #>" class="ew-export-link ew-email" title="' . $Language->phrase("ExportToEmailText") . '" data-caption="' . $Language->phrase("ExportToEmailText") . '" onclick="ew.emailDialogShow({lnk:\'emf_<#= tblVar #>\', hdr:ew.language.phrase(\'ExportToEmailText\'), f:<#= jsFormName #>, sel:<#= JsBool(exportSelectedOnly) #>' . $url . '});">' . <#= exportToEmailCaption #> . '</button>';
            <# } else if (ctrlId == "view") { #>
            return '<button id="emf_<#= tblVar #>" class="ew-export-link ew-email" title="' . $Language->phrase("ExportToEmailText") . '" data-caption="' . $Language->phrase("ExportToEmailText") . '" onclick="ew.emailDialogShow({lnk:\'emf_<#= tblVar #>\', hdr:ew.language.phrase(\'ExportToEmailText\'), f:<#= jsFormName #>, key:' . ArrayToJsonAttribute($this->RecKey) . ', sel:false' . $url . '});">' . <#= exportToEmailCaption #> . '</button>';
            <# } #>
        } elseif (SameText($type, "print")) {
            return <#= exportPrintUrl #> . <#= printerFriendlyCaption #> . "<#= exportEndTag #>";
        }

        <# } #>
    }

    // Set up export options
    protected function setupExportOptions()
    {
        global $Language;

        // Printer friendly
        $item = &$this->ExportOptions->add("print");
        $item->Body = $this->getExportTag("print");
        $item->Visible = <#= Code.bool(printerFriendly) #>;

        // Export to Excel
        $item = &$this->ExportOptions->add("excel");
        <# if (UseCustomTemplate) { #>
        $item->Body = $this->getExportTag("excel", $this->ExportExcelCustom);
        <# } else { #>
        $item->Body = $this->getExportTag("excel");
        <# } #>
        $item->Visible = <#= Code.bool(exportExcel) #>;

        // Export to Word
        $item = &$this->ExportOptions->add("word");
        <# if (UseCustomTemplate) { #>
        $item->Body = $this->getExportTag("word", $this->ExportWordCustom);
        <# } else { #>
        $item->Body = $this->getExportTag("word");
        <# } #>
        $item->Visible = <#= Code.bool(exportWord) #>;

        <# if (["list", "view"].includes(ctrlId)) { #>

        // Export to Html
        $item = &$this->ExportOptions->add("html");
        $item->Body = $this->getExportTag("html");
        $item->Visible = <#= Code.bool(exportHtml) #>;

        // Export to Xml
        $item = &$this->ExportOptions->add("xml");
        $item->Body = $this->getExportTag("xml");
        $item->Visible = <#= Code.bool(exportXml) #>;

        // Export to Csv
        $item = &$this->ExportOptions->add("csv");
        $item->Body = $this->getExportTag("csv");
        $item->Visible = <#= Code.bool(exportCsv) #>;

        <# } #>

        // Export to Pdf
        $item = &$this->ExportOptions->add("pdf");
        <# if (UseCustomTemplate) { #>
        $item->Body = $this->getExportTag("pdf", $this->ExportPdfCustom);
        <# } else { #>
        $item->Body = $this->getExportTag("pdf");
        <# } #>
        $item->Visible = <#= Code.bool(exportPdf) #>;

        // Export to Email
        $item = &$this->ExportOptions->add("email");
        <# if (UseCustomTemplate) { #>
        $item->Body = $this->getExportTag("email", $this->ExportEmailCustom);
        <# } else { #>
        $item->Body = $this->getExportTag("email");
        <# } #>
        $item->Visible = <#= Code.bool(exportEmail) #>;

        // Drop down button for export
        $this->ExportOptions->UseButtonGroup = true;
        $this->ExportOptions->UseDropDownButton = <#= Code.bool(useDropDownForExport) #>;
        <# if (PROJ.UseDropdownForMobile) { #>
        if ($this->ExportOptions->UseButtonGroup && IsMobile()) {
            $this->ExportOptions->UseDropDownButton = true;
        }
        <# } #>
        $this->ExportOptions->DropDownButtonPhrase = $Language->phrase("ButtonExport");

        // Add group option item
        $item = &$this->ExportOptions->add($this->ExportOptions->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;

        <# if (["view", "summary", "crosstab"].includes(ctrlId)) { #>
        // Hide options for export
        if ($this->isExport()) {
            $this->ExportOptions->hideAllOptions();
        }
        <# } #>
    }
<# } #>

<# if (["summary", "crosstab", "list"].includes(ctrlId)) { #>

    // Set up search options
    protected function setupSearchOptions()
    {
        global $Language, $Security;

        $pageUrl = $this->pageUrl();

        $this->SearchOptions = new ListOptions("div");
        $this->SearchOptions->TagClassName = "ew-search-option";

    <# if (useBasicSearch || useExtendedBasicSearch) { #>

    <#
        let searchToggleClass = PROJ.SearchPanelCollapsed ? "" : " active";
    #>
        // Search button
        $item = &$this->SearchOptions->add("searchtoggle");
        $searchToggleClass = ($this->SearchWhere != "") ? " active" : "<#= searchToggleClass #>";
        $item->Body = "<a class=\"btn btn-default ew-search-toggle" . $searchToggleClass . "\" href=\"#\" role=\"button\" title=\"" . $Language->phrase("SearchPanel") . "\" data-caption=\"" . $Language->phrase("SearchPanel") . "\" data-toggle=\"button\" data-form=\"<#= formNameSearch #>\" aria-pressed=\"" . ($searchToggleClass == " active" ? "true" : "false") . "\">" . $Language->phrase("SearchLink") . "</a>";
        $item->Visible = true;

        <# } #>

        <# if (useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) { #>

        <#
        let resetBtn, resetPhrase;
        if (showBlankListPage || hasSearchDefault) {
            resetBtn = Code.languagePhrase("ResetSearchBtn");
            resetPhrase = Code.languagePhrase("ResetSearch");
        } else {
            resetBtn = Code.languagePhrase("ShowAllBtn");
            resetPhrase = Code.languagePhrase("ShowAll");
        }
    #>

        // Show all button
        $item = &$this->SearchOptions->add("showall");
        $item->Body = "<a class=\"btn btn-default ew-show-all\" title=\"" . <#= resetPhrase #> . "\" data-caption=\"" . <#= resetPhrase #> . "\" href=\"" . $pageUrl . "cmd=reset\">" . <#= resetBtn #> . "</a>";
        $item->Visible = ($this->SearchWhere != $this->DefaultSearchWhere && $this->SearchWhere != "0=101");

    <# } #>

    <# if (useAdvancedSearch) { #>

        // Advanced search button
        $item = &$this->SearchOptions->add("advancedsearch");
        <# if (useModalSearch) { #>
        if (IsMobile()) {
            $item->Body = "<a class=\"btn btn-default ew-advanced-search\" title=\"" . $Language->phrase("AdvancedSearch") . "\" data-caption=\"" . $Language->phrase("AdvancedSearch") . "\" href=\"<#= searchPage #>\">" . $Language->phrase("AdvancedSearchBtn") . "</a>";
        } else {
            $item->Body = "<a class=\"btn btn-default ew-advanced-search\" title=\"" . $Language->phrase("AdvancedSearch") . "\" data-table=\"<#= tblVar #>\" data-caption=\"" . $Language->phrase("AdvancedSearch") . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'SearchBtn',url:'<#= searchPage #>'});\">" . $Language->phrase("AdvancedSearchBtn") . "</a>";
        }
        <# } else { #>
        $item->Body = "<a class=\"btn btn-default ew-advanced-search\" title=\"" . $Language->phrase("AdvancedSearch") . "\" data-caption=\"" . $Language->phrase("AdvancedSearch") . "\" href=\"<#= searchPage #>\">" . $Language->phrase("AdvancedSearchBtn") . "</a>";
        <# } #>
        $item->Visible = true;

    <# } #>

    <# if ((useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) && TABLE.TblSearchHighlight) { #>

        // Search highlight button
        $item = &$this->SearchOptions->add("searchhighlight");
        $item->Body = "<a class=\"btn btn-default ew-highlight active\" href=\"#\" role=\"button\" title=\"" . $Language->phrase("Highlight") . "\" data-caption=\"" . $Language->phrase("Highlight") . "\" data-toggle=\"button\" data-form=\"<#= formNameSearch #>\" data-name=\"" . $this->highlightName() . "\">" . $Language->phrase("HighlightBtn") . "</a>";
        $item->Visible = ($this->SearchWhere != "" && $this->TotalRecords > 0);

    <# } #>

        // Button group for search
        $this->SearchOptions->UseDropDownButton = false;
        $this->SearchOptions->UseButtonGroup = true;
        $this->SearchOptions->DropDownButtonPhrase = $Language->phrase("ButtonSearch");

        // Add group option item
        $item = &$this->SearchOptions->add($this->SearchOptions->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;

        // Hide search options
        if ($this->isExport() || $this->CurrentAction) {
            $this->SearchOptions->hideAllOptions();
        }
        <# if (hasUserTable) { #>
        if (!$Security->canSearch()) {
            $this->SearchOptions->hideAllOptions();
            $this->FilterOptions->hideAllOptions();
        }
        <# } #>

    }

<# } #>

<# if (ctrlId == "list" && isImport) { #>

    <#
    // Set up import link visibility
    let importVisible = SecurityCheck("Import", isSecurityEnabled, isSecurityEnabled);
    if (importVisible == "") {
        importVisible = Code.true;
    }
#>

    // Set up import options
    protected function setupImportOptions()
    {
        global $Security, $Language;

        // Import
        $item = &$this->ImportOptions->add("import");
        $item->Body = "<a class=\"ew-import-link ew-import\" href=\"#\" role=\"button\" title=\"" . $Language->phrase("ImportText") . "\" data-caption=\"" . $Language->phrase("ImportText") . "\" onclick=\"return ew.importDialogShow({lnk:this,hdr:ew.language.phrase('ImportText')});\">" . <#= importCaption #> . "</a>";
        $item->Visible = <#= importVisible #>;

        $this->ImportOptions->UseButtonGroup = true;
        $this->ImportOptions->UseDropDownButton = false;
        $this->ImportOptions->DropDownButtonPhrase = $Language->phrase("ButtonImport");

        // Add group option item
        $item = &$this->ImportOptions->add($this->ImportOptions->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;
    }

<# } #>

<#
    if (exportHtml || exportEmail || exportCsv || exportWord || exportExcel || exportXml || exportPdf) {
        if (ctrlId == "list" && listExport || ctrlId == "view" && viewExport) {
            let exportStyle = "h", // Horizontal
            exportPageType = "";
            if (ctrlId == "view") {
                exportStyle = "v"; // Vertical
                exportPageType = "view";
            }
#>

    /**
    * Export data in HTML/CSV/Word/Excel/XML/Email/PDF format
    *
    * @param bool $return Return the data rather than output it
    * @return mixed
    */
    public function exportData($return = false)
    {
        global $Language;
        $utf8 = SameText(Config("PROJECT_CHARSET"), "utf-8");

        // Load recordset
        <# if (ctrlId == "list") { #>
        $this->TotalRecords = $this->listRecordCount();
        <# } else { #>
        if (!$this->Recordset) {
            $this->Recordset = $this->loadRecordset();
        }
        $rs = &$this->Recordset;
        if ($rs) {
            $this->TotalRecords = $rs->recordCount();
        }
        <# } #>

        $this->StartRecord = 1;

        <# if (ctrlId == "list") { #>

        // Export all
        if ($this->ExportAll) {
            if (Config("EXPORT_ALL_TIME_LIMIT") >= 0) {
                @set_time_limit(Config("EXPORT_ALL_TIME_LIMIT"));
            }
            $this->DisplayRecords = $this->TotalRecords;
            $this->StopRecord = $this->TotalRecords;
        } else { // Export one page only
            $this->setupStartRecord(); // Set up start record position
            // Set the last record to display
            if ($this->DisplayRecords <= 0) {
                $this->StopRecord = $this->TotalRecords;
            } else {
                $this->StopRecord = $this->StartRecord + $this->DisplayRecords - 1;
            }
        }
        $rs = $this->loadRecordset($this->StartRecord - 1, $this->DisplayRecords <= 0 ? $this->TotalRecords : $this->DisplayRecords);

        <# } else { #>

        $this->setupStartRecord(); // Set up start record position

        // Set the last record to display
        if ($this->DisplayRecords <= 0) {
            $this->StopRecord = $this->TotalRecords;
        } else {
            $this->StopRecord = $this->StartRecord + $this->DisplayRecords - 1;
        }

        <# } #>

        $this->ExportDoc = GetExportDocument($this, "<#= exportStyle #>");
        $doc = &$this->ExportDoc;
        if (!$doc) {
            $this->setFailureMessage($Language->phrase("ExportClassNotFound")); // Export class not found
        }

        if (!$rs || !$doc) {
            RemoveHeader("Content-Type"); // Remove header
            RemoveHeader("Content-Disposition");
            $this->showMessage();
            return;
        }

        $this->StartRecord = 1;
        $this->StopRecord = $this->DisplayRecords <= 0 ? $this->TotalRecords : $this->DisplayRecords;

        <# if (ServerScriptExist(eventCtrlType, "Page_Exporting")) { #>
        // Call Page Exporting server event
        $this->ExportDoc->ExportCustom = !$this->pageExporting();
        <# } #>

    <#
    if (ctrlId == "list" && masterTables.length > 0) {
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
    #>
        // Export master record
        if (Config("EXPORT_MASTER_RECORD") && $this->getMasterFilter() != "" && $this->getCurrentMasterTable() == "<#= masterTblVar #>") {
            $<#= masterTblVar #> = Container("<#= masterTblVar #>");
            $rsmaster = $<#= masterTblVar #>->loadRs($this->DbMasterFilter); // Load master record
            if ($rsmaster) {
                <# if (showVerticalMasterRecord) { #>
                $exportStyle = $doc->Style;
                $doc->setStyle("v"); // Change to vertical
                <# } #>
                if (!$this->isExport("csv") || Config("EXPORT_MASTER_RECORD_FOR_CSV")) {
                    $doc->Table = $<#= masterTblVar #>;
                    $<#= masterTblVar #>->exportDocument($doc, new Recordset($rsmaster));
                    $doc->exportEmptyRow();
                    $doc->Table = &$this;
                }
                <# if (showVerticalMasterRecord) { #>
                $doc->setStyle($exportStyle); // Restore
                <# } #>
                $rsmaster->closeCursor();
            }
        }
    <#
        } // MasterDetail
    }
    #>

    <# if (ServerScriptExist(eventCtrlType, "Page_DataRendering")) { #>
        $header = $this->PageHeader;
        $this->pageDataRendering($header);
        $doc->Text .= $header;
    <# } #>

        $this->exportDocument($doc, $rs, $this->StartRecord, $this->StopRecord, "<#= exportPageType #>");

    <#
        if (ctrlId == "view" && detailTables.length > 0) {
            for (let md of detailTables) {
                let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar;
    #>
        // Export detail records (<#= detailTblVar #>)
        if (Config("EXPORT_DETAIL_RECORDS") && in_array("<#= detailTblVar #>", explode(",", $this->getCurrentDetailTable()))) {
            $<#= detailTblVar #> = Container("<#= detailTblVar #>");
            $rsdetail = $<#= detailTblVar #>->loadRs($<#= detailTblVar #>->getDetailFilter()); // Load detail records
            if ($rsdetail) {
                $exportStyle = $doc->Style;
                $doc->setStyle("h"); // Change to horizontal
                if (!$this->isExport("csv") || Config("EXPORT_DETAIL_RECORDS_FOR_CSV")) {
                    $doc->exportEmptyRow();
                    $detailcnt = $rsdetail->rowCount();
                    $oldtbl = $doc->Table;
                    $doc->Table = $<#= detailTblVar #>;
                    $<#= detailTblVar #>->exportDocument($doc, new Recordset($rsdetail), 1, $detailcnt);
                    $doc->Table = $oldtbl;
                }
                $doc->setStyle($exportStyle); // Restore
                $rsdetail->closeCursor();
            }
        }
    <#
            } // MasterDetail
        }
    #>

        <# if (ServerScriptExist(eventCtrlType, "Page_DataRendered")) { #>
        $footer = $this->PageFooter;
        $this->pageDataRendered($footer);
        $doc->Text .= $footer;
        <# } #>

        // Close recordset
        $rs->close();

        <# if (ServerScriptExist(eventCtrlType, "Page_Exported")) { #>
        // Call Page Exported server event
        $this->pageExported();
        <# } #>

        // Export header and footer
        $doc->exportHeaderAndFooter();

        // Clean output buffer (without destroying output buffer)
        $buffer = ob_get_contents(); // Save the output buffer
        if (!Config("DEBUG") && $buffer) {
            ob_clean();
        }

        // Write debug message if enabled
        if (Config("DEBUG") && !$this->isExport("pdf")) {
            echo GetDebugMessage();
        }

        // Output data
        if ($this->isExport("email")) {
            <# if (exportEmail) { #>
            if ($return) {
                return $doc->Text; // Return email content
            } else {
                echo $this->exportEmail($doc->Text); // Send email
            }
            <# } else { #>
            // Export-to-email disabled
            <# } #>
        } else {
            $doc->export();
            if ($return) {
                RemoveHeader("Content-Type"); // Remove header
                RemoveHeader("Content-Disposition");
                $content = ob_get_contents();
                if ($content) {
                    ob_clean();
                }
                if ($buffer) {
                    echo $buffer; // Resume the output buffer
                }
                return $content;
            }
        }
    }

<#
        }
    }
#>

<#
if (exportEmail) {
    if (ctrlId == "list" && listExport || ctrlId == "view" && viewExport) {
#>

    // Export email
    protected function exportEmail($emailContent)
    {
        global $TempImages, $Language;
        $sender = Post("sender", "");
        $recipient = Post("recipient", "");
        $cc = Post("cc", "");
        $bcc = Post("bcc", "");

        // Subject
        $subject = Post("subject", "");
        $emailSubject = $subject;

        // Message
        $content = Post("message", "");
        $emailMessage = $content;

        // Check sender
        if ($sender == "") {
            return "<p class=\"text-danger\">" . str_replace("%s", $Language->phrase("Sender"), $Language->phrase("EnterRequiredField")) . "</p>";
        }

        if (!CheckEmail($sender)) {
            return "<p class=\"text-danger\">" . $Language->phrase("EnterProperSenderEmail") . "</p>";
        }

        // Check recipient
        if ($recipient == "") {
            return "<p class=\"text-danger\">" . str_replace("%s", $Language->phrase("Recipient"), $Language->phrase("EnterRequiredField")) . "</p>";
        }

        if (!CheckEmailList($recipient, Config("MAX_EMAIL_RECIPIENT"))) {
            return "<p class=\"text-danger\">" . $Language->phrase("EnterProperRecipientEmail") . "</p>";
        }

        // Check cc
        if (!CheckEmailList($cc, Config("MAX_EMAIL_RECIPIENT"))) {
            return "<p class=\"text-danger\">" . $Language->phrase("EnterProperCcEmail") . "</p>";
        }

        // Check bcc
        if (!CheckEmailList($bcc, Config("MAX_EMAIL_RECIPIENT"))) {
            return "<p class=\"text-danger\">" . $Language->phrase("EnterProperBccEmail") . "</p>";
        }

        // Check email sent count
        $_SESSION[Config("EXPORT_EMAIL_COUNTER")] = Session(Config("EXPORT_EMAIL_COUNTER")) ?? 0;

        if ((int)Session(Config("EXPORT_EMAIL_COUNTER")) > Config("MAX_EMAIL_SENT_COUNT")) {
            return "<p class=\"text-danger\">" . $Language->phrase("ExceedMaxEmailExport") . "</p>";
        }

        // Send email
        $email = new Email();
        $email->Sender = $sender; // Sender
        $email->Recipient = $recipient; // Recipient
        $email->Cc = $cc; // Cc
        $email->Bcc = $bcc; // Bcc
        $email->Subject = $emailSubject; // Subject
        $email->Format = "html";
        if ($emailMessage != "") {
            $emailMessage = RemoveXss($emailMessage) . "<br><br>";
        }
        foreach ($TempImages as $tmpImage) {
            $email->addEmbeddedImage($tmpImage);
        }
        $email->Content = $emailMessage . CleanEmailContent($emailContent); // Content

        <# if (ServerScriptExist("Table", "Email_Sending")) { #>
        $eventArgs = [];
        if ($this->Recordset) {
            $eventArgs["rs"] = &$this->Recordset;
        }
        $emailSent = false;
        if ($this->emailSending($email, $eventArgs)) {
            $emailSent = $email->send();
        }
        <# } else { #>
        $emailSent = $email->send();
        <# } #>

        // Check email sent status
        if ($emailSent) {
            // Update email sent count
            $_SESSION[Config("EXPORT_EMAIL_COUNTER")]++;

            // Sent email success
            return "<p class=\"text-success\">" . $Language->phrase("SendEmailSuccess") . "</p>"; // Set up success message
        } else {
            // Sent email failure
            return "<p class=\"text-danger\">" . $email->SendErrDescription . "</p>";
        }
    }

<#
            }
        } // ExportEmail
#>

<#
    if (hasUserIdFld) {
        if (["list", "grid", "view", "add", "edit", "update", "delete"].includes(ctrlId)) {
#>

    // Show link optionally based on User ID
    protected function showOptionLink($id = "")
    {
        global $Security;
        if ($Security->isLoggedIn() && !$Security->isAdmin() && !$this->userIDAllow($id)) {
            return $Security->isValidUserID($this-><#= userIdFldParm #>->CurrentValue);
        }
        return true;
    }

<#
            }
        }
#>

<#
    if (masterTables.length > 0) {
        if (["list", "grid", "view", "add", "edit", "delete"].includes(ctrlId) && TABLE.TblType != "REPORT" ||
        ["summary", "crosstab"].includes(ctrlId) && TABLE.TblType == "REPORT") {
#>

    // Set up master/detail based on QueryString
    protected function setupMasterParms()
    {

    <# if (ctrlId == "grid") { #>

        // Hide foreign keys
        $masterTblVar = $this->getCurrentMasterTable();
    <#
        // Build master/detail information
        for (let md of masterTables) {
            let masterTable = GetTableObject(md.MasterTable),
            masterTblVar = masterTable.TblVar;
    #>
        if ($masterTblVar == "<#= masterTblVar #>") {
            $masterTbl = Container("<#= masterTblVar #>");
    <#
    for (let rel of md.Relations) {
        let detailField = GetFieldObject(TABLE, rel.DetailField),
        detailFldParm = detailField.FldParm;
        // Do not hide parent fields
        if (!currentFields.some(f => IsLinkTableField(f) && IsLookupField(f) &&
            [f.FldParentSelect, f.FldParentSelect2, f.FldParentSelect3, f.FldParentSelect4].includes(detailField.FldName))) {
    #>
            $this-><#= detailFldParm #>->Visible = false;
    <#
        }
    #>
            if ($masterTbl->EventCancelled) {
                $this->EventCancelled = true;
            }
    <#
            } // MasterDetailField
    #>
        }
    <#
        } // MasterDetail
    #>

    <# } else { #>

        $validMaster = false;
        // Get the keys for master table
        if (($master = Get(Config("TABLE_SHOW_MASTER"), Get(Config("TABLE_MASTER")))) !== null) {
            $masterTblVar = $master;
            if ($masterTblVar == "") {
                $validMaster = true;
                $this->DbMasterFilter = "";
                $this->DbDetailFilter = "";
            }
        <#
            // Build master/detail information
            for (let md of masterTables) {
                let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
        #>
            if ($masterTblVar == "<#= masterTblVar #>") {
                $validMaster = true;
                $masterTbl = Container("<#= masterTblVar #>");
        <#
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(masterTable, rel.MasterField),
                    masterFldParm = masterField.FldParm,
                    detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFldParm = detailField.FldParm;
        #>
                if (($parm = Get("fk_<#= masterFldParm #>", Get("<#= detailFldParm #>"))) !== null) {
                    $masterTbl-><#= masterFldParm #>->setQueryStringValue($parm);
                    $this-><#= detailFldParm #>->setQueryStringValue($masterTbl-><#= masterFldParm #>->QueryStringValue);
                    $this-><#= detailFldParm #>->setSessionValue($this-><#= detailFldParm #>->QueryStringValue);
                    <# if (GetFieldType(masterField.FldType) == 1) { #>
                    if (!is_numeric($masterTbl-><#= masterFldParm #>->QueryStringValue)) {
                        $validMaster = false;
                    }
                    <# } #>
                } else {
                    $validMaster = false;
                }
        <#
            } // MasterDetailField
        #>
            }
        <#
            } // MasterDetail
        #>
        } elseif (($master = Post(Config("TABLE_SHOW_MASTER"), Post(Config("TABLE_MASTER")))) !== null) {
            $masterTblVar = $master;
            if ($masterTblVar == "") {
                    $validMaster = true;
                    $this->DbMasterFilter = "";
                    $this->DbDetailFilter = "";
            }
        <#
            // Build master/detail information
            for (let md of masterTables) {
                let masterTable = GetTableObject(md.MasterTable),
                    masterTblVar = masterTable.TblVar;
            #>
            if ($masterTblVar == "<#= masterTblVar #>") {
                $validMaster = true;
                $masterTbl = Container("<#= masterTblVar #>");
            <#
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(masterTable, rel.MasterField),
                        masterFldParm = masterField.FldParm,
                        detailField = GetFieldObject(TABLE, rel.DetailField),
                        detailFldParm = detailField.FldParm;
            #>
                if (($parm = Post("fk_<#= masterFldParm #>", Post("<#= detailFldParm #>"))) !== null) {
                    $masterTbl-><#= masterFldParm #>->setFormValue($parm);
                    $this-><#= detailFldParm #>->setFormValue($masterTbl-><#= masterFldParm #>->FormValue);
                    $this-><#= detailFldParm #>->setSessionValue($this-><#= detailFldParm #>->FormValue);
                    <# if (GetFieldType(masterField.FldType) == 1) { #>
                    if (!is_numeric($masterTbl-><#= masterFldParm #>->FormValue)) {
                        $validMaster = false;
                    }
                    <# } #>
                } else {
                    $validMaster = false;
                }
        <#
                } // MasterDetailField
        #>
            }
        <#
            } // MasterDetail
        #>
        }

        if ($validMaster) {

            // Save current master table
            $this->setCurrentMasterTable($masterTblVar);
        <# if (["view", "edit"].includes(ctrlId)) { #>
            $this->setSessionWhere($this->getDetailFilter());
        <# } #>

        <# if (ctrlId == "list") { #>
            // Update URL
            $this->AddUrl = $this->addMasterUrl($this->AddUrl);
            $this->InlineAddUrl = $this->addMasterUrl($this->InlineAddUrl);
            $this->GridAddUrl = $this->addMasterUrl($this->GridAddUrl);
            $this->GridEditUrl = $this->addMasterUrl($this->GridEditUrl);
        <# } #>

            <#
                if (TABLE.TblType != "REPORT") {
            #>
            // Reset start record counter (new master key)
            if (!$this->isAddOrEdit()) {
                $this->StartRecord = 1;
                $this->setStartRecordNumber($this->StartRecord);
            }
            <#
                }
            #>

            // Clear previous master key from Session
        <#
            for (let md of masterTables) {
                let masterTable = GetTableObject(md.MasterTable),
                masterTblVar = masterTable.TblVar;
        #>
            if ($masterTblVar != "<#= masterTblVar #>") {
        <#
                for (let rel of md.Relations) {
                    let masterField = GetFieldObject(masterTable, rel.MasterField),
                    masterFldParm = masterField.FldParm,
                    detailField = GetFieldObject(TABLE, rel.DetailField),
                    detailFldParm = detailField.FldParm;
        #>
                if ($this-><#= detailFldParm #>->CurrentValue == "") {
                    $this-><#= detailFldParm #>->setSessionValue("");
                }
        <#
            } // MasterDetailField
        #>
            }
        <#
            } // MasterDetail
        #>
        }

        <# } #>

        $this->DbMasterFilter = $this->getMasterFilter(); // Get master filter
        $this->DbDetailFilter = $this->getDetailFilter(); // Get detail filter
    }
<#
        }
    }
#>

<#
    if (detailTables.length > 0) {
        if ((ctrlId == "view" && isDetailView || ctrlId == "add" && isDetailAdd || ctrlId == "edit" && isDetailEdit) && TABLE.TblType != "REPORT") {
            let detailProp = "";
            if (ctrlId == "view")
                detailProp = "DetailView";
            else if (ctrlId == "edit")
                detailProp = "DetailEdit";
            else if (ctrlId == "add")
                detailProp = "DetailAdd";
#>

    // Set up detail parms based on QueryString
    protected function setupDetailParms()
    {
        // Get the keys for master table
        $detailTblVar = Get(Config("TABLE_SHOW_DETAIL"));
        if ($detailTblVar !== null) {
            $this->setCurrentDetailTable($detailTblVar);
        } else {
            $detailTblVar = $this->getCurrentDetailTable();
        }
        if ($detailTblVar != "") {
            $detailTblVar = explode(",", $detailTblVar);
        <#
            // Build master/detail information
            for (let md of detailTables) {
                let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar,
                    detailPageObj = GetPageObject("grid", detailTable);
                if (detailPageObj && detailTable.TblType != "REPORT") {
                    // Get all detail key fields not associated with this master table
                    let detailKeys = [], otherDetailKeys = [];
                    for (let rel of md.Relations)
                        detailKeys.push(rel.DetailField);
                    for (let md2 of MasterDetails) {
                        if (md2.DetailTable == md.DetailTable) {
                            md2.Relations.forEach(rel => {
                                let df = rel.DetailField;
                                if (!detailKeys.includes(df) && !otherDetailKeys.includes(df))
                                    otherDetailKeys.push(df);
                            });
                        }
                    }
        #>
            if (in_array("<#= detailTblVar #>", $detailTblVar)) {
                $detailPageObj = Container("<#= detailPageObj #>");
                if ($detailPageObj-><#= detailProp #>) {
                <# if (ctrlId == "add") { #>
                    if ($this->CopyRecord) {
                        $detailPageObj->CurrentMode = "copy";
                    } else {
                        $detailPageObj->CurrentMode = "add";
                    }
                    <# if (addConfirm) { #>
                    if ($this->isConfirm()) {
                        $detailPageObj->CurrentAction = "confirm";
                    } else {
                        $detailPageObj->CurrentAction = "gridadd";
                    }
                    if ($this->isCancel()) {
                        $detailPageObj->EventCancelled = true;
                    }
                    <# } else { #>
                    $detailPageObj->CurrentAction = "gridadd";
                    <# } #>
                <# } else if (ctrlId == "edit") { #>
                    $detailPageObj->CurrentMode = "edit";
                    <# if (editConfirm) { #>
                    if ($this->isConfirm()) {
                        $detailPageObj->CurrentAction = "confirm";
                    } else {
                        $detailPageObj->CurrentAction = "gridedit";
                    }
                    if ($this->isCancel()) {
                        $detailPageObj->EventCancelled = true;
                    }
                    <# } else { #>
                    $detailPageObj->CurrentAction = "gridedit";
                    <# } #>
                <# } else { #>
                    $detailPageObj->CurrentMode = "<#= ctrlId #>";
                <# } #>
                    // Save current master table to detail table
                    $detailPageObj->setCurrentMasterTable($this->TableVar);
                    $detailPageObj->setStartRecordNumber(1);
        <#
                    for (let rel of md.Relations) {
                        let masterField = GetFieldObject(TABLE, rel.MasterField),
                            masterFldParm = masterField.FldParm,
                            detailField = GetFieldObject(detailTable, rel.DetailField),
                            detailFldParm = detailField.FldParm;
        #>
                    $detailPageObj-><#= detailFldParm #>->IsDetailKey = true;
                    $detailPageObj-><#= detailFldParm #>->CurrentValue = $this-><#= masterFldParm #>->CurrentValue;
                    $detailPageObj-><#= detailFldParm #>->setSessionValue($detailPageObj-><#= detailFldParm #>->CurrentValue);
        <#
                    } // MasterDetailField

                    // Clear detail key fields not associated with this table
                    for (let otherKey of otherDetailKeys) {
                        let detailField = GetFieldObject(detailTable, otherKey),
                            detailFldParm = detailField.FldParm;
        #>
                    $detailPageObj-><#= detailFldParm #>->setSessionValue(""); // Clear session key
        <#
                    }
        #>
                }
            }
        <#
                }
            } // MasterDetail
        #>
        }
    }

    <# if (ctrlId == "add" && addConfirm || ctrlId == "edit" && editConfirm) { #>

        // Reset detail parms
    protected function resetDetailParms()
    {
        // Get the keys for master table
        $detailTblVar = Get(Config("TABLE_SHOW_DETAIL"));
        if ($detailTblVar !== null) {
            $this->setCurrentDetailTable($detailTblVar);
        } else {
            $detailTblVar = $this->getCurrentDetailTable();
        }
        if ($detailTblVar != "") {
            $detailTblVar = explode(",", $detailTblVar);
        <#
            // Build master/detail information
            for (let md of detailTables) {
                let detailTable = GetTableObject(md.DetailTable),
                    detailTblVar = detailTable.TblVar,
                    detailPageObj = GetPageObject("grid", detailTable);
                if (detailPageObj && detailTable.TblType != "REPORT") {
        #>
            if (in_array("<#= detailTblVar #>", $detailTblVar)) {
                $detailPageObj = Container("<#= detailPageObj #>");
                if ($detailPageObj-><#= detailProp #>) {
        <# if (ctrlId == "add") { #>
                    $detailPageObj->CurrentAction = "gridadd";
        <# } else if (ctrlId == "edit") { #>
                    $detailPageObj->CurrentAction = "gridedit";
        <# } #>
                }
            }
        <#
                }
            } // MasterDetail
        #>
        }
    }

    <# } #>

<#
        }
    }
#>

<# if (ctrlId != "grid") { #>
    // Set up Breadcrumb
    protected function setupBreadcrumb()
    {
        global $Breadcrumb, $Language;
        $Breadcrumb = new Breadcrumb("<#= homePage #>");
    <# if (["table", "report"].includes(ctrlType)) { #>
        $url = CurrentUrl();
        <# if (["list", "summary", "crosstab", "dashboard"].includes(ctrlId)) { #>
        $url = preg_replace('/\?cmd=reset(all){0,1}$/i', '', $url); // Remove cmd=reset / cmd=resetall
        $Breadcrumb->add("<#= ctrlId #>", $this->TableVar, $url, "", $this->TableVar, true);
        <# } else { #>
        $Breadcrumb->add("list", $this->TableVar, $this->addMasterUrl("<#= listPage #>"), "", $this->TableVar, true);
            <# if (ctrlId == "add") { #>
        $pageId = ($this->isCopy()) ? "Copy" : "Add";
            <# } else { #>
        $pageId = "<#= ctrlId #>";
            <# } #>
        $Breadcrumb->add("<#= ctrlId #>", $pageId, $url);
        <# } #>
    <# } #>
    }
<# } #>

<# if (useMultiPage) { #>
    // Set up multi pages
    protected function setupMultiPages()
    {
        $pages = new SubPages();
    <# if (multiPageType == "tabs" || multiPageType == "pills") { #>
        $pages->Style = "<#= multiPageType #>";
    <# } else { #>
        $pages->Parent = "#" . $this->PageObjName;
    <# } #>
    <#
        for (let i = 0; i <= pageCount; i++) {
    #>
        $pages->add(<#= i #>);
    <#
        } // Page
    #>
        $this->MultiPages = $pages;
    }
<# } #>

<# if (showMultiPageForDetails && ["add", "edit", "view"].includes(ctrlId)) { #>
    // Set up detail pages
    protected function setupDetailPages()
    {
        $pages = new SubPages();
    <# if (multiPageType == "tabs" || multiPageType == "pills") { #>
        $pages->Style = "<#= multiPageType #>";
    <# } #>
    <#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar;
            if (detailTable.TblType != "REPORT") {
    #>
        $pages->add('<#= SingleQuote(detailTblVar) #>');
    <#
            }
        }
    #>
        $this->DetailPages = $pages;
    }
<# } #>

<# if (ctrlId != "custom") { #>

    // Setup lookup options
    public function setupLookupOptions($fld)
    {
        if ($fld->Lookup !== null && $fld->Lookup->Options === null) {
            // Get default connection and filter
            $conn = $this->getConnection();
            $lookupFilter = "";

            // No need to check any more
            $fld->Lookup->Options = [];

            // Set up lookup SQL and connection
            switch ($fld->FieldVar) {
    <#
        for (let f of allFields) {
            if (IsLookupField(f)) { // Lookup
                let fldVar = f.FldVar,
                    fldSelectFilter = f.FldSelectFilter.trim(),
                    linkTable = !IsEmpty(f.FldTagLnkTbl) ? GetTableObject(f.FldTagLnkTbl) : TABLE,
                    linkDBID = GetDbId(linkTable.TblName);
    #>
                case "<#= fldVar #>":
    <#
                if (linkDBID != tblDbId) { // Different DbId
    #>
                    $conn = Conn("<#= Quote(linkDBID) #>");
    <#
                }
                if (!IsEmpty(fldSelectFilter)) {
    #>
                    $lookupFilter = function () {
                        return <#= fldSelectFilter #>;
                    };
                    $lookupFilter = $lookupFilter->bindTo($this);
    <#
                }
    #>
                    break;
    <#
            }
        }
    #>
                default:
                    $lookupFilter = "";
                    break;
            }

            // Always call to Lookup->getSql so that user can setup Lookup->Options in Lookup_Selecting server event
            $sql = $fld->Lookup->getSql(false, "", $lookupFilter, $this);

            // Set up lookup cache
            if ($fld->UseLookupCache && $sql != "" && count($fld->Lookup->Options) == 0) {
                $totalCnt = $this->getRecordCount($sql, $conn);
                if ($totalCnt > $fld->LookupCacheCount) { // Total count > cache count, do not cache
                    return;
                }
                $rows = $conn->executeQuery($sql)->fetchAll(\PDO::FETCH_BOTH);
                $ar = [];
                foreach ($rows as $row) {
                    $row = $fld->Lookup->renderViewRow($row);
                    $ar[strval($row[0])] = $row;
                }
                $fld->Lookup->Options = $ar;
            }
        }
    }

<# } #>

<# if (["view", "edit", "list"].includes(ctrlId)) { #>

    // Set up starting record parameters
    public function setupStartRecord()
    {
        if ($this->DisplayRecords == 0) {
            return;
        }

        if ($this->isPageRequest()) { // Validate request
            $startRec = Get(Config("TABLE_START_REC"));
            $pageNo = Get(Config("TABLE_PAGE_NO"));
            if ($pageNo !== null) { // Check for "pageno" parameter first
                if (is_numeric($pageNo)) {
                    $this->StartRecord = ($pageNo - 1) * $this->DisplayRecords + 1;
                    if ($this->StartRecord <= 0) {
                        $this->StartRecord = 1;
                    } elseif ($this->StartRecord >= (int)(($this->TotalRecords - 1) / $this->DisplayRecords) * $this->DisplayRecords + 1) {
                        $this->StartRecord = (int)(($this->TotalRecords - 1) / $this->DisplayRecords) * $this->DisplayRecords + 1;
                    }
                    $this->setStartRecordNumber($this->StartRecord);
                }
            } elseif ($startRec !== null) { // Check for "start" parameter
                $this->StartRecord = $startRec;
                $this->setStartRecordNumber($this->StartRecord);
            }
        }

        $this->StartRecord = $this->getStartRecordNumber();

        // Check if correct start record counter
        if (!is_numeric($this->StartRecord) || $this->StartRecord == "") { // Avoid invalid start record counter
            $this->StartRecord = 1; // Reset start record counter
            $this->setStartRecordNumber($this->StartRecord);
        } elseif ($this->StartRecord > $this->TotalRecords) { // Avoid starting record > total records
            $this->StartRecord = (int)(($this->TotalRecords - 1) / $this->DisplayRecords) * $this->DisplayRecords + 1; // Point to last page first record
            $this->setStartRecordNumber($this->StartRecord);
        } elseif (($this->StartRecord - 1) % $this->DisplayRecords != 0) {
            $this->StartRecord = (int)(($this->StartRecord - 1) / $this->DisplayRecords) * $this->DisplayRecords + 1; // Point to page boundary
            $this->setStartRecordNumber($this->StartRecord);
        }
    }

<# } #>
