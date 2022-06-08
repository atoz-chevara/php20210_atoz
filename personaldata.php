<## Common config #>
<#= include('shared/config-common.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    // Properties
    public $Password;

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $Breadcrumb;

        // Create Password field object (used by validation only)
        $this->Password = new DbField("personaldata", "personaldata", "password", "password", "password", "", 202, 255, 0, false, "", false, false, false);
        $this->Password->EditAttrs->appendClass("<#= inputClass #>");

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "PersonalDataTitle", CurrentUrl(), "ew-personal-data", "", true);

        $this->Heading = $Language->phrase("PersonalDataTitle");

        $cmd = Get("cmd", "");
        if (SameText($cmd, "Download")) {
            if ($this->personalDataResult()) {
                $this->terminate();
                return;
            }
        } elseif (SameText($cmd, "Delete") && IsPost()) {
            if ($this->deletePersonalData()) {
                $this->terminate(GetUrl("<#= logoutPage #>?deleted=1"));
                return;
            }
        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<#
    let fields = secTable.Fields.filter(f => f.FldGenerate && f.FldRegister && f.FldName != DB.SecUserLevelFld)
                    .slice()
                    .map(f => `"${Quote(f.FldName)}"`).join(", ");
#>

    /**
     * Write personal data as JSON
     *
     * @return void
     */
    protected function personalDataResult()
    {
        global $UserTable;
        $result = [];
        $fldNames = [<#= fields #>];
        $UserTable = Container("usertable");
        $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"), CurrentUserName());
        $sql = $UserTable->getSql($filter);
        if ($row = Conn($UserTable->Dbid)->fetchAssoc($sql)) {
            foreach ($fldNames as $fldName) {
                if (array_key_exists($fldName, $row)) {
                    $result[$fldName] = GetUserInfo($fldName, $row);
                }
            }
        <# if (ServerScriptExist("Global", "PersonalData_Downloading")) { #>
            // Call PersonalData_Downloading event
            PersonalData_Downloading($result);
        <# } #>
            $personalDataFileName = Get("_personaldatafilename", "personaldata.json");
            AddHeader("Content-Disposition", "attachment; filename=\"" . $personalDataFileName . "\"");
            WriteJson($result);
            return true;
        } else {
            $this->setFailureMessage($Language->phrase("NoRecord")); // No record found
            return false;
        }
    }

    /**
     * Delete personal data
     *
     * @return bool
     */
    protected function deletePersonalData()
    {
        global $UserTable, $Language;
        $UserTable = Container("usertable");
        $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"), CurrentUserName());
        $sql = $UserTable->getSql($filter);
        $pwd = Post($this->Password->FieldVar, "");
        if ($row = Conn($UserTable->Dbid)->fetchAssoc($sql)) {
            if (ComparePassword(GetUserInfo(Config("LOGIN_PASSWORD_FIELD_NAME"), $row), $pwd)) {
                if (Config("DELETE_UPLOADED_FILES")) // Delete old files
                    $UserTable->deleteUploadedFiles($row);
                if ($UserTable->delete($row)) {
        <# if (ServerScriptExist("Global", "PersonalData_Deleted")) { #>
                    // Call PersonalData_Deleted event
                    PersonalData_Deleted($row);
        <# } #>
                    return true;
                }
                $this->setFailureMessage($Language->phrase("PersonalDataDeleteFailure"));
                return false;
            } else {
                $this->Password->addErrorMessage($Language->phrase("InvalidPassword"));
                return false;
            }
        } else {
            $this->setFailureMessage($Language->phrase("NoRecord"));
            return false;
        }
    }
<## Page class end #>
<#= include('shared/page-class-end.php') #>
