<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<# if (hasUserTable) { #>
<#= include('shared/config-table.php') #>
<# } #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

<## Captcha variables #>
<#= include('shared/captcha-var.php') #>

    // Properties
    public $Username;
    public $Password;
    public $LoginType;
    public $IsModal = false;

<#
let loginTblName = secTable ? secTable.TblName : "login",
    loginTblVar = secTblVar || "login",
    useRawUsername = isHardCodeAdmin && PROJ.SecLoginID.match(/[<>"'&]/),
    useRawPassword = isHardCodeAdmin && PROJ.SecPasswd.match(/[<>"'&]/);

#>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $Breadcrumb, $SkipHeaderFooter;

        $this->OffsetColumnClass = ""; // Override user table

        // Create Username/Password field object (used by validation only)
        $this->Username = new DbField("<#= loginTblVar #>", "<#= Quote(loginTblName) #>", "username", "username", "username", "", 202, 255, 0, false, "", false, false, false);
        $this->Username->EditAttrs->appendClass("<#= inputClass #>");
    <# if (useRawUsername) { #>
        $this->Username->Raw = true;
    <# } #>
        $this->Password = new DbField("<#= loginTblVar #>", "<#= Quote(loginTblName) #>", "password", "password", "password", "", 202, 255, 0, false, "", false, false, false);
        $this->Password->EditAttrs->appendClass("<#= inputClass #>");
    <# if (useRawPassword) { #>
        $this->Password->Raw = true;
    <# } else { #>
        if (Config("ENCRYPTED_PASSWORD")) {
            $this->Password->Raw = true;
        }
    <# } #>
        $this->LoginType = new DbField("<#= loginTblVar #>", "<#= Quote(loginTblName) #>", "type", "logintype", "logintype", "", 202, 255, 0, false, "", false, false, false);

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "LoginPage", CurrentUrl(), "", "", true);
        $this->Heading = $Language->phrase("LoginPage");

        $this->Username->setFormValue(""); // Initialize
        $this->Password->setFormValue("");
        $this->LoginType->setFormValue("");
        $lastUrl = $Security->lastUrl(); // Get last URL
        if ($lastUrl == "") {
            $lastUrl = "<#= indexPage #>";
        }

        // Show messages
        $flash = Container("flash");
        if ($failure = $flash->getFirstMessage("failure")) {
            $this->setFailureMessage($failure);
        }
        if ($success = $flash->getFirstMessage("success")) {
            $this->setSuccessMessage($success);
        }
        if ($warning = $flash->getFirstMessage("warning")) {
            $this->setWarningMessage(warning);
        }

        // Login
        if (IsLoggingIn()) { // After changing password

            $this->Username->setFormValue(Session(SESSION_USER_PROFILE_USER_NAME));
            $this->Password->setFormValue(Session(SESSION_USER_PROFILE_PASSWORD));
            $this->LoginType->setFormValue(Session(SESSION_USER_PROFILE_LOGIN_TYPE));
            $validPwd = $Security->validateUser($this->Username->CurrentValue, $this->Password->CurrentValue, false);
            if ($validPwd) {
                $_SESSION[SESSION_USER_PROFILE_USER_NAME] = "";
                $_SESSION[SESSION_USER_PROFILE_PASSWORD] = "";
                $_SESSION[SESSION_USER_PROFILE_LOGIN_TYPE] = "";
            }

        } elseif (Get("provider")) { // OAuth provider

            $provider = ucfirst(strtolower(trim(Get("provider")))); // e.g. Google, Facebook
            $validate = $Security->validateUser($this->Username->CurrentValue, $this->Password->CurrentValue, false, $provider); // Authenticate by provider
            $validPwd = $validate;
            if ($validate) {
                $this->Username->setFormValue($UserProfile->get("email"));
                if (Config("DEBUG") && !$Security->isLoggedIn()) {
                    $validPwd = false;
                    $this->setFailureMessage(str_replace("%u", $this->Username->CurrentValue, $Language->phrase("UserNotFound"))); // Show debug message
                }
            } else {
                $this->setFailureMessage(str_replace("%p", $provider, $Language->phrase("LoginFailed")));
            }

        } else { // Normal login

            if (!$Security->isLoggedIn()) {
                $Security->autoLogin();
            }

            $Security->loadUserLevel(); // Load user level

            if ($Security->isLoggedIn()) {
                if ($this->getFailureMessage() != "") { // Show error
                    $error = [
                        "statusCode" => 0,
                        "error" => [
                            "class" => "text-warning",
                            "type" => "",
                            "description" => $this->getFailureMessage(),
                        ],
                    ];
                    Container("flash")->addMessage("error", $error);
                    $lastUrl = "<#= GetRouteUrl("error") #>";
                    $this->clearFailureMessage();
                }
                $this->terminate($lastUrl); // Redirect to error page
                return;
            }

            $validate = false;
            if (Post($this->Username->FieldVar) !== null) {
                $this->Username->setFormValue(Post($this->Username->FieldVar));
                $this->Password->setFormValue(Post($this->Password->FieldVar));
                $this->LoginType->setFormValue(strtolower(Post($this->LoginType->FieldVar)));
                $validate = $this->validateForm();
            } elseif (Config("ALLOW_LOGIN_BY_URL") && Get($this->Username->FieldVar) !== null) {
                $this->Username->setQueryStringValue(Get($this->Username->FieldVar));
                $this->Password->setQueryStringValue(Get($this->Password->FieldVar));
                $this->LoginType->setQueryStringValue(strtolower(Get($this->LoginType->FieldVar)));
                $validate = $this->validateForm();
            } else { // Restore settings
                if (ReadCookie("Checksum") == strval(crc32(md5(Config("RANDOM_KEY"))))) {
                    $this->Username->setFormValue(Decrypt(ReadCookie("Username")));
                }
                if (ReadCookie("AutoLogin") == "autologin") {
                    $this->LoginType->setFormValue("a");
                } else { // Restore settings
                    $this->LoginType->setFormValue("");
                }
            }

            if (!EmptyValue($this->Username->CurrentValue)) {

                $_SESSION[SESSION_USER_LOGIN_TYPE] = $this->LoginType->CurrentValue; // Save user login type

                $_SESSION[SESSION_USER_PROFILE_USER_NAME] = $this->Username->CurrentValue; // Save login user name
                $_SESSION[SESSION_USER_PROFILE_LOGIN_TYPE] = $this->LoginType->CurrentValue; // Save login type

<# if (checkLoginRetry) { #>
                // Max login attempt checking
                if ($UserProfile->exceedLoginRetry($this->Username->CurrentValue)) {
                    $validate = false;
                    $this->setFailureMessage(str_replace("%t", Config("USER_PROFILE_RETRY_LOCKOUT"), $Language->phrase("ExceedMaxRetry")));
                }
<# } #>

            }

            $validPwd = false;

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

            if ($validate) {

            <# if (ServerScriptExist("Other", "User_LoggingIn")) { #>
                // Call Logging In event
                $validate = $this->userLoggingIn($this->Username->CurrentValue, $this->Password->CurrentValue);
            <# } else { #>
                $validate = true;
            <# } #>

                if ($validate) {
                    $validPwd = $Security->validateUser($this->Username->CurrentValue, $this->Password->CurrentValue, false); // Manual login
                    if (!$validPwd) {
<# if (checkPasswordExpiry) { #>
                        // Password expired, force change password
                        if (IsPasswordExpired()) {
                            $this->setFailureMessage($Language->phrase("PasswordExpired"));
                            $this->terminate("<#= changePasswordPage #>");
                            return;
                        }
<# } #>
                        $this->Username->setFormValue(""); // Clear login name
                        $this->Username->addErrorMessage($Language->phrase("InvalidUidPwd")); // Invalid user name or password
                        $this->Password->addErrorMessage($Language->phrase("InvalidUidPwd")); // Invalid user name or password
<# if (checkPasswordExpiry) { #>
                    // Password changed date not initialized, set as today
                    } elseif ($UserProfile->emptyPasswordChangedDate($this->Username->CurrentValue)) {
                        $UserProfile->setValue(Config("USER_PROFILE_LAST_PASSWORD_CHANGED_DATE"), StdCurrentDate());
                        $UserProfile->saveProfileToDatabase($this->Username->CurrentValue);
<# } #>
                    }
                } else {
                    if ($this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("LoginCancelled")); // Login cancelled
                    }
                }
            }

        }

        // After login
        if ($validPwd) {
            // Write cookies
            if ($this->LoginType->CurrentValue == "a") { // Auto login
                WriteCookie("AutoLogin", "autologin"); // Set autologin cookie
                WriteCookie("Username", Encrypt($this->Username->CurrentValue)); // Set user name cookie
                WriteCookie("Password", Encrypt($this->Password->CurrentValue)); // Set password cookie
                WriteCookie('Checksum', crc32(md5(Config("RANDOM_KEY"))));
            } else {
                WriteCookie("AutoLogin", ""); // Clear auto login cookie
            }

        <# if (PROJ.SecLogInOutAuditTrail) { #>
            $this->writeAuditTrailOnLogin();
        <# } #>

        <# if (ServerScriptExist("Other", "User_LoggedIn")) { #>
            // Call loggedin event
            $this->userLoggedIn($this->Username->CurrentValue);
        <# } #>

            $this->terminate($lastUrl); // Return to last accessed URL
            return;

        } elseif (!EmptyValue($this->Username->CurrentValue) && !EmptyValue($this->Password->CurrentValue)) {

<# if (ServerScriptExist("Other", "User_LoginError")) { #>
            // Call user login error event
            $this->userLoginError($this->Username->CurrentValue, $this->Password->CurrentValue);
<# } #>
        }

        // Set up error message
        if (EmptyValue($this->Username->ErrorMessage)) {
            $this->Username->ErrorMessage = $Language->phrase("EnterUserName");
        }
        if (EmptyValue($this->Password->ErrorMessage)) {
            $this->Password->ErrorMessage = $Language->phrase("EnterPassword");
        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Validate form
    protected function validateForm()
    {
        global $Language;

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }

        $validateForm = true;
        if (EmptyValue($this->Username->CurrentValue)) {
            $this->Username->addErrorMessage($Language->phrase("EnterUserName"));
            $validateForm = false;
        }

        if (EmptyValue($this->Password->CurrentValue)) {
            $this->Password->addErrorMessage($Language->phrase("EnterPassword"));
            $validateForm = false;
        }

    <# if (ServerScriptExist("Other", "Form_CustomValidate")) { #>
        // Call Form Custom Validate event
        $formCustomError = "";
        $validateForm = $validateForm && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
    <# } #>

        return $validateForm;

    }

<# if (PROJ.SecLogInOutAuditTrail) { #>

    // Write audit trail on login
    protected function writeAuditTrailOnLogin()
    {
        global $Language;
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>
        WriteAuditLog($usr, $Language->phrase("AuditTrailLogin"), CurrentUserIP(), "", "", "", "");
    }

<# } #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Other", "User_LoggingIn") #>
    <#= GetServerScript("Other", "User_LoggedIn") #>
    <#= GetServerScript("Other", "User_LoginError") #>
    <#= GetServerScript("Other", "Form_CustomValidate") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
