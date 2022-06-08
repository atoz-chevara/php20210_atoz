<## Common config #>
<#= include('shared/config-common.php') #>
<#
    // Configure options
    let disableProjectStyles = PROJ.DisableProjectStyles;

    IncludeFilePathPrefix = "$RELATIVE_PATH . ";
    let relativePath = `<?= $basePath ?>`; // PHP

    let timeoutPage = isSecurityEnabled ? logoutPage : indexPage;

    let logoutCond = (PROJ.AuthenticateMode == "Windows") ? "CurrentUserName() != CurrentWindowsUser()" : Code.true;
    let changePasswordCond = (PROJ.AuthenticateMode == "Windows") ? " && !IsAuthenticated()" : "";
    let brandHref = PROJ.BrandHref || "#";

    let brandLogoClass = PROJ.ThemeBrandLogoClass || "";
    if (brandLogoClass)
        brandLogoClass = " " + brandLogoClass;

    let jQueryFolder = FolderPath("_jquery");
    let jsFolder = FolderPath("_js");
    let cssFolder = FolderPath("_css");

    let userCssFile = GetPath().basename(PROJ.CSS.trim());
#>
<?php

namespace <#= ProjectNamespace #>;

// Base path
$basePath = BasePath(true);
?>
<# if (["layout", "header"].includes(ctrlId)) { #>
<!DOCTYPE html>
<html>
<head>
<title><#= Code.raw(Code.languageProjectPhrase("BodyTitle")) #></title>
<#= Charset() #>
<?php if ($ReportExportType != "" && $ReportExportType != "print") { // Stylesheet for exporting reports ?>
<# if (!disableProjectStyles) { #>
<link rel="stylesheet" href="<#= relativePath #><#= Code.write('CssFile(' + Code.Config.ProjectStylesheetFilename + ')') #>">
<# } #>
<?php } ?>
<#= templatePrintExportStart #>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="<#= relativePath #>plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<#= relativePath #>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="stylesheet" href="<#= relativePath #>adminlte3/css/<#= Code.write('CssFile("adminlte.css")') #>">
<link rel="stylesheet" href="<#= relativePath #>plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="<#= relativePath #><#= cssFolder #>/OverlayScrollbars.min.css">
<# if (!disableProjectStyles) { #>
<link rel="stylesheet" href="<#= relativePath #><#= Code.write('CssFile(' + Code.Config.ProjectStylesheetFilename + ')') #>">
<# } #>
<?php if ($CustomExportType == "pdf" && <#= Code.Config.PdfStylesheetFilename #>) { ?>
<link rel="stylesheet" href="<#= relativePath #><#= Code.write('CssFile(' + Code.Config.PdfStylesheetFilename + ')') #>">
<?php } ?>
<# if (userCssFile) { #>
<link rel="stylesheet" href="<#= relativePath #><#= cssFolder #>/<#= userCssFile #>">
<# } #>
<script src="<#= relativePath #><#= jsFolder #>/ewcore.min.js"></script>
<script>
var $rowindex$ = null;
Object.assign(ew, {
    LANGUAGE_ID: "<#= Code.write(Code.getName(Code.CurrentLanguage)) #>",
    DATE_SEPARATOR: "<#= Code.write(Code.getName(Code.DateSeparator)) #>", // Date separator
    TIME_SEPARATOR: "<#= Code.write(Code.getName(Code.TimeSeparator)) #>", // Time separator
    DATE_FORMAT: "<#= Code.write(Code.getName(Code.DateFormat)) #>", // Default date format
    DATE_FORMAT_ID: <#= Code.write(Code.getName(Code.DateFormatId)) #>, // Default date format ID
    DATETIME_WITHOUT_SECONDS: <#= Code.write(Code.jsBool(Code.Config.DatetimeWithoutSeconds)) #>, // Date/Time without seconds
    DECIMAL_POINT: "<#= Code.raw(Code.getName(Code.DecimalPoint)) #>",
    THOUSANDS_SEP: "<#= Code.raw(Code.getName(Code.ThousandsSeparator)) #>",
    <# if (CheckPasswordStrength) { #>
    MIN_PASSWORD_STRENGTH: <#= minPasswordStrength #>,
    <# } #>
    <# if (GeneratePassword) { #>
    GENERATE_PASSWORD_LENGTH: <#= genPasswordLength #>,
    <# } #>
    SESSION_TIMEOUT: <#= Code.write(Code.Config.SessionTimeout + ' > 0 ? SessionTimeoutTime() : 0') #>, // Session timeout time (seconds)
    SESSION_TIMEOUT_COUNTDOWN: <#= Code.write(Code.Config.SessionTimeoutCountdown) #>, // Count down time to session timeout (seconds)
    SESSION_KEEP_ALIVE_INTERVAL: <#= Code.write(Code.Config.SessionKeepAliveInterval) #>, // Keep alive interval (seconds)
    IS_LOGGEDIN: <#= Code.write(Code.jsBool(Code.IsLoggedIn)) #>, // Is logged in
    IS_SYS_ADMIN: <#= Code.write(Code.jsBool(Code.IsSysAdmin)) #>, // Is sys admin
    CURRENT_USER_NAME: "<#= Code.write(Code.jsEncode(Code.CurrentUserName)) #>", // Current user name
    IS_AUTOLOGIN: <#= Code.write(Code.jsBool(Code.IsAutoLogin)) #>, // Is logged in with option "Auto login until I logout explicitly"
    TIMEOUT_URL: "<#= relativePath #><#= timeoutPage #>", // Timeout URL // PHP
    TOKEN_NAME_KEY: "<#= Code.write(Code.getName(Code.TokenNameKey)) #>", // Token name key
    TOKEN_NAME: "<#= Code.write(Code.getName(Code.TokenName)) #>", // Token name
    API_FILE_TOKEN_NAME: "<#= Code.write(Code.Config.ApiFileTokenName) #>", // API file token name
    API_URL: "<#= Code.write(Code.Config.ApiUrl) #>", // API file name // PHP
    API_ACTION_NAME: "<#= Code.write(Code.Config.ApiActionName) #>", // API action name
    API_OBJECT_NAME: "<#= Code.write(Code.Config.ApiObjectName) #>", // API object name
    API_LIST_ACTION: "<#= Code.write(Code.Config.ApiListAction) #>", // API list action
    API_VIEW_ACTION: "<#= Code.write(Code.Config.ApiViewAction) #>", // API view action
    API_ADD_ACTION: "<#= Code.write(Code.Config.ApiAddAction) #>", // API add action
    API_EDIT_ACTION: "<#= Code.write(Code.Config.ApiEditAction) #>", // API edit action
    API_DELETE_ACTION: "<#= Code.write(Code.Config.ApiDeleteAction) #>", // API delete action
    API_LOGIN_ACTION: "<#= Code.write(Code.Config.ApiLoginAction) #>", // API login action
    API_FILE_ACTION: "<#= Code.write(Code.Config.ApiFileAction) #>", // API file action
    API_UPLOAD_ACTION: "<#= Code.write(Code.Config.ApiUploadAction) #>", // API upload action
    API_JQUERY_UPLOAD_ACTION: "<#= Code.write(Code.Config.ApiJqueryUploadAction) #>", // API jQuery upload action
    API_SESSION_ACTION: "<#= Code.write(Code.Config.ApiSessionAction) #>", // API get session action
    API_LOOKUP_ACTION: "<#= Code.write(Code.Config.ApiLookupAction) #>", // API lookup action
    API_LOOKUP_PAGE: "<#= Code.write(Code.Config.ApiLookupPage) #>", // API lookup page name
    API_PROGRESS_ACTION: "<#= Code.write(Code.Config.ApiProgressAction) #>", // API progress action
    API_EXPORT_CHART_ACTION: "<#= Code.write(Code.Config.ApiExportChartAction) #>", // API export chart action
    API_JWT_AUTHORIZATION_HEADER: "<#= PROJ.ApiJwtAuthHeader || "X-Authorization" #>", // API JWT authorization header
    API_JWT_TOKEN: "<#= Code.write('GetJwtToken()') #>", // API JWT token
    MULTIPLE_OPTION_SEPARATOR: "<#= Code.write(Code.Config.MultipleOptionSeparator) #>", // Multiple option separator
    AUTO_SUGGEST_MAX_ENTRIES: <#= Code.write(Code.Config.AutoSuggestMaxEntries) #>, // Auto-Suggest max entries
    <# if (useEmailExport) { #>
    MAX_EMAIL_RECIPIENT: <#= Code.write(Code.Config.MaxEmailRecipient) #>,
    <# } #>
    IMAGE_FOLDER: "<#= FolderPath("_images", true) #>", // Image folder
    PATH_BASE: "<#= relativePath #>", // Path base // PHP
    <# if (PROJ.AuthenticateMode == "Windows") { #>
    IS_WINDOWS_AUTHENTICATION: true, // DN
    <# } #>
    SESSION_ID: "<#= Code.write(Code.encrypt(Code.SessionId)) #>", // Session ID
    UPLOAD_THUMBNAIL_WIDTH: <#= Code.write(Code.Config.UploadThumbnailWidth) #>, // Upload thumbnail width
    UPLOAD_THUMBNAIL_HEIGHT: <#= Code.write(Code.Config.UploadThumbnailHeight) #>, // Upload thumbnail height
    MULTIPLE_UPLOAD_SEPARATOR: "<#= Code.write(Code.Config.MultipleUploadSeparator) #>", // Upload multiple separator
    IMPORT_FILE_ALLOWED_EXT: "<#= Code.write(Code.Config.ImportFileAllowedExt) #>", // Import file allowed extensions
    USE_COLORBOX: <#= Code.write(Code.jsBool(Code.Config.UseColorbox)) #>,
    USE_JAVASCRIPT_MESSAGE: <#= JsBool(useJavaScriptMessage) #>,
    PROJECT_STYLESHEET_FILENAME: "<#= Code.writeRawPath(Code.Config.ProjectStylesheetFilename) #>", // Project style sheet
    PDF_STYLESHEET_FILENAME: "<#= Code.write(`${Code.Config.PdfStylesheetFilename} ?: ""`) #>", // PDF style sheet // PHP
    EMBED_PDF: <#= Code.write(Code.jsBool(Code.Config.EmbedPdf)) #>,
    ANTIFORGERY_TOKEN_KEY: "<#= Code.write(Code.getName(Code.TokenValueKey)) #>", // PHP
    ANTIFORGERY_TOKEN: "<#= Code.write(Code.getName(Code.TokenValue)) #>", // PHP
    CSS_FLIP: <#= Code.write(Code.jsBool(Code.Config.CssFlip)) #>,
    LAZY_LOAD: <#= Code.write(Code.jsBool(Code.Config.LazyLoad)) #>,
    USE_RESPONSIVE_TABLE: <#= Code.write(Code.jsBool(Code.Config.UseResponsiveTable)) #>,
    RESPONSIVE_TABLE_CLASS: "<#= Code.write(Code.Config.ResponsiveTableClass) #>",
    DEBUG: <#= Code.write(Code.jsBool(Code.Config.Debug)) #>,
    SEARCH_FILTER_OPTION: "<#= Code.write(Code.Config.SearchFilterOption) #>",
    OPTION_HTML_TEMPLATE: <#= Code.write(Code.toJson(Code.Config.OptionHtmlTemplate)) #>,
    USE_OVERLAY_SCROLLBARS: <#= JsBool(PROJ.UseOverlayScrollbars) #>,
    REMOVE_XSS: <#= Code.write(Code.jsBool(Code.Config.RemoveXss)) #>,
    ENCRYPTED_PASSWORD: <#= Code.write(Code.jsBool(Code.Config.EncryptedPassword)) #>,
    INVALID_USERNAME_CHARACTERS: "<#= Code.raw(Code.jsEncode(Code.Config.InvalidUsernameCharacters)) #>",
    INVALID_PASSWORD_CHARACTERS: "<#= Code.raw(Code.jsEncode(Code.Config.InvalidPasswordCharacters)) #>",
    IS_RTL: <#= Code.write(Code.jsBool("IsRTL()")) #>
});
loadjs(ew.PATH_BASE + "<#= jQueryFolder #>/jquery-<#= jQueryVersion #>.min.js", "jquery");
loadjs([
    ew.PATH_BASE + "<#= jsFolder #>/mobile-detect.min.js",
    ew.PATH_BASE + "<#= jsFolder #>/purify.min.js",
    ew.PATH_BASE + "<#= jQueryFolder #>/load-image.all.min.js",
    ew.PATH_BASE + "<#= jsFolder #>/loading-attribute-polyfill.min.js"
], "others");
loadjs([
    ew.PATH_BASE + "plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.css",
    ew.PATH_BASE + "plugins/sweetalert2/sweetalert2.all.min.js"
], "swal");
<#= Code.raw(Code.languageProperty(Code.Language.ToJson)) #>
ew.vars = <#= Code.raw(Code.toJson(Code.getName(Code.ClientVariables))) #>;
ew.ready(["wrapper", "jquery"], ew.PATH_BASE + "<#= jQueryFolder #>/jsrender.min.js", "jsrender", ew.renderJsTemplates);
ew.ready("jsrender", ew.PATH_BASE + "<#= jQueryFolder #>/jquery.overlayScrollbars.min.js", "scrollbars"); // Init sidebar scrollbars after rendering menu
ew.ready("jquery", ew.PATH_BASE + "<#= jQueryFolder #>/jquery.ui.widget.min.js", "widget");
ew.loadjs([
    ew.PATH_BASE + "moment/moment.min.js",
    ew.PATH_BASE + "<#= jsFolder #>/Chart.min.js",
    ew.PATH_BASE + "<#= jsFolder #>/chartjs-plugin-annotation.min.js",
    ew.PATH_BASE + "<#= jsFolder #>/chartjs-plugin-datalabels.min.js"
], "moment");
</script>
<#= IncludeFile("menu") #>
<script>
var cssfiles = [
    ew.PATH_BASE + "<#= cssFolder #>/Chart.min.css",
    ew.PATH_BASE + "<#= cssFolder #>/jquery.fileupload.css",
    ew.PATH_BASE + "<#= cssFolder #>/jquery.fileupload-ui.css"
];
<# if (PROJ.UseColorbox) { #>
cssfiles.push(ew.PATH_BASE + "colorbox/colorbox.css");
<# } #>
loadjs(cssfiles, "css");
var cssjs = [];
<?php foreach (array_merge(Config("STYLESHEET_FILES"), Config("JAVASCRIPT_FILES")) as $file) { // External Stylesheets and JavaScripts ?>
cssjs.push("<?= (IsRemote($file) ? "" : BasePath(true)) . $file ?>");
<?php } ?>
var jqueryjs = [
    ew.PATH_BASE + "adminlte3/js/adminlte.js",
    ew.PATH_BASE + "bootstrap4/js/bootstrap.bundle.min.js",
    ew.PATH_BASE + "plugins/select2/js/select2.full.min.js",
    ew.PATH_BASE + "<#= jQueryFolder #>/jqueryfileupload.min.js",
    ew.PATH_BASE + "<#= jQueryFolder #>/typeahead.jquery.min.js"
];
<# if (CheckPasswordStrength) { #>
jqueryjs.push(ew.PATH_BASE + "<#= jQueryFolder #>/pStrength.jquery.min.js");
<# } #>
<# if (GeneratePassword) { #>
jqueryjs.push(ew.PATH_BASE + "<#= jQueryFolder #>/pGenerator.jquery.min.js");
<# } #>
<# if (PROJ.UseColorbox) { #>
jqueryjs.push(ew.PATH_BASE + "colorbox/jquery.colorbox-min.js");
<# } #>
<# if (PROJ.EmbedPdfDocuments) { #>
jqueryjs.push(ew.PATH_BASE + "<#= jsFolder #>/pdfobject.min.js");
<# } #>
<# if (useModalLookup || DB.UseDynamicUserLevel) { #>
jqueryjs.push(ew.PATH_BASE + "<#= jQueryFolder #>/jquery.ewjtable.min.js");
<# } #>
ew.ready(["jquery", "widget", "scrollbars", "moment", "others"], [jqueryjs, ew.PATH_BASE + "<#= jsFolder #>/ew.min.js"], "makerjs");
ew.ready("makerjs", [
    cssjs,
    ew.PATH_BASE + "<#= jsFolder #>/<#= GetFileName("userglobaljs", "", false) #>",
    ew.PATH_BASE + "<#= jsFolder #>/<#= GetFileName("usereventjs", "", false) #>"
], "head");
</script>
<#= include('header.html') #>
<# if (ServerScriptExist("Global", "Page_Head")) { #>
<#= GetServerScript("Global", "Page_Head") #>
<# } #>
<!-- Navbar -->
<script type="text/html" id="navbar-menu-items" class="ew-js-template" data-name="navbar" data-seq="10" data-data="navbar" data-method="appendTo" data-target="#ew-navbar">
{{if items}}
    {{for items}}
        <li id="{{:id}}" name="{{:name}}" class="{{if parentId == -1}}nav-item ew-navbar-item{{/if}}{{if isHeader && parentId > -1}}dropdown-header{{/if}}{{if items}} dropdown{{/if}}{{if items && parentId != -1}} dropdown-submenu{{/if}}{{if items && level == 1}} dropdown-hover{{/if}} d-none d-md-block">
            {{if isHeader && parentId > -1}}
                {{if icon}}<i class="{{:icon}}"></i>{{/if}}
                <span>{{:text}}</span>
            {{else}}
            <a href="{{:href}}"{{if target}} target="{{:target}}"{{/if}}{{if attrs}}{{:attrs}}{{/if}} class="{{if parentId == -1}}nav-link{{else}}dropdown-item{{/if}}{{if active}} active{{/if}}{{if items}} dropdown-toggle ew-dropdown{{/if}}"{{if items}} role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"{{/if}}>
                {{if icon}}<i class="{{:icon}}"></i>{{/if}}
                <span>{{:text}}</span>
            </a>
            {{/if}}
            {{if items}}
            <ul class="dropdown-menu">
                {{include tmpl="#navbar-menu-items"/}}
            </ul>
            {{/if}}
        </li>
    {{/for}}
{{/if}}
</script>
<!-- Sidebar -->
<script type="text/html" class="ew-js-template" data-name="menu" data-seq="10" data-data="menu" data-target="#ew-menu">
{{if items}}
    <ul class="nav nav-pills nav-sidebar nav-child-indent flex-column" data-widget="treeview" role="menu" data-accordion="{{:accordion}}">
    {{include tmpl="#menu-items"/}}
    </ul>
{{/if}}
</script>
<script type="text/html" id="menu-items">
{{if items}}
    {{for items}}
        <li id="{{:id}}" name="{{:name}}" class="{{if isHeader}}nav-header{{else}}nav-item{{if items}} has-treeview{{/if}}{{if active}} active current{{/if}}{{if open}} menu-open{{/if}}{{/if}}{{if isNavbarItem}} d-block d-md-none{{/if}}">
            {{if isHeader}}
                {{if icon}}<i class="{{:icon}}"></i>{{/if}}
                <span>{{:text}}</span>
                {{if label}}
                <span class="right">
                    {{:label}}
                </span>
                {{/if}}
            {{else}}
            <a href="{{:href}}" class="nav-link{{if active}} active{{/if}}"{{if target}} target="{{:target}}"{{/if}}{{if attrs}}{{:attrs}}{{/if}}>
                {{if icon}}<i class="nav-icon {{:icon}}"></i>{{/if}}
                <p>{{:text}}
                    {{if items}}
                        <i class="right fas fa-angle-left"></i>
                        {{if label}}
                            <span class="right">
                                {{:label}}
                            </span>
                        {{/if}}
                    {{else}}
                        {{if label}}
                            <span class="right">
                                {{:label}}
                            </span>
                        {{/if}}
                    {{/if}}
                </p>
            </a>
            {{/if}}
            {{if items}}
            <ul class="nav nav-treeview"{{if open}} style="display: block;"{{/if}}>
                {{include tmpl="#menu-items"/}}
            </ul>
            {{/if}}
        </li>
    {{/for}}
{{/if}}
</script>
<script type="text/html" class="ew-js-template" data-name="languages" data-seq="10" data-data="languages" data-method="<#= Code.write(Code.languageProperty(Code.Language.Method)) #>" data-target="<#= Code.write(Code.htmlEncode(Code.languageProperty(Code.Language.Target))) #>">
<#= Code.raw(Code.languageProperty(Code.Language.GetTemplate)) #>
</script>
<# if (isSecurityEnabled) { #>
<script type="text/html" class="ew-js-template" data-name="login" data-seq="10" data-data="login" data-method="appendTo" data-target=".navbar-nav.ml-auto">
{{if isLoggedIn}}
<li class="nav-item dropdown text-body">
    <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="fas fa-user"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <div class="dropdown-item p-3"><i class="fas fa-user mr-2"></i>{{:currentUserName}}</div>
        {{if (hasPersonalData || canChangePassword)}}
        <div class="dropdown-divider"></div>
        <div class="text-nowrap p-3">
            {{if hasPersonalData}}
            <a class="btn btn-default" href="#" onclick="{{:personalDataUrl}}">{{:personalDataText}}</a>
            {{/if}}
            {{if canChangePassword}}
            <a class="btn btn-default" href="#" onclick="{{:changePasswordUrl}}">{{:changePasswordText}}</a>
            {{/if}}
        </div>
        {{/if}}
        {{if canLogout}}
        <div class="dropdown-divider"></div>
        <div class="dropdown-footer p-2 text-right">
            <a class="btn btn-default" href="#" onclick="{{:logoutUrl}}">{{:logoutText}}</a>
        </div>
        {{/if}}
    </div>
<li>
{{else}}
    {{if canLogin}}
<li class="nav-item"><a class="nav-link" href="#" onclick="{{:loginUrl}}">{{:loginText}}</a></li>
    {{/if}}
{{/if}}
</script>
<# } #>
<#= templateExportEnd #>
<#= FavIcon() #>
</head>
<body class="<#= Code.write(Code.Config.BodyClass) #>" dir="<#= Code.write('IsRTL() ? "rtl" : "ltr"') #>">
<#= templateSkipHeaderFooterStart #>
<#= templateExportStart #>
<# if (PROJ.UseCookiePolicy) { #>
<#= IncludeFile("cookieconsent") #>
<# } #>
<div class="wrapper ew-layout">
    <!-- Main Header -->

    <!-- Navbar -->
    <nav class="<#= Code.write(Code.Config.NavbarClass) #>">
        <!-- Left navbar links -->
        <ul id="ew-navbar" class="navbar-nav">
            <li class="nav-item d-block<# if (layoutTopNav || allTopMenuItems) { #> d-md-none<# } #>">
                <a class="nav-link" data-widget="pushmenu" data-enable-remember="true" href="#" onclick="return false;"><i class="fas fa-bars"></i></a>
            </li>
            <a class="navbar-brand d-none<# if (layoutTopNav || allTopMenuItems) { #> d-md-block<# } #>" href="<#= brandHref #>"<# if (brandHref == "#") { #>  onclick="return false;"<# } #>>
                <#= HeaderLogo() #>
            </a>
        </ul>
        <!-- Right navbar links -->
        <ul id="ew-navbar-right" class="navbar-nav ml-auto"></ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="<#= Code.write(Code.Config.SidebarClass) #>">
        <!-- Brand Logo //** Note: Only licensed users are allowed to change the logo ** -->
        <a href="<#= brandHref #>" class="brand-link<#= brandLogoClass #>">
            <#= HeaderLogo() #>
        </a>
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav id="ew-menu" class="mt-2"></nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
    <?php if (Config("PAGE_TITLE_STYLE") != "None") { ?>
            <div class="container-fluid">
                <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><#= Code.raw(Code.CurrentPageHeading) #> <small class="text-muted"><#= Code.raw(Code.CurrentPageSubheading) #></small></h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <?php Breadcrumb()->render() ?>
                </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
    <?php } ?>
        </div>
        <!-- /.content-header -->
        <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
<#= templateExportEnd #>
<#= templateSkipHeaderFooterEnd #>
<# } #>
<# if (ctrlId == "layout") { #>
<?= $content ?>
<# } #>
<# if (["layout", "footer"].includes(ctrlId)) { #>
<#= templateExportStart #>
<#= templateSkipHeaderFooterStart #>
<?php
if (isset($DebugTimer)) {
    $DebugTimer->stop();
}
?>
        </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <!-- ** Note: Only licensed users are allowed to change the copyright statement. ** -->
        <div class="ew-footer-text"><#= Code.raw(Code.languageProjectPhrase("FooterText")) #></div>
        <div class="float-right d-none d-sm-inline-block"></div>
    </footer>

    <# if (ServerScriptExist("Global", "Page_Foot")) { #>
    <#= GetServerScript("Global", "Page_Foot") #>
    <# } #>
</div>
<!-- ./wrapper -->
<#= templateSkipHeaderFooterEnd #>
<script>
loadjs.done("wrapper");
</script>
<!-- template upload (for file upload) -->
<script id="template-upload" type="text/html">
{{for files}}
    <tr class="template-upload">
        <td>
            <span class="preview"></span>
        </td>
        <td>
            <p class="name">{{:name}}</p>
            <p class="error"></p>
        </td>
        <td>
            <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar bg-success" style="width: 0%;"></div></div>
        </td>
        <td>
            {{if !#index && !~root.options.autoUpload}}
            <button class="btn btn-default btn-sm start" disabled><#= Code.raw(Code.languagePhrase("UploadStart")) #></button>
            {{/if}}
            {{if !#index}}
            <button class="btn btn-default btn-sm cancel"><#= Code.raw(Code.languagePhrase("UploadCancel")) #></button>
            {{/if}}
        </td>
    </tr>
{{/for}}
</script>
<!-- template download (for file upload) -->
<script id="template-download" type="text/html">
{{for files}}
    <tr class="template-download">
        <td>
            <span class="preview">
                {{if !exists}}
                <span class="error"><#= Code.raw(Code.languagePhrase("FileNotFound")) #></span>
                {{else url && extension == "pdf"}}
                <div class="ew-pdfobject" data-url="{{>url}}" style="width: <#= Code.write(Code.Config.UploadThumbnailWidth) #>px;"></div>
                {{else url && extension == "mp3"}}
                <audio controls><source type="audio/mpeg" src="{{>url}}"></audio>
                {{else url && extension == "mp4"}}
                <video controls><source type="video/mp4" src="{{>url}}"></video>
                {{else thumbnailUrl}}
                <a href="{{>url}}" title="{{>name}}" download="{{>name}}" class="ew-lightbox"><img class="ew-lazy" loading="lazy" src="{{>thumbnailUrl}}"></a>
                {{/if}}
            </span>
        </td>
        <td>
            <p class="name">
                {{if !exists}}
                <span class="text-muted">{{:name}}</span>
                {{else url && (extension == "pdf" || thumbnailUrl) && extension != "mp3" && extension != "mp4"}}
                <a href="{{>url}}" title="{{>name}}" target="_blank">{{:name}}</a>
                {{else url}}
                <a href="{{>url}}" title="{{>name}}" download="{{>name}}">{{:name}}</a>
                {{else}}
                <span>{{:name}}</span>
                {{/if}}
            </p>
            {{if error}}
            <div><span class="error">{{:error}}</span></div>
            {{/if}}
        </td>
        <td>
            <span class="size">{{:~root.formatFileSize(size)}}</span>
        </td>
        <td>
            {{if !~root.options.readonly && deleteUrl}}
            <button class="btn btn-default btn-sm delete" data-type="{{>deleteType}}" data-url="{{>deleteUrl}}"><#= Code.raw(Code.languagePhrase("UploadDelete")) #></button>
            {{else !~root.options.readonly}}
            <button class="btn btn-default btn-sm cancel"><#= Code.raw(Code.languagePhrase("UploadCancel")) #></button>
            {{/if}}
        </td>
    </tr>
{{/for}}
</script>
<!-- modal dialog -->
<div id="ew-modal-dialog" class="modal" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h4 class="modal-title"></h4></div><div class="modal-body"></div><div class="modal-footer"></div></div></div></div>
<# if (useModalLookup) { #>
<!-- modal lookup dialog -->
<div id="ew-modal-lookup-dialog" class="modal" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h4 class="modal-title"></h4><div class="modal-tools"><input type="search" name="sv" class="form-control" placeholder="<#= Code.write(Code.htmlEncode(Code.languagePhrase("Search"))) #>"></div></div><div class="modal-body p-0"></div><div class="modal-footer"></div></div></div></div>
<# } #>
<# if (UseAddOption()) { #>
<!-- add option dialog -->
<div id="ew-add-opt-dialog" class="modal" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h4 class="modal-title"></h4></div><div class="modal-body"></div><div class="modal-footer"><button type="button" class="btn btn-primary ew-btn"><#= Code.raw(Code.languagePhrase("AddBtn")) #></button><button type="button" class="btn btn-default ew-btn" data-dismiss="modal"><#= Code.raw(Code.languagePhrase("CancelBtn")) #></button></div></div></div></div>
<# } #>
<# if (useEmailExport) { #>
<!-- email dialog -->
<div id="ew-email-dialog" class="modal" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h4 class="modal-title"></h4></div>
<div class="modal-body">
<#= IncludeFile("email") #>
</div><div class="modal-footer"><button type="button" class="btn btn-primary ew-btn"><#= Code.raw(Code.languagePhrase("SendEmailBtn")) #></button><button type="button" class="btn btn-default ew-btn" data-dismiss="modal"><#= Code.raw(Code.languagePhrase("CancelBtn")) #></button></div></div></div></div>
<# } #>
<!-- import dialog -->
<div id="ew-import-dialog" class="modal" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h4 class="modal-title"></h4></div>
<div class="modal-body">
<div class="custom-file">
    <input type="file" class="custom-file-input" id="importfiles" title=" " name="importfiles[]" multiple lang="<#= Code.write('CurrentLanguageID()') #>">
    <label class="custom-file-label ew-file-label" for="importfiles"><#= Code.raw(Code.languagePhrase("ChooseFiles")) #></label>
</div>
<div class="message d-none mt-3"></div>
<div class="progress d-none mt-3"><div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-default ew-close-btn" data-dismiss="modal"><#= Code.raw(Code.languagePhrase("CloseBtn")) #></button></div></div></div></div>
<!-- tooltip -->
<div id="ew-tooltip"></div>
<!-- drill down -->
<div id="ew-drilldown-panel"></div>
<#= templateExportEnd #>
<#= templatePrintStart #>
<script>
loadjs.done("wrapper");
</script>
<#= templateExportEnd #>
<script>
loadjs.ready(ew.bundleIds, function() {
    if (!loadjs.isDefined("foot"))
        loadjs.done("foot");
});
</script>
</body>
</html>
<# } #>
