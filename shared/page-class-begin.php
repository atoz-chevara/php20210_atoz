<#
    global.isExtendPageClass = ["register", "reset_password", "change_password", "userpriv"].includes(ctrlId) ||
        ctrlType == "table" || ctrlType == "report" && !IsEmpty(TABLE.TblRptSrc) ||
        hasUserTable && ctrlId == "login";
#>
<?php
<# if (ctrlTagExt == "class" || ctrlId == "logout") { #>
namespace <#= ProjectNamespace #>;
<# } #>

use Doctrine\DBAL\ParameterType;

/**
 * Page class
 */

<# if (isExtendPageClass) { #>
class <#= pageObj #> extends <#= tblClassName #>
<# } else if (ctrlId == "dashboard") { #>
class <#= pageObj #> extends ReportTable
<# } else { #>
class <#= pageObj #>
<# } #>
{
    use MessagesTrait;

    // Page ID
    public $PageID = "<#= ctrlId #>";

    // Project ID
    public $ProjectID = PROJECT_ID;

<# if (["table", "report"].includes(ctrlType)) { #>
    // Table name
    public $TableName = '<#= SingleQuote(TABLE.TblName) #>';
<# } #>

    // Page object name
    public $PageObjName = "<#= pageObj #>";

    // Rendering View
    public $RenderingView = false;

<# if (["grid", "list"].includes(ctrlId)) { #>

    // Grid form hidden field names
    public $FormName = "<#= formName #>";
    public $FormActionName = "k_action";
    public $FormBlankRowName = "k_blankrow";
    public $FormKeyCountName = "key_count";

<# } #>

<# if (ctrlType == "report") { #>
    // CSS
    public $ReportTableClass = "";
    public $ReportTableStyle = "";
<# } #>

<# if (ctrlType == "table" && ["list", "grid", "view"].includes(ctrlId) || ["summary", "crosstab"].includes(ctrlId)) { #>
    // Page URLs
    public $AddUrl;
    public $EditUrl;
    public $CopyUrl;
    public $DeleteUrl;
    public $ViewUrl;
    public $ListUrl;

    <# if (ctrlId != "grid") { #>
    // Export URLs
    public $ExportPrintUrl;
    public $ExportHtmlUrl;
    public $ExportExcelUrl;
    public $ExportWordUrl;
    public $ExportXmlUrl;
    public $ExportCsvUrl;
    public $ExportPdfUrl;

    // Custom export
    public $ExportExcelCustom = <#= Code.bool(UseCustomTemplate) #>;
    public $ExportWordCustom = <#= Code.bool(UseCustomTemplate) #>;
    public $ExportPdfCustom = <#= Code.bool(UseCustomTemplate) #>;
    public $ExportEmailCustom = <#= Code.bool(UseCustomTemplate) #>;

    // Update URLs
    public $InlineAddUrl;
    public $InlineCopyUrl;
    public $InlineEditUrl;
    public $GridAddUrl;
    public $GridEditUrl;
    public $MultiDeleteUrl;
    public $MultiUpdateUrl;
    <# } #>
<# } #>

<# if (ctrlType == "table" && TABLE.TblAuditTrail) { #>
    // Audit Trail
    public $AuditTrailOnAdd = <#= Code.bool(auditTrailOnAdd) #>;
    public $AuditTrailOnEdit = <#= Code.bool(auditTrailOnEdit) #>;
    public $AuditTrailOnDelete = <#= Code.bool(auditTrailOnDelete) #>;
    public $AuditTrailOnView = <#= Code.bool(auditTrailOnView) #>;
    public $AuditTrailOnViewData = <#= Code.bool(auditTrailOnViewData) #>;
    public $AuditTrailOnSearch = <#= Code.bool(auditTrailOnSearch) #>;
<# } #>

    // Page headings
    public $Heading = "";
    public $Subheading = "";
<# if (ctrlType == "table" || ctrlType == "report" && ["summary", "crosstab", "dashboard"].includes(ctrlId) || ["other", "simple"].includes(ctrlType) && ctrlId != "logout") { #>
    public $PageHeader;
    public $PageFooter;
<# } #>

    // Page terminated
    private $terminated = false;

    // Page heading
    public function pageHeading()
    {
        global $Language;
        if ($this->Heading != "") {
            return $this->Heading;
        }
        if (method_exists($this, "tableCaption")) {
            return $this->tableCaption();
        }
        return "";
    }

    // Page subheading
    public function pageSubheading()
    {
        global $Language;
        if ($this->Subheading != "") {
            return $this->Subheading;
        }
        <# if (ctrlType == "table") { #>
        if ($this->TableName) {
            return $Language->phrase($this->PageID);
        }
        <# } #>
        return "";
    }

    // Page name
    public function pageName()
    {
        return CurrentPageName();
    }

    // Page URL
    public function pageUrl()
    {
        $url = ScriptName() . "?";
        <# if (ctrlType == "table") { #>
        if ($this->UseTokenInUrl) {
            $url .= "t=" . $this->TableVar . "&"; // Add page token
        }
        <# } #>
        return $url;
    }

<# if (ctrlType == "table" || ctrlType == "report" && ["summary", "crosstab", "dashboard"].includes(ctrlId) || ["other", "simple"].includes(ctrlType) && !["logout", "privacy"].includes(ctrlId)) { #>
    // Show Page Header
    public function showPageHeader()
    {
        $header = $this->PageHeader;
        <# if (ServerScriptExist(eventCtrlType, "Page_DataRendering")) { #>
        $this->pageDataRendering($header);
        <# } #>
        if ($header != "") { // Header exists, display
            echo '<p id="ew-page-header">' . $header . '</p>';
        }
    }

    // Show Page Footer
    public function showPageFooter()
    {
        $footer = $this->PageFooter;
        <# if (ServerScriptExist(eventCtrlType, "Page_DataRendered")) { #>
        $this->pageDataRendered($footer);
        <# } #>
        if ($footer != "") { // Footer exists, display
            echo '<p id="ew-page-footer">' . $footer . '</p>';
        }
    }

    // Validate page request
    protected function isPageRequest()
    {
        <# if (ctrlType == "table") { #>
        global $CurrentForm;
        if ($this->UseTokenInUrl) {
            if ($CurrentForm) {
                return ($this->TableVar == $CurrentForm->getValue("t"));
            }
            if (Get("t") !== null) {
                return ($this->TableVar == Get("t"));
            }
        }
        return true;
        <# } else { #>
        return true;
        <# } #>
    }

<# } #>

    // Constructor
    public function __construct()
    {
        global $Language, $DashboardReport, $DebugTimer;
        <# if (hasUserTable) { #>
        global $UserTable;
        <# } #>

	<# if (UseCustomTemplate) { #>
        // Custom template
        $this->UseCustomTemplate = <#= Code.bool(UseCustomTemplate) #>;
	<# } #>

        // Initialize
        <# if (ctrlId == "grid") { #>
        $this->FormActionName .= "_" . $this->FormName;
        $this->OldKeyName .= "_" . $this->FormName;
        $this->FormBlankRowName .= "_" . $this->FormName;
        $this->FormKeyCountName .= "_" . $this->FormName;
        $GLOBALS["Grid"] = &$this;
        <# } else if (ctrlId == "dashboard") { #>
        $this->TableVar = '<#= tblVar #>';
        $this->TableName = '<#= SingleQuote(TABLE.TblName) #>';
        $this->TableType = '<#= TABLE.TblType #>';
        $this->TableReportType = '<#= TABLE.TblReportType #>';

        // Set running dashboard report
        $DashboardReport = true;
        $GLOBALS["Page"] = &$this;
        <# } else { #>
        $GLOBALS["Page"] = &$this;
        <# } #>

        // Language object
        $Language = Container("language");

<# if (isExtendPageClass) { #>
        // Parent constuctor
        parent::__construct();

    <# if (ctrlId != "custom") { #>

        // Table object (<#= tblVar #>)
        if (!isset($GLOBALS["<#= tblVar #>"]) || get_class($GLOBALS["<#= tblVar #>"]) == PROJECT_NAMESPACE . "<#= tblVar #>") {
            $GLOBALS["<#= tblVar #>"] = &$this;
        }

    <# } #>

<# } #>

<# if (ctrlType == "table" || ["summary", "crosstab", "dashboard"].includes(ctrlId) || ctrlType == "field") { #>

        // Page URL
        $pageUrl = $this->pageUrl();

    <# if (["list", "summary", "crosstab"].includes(ctrlId)) { #>

        // Initialize URLs
        $this->ExportPrintUrl = $pageUrl . "export=print";
        $this->ExportExcelUrl = $pageUrl . "export=excel";
        $this->ExportWordUrl = $pageUrl . "export=word";
        $this->ExportPdfUrl = $pageUrl . "export=pdf";
        <# if (ctrlId == "list") { #>
        $this->ExportHtmlUrl = $pageUrl . "export=html";
        $this->ExportXmlUrl = $pageUrl . "export=xml";
        $this->ExportCsvUrl = $pageUrl . "export=csv";
            <# if (isDetailAdd && detailTables.length > 0) { #>
        $this->AddUrl = "<#= addPage #>?" . Config("TABLE_SHOW_DETAIL") . "=";
            <# } else { #>
        $this->AddUrl = "<#= addPage #>";
            <# } #>
        $this->InlineAddUrl = $pageUrl . "action=add";
        $this->GridAddUrl = $pageUrl . "action=gridadd";
        $this->GridEditUrl = $pageUrl . "action=gridedit";
        $this->MultiDeleteUrl = "<#= deletePage #>";
        $this->MultiUpdateUrl = "<#= updatePage #>";
        <# } #>

    <# } else if (ctrlId == "view") { #>

        <#
        for (let f of keyFields) {
            let fldParm = f.FldParm;
        #>
        if (($keyValue = Get("<#= fldParm #>") ?? Route("<#= fldParm #>")) !== null) {
            $this->RecKey["<#= fldParm #>"] = $keyValue;
        }
        <#
            }
        #>
        $this->ExportPrintUrl = $pageUrl . "export=print";
        $this->ExportHtmlUrl = $pageUrl . "export=html";
        $this->ExportExcelUrl = $pageUrl . "export=excel";
        $this->ExportWordUrl = $pageUrl . "export=word";
        $this->ExportXmlUrl = $pageUrl . "export=xml";
        $this->ExportCsvUrl = $pageUrl . "export=csv";
        $this->ExportPdfUrl = $pageUrl . "export=pdf";

    <# } else if (ctrlId == "grid") { #>

        $this->AddUrl = "<#= addPage #>";

    <# } #>

<# } #>

        <# if (["table", "report", "field"].includes(ctrlType)) { #>
        // Table name (for backward compatibility only)
        if (!defined(PROJECT_NAMESPACE . "TABLE_NAME")) {
            define(PROJECT_NAMESPACE . "TABLE_NAME", '<#= SingleQuote(TABLE.TblName) #>');
        }
        <# } #>

        // Start timer
        $DebugTimer = Container("timer");

        // Debug message
        LoadDebugMessage();

        // Open connection
        <# if (isExtendPageClass) { #>
        $GLOBALS["Conn"] = $GLOBALS["Conn"] ?? $this->getConnection();
        <# } else { #>
        $GLOBALS["Conn"] = $GLOBALS["Conn"] ?? GetConnection();
        <# } #>

        <# if (hasUserTable) { #>
        // User table object
        $UserTable = Container("usertable");
        <# } #>

        <# if (["list", "grid", "preview"].includes(ctrlId)) { #>
        // List options
        $this->ListOptions = new ListOptions();
        $this->ListOptions->TableVar = $this->TableVar;
        <# } #>

        <# if (["list", "view", "summary", "crosstab"].includes(ctrlId)) { #>
        // Export options
        $this->ExportOptions = new ListOptions("div");
        $this->ExportOptions->TagClassName = "ew-export-option";
        <# } #>

        <# if (ctrlId == "list") { #>
        // Import options
        $this->ImportOptions = new ListOptions("div");
        $this->ImportOptions->TagClassName = "ew-import-option";
        <# } #>

    <# if (["list", "grid", "view", "preview"].includes(ctrlId)) { #>
        // Other options
        if (!$this->OtherOptions) {
            $this->OtherOptions = new ListOptionsArray();
        }
        <# if (["list", "grid", "preview"].includes(ctrlId)) { #>
        $this->OtherOptions["addedit"] = new ListOptions("div");
        $this->OtherOptions["addedit"]->TagClassName = "ew-add-edit-option";
        <# } #>
        <# if (ctrlId == "list") { #>
        $this->OtherOptions["detail"] = new ListOptions("div");
        $this->OtherOptions["detail"]->TagClassName = "ew-detail-option";
        $this->OtherOptions["action"] = new ListOptions("div");
        $this->OtherOptions["action"]->TagClassName = "ew-action-option";
        <# } else if (ctrlId == "view") { #>
        $this->OtherOptions["action"] = new ListOptions("div");
        $this->OtherOptions["action"]->TagClassName = "ew-action-option";
        $this->OtherOptions["detail"] = new ListOptions("div");
        $this->OtherOptions["detail"]->TagClassName = "ew-detail-option";
        <# } #>
     <# } #>

        <# if (["list", "summary", "crosstab"].includes(ctrlId)) { #>
        // Filter options
        $this->FilterOptions = new ListOptions("div");
        $this->FilterOptions->TagClassName = "ew-filter-option <#= formNameSearch #>";
        <# } #>

        <# if (ctrlId == "list") { #>
        // List actions
        $this->ListActions = new ListActions();
        <# } #>
    }

    // Get content from stream
    public function getContents($stream = null): string
    {
        global $Response;
        return is_object($Response) ? $Response->getBody() : ob_get_clean();
    }

    // Is lookup
    public function isLookup()
    {
        return SameText(Route(0), Config("API_LOOKUP_ACTION"));
    }

    // Is AutoFill
    public function isAutoFill()
    {
        return $this->isLookup() && SameText(Post("ajax"), "autofill");
    }

    // Is AutoSuggest
    public function isAutoSuggest()
    {
        return $this->isLookup() && SameText(Post("ajax"), "autosuggest");
    }

    // Is modal lookup
    public function isModalLookup()
    {
        return $this->isLookup() && SameText(Post("ajax"), "modal");
    }

    // Is terminated
    public function isTerminated()
    {
        return $this->terminated;
    }

    /**
     * Terminate page
     *
     * @param string $url URL for direction
     * @return void
     */
    public function terminate($url = "")
    {
<# if (ctrlId == "error") { #>

        // Close connection
        CloseConnections();

        // Return
        return;

<# } else { #>
        if ($this->terminated) {
            return;
        }

        global $ExportFileName, $TempImages, $DashboardReport, $Response;

        // Page is terminated
        $this->terminated = true;

        <# if (CONTROL.CtrlSkipHeaderFooter) { #>
        global $OldSkipHeaderFooter, $SkipHeaderFooter;
        $SkipHeaderFooter = $OldSkipHeaderFooter;
        <# } #>

    <# if (ctrlId != "grid") { #>

        <# if (UseCustomTemplate) { #>
        if (Post("customexport") === null) {
        <# } #>

        <#
            if (!["privacy", "personal_data"].includes(ctrlId)) {
                let pageUnload = "";
                if (ServerScriptExist(eventCtrlType, "Page_Unload")) {
                    pageUnload = ` // Page Unload event
if (method_exists($this, "pageUnload")) {
    $this->pageUnload();
}`;
                }
                if (pageUnload) {
        #>
        <# if (UseCustomTemplate) { #>
            <#= pageUnload #>
        <# } else { #>
        <#= pageUnload #>
        <# } #>
        <#
                }
            }
        #>

        <#
            let pageUnloaded = "";
            if (ServerScriptExist("Global", "Page_Unloaded")) {
                pageUnloaded = `// Global Page Unloaded event (in userfn*.php)
Page_Unloaded();
`;
            }
            if (pageUnloaded) {
        #>
        <# if (UseCustomTemplate) { #>
            <#= pageUnloaded #>
        <# } else { #>
        <#= pageUnloaded #>
        <# } #>
        <#
            }
        #>

        <# if (UseCustomTemplate) { #>
        }
        <# } #>

    <# } #>

        // Export
    <# if (ctrlType == "table") { #>
        if ($this->CustomExport && $this->CustomExport == $this->Export && array_key_exists($this->CustomExport, Config("EXPORT_CLASSES"))) {
        <# if (UseCustomTemplate) { #>
            if (is_array(Session(SESSION_TEMP_IMAGES))) { // Restore temp images
                $TempImages = Session(SESSION_TEMP_IMAGES);
            }
            if (Post("data") !== null) {
                $content = Post("data");
            }
            $ExportFileName = Post("filename", "");
        <# } else { #>
            $content = $this->getContents();
        <# } #>
            if ($ExportFileName == "") {
                $ExportFileName = $this->TableVar;
            }
            $class = PROJECT_NAMESPACE . Config("EXPORT_CLASSES." . $this->CustomExport);
            if (class_exists($class)) {
                $doc = new $class(Container("<#= tblVar #>"));
                $doc->Text = @$content;
                if ($this->isExport("email")) {
                    echo $this->exportEmail($doc->Text);
                } else {
                    $doc->export();
                }
                DeleteTempImages(); // Delete temp images
                return;
            }
        }
    <# } else if (["summary", "crosstab"].includes(ctrlId)) { #>
        if ($this->isExport() && !$this->isExport("print")) {
            $class = PROJECT_NAMESPACE . Config("REPORT_EXPORT_CLASSES." . $this->Export);
            if (class_exists($class)) {
                <# if (UseCustomTemplate) { #>
                if (Post("data") !== null) {
                    $content = Post("data");
                } else {
                    $content = $this->getContents();
                }
                <# } else { #>
                $content = $this->getContents();
                <# } #>
                $doc = new $class();
                $doc($this, $content);
            }
        }
    <# } #>

    <# if (ctrlType == "table" && UseCustomTemplate) { #>

        if ($this->CustomExport) { // Save temp images array for custom export
            if (is_array($TempImages)) {
                $_SESSION[SESSION_TEMP_IMAGES] = $TempImages;
            }
        }

    <# } #>

    <# if (ctrlId == "grid" && masterTables.length > 0) { #>
        unset($GLOBALS["Grid"]);
        if ($url === "") {
            return;
        }
    <# } #>

    <# if (!["custom", "privacy", "personal_data"].includes(ctrlId) && ServerScriptExist(eventCtrlType, "Page_Redirecting")) { #>
        if (!IsApi() && method_exists($this, "pageRedirecting")) {
            $this->pageRedirecting($url);
        }
    <# } #>

    <# if (ctrlId != "grid") { #>
        <# if (ctrlType == "report" && !["custom", "dashboard"].includes(ctrlId)) { #>
        // Close connection if not in dashboard
        if (!$DashboardReport) {
            CloseConnections();
        }
        <# } else { #>
        // Close connection
        CloseConnections();
        <# } #>
    <# } #>

        // Return for API
        if (IsApi()) {
            $res = $url === true;
            if (!$res) { // Show error
                WriteJson(array_merge(["success" => false], $this->getMessages()));
            }
            return;
        } else { // Check if response is JSON
            if (StartsString("application/json", $Response->getHeaderLine("Content-type")) && $Response->getBody()->getSize()) { // With JSON response
                $this->clearMessages();
                return;
            }
        }

        // Go to URL if specified
        if ($url != "") {
            if (!Config("DEBUG") && ob_get_length()) {
                ob_end_clean();
            }
            <# if (["add", "edit", "update", "view", "search"].includes(ctrlId)) { #>
            // Handle modal response
            if ($this->IsModal) { // Show as modal
                $row = ["url" => GetUrl($url), "modal" => "1"];
                $pageName = GetPageName($url);
                if ($pageName != $this->getListUrl()) { // Not List page
                    $row["caption"] = $this->getModalCaption($pageName);
                    if ($pageName == "<#= viewPage #>") {
                        $row["view"] = "1";
                    }
                } else { // List page should not be shown as modal => error
                    $row["error"] = $this->getFailureMessage();
                    $this->clearFailureMessage();
                }
                WriteJson($row);
            } else {
                SaveDebugMessage();
                Redirect(GetUrl($url));
            }
            <# } else if (["login", "change_password", "reset_password", "register"].includes(ctrlId)) { #>
            // Handle modal response
            if ($this->IsModal) { // Show as modal
                $row = ["url" => $url];
                WriteJson($row);
            } else {
                SaveDebugMessage();
                Redirect(GetUrl($url));
            }
            <# } else { #>
            SaveDebugMessage();
            Redirect(GetUrl($url));
            <# } #>
        }

        return; // Return to controller

<# } #>

    }

<# if (ctrlType == "table" || ctrlId == "register") { #>

    // Get records from recordset
    protected function getRecordsFromRecordset($rs, $current = false)
    {
        $rows = [];
        if (is_object($rs)) { // Recordset
            while ($rs && !$rs->EOF) {
                $this->loadRowValues($rs); // Set up DbValue/CurrentValue
	<#
	let allFileFields = allFields.filter(f => f.FldHtmlTag == "FILE"); // All upload fields
	for (let f of allFileFields) {
		if (!IsBinaryField(f)) {
			let fldParm = f.FldParm;
	#>
	<# if (!IsEmpty(f.FldUploadPath)) { #>
		        $this-><#= fldParm #>->OldUploadPath = <#= f.FldUploadPath #>;
		        $this-><#= fldParm #>->UploadPath = $this-><#= fldParm #>->OldUploadPath;
	<# } #>
	<#
		}
	} // Field
	#>
                $row = $this->getRecordFromArray($rs->fields);
                if ($current) {
                    return $row;
                } else {
                    $rows[] = $row;
                }
                $rs->moveNext();
            }
        } elseif (is_array($rs)) {
            foreach ($rs as $ar) {
                $row = $this->getRecordFromArray($ar);
                if ($current) {
                    return $row;
                } else {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    // Get record from array
    protected function getRecordFromArray($ar)
    {
        $row = [];
        if (is_array($ar)) {
            foreach ($ar as $fldname => $val) {
                if (array_key_exists($fldname, $this->Fields) && ($this->Fields[$fldname]->Visible || $this->Fields[$fldname]->IsPrimaryKey)) { // Primary key or Visible
                    $fld = &$this->Fields[$fldname];
                    if ($fld->HtmlTag == "FILE") { // Upload field
                        if (EmptyValue($val)) {
                            $row[$fldname] = null;
                        } else {
                            if ($fld->DataType == DATATYPE_BLOB) {
                                $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                    "/" . $fld->TableVar . "/" . $fld->Param . "/" . rawurlencode($this->getRecordKeyValue($ar))));
                                $row[$fldname] = ["type" => ContentType($val), "url" => $url, "name" => $fld->Param . ContentExtension($val)];
                            } elseif (!$fld->UploadMultiple || !ContainsString($val, Config("MULTIPLE_UPLOAD_SEPARATOR"))) { // Single file
                                $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                    "/" . $fld->TableVar . "/" . Encrypt($fld->physicalUploadPath() . $val)));
                                $row[$fldname] = ["type" => MimeContentType($val), "url" => $url, "name" => $val];
                            } else { // Multiple files
                                $files = explode(Config("MULTIPLE_UPLOAD_SEPARATOR"), $val);
                                $ar = [];
                                foreach ($files as $file) {
                                    $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                        "/" . $fld->TableVar . "/" . Encrypt($fld->physicalUploadPath() . $file)));
                                    if (!EmptyValue($file)) {
                                        $ar[] = ["type" => MimeContentType($file), "url" => $url, "name" => $file];
                                    }
                                }
                                $row[$fldname] = $ar;
                            }
                        }
                    } else {
                        <# if (ctrlId == "list") { #>
                        if ($fld->DataType == DATATYPE_MEMO && $fld->MemoMaxLength > 0) {
                            $val = TruncateMemo($val, $fld->MemoMaxLength, $fld->TruncateMemoRemoveHtml);
                        }
                        <# } #>
                        $row[$fldname] = $val;
                    }
                }
            }
        }
        return $row;
    }

    // Get record key value from array
    protected function getRecordKeyValue($ar)
    {
        $key = "";
        if (is_array($ar)) {
    <#
    keyFields.forEach((f, i, ar) => {
        let concat = (i < ar.length - 1) ? ' . Config("COMPOSITE_KEY_SEPARATOR")' : '',
            fldName = f.FldName;
    #>
            $key .= @$ar['<#= SingleQuote(fldName) #>']<#= concat #>;
    <#
        }); // KeyField
    #>
        }
        return $key;
    }

    /**
     * Hide fields for add/edit
     *
     * @return void
     */
    protected function hideFieldsForAddEdit()
    {
    <#
        // Hide non-updatable fields for add/edit
        for (let f of allFields) {
            if (!UseCustomTemplate) { // Non custom template
                let fldParm = f.FldParm;
                if (f.FldIsPrimaryKey && f.FldAutoIncrement) {
    #>
        if ($this->isAdd() || $this->isCopy() || $this->isGridAdd()) {
            $this-><#= fldParm #>->Visible = false;
        }
    <#
            } else if (!IsFieldUpdatable(f) || !IsEmpty(f.FldAutoUpdateValue) && ["list", "grid"].includes(ctrlId)) {
    #>
        if ($this->isAddOrEdit()) {
            $this-><#= fldParm #>->Visible = false;
        }
	<#
                }
            }
        } // Field
	#>
    }

<# } #>

<# if (["list", "grid", "add", "addopt", "register", "edit", "update", "search", "view", "summary", "crosstab"].includes(ctrlId)) { #>

    // Lookup data
    public function lookup()
    {
        global $Language, $Security;

        // Get lookup object
        $fieldName = Post("field");
        $lookup = $this->Fields[$fieldName]->Lookup;

        <# if (TABLE && TABLE.TblType == "REPORT") { // Lookup for report #>
        if (in_array($lookup->LinkTable, [$this->ReportSourceTable, $this->TableVar])) {
            $lookup->RenderViewFunc = "renderLookup"; // Set up view renderer
        }
        $lookup->RenderEditFunc = ""; // Set up edit renderer
        <# } #>

        // Get lookup parameters
        $lookupType = Post("ajax", "unknown");
        $pageSize = -1;
        $offset = -1;
        $searchValue = "";
        if (SameText($lookupType, "modal")) {
            $searchValue = Post("sv", "");
            $pageSize = Post("recperpage", 10);
            $offset = Post("start", 0);
        } elseif (SameText($lookupType, "autosuggest")) {
            $searchValue = Param("q", "");
            $pageSize = Param("n", -1);
            $pageSize = is_numeric($pageSize) ? (int)$pageSize : -1;
            if ($pageSize <= 0) {
                $pageSize = Config("AUTO_SUGGEST_MAX_ENTRIES");
            }
            $start = Param("start", -1);
            $start = is_numeric($start) ? (int)$start : -1;
            $page = Param("page", -1);
            $page = is_numeric($page) ? (int)$page : -1;
            $offset = $start >= 0 ? $start : ($page > 0 && $pageSize > 0 ? ($page - 1) * $pageSize : 0);
        }
        $userSelect = Decrypt(Post("s", ""));
        $userFilter = Decrypt(Post("f", ""));
        $userOrderBy = Decrypt(Post("o", ""));
        $keys = Post("keys");

        $lookup->LookupType = $lookupType; // Lookup type
        if ($keys !== null) { // Selected records from modal
            if (is_array($keys)) {
                $keys = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $keys);
            }
            $lookup->FilterFields = []; // Skip parent fields if any
            $lookup->FilterValues[] = $keys; // Lookup values
            $pageSize = -1; // Show all records
        } else { // Lookup values
            $lookup->FilterValues[] = Post("v0", Post("lookupValue", ""));
        }
        $cnt = is_array($lookup->FilterFields) ? count($lookup->FilterFields) : 0;
        for ($i = 1; $i <= $cnt; $i++) {
            $lookup->FilterValues[] = Post("v" . $i, "");
        }
        $lookup->SearchValue = $searchValue;
        $lookup->PageSize = $pageSize;
        $lookup->Offset = $offset;
        if ($userSelect != "") {
            $lookup->UserSelect = $userSelect;
        }
        if ($userFilter != "") {
            $lookup->UserFilter = $userFilter;
        }
        if ($userOrderBy != "") {
            $lookup->UserOrderBy = $userOrderBy;
        }
        $lookup->toJson($this); // Use settings from current page
    }

<# } #>
