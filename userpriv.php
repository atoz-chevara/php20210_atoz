<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

<#
    let userLevelIdField = GetFieldObject(TABLE, DB.UserLevelIdFld),
        userLevelIdFldVar = userLevelIdField.FldVar,
        userLevelIdFldParm = userLevelIdField.FldParm;
#>

    public $Disabled;
    public $TableNameCount;
    public $Privileges = [];
    public $UserLevelList = [];
    public $UserLevelPrivList = [];
    public $TableList = [];

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $Breadcrumb;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("list", "<#= tblVar #>", "<#= userLevelListPage #>", "", "<#= tblVar #>");
        $Breadcrumb->add("<#= ctrlId #>", "UserLevelPermission", CurrentUrl());
        $this->Heading = $Language->phrase("UserLevelPermission");

        // Load user level settings
        $this->UserLevelList = $GLOBALS["USER_LEVELS"];
        $this->UserLevelPrivList = $GLOBALS["USER_LEVEL_PRIVS"];
        $ar = $GLOBALS["USER_LEVEL_TABLES"];

        // Set up allowed table list
        foreach ($ar as $t) {
            if ($t[3]) { // Allowed
                $tempPriv = $Security->getUserLevelPrivEx($t[4] . $t[0], $Security->CurrentUserLevelID);
                if (($tempPriv & ALLOW_ADMIN) == ALLOW_ADMIN) { // Allow Admin
                    $this->TableList[] = array_merge($t, [$tempPriv]);
                }
            }
        }
        $this->TableNameCount = count($this->TableList);

        // Get action
        if (Post("action") == "") {
            $this->CurrentAction = "show"; // Display with input box
            // Load key from QueryString
            if (Get("<#= userLevelIdFldParm #>") !== null) {
                $this-><#= userLevelIdFldParm #>->setQueryStringValue(Get("<#= userLevelIdFldParm #>"));
            } else {
                $this->terminate("<#= userLevelListPage #>"); // Return to list
                return;
            }
            if ($this-><#= userLevelIdFldParm #>->QueryStringValue == "-1") {
                $this->Disabled = " disabled";
            } else {
                $this->Disabled = "";
            }
        } else {
            $this->CurrentAction = Post("action");
            // Get fields from form
            $this-><#= userLevelIdFldParm #>->setFormValue(Post("<#= userLevelIdFldVar #>"));
            for ($i = 0; $i < $this->TableNameCount; $i++) {
                if (Post("table_" . $i) !== null) {
                    $this->Privileges[$i] = (int)Post("add_" . $i) +
                        (int)Post("delete_" . $i) + (int)Post("edit_" . $i) +
                        (int)Post("list_" . $i) + (int)Post("view_" . $i) +
                        (int)Post("search_" . $i) + (int)Post("admin_" . $i) +
                        (int)Post("import_" . $i) + (int)Post("lookup_" . $i);
                }
            }
        }

        // Should not edit own permissions
        if ($Security->hasUserLevelID($this-><#= userLevelIdFldParm #>->CurrentValue)) {
            $this->terminate("<#= userLevelListPage #>"); // Return to list
            return;
        }

        switch ($this->CurrentAction) {
            case "show": // Display
                if (!$Security->setupUserLevelEx()) { // Get all User Level info
                    $this->terminate("<#= userLevelListPage #>"); // Return to list
                    return;
                }
                $ar = [];
                for ($i = 0; $i < $this->TableNameCount; $i++) {
                    $table = $this->TableList[$i];
                    $cnt = count($table);
                    $tempPriv = $Security->getUserLevelPrivEx($table[4] . $table[0], $this-><#= userLevelIdFldParm #>->CurrentValue);
                    $ar[] = ["table" => ConvertToUtf8($this->getTableCaption($i)), "index" => $i, "permission" => $tempPriv, "allowed" => $table[$cnt - 1]];
                }
                $this->Privileges["disabled"] = $this->Disabled;
                $this->Privileges["permissions"] = $ar;
                $this->Privileges["add"] = 1; // Add
                $this->Privileges["delete"] = 2; // Delete
                $this->Privileges["edit"] = 4; // Edit
                $this->Privileges["list"] = 8; // List
                $this->Privileges["report"] = 8; // Report
                $this->Privileges["admin"] = 16; // Admin
                $this->Privileges["view"] = 32; // View
                $this->Privileges["search"] = 64; // Search
                $this->Privileges["import"] = 128; // Import
                $this->Privileges["lookup"] = 256; // Lookup
                break;
            case "update": // Update
                if ($this->editRow()) { // Update record based on key
                    if ($this->getSuccessMessage() == "") {
                        $this->setSuccessMessage($Language->phrase("UpdateSuccess")); // Set up update success message
                    }
                    // Alternatively, comment out the following line to go back to this page
                    $this->terminate("<#= userLevelListPage #>"); // Return to list
                    return;
                }
        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Update privileges
    protected function editRow()
    {
        global $Security;
        $c = Conn(Config("USER_LEVEL_PRIV_DBID"));
        foreach ($this->Privileges as $i => $privilege) {
            $table = $this->TableList[$i];
            $cnt = count($table);
            $sql = "SELECT * FROM " . Config("USER_LEVEL_PRIV_TABLE") . " WHERE " .
                Config("USER_LEVEL_PRIV_TABLE_NAME_FIELD") . " = '" . AdjustSql($table[4] . $table[0], Config("USER_LEVEL_PRIV_DBID")) . "' AND " .
                Config("USER_LEVEL_PRIV_USER_LEVEL_ID_FIELD") . " = " . $this-><#= userLevelIdFldParm #>->CurrentValue;
            $privilege = $privilege & $table[$cnt - 1]; // Set maximum allowed privilege (protect from hacking)
            $rs = $c->fetchArray($sql);
            if ($rs) {
                $sql = "UPDATE " . Config("USER_LEVEL_PRIV_TABLE") . " SET " . Config("USER_LEVEL_PRIV_PRIV_FIELD") . " = " . $privilege . " WHERE " .
                    Config("USER_LEVEL_PRIV_TABLE_NAME_FIELD") . " = '" . AdjustSql($table[4] . $table[0], Config("USER_LEVEL_PRIV_DBID")) . "' AND " .
                    Config("USER_LEVEL_PRIV_USER_LEVEL_ID_FIELD") . " = " . $this-><#= userLevelIdFldParm #>->CurrentValue;
                $c->executeUpdate($sql);
            } else {
                $sql = "INSERT INTO " . Config("USER_LEVEL_PRIV_TABLE") . " (" . Config("USER_LEVEL_PRIV_TABLE_NAME_FIELD") . ", " . Config("USER_LEVEL_PRIV_USER_LEVEL_ID_FIELD") . ", " . Config("USER_LEVEL_PRIV_PRIV_FIELD") . ") VALUES ('" . AdjustSql($table[4] . $table[0], Config("USER_LEVEL_PRIV_DBID")) . "', " . $this-><#= userLevelIdFldParm #>->CurrentValue . ", " . $privilege . ")";
                $c->executeUpdate($sql);
            }
        }
        $Security->setupUserLevel();
        return true;
    }

    // Get table caption
    protected function getTableCaption($i)
    {
        global $Language;
        $caption = "";
        if ($i < $this->TableNameCount) {
            $caption = $Language->TablePhrase($this->TableList[$i][1], "TblCaption");
            if ($caption == "") {
                $caption = $this->TableList[$i][2];
            }
            if ($caption == "") {
                $caption = $this->TableList[$i][0];
                $caption = preg_replace('/^\{\w{8}-\w{4}-\w{4}-\w{4}-\w{12}\}/', '', $caption); // Remove project id
            }
        }
        return $caption;
    }

<## Common server events #>
<#= include('shared/server-events.php') #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
