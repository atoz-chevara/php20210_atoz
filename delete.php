<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $DbMasterFilter = "";
    public $DbDetailFilter = "";
    public $StartRecord;
    public $TotalRecords = 0;
    public $RecordCount;
    public $RecKeys = [];

    public $StartRowCount = 1;
    public $RowCount = 0;

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

    <# if (masterTables.length > 0) { #>
        // Set up master/detail parameters
        $this->setupMasterParms();
    <# } #>

        // Set up Breadcrumb
        $this->setupBreadcrumb();

        // Load key parameters
        $this->RecKeys = $this->getRecordKeys(); // Load record keys
        $filter = $this->getFilterFromRecordKeys();
        if ($filter == "") {
            $this->terminate("<#= listPage #>"); // Prevent SQL injection, return to list
            return;
        }

        // Set up filter (WHERE Clause)
        $this->CurrentFilter = $filter;

    <#
        if (hasUserIdFld) {
    #>

        // Check if valid User ID
        $conn = $this->getConnection();
        $sql = $this->getSql($this->CurrentFilter);
        $rows = $conn->fetchAll($sql);
        $res = true;
        foreach ($rows as $row) {
            $this->loadRowValues($row);
            if (!$this->showOptionLink("<#= ctrlId #>")) {
                $userIdMsg = $Language->phrase("NoDeletePermission");
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

        // Get action
        if (IsApi()) {
            $this->CurrentAction = "delete"; // Delete record directly
        } elseif (Post("action") !== null) {
            $this->CurrentAction = Post("action");
        } elseif (Get("action") == "1") {
            $this->CurrentAction = "delete"; // Delete record directly
        } else {
        <# if (inlineDelete) { #>
            $this->CurrentAction = "delete"; // Delete record directly
        <# } else { #>
            $this->CurrentAction = "show"; // Display record
        <# } #>
        }

        if ($this->isDelete()) {
            $this->SendEmail = true; // Send email on delete success
            if ($this->deleteRows()) { // Delete rows
                if ($this->getSuccessMessage() == "") {
                    $this->setSuccessMessage($Language->phrase("DeleteSuccess")); // Set up success message
                }
                if (IsApi()) {
                    $this->terminate(true);
                    return;
                } else {
                    $this->terminate($this->getReturnUrl()); // Return to caller
                    return;
                }
            } else { // Delete failed
                if (IsApi()) {
                    $this->terminate();
                    return;
                }
        <# if (inlineDelete) { #>
                $this->terminate($this->getReturnUrl()); // Return to caller
                return;
        <# } else { #>
                $this->CurrentAction = "show"; // Display record
        <# } #>
            }
        }
        if ($this->isShow()) { // Load records for display
            if ($this->Recordset = $this->loadRecordset()) {
                $this->TotalRecords = $this->Recordset->recordCount(); // Get record count
            }
            if ($this->TotalRecords <= 0) { // No record found, exit
                if ($this->Recordset) {
                    $this->Recordset->close();
                }
                $this->terminate("<#= listPage #>"); // Return to list
                return;
            }
        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
