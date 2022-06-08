<script>
var currentForm, currentPageID;
<# if (!["search", "summary", "crosstab"].includes(ctrlId)) { #>
var <#= formName #>;
loadjs.ready("head", function () {
    var $ = jQuery;
    // Form object
    <# if (ctrlId == "grid") { #>
    <#= formName #> = new ew.Form("<#= formName #>", "<#= ctrlId #>");
    <# } else { #>
    currentPageID = ew.PAGE_ID = "<#= ctrlId #>";
    <#= formName #> = currentForm = new ew.Form("<#= formName #>", "<#= ctrlId #>");
    <# } #>

    <# if (["list", "grid"].includes(ctrlId)) { #>
    <#= formName #>.formKeyCountName = '<#= Code.write(formKeyCountName) #>';
    <# } #>

    <#
        if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "edit", "update", "register", "addopt", "search"].includes(ctrlId)) {
    #>
    // Add fields
    var currentTable = <#= Code.write(Code.toJson(`GetClientVar("tables", "${tblVar}")`)) #>,
        fields = currentTable.fields;
    if (!ew.vars.tables.<#= tblVar #>)
        ew.vars.tables.<#= tblVar #> = currentTable;
    <#= formName #>.addFields([
    <#
        currentFields.forEach((f, i, ar) => {
            let validator = IsValidateText(f) ? ValidatorFn(f) : "",
                requiredValidator = (f.FldHtmlTag == "FILE") ? "fileRequired" : "required";
            validator = validator ? ", " + validator : "";
            requiredValidator = requiredValidator ? `ew.Validators.${requiredValidator}(fields.${f.FldParm}.caption)` : "";
    #>
        <# if (ctrlId == "register" && f.FldName == PROJ.SecPasswdFld) { #>
        ["c_<#= f.FldParm #>", [ew.Validators.required(ew.language.phrase("ConfirmPassword")), ew.Validators.mismatchPassword], fields.<#= f.FldParm #>.isInvalid],
        <# } #>
        ["<#= f.FldParm #>", [fields.<#= f.FldParm #>.visible && fields.<#= f.FldParm #>.required ? <#= requiredValidator #> : null<#= validator #>], fields.<#= f.FldParm #>.isInvalid]<# if (i < ar.length - 1) { #>,<# } #>
    <#
        });
    #>
    ]);

    <# if (ctrlId == "add" && TABLE.TblAddCaptcha ||
        ctrlId == "edit" && TABLE.TblEditCaptcha ||
        ctrlId ==  "register" && PROJ.SecRegisterCaptcha) { #>
    <?= Captcha()->getScript("<#= formName #>") ?>
    <# } #>

    // Set invalid fields
    $(function() {
        var f = <#= formName #>,
            fobj = f.getForm(),
            $fobj = $(fobj),
            $k = $fobj.find("#" + f.formKeyCountName), // Get key_count
            rowcnt = ($k[0]) ? parseInt($k.val(), 10) : 1,
            startcnt = (rowcnt == 0) ? 0 : 1; // Check rowcnt == 0 => Inline-Add
        for (var i = startcnt; i <= rowcnt; i++) {
            var rowIndex = ($k[0]) ? String(i) : "";
            f.setInvalid(rowIndex);
        }
    });

    // Validate form
    <#= formName #>.validate = function () {
        if (!this.validateRequired)
            return true; // Ignore validation

        var fobj = this.getForm(),
            $fobj = $(fobj);

        if ($fobj.find("#confirm").val() == "confirm")
            return true;

        <# if (ctrlId == "update") { #>
        if (!ew.updateSelected(fobj)) {
            ew.alert(ew.language.phrase("NoFieldSelected"));
            return false;
        }
        <# } #>

        var addcnt = 0,
            $k = $fobj.find("#" + this.formKeyCountName), // Get key_count
            rowcnt = ($k[0]) ? parseInt($k.val(), 10) : 1,
            startcnt = (rowcnt == 0) ? 0 : 1, // Check rowcnt == 0 => Inline-Add
            gridinsert = ["insert", "gridinsert"].includes($fobj.find("#action").val()) && $k[0];

        for (var i = startcnt; i <= rowcnt; i++) {
            var rowIndex = ($k[0]) ? String(i) : "";
            $fobj.data("rowindex", rowIndex);
        <#
            if (ctrlId == "list" && gridAdd || ctrlId == "grid") {
        #>
            var checkrow = (gridinsert) ? !this.emptyRow(rowIndex) : true;
            if (checkrow) {
                addcnt++;
        <#
            }
        #>

            // Validate fields
            if (!this.validateFields(rowIndex))
                return false;
        <#

            if (ClientScriptExist(eventCtrlType, "Form_CustomValidate")) {
        #>
            // Call Form_CustomValidate event
            if (!this.customValidate(fobj)) {
                this.focus();
                return false;
            }
        <#
            }

            if (ctrlId == "list" && gridAdd || ctrlId == "grid") {
        #>
            } // End Grid Add checking
        <#
            }
        #>
        }

        <#
            if (ctrlId == "list" && gridAdd) {
        #>
        if (gridinsert && addcnt == 0) { // No row added
            ew.alert(ew.language.phrase("NoAddRecord"));
            return false;
        }
        <#
            }
        #>

        <#
            if (["add", "edit"].includes(ctrlId)) {
        #>
        // Process detail forms
        var dfs = $fobj.find("input[name='detailpage']").get();
        for (var i = 0; i < dfs.length; i++) {
            var df = dfs[i],
                val = df.value,
                frm = ew.forms.get(val);
            if (val && frm && !frm.validate())
                return false;
        }
        <#
            }
        #>

        return true;
    }

    <#
        }
    #>

    <#
        if (ctrlId == "list" && gridAdd || ctrlId == "grid") {
    #>
    // Check empty row
    <#= formName #>.emptyRow = function (rowIndex) {
        var fobj = this.getForm();
        <#
            for (let f of currentFields) {
                if (!f.FldAutoIncrement && IsEmpty(f.FldAutoUpdateValue)) {
        #>
        if (ew.valueChanged(fobj, rowIndex, "<#= AddSquareBrackets(f.FldParm, f) #>", <#= JsBool(IsBooleanField(TABLE, f)) #>))
            return false;
        <#
                }
            }
        #>
        return true;
    }
    <#
        }
    #>

    <#
        if (ctrlId == "list" && listAddOrEdit || ["grid", "add", "edit", "update", "register", "addopt", "search"].includes(ctrlId)) {
    #>

    <# if (ClientScriptExist(eventCtrlType, "Form_CustomValidate")) { #>
    // Form_CustomValidate
    <#= formName #>.customValidate = <#= GetClientScript(eventCtrlType, "Form_CustomValidate").replace(/(\r\n|\r|\n)/g, "$1    ") #>
    <# } #>

    // Use JavaScript validation or not
    <#= formName #>.validateRequired = <#= Code.write(Code.jsBool(Code.Config.ClientValidate)) #>;

    <#
        if (useMultiPage) {
    #>
    // Multi-Page
    <#= formName #>.multiPage = new ew.MultiPage("<#= formName #>");
    <#
        }
    #>

    // Dynamic selection lists
    <#
        for (let f of currentFields) {
            if (IsLookupField(f) && !(IsFieldEdit(f) && f.FldHtmlTagReadOnly)) { // Lookup and not read only
                let fldParm = f.FldParm;
    #>
    <#= formName #>.lists.<#= fldParm #> = <?= $<#= pageObj #>-><#= fldParm #>->toClientList($<#= pageObj #>) ?>;
    <#
            }
        }
    #>

    <#
        }
    #>

    loadjs.done("<#= formName #>");
});
<# } #>

<# if ((useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) && ctrlId == "list" || ctrlId == "search" || useExtendedBasicSearch && ["summary", "crosstab"].includes(ctrlId)) { #>
var <#= formNameSearch #>, currentSearchForm, currentAdvancedSearchForm;
loadjs.ready("head", function () {
    var $ = jQuery;
    // Form object for search
    <# if (ctrlId == "search") { #>
    <?php if (<#= isModal #>) { ?>
    <#= formNameSearch #> = currentAdvancedSearchForm = new ew.Form("<#= formName #>", "<#= ctrlId #>");
    <?php } else { ?>
    <#= formNameSearch #> = currentForm = new ew.Form("<#= formName #>", "<#= ctrlId #>");
    <?php } ?>
    currentPageID = ew.PAGE_ID = "<#= ctrlId #>";
    <# } else if (ctrlId == "list") { #>
    <#= formNameSearch #> = currentSearchForm = new ew.Form("<#= formNameSearch #>");
    <# } else { #>
    <#= formNameSearch #> = currentForm = new ew.Form("<#= formName #>", "<#= ctrlId #>");
    currentPageID = ew.PAGE_ID = "<#= ctrlId #>";
    <# } #>

    <# if (ctrlId == "search" || useExtendedBasicSearch) { #>

    // Add fields
    var currentTable = <#= Code.write(Code.toJson(`GetClientVar("tables", "${tblVar}")`)) #>,
        fields = currentTable.fields;
    <#= formNameSearch #>.addFields([
    <#
        currentFields.forEach((f, i, ar) => {
            let validator = IsValidateSearch(f) ? ValidatorFn(f) : "",
                betweenValidator = ["USER SELECT", "BETWEEN"].includes(f.FldSrchOpr) ? "ew.Validators.between" : "";
    #>
        ["<#= f.FldParm #>", [<#= validator #>], fields.<#= f.FldParm #>.isInvalid]<# if (i < ar.length - 1 || betweenValidator) { #>,<# } #>
        <# if (betweenValidator) { #>
        ["y_<#= f.FldParm #>", [<#= betweenValidator #>], false]<# if (i < ar.length - 1) { #>,<# } #>
        <# } #>
    <#
        });
    #>
    ]);

    // Set invalid fields
    $(function() {
        <#= formNameSearch #>.setInvalid();
    });

    // Validate form
    <#= formNameSearch #>.validate = function () {
        if (!this.validateRequired)
            return true; // Ignore validation

        var fobj = this.getForm(),
            $fobj = $(fobj),
            rowIndex = "";
        $fobj.data("rowindex", rowIndex);

        // Validate fields
        if (!this.validateFields(rowIndex))
            return false;
        <#
            if (ClientScriptExist(eventCtrlType, "Form_CustomValidate")) {
        #>
        // Call Form_CustomValidate event
        if (!this.customValidate(fobj)) {
            this.focus();
            return false;
        }
        <#
            }
        #>

        return true;
    }

    <# if (ClientScriptExist(eventCtrlType, "Form_CustomValidate")) { #>
    // Form_CustomValidate
    <#= formNameSearch #>.customValidate = <#= GetClientScript(eventCtrlType, "Form_CustomValidate").replace(/(\r\n|\r|\n)/g, "$1    ") #>
    <# } #>

    // Use JavaScript validation or not
    <#= formNameSearch #>.validateRequired = <#= Code.write(Code.jsBool(Code.Config.ClientValidate)) #>;

    <# } #>

    // Dynamic selection lists
    <#
        for (let f of allFields) {
            if ((IsFieldAdvancedSearch(f) || IsFieldExtendedSearch(f)) && IsLookupField(f)) {
                let fldParm = f.FldParm;
    #>
    <#= formNameSearch #>.lists.<#= fldParm #> = <?= $<#= pageObj #>-><#= fldParm #>->toClientList($<#= pageObj #>) ?>;
    <#
            }
        }
    #>

    <# if (ctrlId != "search") { #>

    // Filters
    <#= formNameSearch #>.filterList = <?= $<#= pageObj #>->getFilterList() ?>;

    <# if (PROJ.SearchPanelCollapsed && !showBlankListPage) { #>
    // Init search panel as collapsed
    <#= formNameSearch #>.initSearchPanel = true;
    <# } #>

    <# } #>

    loadjs.done("<#= formNameSearch #>");
});
<# } #>
</script>
