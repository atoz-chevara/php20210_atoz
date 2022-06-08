<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $ExportOptions; // Export options
    public $OtherOptions; // Other options
    public $DisplayRecords = 1;
    public $DbMasterFilter;
    public $DbDetailFilter;
    public $StartRecord;
    public $StopRecord;
    public $TotalRecords = 0;
    public $RecordRange = 10;
    public $RecKey = [];
    public $IsModal = false;

<# if (useMultiPage) { #>
    public $MultiPages; // Multi pages object
<# } #>

<# if (showMultiPageForDetails) { #>
    public $DetailPages; // Detail pages object
<# } #>

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

        // Load current record
        $loadCurrentRecord = false;

        $returnUrl = "";

        $matchRecord = false;

    <# if (masterTables.length > 0) { #>
        // Set up master/detail parameters
        $this->setupMasterParms();
    <# } #>

        if ($this->isPageRequest()) { // Validate request

            <#
                keyFields.forEach((f, i) => {
                    let fldParm = f.FldParm;
            #>
            if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
                $this-><#= fldParm #>->setQueryStringValue($keyValue);
                $this->RecKey["<#= fldParm #>"] = $this-><#= fldParm #>->QueryStringValue;
            } elseif (Post("<#= fldParm #>") !== null) {
                $this-><#= fldParm #>->setFormValue(Post("<#= fldParm #>"));
                $this->RecKey["<#= fldParm #>"] = $this-><#= fldParm #>->FormValue;
            } elseif (IsApi() && ($keyValue = Key(<#= i #>) ?? Route(<#= i + 2 #>)) !== null) {
                $this-><#= fldParm #>->setQueryStringValue($keyValue);
                $this->RecKey["<#= fldParm #>"] = $this-><#= fldParm #>->QueryStringValue;
            } else {
                <# if (detailViewPaging) { #>
                $loadCurrentRecord = true;
                <# } else { #>
                $returnUrl = "<#= listPage #>"; // Return to list
                <# } #>
            }
            <#
                }); // Field
            #>

            // Get action
            $this->CurrentAction = "show"; // Display

            switch ($this->CurrentAction) {
                case "show": // Get a record to display

            <# if (detailViewPaging) { #>

                    $this->StartRecord = 1; // Initialize start position
                    if ($this->Recordset = $this->loadRecordset()) { // Load records
                        $this->TotalRecords = $this->Recordset->recordCount(); // Get record count
                    }
                    if ($this->TotalRecords <= 0) { // No record found
                        if ($this->getSuccessMessage() == "" && $this->getFailureMessage() == "") {
                            $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                        }
                        $this->terminate("<#= listPage #>"); // Return to list page
                        return;
                    } elseif ($loadCurrentRecord) { // Load current record position
                        $this->setupStartRecord(); // Set up start record position
                        // Point to current record
                        if ($this->StartRecord <= $this->TotalRecords) {
                            $matchRecord = true;
                            $this->Recordset->move($this->StartRecord - 1);
                        }
                    } else { // Match key values
                        while (!$this->Recordset->EOF) {
                <#
                    let matchCond = keyFields.map(kf => `SameString($this->${kf.FldParm}->CurrentValue, $this->Recordset->fields['${SingleQuote(kf.FldName)}'])`).join(" && ");
                #>
                            if (<#= matchCond #>) {
                                $this->setStartRecordNumber($this->StartRecord); // Save record position
                                $matchRecord = true;
                                break;
                            } else {
                                $this->StartRecord++;
                                $this->Recordset->moveNext();
                            }
                        }
                    }
                    if (!$matchRecord) {
                        if ($this->getSuccessMessage() == "" && $this->getFailureMessage() == "") {
                            $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                        }
                        $returnUrl = "<#= listPage #>"; // No matching record, return to list
                    } else {
                        $this->loadRowValues($this->Recordset); // Load row values
                    }

            <# } else { #>

                    // Load record based on key
                    if (IsApi()) {
                        $filter = $this->getRecordFilter();
                        $this->CurrentFilter = $filter;
                        $sql = $this->getCurrentSql();
                        $conn = $this->getConnection();
                        $this->Recordset = LoadRecordset($sql, $conn);
                        $res = $this->Recordset && !$this->Recordset->EOF;
                    } else {
                        $res = $this->loadRow();
                    }

                    if (!$res) { // Load record based on key
                        if ($this->getSuccessMessage() == "" && $this->getFailureMessage() == "") {
                            $this->setFailureMessage($Language->phrase("NoRecord")); // Set no record message
                        }
                        $returnUrl = "<#= listPage #>"; // No matching record, return to list
                    }

            <# } #>
                    break;

            }

            <#
                if (viewExport) {
                    if (exportHtml || exportEmail || exportCsv || exportWord || exportExcel || exportXml || exportPdf) {
            #>
            // Export data only
            if (!$this->CustomExport && in_array($this->Export, array_keys(Config("EXPORT_CLASSES")))) {
                $this->exportData();
                $this->terminate();
                return;
            }
            <#
                    }
                }
            #>

        } else {
            $returnUrl = "<#= listPage #>"; // Not page request, return to list
        }

        if ($returnUrl != "") {
            $this->terminate($returnUrl);
            return;
        }

        // Set up Breadcrumb
        if (!$this->isExport()) {
            $this->setupBreadcrumb();
        }

        // Render row
        $this->RowType = ROWTYPE_VIEW;
        $this->resetAttributes();
        $this->renderRow();

    <# if (isDetailView && detailTables.length > 0) { #>
        // Set up detail parameters
        $this->setupDetailParms();
    <# } #>

        // Normal return
        if (IsApi()) {
            $rows = $this->getRecordsFromRecordset($this->Recordset, true); // Get current record only
            $this->Recordset->close();
            WriteJson(["success" => true, $this->TableVar => $rows]);
            $this->terminate(true);
            return;
        }

    <# if (detailViewPaging) { #>
        // Set up pager
        $this->Pager = new <#= pagerClass #>($this->StartRecord, $this->DisplayRecords, $this->TotalRecords, "", $this->RecordRange, $this->AutoHidePager);
    <# } #>

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Set up other options
    protected function setupOtherOptions()
    {
        global $Language, $Security;

        $options = &$this->OtherOptions;
        $option = $options["action"];
<#
    // Set up link visibility
    let masterViewVisible = SecurityCheck("View", isSecurityEnabled, isSecurityEnabled),
        masterEditVisible = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled),
        masterCopyVisible = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled),
        detailLink = viewPageDetailLinkCaption;

    // Set up edit check
    let editSecurityCheck = SecurityCheck("Edit", isSecurityEnabled, isSecurityEnabled),
        detailEditSecurityCheck = editSecurityCheck;
    if (IsEmpty(detailEditSecurityCheck))
        detailEditSecurityCheck = Code.true;
    if (!IsEmpty(editSecurityCheck))
        editSecurityCheck = " && " + editSecurityCheck;

    // Set up add check
    let addSecurityCheck = SecurityCheck("Add", isSecurityEnabled, isSecurityEnabled),
        detailCopySecurityCheck = addSecurityCheck;
    if (IsEmpty(detailCopySecurityCheck))
        detailCopySecurityCheck = Code.true;
    if (!IsEmpty(addSecurityCheck))
        addSecurityCheck = " && " + addSecurityCheck;

    // Set up delete check
    let deleteSecurityCheck = SecurityCheck("Delete", isSecurityEnabled, isSecurityEnabled);
    if (!IsEmpty(deleteSecurityCheck))
        deleteSecurityCheck = " && " + deleteSecurityCheck;

    // Show Option Link
    let optionLinkCheck = "";
    if (hasUserIdFld)
        optionLinkCheck = "$this->showOptionLink()";

    if (TABLE.TblAdd) {
#>
        // Add
        $item = &$option->add("add");
        $addcaption = HtmlTitle($Language->phrase("ViewPageAddLink"));
        if ($this->IsModal) {
            $item->Body = "<a class=\"ew-action ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,url:'" . HtmlEncode(GetUrl($this->AddUrl)) . "'});\">" . $Language->phrase("ViewPageAddLink") . "</a>";
        } else {
            $item->Body = "<a class=\"ew-action ew-add\" title=\"" . $addcaption . "\" data-caption=\"" . $addcaption . "\" href=\"" . HtmlEncode(GetUrl($this->AddUrl)) . "\">" . $Language->phrase("ViewPageAddLink") . "</a>";
        }
        $item->Visible = ($this->AddUrl != ""<#= addSecurityCheck #>);
<#
    }

    if (TABLE.TblEdit) {
        if (hasUserIdFld)
            optionLinkCheck = ' && $this->showOptionLink("edit")';
        else
            optionLinkCheck = "";
#>
        // Edit
        $item = &$option->add("edit");
        $editcaption = HtmlTitle($Language->phrase("ViewPageEditLink"));
        if ($this->IsModal) {
            $item->Body = "<a class=\"ew-action ew-edit\" title=\"" . $editcaption . "\" data-caption=\"" . $editcaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,url:'" . HtmlEncode(GetUrl($this->EditUrl)) . "'});\">" . $Language->phrase("ViewPageEditLink") . "</a>";
        } else {
            $item->Body = "<a class=\"ew-action ew-edit\" title=\"" . $editcaption . "\" data-caption=\"" . $editcaption . "\" href=\"" . HtmlEncode(GetUrl($this->EditUrl)) . "\">" . $Language->phrase("ViewPageEditLink") . "</a>";
        }
        $item->Visible = ($this->EditUrl != ""<#= editSecurityCheck #><#= optionLinkCheck #>);
<#
    }

    if (TABLE.TblCopy && TABLE.TblAdd) {
        if (hasUserIdFld)
            optionLinkCheck = ' && $this->showOptionLink("add")';
        else
            optionLinkCheck = "";
#>
        // Copy
        $item = &$option->add("copy");
        $copycaption = HtmlTitle($Language->phrase("ViewPageCopyLink"));
        if ($this->IsModal) {
            $item->Body = "<a class=\"ew-action ew-copy\" title=\"" . $copycaption . "\" data-caption=\"" . $copycaption . "\" href=\"#\" onclick=\"return ew.modalDialogShow({lnk:this,btn:'AddBtn',url:'" . HtmlEncode(GetUrl($this->CopyUrl)) . "'});\">" . $Language->phrase("ViewPageCopyLink") . "</a>";
        } else {
            $item->Body = "<a class=\"ew-action ew-copy\" title=\"" . $copycaption . "\" data-caption=\"" . $copycaption . "\" href=\"" . HtmlEncode(GetUrl($this->CopyUrl)) . "\">" . $Language->phrase("ViewPageCopyLink") . "</a>";
        }
        $item->Visible = ($this->CopyUrl != ""<#= addSecurityCheck #><#= optionLinkCheck #>);
<#
    }

    if (TABLE.TblDelete) {
        if (hasUserIdFld)
            optionLinkCheck = ' && $this->showOptionLink("delete")';
        else
            optionLinkCheck = "";
        let deleteConfirm = ` onclick=\\"return ew.confirmDelete(this);\\"`;
#>
        // Delete
        $item = &$option->add("delete");
    <# if (inlineDelete) { #>
        $item->Body = "<a<#= deleteConfirm #> class=\"ew-action ew-delete\" title=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->DeleteUrl)) . "\">" . $Language->phrase("ViewPageDeleteLink") . "</a>";
    <# } else { #>
        if ($this->IsModal) { // Handle as inline delete
            $item->Body = "<a<#= deleteConfirm #> class=\"ew-action ew-delete\" title=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" href=\"" . HtmlEncode(UrlAddQuery(GetUrl($this->DeleteUrl), "action=1")) . "\">" . $Language->phrase("ViewPageDeleteLink") . "</a>";
        } else {
            $item->Body = "<a class=\"ew-action ew-delete\" title=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" data-caption=\"" . HtmlTitle($Language->phrase("ViewPageDeleteLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->DeleteUrl)) . "\">" . $Language->phrase("ViewPageDeleteLink") . "</a>";
        }
    <# } #>
        $item->Visible = ($this->DeleteUrl != ""<#= deleteSecurityCheck #><#= optionLinkCheck #>);
<#
    }
#>

<#
    // Detail links
    if (detailTables.length > 0) {
#>
        $option = $options["detail"];
        $detailTableLink = "";
        $detailViewTblVar = "";
        $detailCopyTblVar = "";
        $detailEditTblVar = "";
<#
        for (let md of detailTables) {
            let detailTable = GetTableObject(md.DetailTable),
                detailTblVar = detailTable.TblVar,
                detailPageObj = GetPageObject("grid", detailTable),
                detailUrl = detailTable.TblType == "REPORT" ? GetRouteUrl(detailTable.TblReportType, detailTable) : GetRouteUrl("list", detailTable),
                qry = "";
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

            let detailVisible = SecurityCheck("Detail", isSecurityEnabled, isSecurityEnabled, detailTable);
            if (detailVisible == "")
                detailVisible = Code.true;

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
        $item = &$option->add("<#= detailPrefix #>_<#= detailTblVar #>");
        $body = <#= detailLink #> . $Language->TablePhrase("<#= detailTblVar #>", "TblCaption");
    <# if (showDetailCount && detailTable.TblType != "REPORT") { #>
        $body .= "&nbsp;" . str_replace("%c", Container("<#= detailTblVar #>")->Count, $Language->phrase("DetailCount"));
    <# } #>
    <# if (detailTable.TblType == "REPORT") { #>
        $body = "<a class=\"btn btn-default ew-row-link\" href=\"" . HtmlEncode(GetUrl("<#= detailUrl #>")) . "\">" . $body . "</a>";
    <# } else if (detailPageObj) { #>
        $body = "<a class=\"btn btn-default ew-row-link ew-detail\" data-action=\"list\" href=\"" . HtmlEncode(GetUrl("<#= detailUrl #>")) . "\">" . $body . "</a>";
        $links = "";
        $detailPageObj = Container("<#= detailPageObj #>");
        <# if (TABLE.TblView && isDetailView) { #>
        if ($detailPageObj->DetailView<#= detailViewVisible #>) {
            $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-view\" data-action=\"view\" data-caption=\"" . HtmlTitle(<#= masterDetailViewLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->getViewUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>"))) . "\">" . HtmlImageAndText(<#= masterDetailViewLinkCaption #>) . "</a></li>";
            if ($detailViewTblVar != "") {
                $detailViewTblVar .= ",";
            }
            $detailViewTblVar .= "<#= detailTblVar #>";
        }
        <# } #>
        <# if (TABLE.TblEdit && isDetailEdit) { #>
        if ($detailPageObj->DetailEdit<#= detailEditVisible #>) {
            $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-edit\" data-action=\"edit\" data-caption=\"" . HtmlTitle(<#= masterDetailEditLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->getEditUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>"))) . "\">" . HtmlImageAndText(<#= masterDetailEditLinkCaption #>) . "</a></li>";
            if ($detailEditTblVar != "") {
                $detailEditTblVar .= ",";
            }
            $detailEditTblVar .= "<#= detailTblVar #>";
        }
        <# } #>
        <# if (isDetailCopy) { #>
        if ($detailPageObj->DetailAdd<#= detailCopyVisible #>) {
            $links .= "<li><a class=\"dropdown-item ew-row-link ew-detail-copy\" data-action=\"add\" data-caption=\"" . HtmlTitle(<#= masterDetailCopyLinkCaption #>) . "\" href=\"" . HtmlEncode(GetUrl($this->getCopyUrl(Config("TABLE_SHOW_DETAIL") . "=<#= detailTblVar #>"))) . "\">" . HtmlImageAndText(<#= masterDetailCopyLinkCaption #>) . "</a></li>";
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
        $item->Body = $body;
        $item->Visible = <#= detailVisible #>;
        if ($item->Visible) {
            if ($detailTableLink != "") {
                $detailTableLink .= ",";
            }
            $detailTableLink .= "<#= detailTblVar #>";
        }
        if ($this->ShowMultipleDetails) {
            $item->Visible = false;
        }
<#
        } // MasterDetail
#>
        // Multiple details
        if ($this->ShowMultipleDetails) {
            $body = "<div class=\"btn-group btn-group-sm ew-btn-group\">";
            $links = "";
            if ($detailViewTblVar != "") {
                $links .= "<li><a class=\"ew-row-link ew-detail-view\" data-action=\"view\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailViewLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->getViewUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailViewTblVar))) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailViewLink")) . "</a></li>";
            }
            if ($detailEditTblVar != "") {
                $links .= "<li><a class=\"ew-row-link ew-detail-edit\" data-action=\"edit\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailEditLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->getEditUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailEditTblVar))) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailEditLink")) . "</a></li>";
            }
            if ($detailCopyTblVar != "") {
                $links .= "<li><a class=\"ew-row-link ew-detail-copy\" data-action=\"add\" data-caption=\"" . HtmlTitle($Language->phrase("MasterDetailCopyLink")) . "\" href=\"" . HtmlEncode(GetUrl($this->getCopyUrl(Config("TABLE_SHOW_DETAIL") . "=" . $detailCopyTblVar))) . "\">" . HtmlImageAndText($Language->phrase("MasterDetailCopyLink")) . "</a></li>";
            }
            if ($links != "") {
                $body .= "<button class=\"dropdown-toggle btn btn-default ew-master-detail\" title=\"" . HtmlTitle($Language->phrase("MultipleMasterDetails")) . "\" data-toggle=\"dropdown\">" . $Language->phrase("MultipleMasterDetails") . "</button>";
                $body .= "<ul class=\"dropdown-menu ew-menu\">" . $links . "</ul>";
            }
            $body .= "</div>";
            // Multiple details
            $item = &$option->add("details");
            $item->Body = $body;
        }

        // Set up detail default
        $option = $options["detail"];
        $options["detail"]->DropDownButtonPhrase = $Language->phrase("ButtonDetails");
        $ar = explode(",", $detailTableLink);
        $cnt = count($ar);
        $option->UseDropDownButton = ($cnt > 1);
        $option->UseButtonGroup = true;
        $item = &$option->add($option->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;
<#
    }
#>

        // Set up action default
        $option = $options["action"];
        $option->DropDownButtonPhrase = $Language->phrase("ButtonActions");
        $option->UseDropDownButton = <#= Code.bool(useDropDownForListOptions) #>;
        $option->UseButtonGroup = true;
        $item = &$option->add($option->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;

    }

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Table", "Page_Exporting") #>
    <#= GetServerScript("Table", "Row_Export") #>
    <#= GetServerScript("Table", "Page_Exported") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
