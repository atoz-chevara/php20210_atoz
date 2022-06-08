<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

<## Captcha variables #>
<#= include('shared/captcha-var.php') #>

    public $Email;
    public $IsModal = false;

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $UserTable, $Breadcrumb, $SkipHeaderFooter;

        $this->OffsetColumnClass = ""; // Override user table

        // Create Email field object (used by validation only)
        $this->Email = new DbField("<#= secTblVar #>", "<#= Quote(secTable.TblName) #>", "email", "email", "email", "", 202, 255, 0, false, "", false, false, false);
        $this->Email->EditAttrs->appendClass("<#= inputClass #>");

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "ResetPwd", CurrentUrl(), "", "", true);
        $this->Heading = $Language->phrase("ResetPwd");

        $postBack = IsPost();
        $validEmail = false;
        $action = "";
        $userName = "";
        $activateCode = "";
        $filter = "";

        if ($postBack) {

            // Setup variables
            $this->Email->setFormValue(Post($this->Email->FieldVar));
            $validEmail = $this->validateForm();

            if ($validEmail) {
                $action = "reset"; // Prompt user to change password
            }

            // Set up filter (WHERE Clause)
            $emailFld = @$UserTable->Fields[Config("USER_EMAIL_FIELD_NAME")];
            if ($emailFld && $emailFld->isEncrypt()) { // If encrypted, need to loop all records (to be improved)
                $filter = "";
            } else {
                $filter = GetUserFilter(Config("USER_EMAIL_FIELD_NAME"), $this->Email->CurrentValue);
            }

        // Handle email activation
        } elseif (Get("action") != "") {
            $action = Get("action");
            $userName = Get("user");
            $activateCode = Get("code");
            if ($userName != Decrypt($activateCode) || !SameText($action, "reset")) { // Email activation
                if ($this->getFailureMessage() == "") {
                    $this->setFailureMessage($Language->phrase("ActivateFailed")); // Set activate failed message
                }
                $this->terminate("<#= loginPage #>"); // Go to login page
                return;
            }
            if (SameText($action, "reset")) {
                $action = "resetpassword";
            }
            $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"), $userName);
        }

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

        if ($action != "") {

            $emailSent = false;

            $this->CurrentFilter = $filter;
            $sql = $this->getCurrentSql();

            if ($rsuser = Conn($UserTable->Dbid)->executeQuery($sql)) {
                $validEmail = false;
                while ($rsold = $rsuser->fetch(\PDO::FETCH_ASSOC)) {
                    if ($action == "resetpassword") // Check username if email activation
                        $validEmail = SameString($userName, GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $rsold));
                    else
                        $validEmail = SameText($this->Email->CurrentValue, GetUserInfo(Config("USER_EMAIL_FIELD_NAME"), $rsold));
                    if ($validEmail) {
    <# if (ServerScriptExist("Other", "User_RecoverPassword")) { #>
                        // Call User Recover Password event
                        $validEmail = $this->userRecoverPassword($rsold);
    <# } #>
                        if ($validEmail) {
                            $userName = GetUserInfo(Config("LOGIN_USERNAME_FIELD_NAME"), $rsold);
                            $password = GetUserInfo(Config("LOGIN_PASSWORD_FIELD_NAME"), $rsold);
                        }
                    }
                    if ($validEmail) {
                        break;
                    }
                }

                $rsuser->closeCursor();

                if ($validEmail) {
                    if (SameText($action, "resetpassword")) { // Reset password
                        $_SESSION[SESSION_USER_PROFILE_USER_NAME] = $userName; // Save login user name
                        $_SESSION[SESSION_STATUS] = "passwordreset";
                        $this->terminate("<#= changePasswordPage #>");
                        return;
                    } else {
                        $email = new Email();
                        $email->load(Config("EMAIL_RESET_PASSWORD_TEMPLATE"));
                        $activateLink = FullUrl("", "resetpwd") . "?action=reset";
                        $activateLink .= "&user=" . rawurlencode($userName);
                        $activateLink .= "&code=" . Encrypt($userName);
                        $email->replaceContent('<!--$ActivateLink-->', $activateLink);
                        $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
                        $email->replaceRecipient($this->Email->CurrentValue); // Replace Recipient
                        $email->replaceContent('<!--$UserName-->', $userName);
        <# if (ServerScriptExist("Other", "Email_Sending")) { #>
                        $args = [];
                        $args["rs"] = &$rsold;
                        if ($this->emailSending($email, $args)) {
                            $emailSent = $email->send();
                        }
        <# } else { #>
                        $emailSent = $email->send();
        <# } #>
                    }
                }

            }

            if ($validEmail && !$emailSent) {
                $this->setFailureMessage($email->SendErrDescription); // Set up error message
            }
            $this->setSuccessMessage($Language->phrase("ResetPasswordResponse")); // Set up success message
            $this->terminate("<#= loginPage #>"); // Return to login page
            return;

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
        if (EmptyValue($this->Email->CurrentValue)) {
            $this->Email->addErrorMessage(str_replace("%s", $Language->phrase("Email"), $Language->phrase("EnterRequiredField")));
            $validateForm = false;
        }

        if (!CheckEmail($this->Email->CurrentValue)) {
            $this->Email->addErrorMessage($Language->phrase("IncorrectEmail"));
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

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Other", "Email_Sending") #>
    <#= GetServerScript("Other", "Form_CustomValidate") #>
    <#= GetServerScript("Other", "User_RecoverPassword") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
