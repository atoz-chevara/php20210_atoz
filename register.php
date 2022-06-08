<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    public $FormClassName = "ew-horizontal ew-form ew-register-form";
    public $IsModal = false;
    public $IsMobileOrModal = false;

<# if (useMultiPage) { #>
    public $MultiPages; // Multi pages object
<# } #>

<## Captcha variables #>
<#= include('shared/captcha-var.php') #>

<#
    let loginIdField = GetFieldObject(secTable, PROJ.SecLoginIDFld),
        loginIdFldParm = loginIdField.FldParm,
        loginIdFldObj = "$this->" + loginIdFldParm,
        passwordField = GetFieldObject(secTable, PROJ.SecPasswdFld),
        passwordFldParm = passwordField.FldParm,
        passwordFldObj = "$this->" + passwordFldParm;
#>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $UserTable, $CurrentLanguage, $Breadcrumb, $SkipHeaderFooter;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }
        $this->IsMobileOrModal = IsMobile() || $this->IsModal;
        $this->FormClassName = "ew-form ew-register-form ew-horizontal";

        // Set up Breadcrumb
        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "RegisterPage", CurrentUrl(), "", "", true);
        $this->Heading = $Language->phrase("RegisterPage");

        $userExists = false;

        $this->loadRowValues(); // Load default values

        // Get action
        $action = "";
        if (IsApi()) {
            $action = "insert";
        } elseif (Post("action") != "") {
            $action = Post("action");
        }

        // Check action
        if ($action != "") {

            // Get action
            $this->CurrentAction = $action;

            $this->loadFormValues(); // Get form values

            // Validate form
            if (!$this->validateForm()) {
                if (IsApi()) {
                    $this->terminate();
                    return;
                } else {
                    $this->CurrentAction = "show"; // Form error, reset action
                }
            }

        } else {
            $this->CurrentAction = "show"; // Display blank record
        }

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

        <#
            if (PROJ.SecRegisterActivate && !IsEmpty(PROJ.SecRegisterActivateFld)) {
        #>
        // Handle email activation
        if (Get("action") != "") {
            $action = Get("action");
            $userName = Get("user");
            $code = Get("activatetoken");
            @list($emailAddress, $approvalCode, $pwd) = explode(",", $code, 3);
            $emailAddress = Decrypt($emailAddress);
            $approvalCode = Decrypt($approvalCode);
            $pwd = Decrypt($pwd);
            if ($userName == $approvalCode) {
                if (SameText($action, "confirm")) { // Email activation
                    if ($this->activateUser($userName)) { // Activate this user
                        if ($this->getSuccessMessage() == "") {
                            $this->setSuccessMessage($Language->phrase("ActivateAccount")); // Set up message acount activated
                        }
    <# if (PROJ.SecRegisterAutoLogin && !IsEmpty(PROJ.SecLoginIDFld) && !IsEmpty(PROJ.SecPasswdFld)) { #>
                        if ($Security->validateUser($userName, $pwd, true)) {
                            $this->terminate(<#= registerReturnPage #>); // Go to return page
                            return;
                        } else {
                            $this->setFailureMessage($Language->phrase("AutoLoginFailed")); // Set auto login failed message
                            $this->terminate("<#= loginPage #>"); // Go to login page
                            return;
                        }
    <# } else { #>
                        $this->terminate("<#= loginPage #>"); // Go to login page
                        return;
    <# } #>
                    }
                }
            }
            if ($this->getFailureMessage() == "") {
                $this->setFailureMessage($Language->phrase("ActivateFailed")); // Set activate failed message
            }
            $this->terminate("<#= loginPage #>"); // Go to login page
            return;
        }
        <#
            }
        #>

        // Insert record
        if ($this->isInsert()) {

    <# if (!loginIdField.FldAutoIncrement) { #>
            // Check for duplicate User ID
            $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"), <#= loginIdFldObj #>->CurrentValue);
            // Set up filter (WHERE Clause)
            $this->CurrentFilter = $filter;
            $userSql = $this->getCurrentSql();
            $rs = Conn($UserTable->Dbid)->executeQuery($userSql);
            if ($rs->fetch()) {
                $userExists = true;
                $this->restoreFormValues(); // Restore form values
                $this->setFailureMessage($Language->phrase("UserExists")); // Set user exist message
            }
    <# } else { #>
            $userExists = false;
    <# } #>

            if (!$userExists) {
                $this->SendEmail = true; // Send email on add success
                if ($this->addRow()) { // Add record
    <#
        if (PROJ.SecRegisterEmail && !IsEmpty(PROJ.SecEmailFld)) {
    #>

                    $email = $this->prepareRegisterEmail();
                    // Get new record
                    $this->CurrentFilter = $this->getRecordFilter();
                    $sql = $this->getCurrentSql();
                    $row = Conn($UserTable->Dbid)->fetchAssoc($sql);
<# if (ServerScriptExist("Other", "Email_Sending")) { #>
                    $args = [];
                    $args["rs"] = $row;
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

<# if (hasUserProfile && MultiLanguage) { #>
                    // Save user language
                    global $UserProfile;
                    $userName = GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $row);
                    $UserProfile->setLanguageId($userName, $CurrentLanguage);
<# } #>

    <#
        }
    #>

    <# if (PROJ.SecRegisterActivate) { #>
                    if ($this->getSuccessMessage() == "") {
                        $this->setSuccessMessage($Language->phrase("RegisterSuccessActivate")); // Activate success
                    }
    <# } else { #>
                    if ($this->getSuccessMessage() == "") {
                        $this->setSuccessMessage($Language->phrase("RegisterSuccess")); // Register success
                    }
        <# if (PROJ.SecRegisterAutoLogin && !IsEmpty(PROJ.SecLoginIDFld) && !IsEmpty(PROJ.SecPasswdFld)) { #>
                    // Auto login user
                    if ($Security->validateUser(<#= loginIdFldObj #>->CurrentValue, <#= passwordFldObj #>->FormValue, true)) {
                        // Nothing to do
                    } else {
                        $this->setFailureMessage($Language->phrase("AutoLoginFailed")); // Set auto login failed message
                    }
        <# } #>
    <# } #>

                    if (IsApi()) { // Return to caller
                        $this->terminate(true);
                        return;
                    } else {
                        $this->terminate(<#= registerReturnPage #>); // Return
                        return;
                    }

                } else {
                    $this->restoreFormValues(); // Restore form values
                }
            }

        }

        // API request, return
        if (IsApi()) {
            $this->terminate();
            return;
        }

        // Render row
        <# if (registerConfirm) { #>
        if ($this->isConfirm()) { // Confirm page
            $this->RowType = ROWTYPE_VIEW; // Render view
        } else {
            $this->RowType = ROWTYPE_ADD; // Render add
        }
        <# } else { #>
        $this->RowType = ROWTYPE_ADD; // Render add
        <# } #>
        $this->resetAttributes();
        $this->renderRow();

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<#
    if (PROJ.SecRegisterActivate && !IsEmpty(PROJ.SecRegisterActivateFld)) {
        let activateField = GetFieldObject(secTable, PROJ.SecRegisterActivateFld),
            activateFldName = activateField.FldName,
            activateFldQuoteS = activateField.FldQuoteS,
            activateFldQuoteE = activateField.FldQuoteE,
            activateFldValue = ActivateFieldValue(secTable, activateField);
        if (!IsEmpty(activateFldQuoteS))
            activateFldValue = DoubleQuote(activateFldValue);
#>

    // Activate account based on user
    protected function activateUser($usr)
    {
        global $UserTable, $Language;
        $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"), $usr);
        $sql = $this->getSql($filter);
        $conn = Conn($UserTable->Dbid);
        $rsnew = $conn->fetchAssoc($sql);
        if ($rsnew) {
            $this->loadRowValues($rsnew); // Load row values
            $rsact = [Config("REGISTER_ACTIVATE_FIELD_NAME") => <#= activateFldValue #>]; // Auto register
            $this->CurrentFilter = $filter;
            $res = $this->update($rsact);
            <# if (ServerScriptExist("Other", "User_Activated")) { #>
            if ($res) { // Call User Activated event
                $rsnew[Config("REGISTER_ACTIVATE_FIELD_NAME")] = <#= activateFldValue #>;
                $this->userActivated($rsnew);
            }
            <# } #>
            return $res;
        } else {
            $this->setFailureMessage($Language->phrase("NoRecord"));
            return false;
        }
    }
<#
    }
#>

<## Shared functions #>
<#= include('shared/shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Other", "Email_Sending") #>
    <#= GetServerScript("Other", "Form_CustomValidate") #>
    <#= GetServerScript("Other", "User_Registered") #>
    <#= GetServerScript("Other", "User_Activated") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
