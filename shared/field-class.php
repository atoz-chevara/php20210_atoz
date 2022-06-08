<#
    let fieldClass = TABLE.TblType == "REPORT" ? "ReportField" : "DbField",
        fldName = FIELD.FldName,
        fldParm = FIELD.FldParm,
        fldObj = "this->" + fldParm,
        fldVar = FIELD.FldVar,
        fldType = FIELD.FldType,
        fldSize = FIELD.FldSize,
        fldExpression = FieldSqlName(FIELD, tblDbId),
        fldBasicSearchExpression = CastFieldForLike(TABLE, FIELD),
        fldDateTimeFormat = FIELD.FldFmtType == "Date/Time" ? FIELD.FldDtFormat : "-1",
        fldUpload = Code.bool(FIELD.FldHtmlTag == "FILE");

    // Crosstab year field
    if (IsCrosstabYearField(TABLE, FIELD) && IsTextFilter2(FIELD))
        fldExpression = DbGroupSql("y", 0, tblDbId).replace(/%s/g, fldExpression);

    // Boolean field
    let fldIsBoolType = IsBooleanField(TABLE, FIELD),
        fldIsBitType = tblIsPostgreSql && [1560, 1562].includes(FIELD.NativeDataType) || tblIsMySql && FIELD.NativeDataType == 16;

    // Virtual field
    let fldVirtualExpression = fldExpression,
        fldIsVirtual = Code.false,
        fldForceSelect = Code.false,
        fldVirtualSearch = Code.false;
    if (IsVirtualLookupField(FIELD)) {
        fldVirtualExpression = QuotedName(VirtualLookupFieldName(FIELD, tblDbId), tblDbId);
        fldIsVirtual = Code.true;
        fldForceSelect = Code.bool(IsForceSelectField(FIELD));
        fldVirtualSearch = Code.bool(!(FIELD.FldHtmlTag == "CHECKBOX" || FIELD.FldHtmlTag == "SELECT" && FIELD.FldSelectMultiple) && FIELD.FldVirtualLookupSearch || FIELD.FldHtmlTag == "TEXT");
    }

    let fldViewTag = FIELD.FldViewTag,
        fldHtmlTag = FIELD.FldHtmlTag,
        fldSrchOpr = FIELD.FldSrchOpr,
        fldSrchOpr2 = FIELD.FldSrchOpr2,
        fldSrchCond = "";
    if (fldSrchOpr == "USER SELECT")
        fldSrchOpr = "=";
    if (fldSrchOpr2 == "USER SELECT")
        fldSrchOpr2 = "=";
    if (fldSrchOpr == "BETWEEN") {
        fldSrchCond = "AND";
        fldSrchOpr2 = "";
    } else if (!IsEmpty(fldSrchOpr)) {
        fldSrchCond = "AND";
    }

    // Set up IsForeignKey
    let isForeignKey = false;
    for (let md of MasterDetails) {
        if (md.MasterTable == TABLE.TblName) {
            md.Relations.forEach((rel) => {
                if (rel.MasterField == FIELD.FldName)
                    isForeignKey = true;
            });
        } else if (md.DetailTable == TABLE.TblName) {
            md.Relations.forEach((rel) => {
                if (rel.DetailField == FIELD.FldName)
                    isForeignKey = true;
            });
        }
        if (isForeignKey)
            break;
    }

    // Search default value
    let searchDefault = FIELD.FldSearchDefault;
    if (["summary", "crosstab"].includes(TABLE.TblReportType) && IsExtendedFilter(FIELD) && !IsTextFilter(FIELD))
        searchDefault = GetDropdownDefaultValue();
#>
        // <#= fldName #>
        $<#= fldObj #> = new <#= fieldClass #>('<#= tblVar #>', '<#= SingleQuote(tblName) #>', '<#= fldVar #>', '<#= SingleQuote(fldName) #>', '<#= SingleQuote(fldExpression) #>', <#= fldBasicSearchExpression #>, <#= fldType #>, <#= fldSize #>, <#= fldDateTimeFormat #>, <#= fldUpload #>, '<#= SingleQuote(fldVirtualExpression) #>', <#= fldIsVirtual #>, <#= fldForceSelect #>, <#= fldVirtualSearch #>, '<#= SingleQuote(fldViewTag) #>', '<#= SingleQuote(fldHtmlTag) #>');

<# if (PROJ.SecTbl == TABLE.TblName && PROJ.SecPasswdFld == FIELD.FldName) { #>
        if (Config("ENCRYPTED_PASSWORD")) {
            $<#= fldObj #>->Raw = true;
        }
<# } #>
<# if (TABLE.TblReportType == "crosstab" && FIELD.FldRowID > 0) { #>
        $<#= fldObj #>->GroupingFieldId = <#= FIELD.FldRowID #>;
<# } else if (TABLE.TblReportType == "summary" && FIELD.FldGroupBy > 0) { #>
        $<#= fldObj #>->GroupingFieldId = <#= FIELD.FldGroupBy #>;
        $<#= fldObj #>->ShowGroupHeaderAsRow = $this->ShowGroupHeaderAsRow;
        $<#= fldObj #>->ShowCompactSummaryFooter = $this->ShowCompactSummaryFooter;
        $<#= fldObj #>->GroupByType = "<#= FIELD.FldGroupByType || "" #>";
        $<#= fldObj #>->GroupInterval = "<#= FIELD.FldGroupByInterval || 0 #>";
        $<#= fldObj #>->GroupSql = "<#= DbGroupSql(FIELD.FldGroupByType, FIELD.FldGroupByInterval, tblDbId).replace("\\", "\\\\") #>";
<# } #>
<# if (IsCustomField(FIELD)) { // Custom field #>
        $<#= fldObj #>->IsCustom = true; // Custom field
<# } #>
<# if (FIELD.FldAutoIncrement) { // Autoincrement field #>
        $<#= fldObj #>->IsAutoIncrement = true; // Autoincrement field
<# } #>
<# if (FIELD.FldIsPrimaryKey) { // Primary key field #>
        $<#= fldObj #>->IsPrimaryKey = true; // Primary key field
<# } #>
<# if (isForeignKey) { // Foreign key field #>
        $<#= fldObj #>->IsForeignKey = true; // Foreign key field
<# } #>
<# if (FIELD.FldReq) { // NOT NULL field #>
        $<#= fldObj #>->Nullable = false; // NOT NULL field
<# } #>
<# if (IsRequiredField(FIELD)) { // Required field #>
        $<#= fldObj #>->Required = true; // Required field
<# } #>
        $<#= fldObj #>->Sortable = <#= Code.bool(FIELD.FldSort) #>; // Allow sort
<# if (IsFloatFormatField(FIELD) && FIELD.FldNumDigits > 0) { // Set up DefaultDecimalPrecision for float #>
        $<#= fldObj #>->DefaultDecimalPrecision = <#= FIELD.FldNumDigits #>; // Default decimal precision
<# } #>
<# if (FIELD.FldHtmlTag == "SELECT") { #>
    <# if (FIELD.FldSelectMultiple) { #>
        $<#= fldObj #>->SelectMultiple = true; // Multiple select
    <# } else { #>
        $<#= fldObj #>->UsePleaseSelect = true; // Use PleaseSelect by default
        $<#= fldObj #>->PleaseSelectText = $Language->phrase("PleaseSelect"); // "PleaseSelect" text
    <# } #>
<# } #>
<# if (fldIsBitType) { #>
        $<#= fldObj #>->DataType = DATATYPE_BIT;
<# } #>
<# if (fldIsBoolType) { #>
    <# if (!fldIsBitType) { #>
        $<#= fldObj #>->DataType = DATATYPE_BOOLEAN;
    <# } #>
    <# if (TrueValue == "Y") { #>
        $<#= fldObj #>->TrueValue = "Y";
        $<#= fldObj #>->FalseValue = "N";
    <# } else if (TrueValue == "y") { #>
        $<#= fldObj #>->TrueValue = "y";
        $<#= fldObj #>->FalseValue = "n";
    <# } #>
<# } #>
<# if (IsLookupField(FIELD)) { #>
    <#
        if (PROJ.MultiLanguage) {
    #>
        switch ($CurrentLanguage) {
    <#
            for (let lang of Languages) {
    #>
            case "<#= lang #>":
                $<#= fldObj #>->Lookup = <#= LookupSettings({ lang: lang }) #>;
                break;
    <#
            }
    #>
            default:
                $<#= fldObj #>->Lookup = <#= LookupSettings() #>;
                break;
        }
    <#
        } else { // Single language
    #>
        $<#= fldObj #>->Lookup = <#= LookupSettings("") #>;
    <#
        }
    #>
<# } #>
<#
    if (["summary", "crosstab"].includes(TABLE.TblReportType)) {
        if (IsDateFilter(FIELD)) {
#>
        $<#= fldObj #>->DateFilter = "<#= FIELD.FldDateSearch #>";
<#
        }
        let grpFld = "";
         if (IsDateFilter(FIELD))
            grpFld = DateSql(FIELD, tblDbId);
        else if (IsDetailGroupTypeField(TABLE, FIELD))
            grpFld = DbGroupSql(FIELD.FldGroupByType, FIELD.FldGroupByInterval, tblDbId);
        else if (TABLE.TblReportType == "crosstab" && FIELD.FldName == columnField.FldName && ["y", "q", "m"].includes(columnDateType))
            grpFld = DbGroupSql("y", 0, tblDbId);
        if (grpFld != "") {
            let fld = FieldSqlName(FIELD, tblDbId);
            grpFld = grpFld.replace(/%s/g, fld);
#>
        $<#= fldObj #>->LookupExpression = "<#= Code.quote(grpFld) #>";
<#
        }

        let drillDownUrl = FieldDrillDownUrl(FIELD);
        if (drillDownUrl) {
#>
        $<#= fldObj #>->DrillDownTable = "<#= Quote(FIELD.FldDrillTable) #>";
        $<#= fldObj #>->DrillDownUrl = "<#= drillDownUrl #>";
<#
        }
    }
#>
<# if (FIELD.FldSelectType != "Table" && !IsEmpty(FieldTagValues(FIELD)) && ["SELECT", "RADIO", "CHECKBOX"].includes(FIELD.FldHtmlTag)) { #>
        $<#= fldObj #>->OptionCount = <#= FieldTagValues(FIELD).trim().split("|").length #>;
<# } #>
<#
    if (TABLE.TblReportType == "summary" && !IsEmpty(FIELD.FldFilterName)) {
        let filters = FIELD.FldFilterName.split(",").map(filter => PascalCase(RemoveQuotes(filter)));
        for (let filter of filters) {
#>
        RegisterFilterGroup($<#= fldObj #>, "<#= Quote(filter) #>");
<#
        }
    }
#>
<#
    if (tblIsOracle) {
        let fldTypeName = FIELD.FldTypeName.toUpperCase();
        if (["BLOB", "CLOB"].includes(fldTypeName)) {
#>
        $<#= fldObj #>->BlobType = "<#= fldTypeName #>";
<#
        }
    }
#>
<# if (FIELD.FldViewThumbnail) { #>
        $<#= fldObj #>->ImageResize = true;
<# } #>
<# if (!IsEmpty(FIELD.UploadAllowedFileExt)) { #>
        $<#= fldObj #>->UploadAllowedFileExt = "<#= FIELD.UploadAllowedFileExt #>";
<# } #>
<# if (!IsEmpty(FIELD.UploadMaxFileSize) && IsNumber(FIELD.UploadMaxFileSize) && FIELD.UploadMaxFileSize > 0) { #>
        $<#= fldObj #>->UploadMaxFileSize = <#= FIELD.UploadMaxFileSize #>;
<# } #>
<# if (FIELD.FldHtmlTag == "FILE" && FIELD.FldUploadMultiple && !IsBinaryField(FIELD)) { #>
        $<#= fldObj #>->UploadMultiple = true;
        $<#= fldObj #>->Upload->UploadMultiple = true;
    <# if (!IsEmpty(FIELD.UploadMaxFileCount)) { #>
        $<#= fldObj #>->UploadMaxFileCount = <#= FIELD.UploadMaxFileCount #>;
    <# } #>
<# } #>
<# if (!IsEmpty(FIELD.FldMemoMaxLength) && IsNumber(FIELD.FldMemoMaxLength) && FIELD.FldMemoMaxLength > 0) { #>
        $<#= fldObj #>->MemoMaxLength = <#= FIELD.FldMemoMaxLength #>;
<# } #>
<# if (FIELD.FldUseDHtmlEditor) { #>
        $<#= fldObj #>->TruncateMemoRemoveHtml = <#= Code.bool(FIELD.FldUseDHtmlEditor) #>;
<# } #>
<# if (!IsEmpty(FIELD.FldValidate)) { #>
        $<#= fldObj #>->DefaultErrorMessage = <#= ServerDefaultMsg() #>;
<# } #>
        $<#= fldObj #>->CustomMsg = $Language->FieldPhrase($this->TableVar, $<#= fldObj #>->Param, "CustomMsg");
<# if (!IsEmpty(searchDefault)) { #>
        $<#= fldObj #>->AdvancedSearch->SearchValueDefault = <#= searchDefault #>;
<# } #>
<# if (!IsEmpty(FIELD.FldSearchDefault2)) { #>
        $<#= fldObj #>->AdvancedSearch->SearchValue2Default = <#= FIELD.FldSearchDefault2 #>;
<# } #>
<# if (!IsEmpty(FIELD.FldSearchDefault) || !IsEmpty(FIELD.FldSearchDefault2)) { #>
        $<#= fldObj #>->AdvancedSearch->SearchOperatorDefault = "<#= fldSrchOpr #>";
        $<#= fldObj #>->AdvancedSearch->SearchOperatorDefault2 = "<#= fldSrchOpr2 #>";
        $<#= fldObj #>->AdvancedSearch->SearchConditionDefault = "<#= fldSrchCond #>";
<# } #>
<#
    if (IsEncryptField(FIELD)) { // Field allow encryption
        // Get field encryption settings
        let encryptExtName = "FieldEncryption",
            encryptExt = GetExtensionObject(encryptExtName),
            encryptExtField = encryptExt && encryptExt.Enabled ? GetExtensionField(encryptExtName, TABLE.TblName, FIELD.FldName) : null;
        if (encryptExtField && encryptExtField.IsEncrypt) {
#>
        $<#= fldObj #>->IsEncrypt = true;
<#
        }
    }
#>
<# if (TABLE.TblType == "REPORT" && !IsEmpty(TABLE.TblRptSrc)) { #>
        $<#= fldObj #>->SourceTableVar = '<#= SingleQuote(GetTableObject(TABLE.TblRptSrc).TblVar) #>';
<# } #>
        $this->Fields['<#= SingleQuote(fldName) #>'] = &$<#= fldObj #>;