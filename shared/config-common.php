<#
    // jQuery version
    global.jQueryVersion = "3.6.0";

    // Project Name
    global.projVar = PROJ.ProjVar;

    // Control
    global.ctrlId = CONTROL.CtrlID.toLowerCase();
    global.ctrlType = CONTROL.CtrlType.toLowerCase();
    global.eventCtrlType = ctrlType;
    global.ctrlTagExt = CONTROL.CtrlTagExt.toLowerCase();

    // Control type
    switch (ctrlType) {
        case "table":
        case "report":
            eventCtrlType = "Table";
            break;
        default:
            eventCtrlType = "Other";
            break;
    }

    // Page object
    global.pageObj = GetPageObject();
    if (CONTROL.CtrlOFolderID == "_views") {
        pageObj = ctrlId == "grid" ? Code.Grid : Code.Page; // PHP
    }
    PageObject = pageObj; // Used in ServerFldObj

    // Form object
    global.formName = GetFormObject();
    global.formNoValidate = PROJ.AddNoValidateToForm ? " novalidate" : "";

    // Common files
    global.indexPage = GetRouteUrl("index");
    global.homePage = PROJ.StartPage;
    if (homePage == "")
        homePage = indexPage;
    global.loginPage = GetRouteUrl("login");
    global.logoutPage = GetRouteUrl("logout");
    global.registerPage = GetRouteUrl("register");
    global.resetPasswordPage = GetRouteUrl("reset_password");
    global.changePasswordPage = GetRouteUrl("change_password");
    global.personalDataPage = GetRouteUrl("personal_data");
    global.userPrivPageQuoted = "";
    global.userLevelListPage = "";
    global.userLevelTblVar = "";
    if (DB.UseDynamicUserLevel && !IsEmpty(DB.UserLevelTbl) && !IsEmpty(DB.UserLevelIdFld)) {
        let userPrivPage = GetRouteUrl("userpriv"),
            userLevelTable = GetTableObject(DB.UserLevelTbl),
            userLevelIdField = GetFieldObject(userLevelTable, DB.UserLevelIdFld),
            userLevelIdFldParm = userLevelIdField.FldParm;
        global.userLevelTblVar = userLevelTable.TblVar;
        userLevelListPage = GetRouteUrl("list", userLevelTable);
        userPrivPageQuoted = `"${userPrivPage}?${userLevelIdFldParm}=" . $this->${userLevelIdFldParm}->CurrentValue`;
    }

    // JavasSript popup message
    global.useJavaScriptMessage = PROJ.UseJavaScriptMessage;

    // CSS classes
    global.tableClasses = "table";
    if (PROJ.ThemeTableStriped)
        tableClasses += " table-striped";
    if (PROJ.ThemeTableBordered && !PROJ.ThemeTableBorderless)
        tableClasses += " table-bordered";
    if (PROJ.ThemeTableBorderless && !PROJ.ThemeTableBordered)
        tableClasses += " table-borderless";
    if (PROJ.ThemeTableHover)
        tableClasses += " table-hover";
    if (PROJ.ThemeTableCondensed)
        tableClasses += " table-sm";

    global.desktopTableClass = tableClasses + " ew-desktop-table";

    global.tableClass = "";
    if (["list", "grid", "delete", "userpriv", "master"].includes(ctrlId)) {
        tableClass = "table ew-table";
    } else if (ctrlId == "preview") {
        tableClass = "table ew-table ew-preview-table";
    } else if (["add", "register", "edit", "update", "search"].includes(ctrlId)) {
        tableClass = "ew-" + ctrlId + "-div";
    } else {
        tableClass = tableClasses + " ew-view-table";
    }

    global.tableIdBase = "";
    if (["table", "report"].includes(ctrlType))
        tableIdBase = "tbl_" + TABLE.TblVar + ctrlId;
    else
        tableIdBase = "tbl_" + ctrlId;

    // CSS classes
    global.leftColumnClass = PROJ.FormLeftColumnClass || "col-sm-2";
    global.rightColumnClass = "";
    global.offsetClass = "";
    let match = leftColumnClass.match(/^col\-(\w+)\-(\d+)$/);
    if (match) {
        rightColumnClass = "col-" + match[1] + "-" + (12 - parseInt(match[2], 10));
        offsetClass = rightColumnClass + " " + leftColumnClass.replace("col-", "offset-");
    } else {
        leftColumnClass = "col-sm-2";
        rightColumnClass = "col-sm-10";
        offsetClass = "col-sm-10 offset-sm-2";
    }
    global.labelClass = leftColumnClass + " col-form-label ew-label";
    global.inputClass = "form-control ew-control";
    global.tableLeftColumnClass = leftColumnClass.replace(/^col\-\w+\-(\d+)$/, "w-col-$1"); // Change to w-col-*

    // Use tabular form for desktop
    global.useTabularFormForDesktop = PROJ.UseTabularFormForDesktop;

    // Security
    global.isSecurityEnabled = PROJ.SecType && PROJ.SecType != "None";
    global.isHardCodeAdmin = ["Both", "Hard Code"].includes(PROJ.SecType);
    global.hasUserTable = HasUserTable();
    global.secTblVar = "";
    global.secTable = null;
    if (hasUserTable) {
        secTable = GetTableObject(PROJ.SecTbl);
        secTblVar = secTable.TblVar;
    }
    global.isStaticUserLevel = IsStaticUserLevel();
    global.isDynamicUserLevel = IsDynamicUserLevel();
    global.isUserLevel = isStaticUserLevel || isDynamicUserLevel;
    global.hasUserId = hasUserTable && !IsEmpty(DB.SecuUserIDFld);
    global.useUserIdForAuditTrail = hasUserId && PROJ.UseUserIDForAuditTrail;
    global.hasParentUserId = hasUserId && !IsEmpty(DB.SecuParentUserIDFld);
    global.hasUserProfile = hasUserTable && !IsEmpty(DB.SecUserProfileFld);
    global.checkConcurrentUser = hasUserProfile && PROJ.CheckConcurrentUser;
    global.checkLoginRetry = hasUserProfile && PROJ.CheckLoginRetry;
    global.checkPasswordExpiry = hasUserProfile && PROJ.CheckPasswordExpiry;
    global.resetConcurrentUser = checkConcurrentUser && PROJ.ResetConcurrentUser;
    global.resetLoginRetry = checkLoginRetry && PROJ.ResetLoginRetry;
    global.setPasswordExpired = checkPasswordExpiry && PROJ.SetPasswordExpired;
    global.resendRegisterEmail = hasUserTable && PROJ.SecRegisterEmail && !IsEmpty(PROJ.SecEmailFld) && PROJ.ResendRegisterEmail;

    global.minPasswordStrength = PROJ.MinPasswordStrength;
    global.genPasswordLength = PROJ.GenPasswordLength;

    global.submitButtonClass = "btn btn-primary ew-btn";
    global.resetButtonClass = "btn btn-default ew-btn";
    global.cancelButtonClass = resetButtonClass;

    // Use place holder for textbox
    UsePlaceHolder = PROJ.UsePlaceHolder;

    // Custom file relative paths
    RelativePath = "";
    IncludeFilePathPrefix = "";
    AppRootRelativePath = "";

    // Multi-page
    global.useMultiPage = false;
    global.showMultiPageForDetails = false;

    // Google/Facebook login
    global.googleClientId = PROJ.GoogleClientId;
    global.googleClientSecret = PROJ.GoogleClientSecret;
    global.googleAuthEnabled = !IsEmpty(googleClientId) && !IsEmpty(googleClientSecret);
    global.facebookAppId = PROJ.FacebookAppId;
    global.facebookAppSecret = PROJ.FacebookAppSecret;
    global.facebookAuthEnabled = !IsEmpty(facebookAppId) && !IsEmpty(facebookAppSecret);
    global.useOAuth2 = googleAuthEnabled || facebookAuthEnabled;

    // Menu items
    global.layoutTopNav = PROJ.LayoutClass == "layout-top-nav";
    global.allMenuItems = GetMenuItems();
    global.topMenuItems = layoutTopNav ? allMenuItems : GetMenuItems(true);
    global.allTopMenuItems = topMenuItems.length == allMenuItems.length; // All items are navbar items

    // Header / footer
    global.templateExportStart = "";
    global.templateExportEnd = "";
    global.templatePrintStart = "";
    global.templatePrintExportStart = "";
    global.templateSkipHeaderFooterStart = Code.if('@!$SkipHeaderFooter'); // Skip header footer
    global.templateSkipHeaderFooterEnd = Code.end;
    if (IsExport()) {
        templateExportStart = Code.if('!IsExport()');
        templateExportEnd = Code.end;
        templatePrintStart = Code.if('IsExport("print")');
        templatePrintExportStart = Code.if('!IsExport() || IsExport("print")');
    }
    global.useModalLookup = UseModalLookup(); // Use modal lookup
    global.useEmailExport = UseEmailExport(); // Export to Email

    // Modal dialogs
    global.useModalLogin = PROJ.ModalLogin; // Modal login
    global.useModalRegister = PROJ.ModalRegister; // Modal register
    global.useModalChangePassword = PROJ.ModalChangePassword; // Modal change password
    global.useModalResetPassword = PROJ.ModalForgotPassword; // Modal reset password

    // Captcha
    global.extCaptcha = GetExtensionObject(captchaExtName);
    global.useCaptcha = false;
    global.confirmCaptcha = false;

    // Set up Captcha for login page
    let useCaptchaInLoginPage = false;
    if (extCaptcha && extCaptcha.Enabled && extCaptcha.PROJ)
        useCaptchaInLoginPage = extCaptcha.PROJ.UseCaptchaInLoginPage == "1";
    if (ctrlId == "login" && (PROJ.SecLoginCaptcha || useCaptchaInLoginPage)) {
        useCaptcha = true;
        confirmCaptcha = false;
    }

    // Common names
    global.isModal = Code.getName(pageObj, Code.IsModal);
    global.isMobileOrModal = Code.getName(pageObj, Code.IsMobileOrModal);

    // Get list of tables
    global.controllerTables = TABLES.filter(t => t.TblGen &&
        !(t.TblType == "REPORT" && t.TblReportType == "custom" && !(EndsText(t.TblName, ".php") && t.IncludeFiles)) || t.TblAddOpt);

    // Get list of other actions
    let actions = new Map();
    for (let ctrl of CONTROLS) {
        let id = ctrl.CtrlID.toLowerCase(), ofid = ctrl.CtrlOFolderID.toLowerCase();
        if (["login", "logout", "reset_password", "change_password", "register", "userpriv", "privacy", "personal_data", "error"].includes(id) && ofid == "_models") {
            if (id == "login" && !isSecurityEnabled ||
                id == "logout" && !isSecurityEnabled ||
                id == "reset_password" && (!hasUserTable || !PROJ.SecForgetPwdPage) ||
                id == "change_password" && !((PROJ.SecChangePwdPage || PROJ.MD5Password) && (PROJ.SecType == "Use Table" || PROJ.SecType == "Both")) ||
                id == "register" && (!hasUserTable || !PROJ.SecRegisterPage) ||
                id == "userpriv" && (!hasUserTable || !isDynamicUserLevel) ||
                id == "privacy" && !PROJ.UseCookiePolicy ||
                id == "personal_data" && (!hasUserTable || !PROJ.UsePersonalData))
                continue;
            actions.set(ctrl.CtrlID, ""); // Use original case
        }
    }
    global.controllerActions = actions;
#>