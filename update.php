<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $FormClassName = "ew-horizontal ew-form ew-update-form";
    public $IsModal = false;
    public $IsMobileOrModal = false;
    public $RecKeys;
    public $Disabled;
    public $UpdateCount = 0;

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
        $this->FormClassName = "ew-form ew-update-form ew-horizontal";

        // Set up Breadcrumb
        $this->setupBreadcrumb();

        // Try to load keys from list form
        $this->RecKeys = $this->getRecordKeys(); // Load record keys

    <#
        if (hasUserIdFld) {
    #>

        // Check if valid User ID
        $sql = $this->getSql($this->getFilterFromRecordKeys(false));
        $conn = $this->getConnection();
        $rows = $conn->fetchAll($sql);
        $res = true;
        foreach ($rows as $row) {
            $this->loadRowValues($row);
            if (!$this->showOptionLink("<#= ctrlId #>")) {
                $userIdMsg = $Language->phrase("NoEditPermission");
                $this->setFailureMessage($userIdMsg);
                $res = false;
                break;
            }
        }
        if (!$res) {
            $this->terminate("<#= listPage #>"); // Return to list
            return;
        }

    <#
        }
    #>

        if (Post("action") !== null && Post("action") !== "") {

            // Get action
            $this->CurrentAction = Post("action");

            $this->loadFormValues(); // Get form values

            // Validate form
            if (!$this->validateForm()) {
                $this->CurrentAction = "show"; // Form error, reset action
                if (!$this->hasInvalidFields()) { // No fields selected
                    $this->setFailureMessage($Language->phrase("NoFieldSelected"));
                }
            }

        } else {
            $this->loadMultiUpdateValues(); // Load initial values to form
        }

        if (count($this->RecKeys) <= 0) {
            $this->terminate("<#= listPage #>"); // No records selected, return to list
            return;
        }

        if ($this->isUpdate()) {

                if ($this->updateRows()) { // Update Records based on key
                    if ($this->getSuccessMessage() == "") {
                        $this->setSuccessMessage($Language->phrase("UpdateSuccess")); // Set up update success message
                    }
                    $this->terminate($this->getReturnUrl()); // Return to caller
                    return;
                } else {
                    $this->restoreFormValues(); // Restore form values
                }

        }

        // Render row
        <# if (multiUpdateConfirm) { #>
        if ($this->isConfirm()) { // Confirm page
            $this->RowType = ROWTYPE_VIEW; // Render view
            $this->Disabled = " disabled";
        } else {
            $this->RowType = ROWTYPE_EDIT; // Render edit
            $this->Disabled = "";
        }
        <# } else { #>
        $this->RowType = ROWTYPE_EDIT; // Render edit
        <# } #>
        $this->resetAttributes();
        $this->renderRow();

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Load initial values to form if field values are identical in all selected records
    protected function loadMultiUpdateValues()
    {

        $this->CurrentFilter = $this->getFilterFromRecordKeys();

        // Load recordset
        if ($rs = $this->loadRecordset()) {
            $i = 1;
            while (!$rs->EOF) {
                if ($i == 1) {
        <#
                for (let f of currentFields) {
                    if (f.FldHtmlTag != "FILE") {
                        let fldParm = f.FldParm, fldName = f.FldName;
        #>
                    $this-><#= fldParm #>->setDbValue($rs->fields['<#= SingleQuote(fldName) #>']);
        <#
                    }
                } // Field
        #>
                } else {
        <#
                for (let f of currentFields) {
                    if (f.FldHtmlTag != "FILE") {
                        let fldParm = f.FldParm, fldName = f.FldName;
        #>
                    if (!CompareValue($this-><#= fldParm #>->DbValue, $rs->fields['<#= SingleQuote(fldName) #>'])) {
                        $this-><#= fldParm #>->CurrentValue = null;
                    }
        <#
                    }
                } // Field
        #>
                }
                $i++;
                $rs->moveNext();
            }
            $rs->close();
        }
    }

    // Set up key value
    protected function setupKeyValues($key)
    {

    <# if (keyFields.length > 1) { #>
        if (count($key) != <#= keyFields.length #>) {
            return false;
        }
    <# } #>

    <#
        keyFields.forEach((kf, i) => {
            let fldParm = kf.FldParm,
                isNumericKey = (GetFieldType(kf.FldType) == 1);
    #>
    <# if (keyFields.length > 1) { #>
        $keyFld = $key[<#= i #>];
    <# } else { #>
        $keyFld = $key;
    <# } #>
    <# if (isNumericKey) { #>
        if (!is_numeric($keyFld)) {
            return false;
        }
    <# } #>
        $this-><#= fldParm #>->OldValue = $keyFld;
    <#
        }); // KeyField
    #>
        return true;
    }

    // Update all selected rows
    protected function updateRows()
    {
        global $Language;

        $conn = $this->getConnection();
        $conn->beginTransaction();
    <# if (TABLE.TblAuditTrail) { #>
        if ($this->AuditTrailOnEdit) {
            $this->writeAuditTrailDummy($Language->phrase("BatchUpdateBegin")); // Batch update begin
        }
    <# } #>

        // Get old records
        $this->CurrentFilter = $this->getFilterFromRecordKeys(false);
        $sql = $this->getCurrentSql();
        $rsold = $conn->fetchAll($sql);

        // Update all rows
        $key = "";
        foreach ($this->RecKeys as $reckey) {
            if ($this->setupKeyValues($reckey)) {
    <# if (keyFields.length > 1) { #>
                $thisKey = implode(Config("COMPOSITE_KEY_SEPARATOR"), $reckey);
    <# } else { #>
                $thisKey = $reckey;
    <# } #>
                $this->SendEmail = false; // Do not send email on update success
                $this->UpdateCount += 1; // Update record count for records being updated
                $updateRows = $this->editRow(); // Update this row
            } else {
                $updateRows = false;
            }

            if (!$updateRows) {
                break; // Update failed
            }

            if ($key != "") {
                $key .= ", ";
            }
            $key .= $thisKey;

        }

        // Check if all rows updated
        if ($updateRows) {
            $conn->commit(); // Commit transaction

            // Get new records
            $rsnew = $conn->fetchAll($sql);

        <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnEdit) {
                $this->writeAuditTrailDummy($Language->phrase("BatchUpdateSuccess")); // Batch update success
            }
        <# } #>
        <# if (TABLE.TblSendMailOnEdit) { #>
            $table = '<#= SingleQuote(TABLE.TblName) #>';
            $subject = $table . " " . $Language->phrase("RecordUpdated");
            $action = $Language->phrase("ActionUpdatedMultiUpdate");

            $email = new Email();
            $email->load(Config("EMAIL_NOTIFY_TEMPLATE"));
            $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
            $email->replaceRecipient(Config("RECIPIENT_EMAIL")); // Replace Recipient
            $email->replaceSubject($subject); // Replace Subject
            $email->replaceContent('<!--table-->', $table);
            $email->replaceContent('<!--key-->', $key);
            $email->replaceContent('<!--action-->', $action);

<# if (ServerScriptExist("Table", "Email_Sending")) { #>
            $args = ["rsold" => $rsold, "rsnew" => $rsnew];
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

        <# } #>
        } else {
            $conn->rollback(); // Rollback transaction
        <# if (TABLE.TblAuditTrail) { #>
            if ($this->AuditTrailOnEdit) {
                $this->writeAuditTrailDummy($Language->phrase("BatchUpdateRollback")); // Batch update rollback
            }
        <# } #>
        }

        return $updateRows;

    }

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Table", "Form_CustomValidate") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
