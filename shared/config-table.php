<#
    if (hasUserTable && ["index", "login", "logout", "reset_password", "change_password", "register"].includes(ctrlId)) { // User table as current table
        TABLE = secTable;
    } else if (ctrlId == "userpriv" && DB.UseDynamicUserLevel && !IsEmpty(DB.UserLevelTbl)) { // User level table as current table
        TABLE = GetTableObject(DB.UserLevelTbl);
    }

    global.tblName = TABLE.TblName;
    global.tblVar = TABLE.TblVar;
    global.tblClassName = PascalCase(tblVar);
    global.tblDbId = GetDbId(tblName);
    global.tblDbType = DbType(tblDbId);
    global.tblIsMsAccess = tblDbType == "ACCESS";
    global.tblIsMsSql = tblDbType == "MSSQL";
    global.tblIsMySql = tblDbType == "MYSQL";
    global.tblIsPostgreSql = tblDbType == "POSTGRESQL";
    global.tblIsOracle = tblDbType == "ORACLE";
    global.tblIsSQLite = tblDbType == "SQLITE";

    global.customListOptionsHeader = "block";
    global.customListOptionsBody = "block";
    global.customListOptionsFooter = "block";
    if (ctrlId == "list") {
        UseCustomTemplate = CustomTemplateExist();
        if (UseCustomTemplate) {
            customListOptionsHeader = CustomListOptions("header");
            customListOptionsBody = CustomListOptions("body");
            customListOptionsFooter = CustomListOptions("footer");
        }
        UseCustomTemplateSearch = CustomTemplateSearchExist();
    } else {
        UseCustomTemplate = CustomTemplateExist();
    }
    if (UseCustomTemplate) {
        UseCustomMultiPageTemplate = CustomMultiPageTemplateExist();
        ScriptTemplateClass = TABLE.TblVar + ctrlId;
    }
    global.templateClass = ScriptTemplateClass;

    global.useBasicSearch = false;
    global.useExtendedBasicSearch = false;
    global.useAdvancedSearch = false;

    // Config link captions
    global.insertLinkCaption = Code.languagePhrase("InsertLink");
    global.cancelLinkCaption = Code.languagePhrase("CancelLink");
    global.updateLinkCaption = Code.languagePhrase("UpdateLink");
    global.viewLinkCaption = Code.languagePhrase("ViewLink");
    global.editLinkCaption = Code.languagePhrase("EditLink");
    global.inlineEditLinkCaption = Code.languagePhrase("InlineEditLink");
    global.copyLinkCaption = Code.languagePhrase("CopyLink");
    global.inlineCopyLinkCaption = Code.languagePhrase("InlineCopyLink");
    global.deleteLinkCaption = Code.languagePhrase("DeleteLink");
    global.viewPageDetailLinkCaption = Code.languagePhrase("ViewPageDetailLink");
    global.detailLinkCaption = Code.languagePhrase("DetailLink");
    global.masterDetailViewLinkCaption = Code.languagePhrase("MasterDetailViewLink");
    global.masterDetailEditLinkCaption = Code.languagePhrase("MasterDetailEditLink");
    global.masterDetailCopyLinkCaption = Code.languagePhrase("MasterDetailCopyLink");
    global.addBlankRowLinkCaption = Code.raw(Code.languagePhrase("AddBlankRow"));
    global.gridInsertLinkCaption = Code.raw(Code.languagePhrase("GridInsertLink"));
    global.gridSaveLinkCaption = Code.raw(Code.languagePhrase("GridSaveLink"));
    global.gridCancelLinkCaption = Code.raw(Code.languagePhrase("GridCancelLink"));
    global.reloadLinkCaption = Code.languagePhrase("ReloadLink");
    global.overwriteLinkCaption = Code.languagePhrase("OverwriteLink");
    global.conflictCancelLinkCaption = Code.languagePhrase("ConflictCancelLink");
    global.gridEditReloadCaption = Code.raw(Code.languagePhrase("ReloadLink"));
    global.gridEditOverwriteCaption = Code.raw(Code.languagePhrase("OverwriteLink"));
    global.gridEditConflictCancelCaption = Code.raw(Code.languagePhrase("ConflictCancelLink"));
    global.printerFriendlyCaption = Code.languagePhrase("PrinterFriendly");
    global.exportToHtmlCaption = Code.languagePhrase("ExportToHtml");
    global.exportToExcelCaption = Code.languagePhrase("ExportToExcel");
    global.exportToWordCaption = Code.languagePhrase("ExportToWord");
    global.exportToXmlCaption = Code.languagePhrase("ExportToXml");
    global.exportToCsvCaption = Code.languagePhrase("ExportToCsv");
    global.exportToEmailCaption = Code.languagePhrase("ExportToEmail");
    global.exportToPdfCaption = Code.languagePhrase("ExportToPDF");
    global.importCaption = Code.languagePhrase("Import");

    // Common file names
    global.listPage = "";
    global.addPage = "";
    global.addoptPage = "";
    global.viewPage = "";
    global.editPage = "";
    global.deletePage = "";
    global.searchPage = "";
    global.updatePage = "";
    global.viewPageName = "";
    global.addPageName = "";
    global.editPageName = "";
    global.deletePageName = "";
    global.listPageName = "";
    if (TABLE.TblType != "REPORT") {
        listPage = GetRouteUrl("list");
        addPage = GetRouteUrl("add");
        addoptPage = GetRouteUrl("addopt");
        viewPage = GetRouteUrl("view");
        editPage = GetRouteUrl("edit");
        deletePage = GetRouteUrl("delete");
        searchPage = GetRouteUrl("search");
        updatePage = GetRouteUrl("update");
        viewPageName = GetFileName("view", "", false, TABLE).replace(/\.php$/i, "");
        addPageName = GetFileName("add", "", false, TABLE).replace(/\.php$/i, "");
        editPageName = GetFileName("edit", "", false, TABLE).replace(/\.php$/i, "");
        deletePageName = GetFileName("delete", "", false, TABLE).replace(/\.php$/i, "");
        listPageName = GetFileName("list", "", false, TABLE).replace(/\.php$/i, "");
    }

    // Set up return page (add/edit/register)
    // Register return page
    global.registerReturnPage = "";
    if (!IsEmpty(PROJ.RegisterReturnPage)) {
        registerReturnPage = GetReturnPage(PROJ.RegisterReturnPage);
    } else {
        if (PROJ.SecRegisterAutoLogin)
            registerReturnPage = DoubleQuote(indexPage);
        else
            registerReturnPage = DoubleQuote(loginPage);
    }
    // Add return page
    global.addReturnPage = TABLE.TblAddReturnPage;
    global.isCustomAddReturnPage = !IsEmpty(addReturnPage) && !addReturnPage.startsWith("_");
    if (!IsEmpty(addReturnPage)) {
        addReturnPage = GetReturnPage(addReturnPage);
    } else {
        addReturnPage = "$this->getReturnUrl()";
    }
    // Edit return page
    global.editReturnPage = TABLE.TblEditReturnPage;
    global.isCustomEditReturnPage = !IsEmpty(editReturnPage) && !editReturnPage.startsWith("_");
    if (!IsEmpty(editReturnPage)) {
        editReturnPage = GetReturnPage(editReturnPage);
    } else {
        editReturnPage = "$this->getReturnUrl()";
    }

    // All fields
    global.allFields = TABLE.Fields.filter(f => f.FldGenerate);

    // Key fields
    global.keyFields = allFields.filter(f => f.FldIsPrimaryKey);

    // Current page fields
    global.currentFields = allFields.filter(f =>
        IsFieldList(f) || IsFieldReport(f) || IsFieldView(f) ||
        IsFieldAdd(f) || IsFieldAddOption(f) || IsFieldRegister(f) ||
        IsFieldEdit(f) || IsFieldUpdate(f) ||
        IsFieldDelete(f) || IsFieldAdvancedSearch(f));

    global.orderByFields = allFields.filter(f => f.FldOrderBy > 0) // FldOrderBy > 0
                            .slice()
                            .sort((f1, f2) => f1.FldOrderBy - f2.FldOrderBy);

    // SQL parts
    global.selectPart = "";
    global.fromPart = "";
    global.wherePart = "";
    global.groupByPart = "";
    global.havingPart = "";
    global.orderByPart = "";
    global.selectAggPart = "";
    global.groupByAggPart = "";
    global.aggPrefixPart = "";
    global.aggSuffixPart = "";
    global.srcTable = null;
    if (TABLE.TblType == "REPORT") { // REPORT
        srcTable = TABLE;
        if (!IsEmpty(TABLE.TblRptSrc))
            srcTable = GetTableObject(TABLE.TblRptSrc);

        // Crosstab
        if (TABLE.TblReportType == "crosstab") {

            let sqlParts = CrosstabSql();
            selectPart = sqlParts["SELECT"];
            selectAggPart = sqlParts["SELECT AGGREGATE"]; // Select Aggregate
            fromPart = sqlParts["FROM"];
            wherePart = '"' + Code.quote(sqlParts["WHERE"]) + '"';
            groupByPart = sqlParts["GROUP BY"];
            groupByAggPart = sqlParts["GROUP BY AGGREGATE"]; // Group By Aggregate
            orderByPart = sqlParts["ORDER BY"];

        // Detail summary
        } else if (TABLE.TblReportType == "summary") {

            let sqlParts = ReportSql();
            selectPart = sqlParts["SELECT"];
            fromPart = sqlParts["FROM"];
            wherePart = '"' + Code.quote(sqlParts["WHERE"]) + '"';
            groupByPart = sqlParts["GROUP BY"];
            // Get summary fields
            selectAggPart = sqlParts["SELECT AGGREGATE"];
            let aggPfxFlds = sqlParts["AGGREGATE PREFIX"];
            if (!IsEmpty(aggPfxFlds)) {
                aggPrefixPart = "SELECT " + aggPfxFlds + " FROM (";
                aggSuffixPart = ") AS " + QuotedName("TMPTABLE", tblDbId);
            }
            havingPart = sqlParts["HAVING"];
            orderByPart = sqlParts["ORDER BY"];

        }

    } else { // Table
        if (TABLE.TblType == "CUSTOMVIEW") {
            let limitPart = SqlPart(TABLE.TblCustomSQL, "LIMIT").trim();
            groupByPart = SqlPart(TABLE.TblCustomSQL, "GROUP BY").trim();
            havingPart = SqlPart(TABLE.TblCustomSQL, "HAVING").trim();
            if (limitPart == "" && !(!IsEmpty(groupByPart) && !IsEmpty(havingPart))) { // Allow GROUP BY without HAVING
                selectPart = SqlPart(TABLE.TblCustomSQL, "SELECT");
                fromPart = SqlPart(TABLE.TblCustomSQL, "FROM");
                wherePart = '"' + Code.quote(SqlPart(TABLE.TblCustomSQL, "WHERE")) + '"';
                orderByPart = SqlPart(TABLE.TblCustomSQL, "ORDER BY");
            } else {
                selectPart = "*";
                fromPart = "(" + TABLE.TblCustomSQL + ") " + QuotedName("CV_" + tblVar, tblDbId);
            }
        } else {
            selectPart = tblIsOracle ? SqlTableName(TABLE, tblDbId) + ".*" : "*";
            fromPart = SqlTableName(TABLE, tblDbId);
        }
    }
    if (wherePart == "")
        wherePart = '""'; // Empty String
    global.defaultOrderBy = OrderByFieldSources();

    // Add Custom Field SQL
    let selectCustom = allFields.filter(f => IsCustomField(f)).map(f => (f.FldSQL == "" ? "''" : Quote(f.FldSQL)) + " AS " + QuotedName(f.FldName, tblDbId)).join(", ");
    if (selectCustom != "")
        selectPart += ", " + selectCustom;

    // Multi select key
    global.multiSelectKey = "";
    keyFields.forEach(f => {
        if (!IsEmpty(multiSelectKey))
            multiSelectKey += ` . Config("COMPOSITE_KEY_SEPARATOR") . `;
        multiSelectKey += `$this->${f.FldParm}->CurrentValue`;
    });

    // Virtual lookup
    let virtualLookupFields = currentFields.filter(f => IsVirtualLookupField(f));
    global.useVirtualLookup = ctrlId == "info" ? allFields.some(f => IsVirtualLookupField(f)) : virtualLookupFields.length > 0;
    global.virtualFieldList = Array(LanguageCount).fill("");
    virtualLookupFields.forEach(f => {
        Languages.forEach((lang, j) => {
            if (!IsEmpty(virtualFieldList[j]))
                virtualFieldList[j] += ", ";
            virtualFieldList[j] += VirtualLookupFieldSql(lang, f, tblDbId) + " AS " + Quote(QuotedName(VirtualLookupFieldName(f, tblDbId), tblDbId));
        });
    });

    // User table password
    global.isUserTablePassword = TABLE.TblName == PROJ.SecTbl && currentFields.some(f => f.FldName == PROJ.SecPasswdFld);

    // Table options
    global.recPerPage = (TABLE.TblUseGlobal ? PROJ.RecPerPage : TABLE.TblRecPerPage) || 10;
    global.recPerPageList = RecordsPerPageList(TABLE.TblUseGlobal ? PROJ.RecPerPageList : TABLE.TblRecPerPageList, recPerPage);
    global.printerFriendly = TABLE.TblUseGlobal ? PROJ.PrinterFriendly : TABLE.TblPrinterFriendly;
    global.exportHtml = TABLE.TblUseGlobal ? PROJ.ExportHtml : TABLE.TblExportHtml;
    global.exportWord = TABLE.TblUseGlobal ? PROJ.ExportWord : TABLE.TblExportWord;
    global.exportExcel = TABLE.TblUseGlobal ? PROJ.ExportExcel : TABLE.TblExportExcel;
    global.exportXml = TABLE.TblUseGlobal ? PROJ.ExportXml : TABLE.TblExportXml;
    global.exportCsv = TABLE.TblUseGlobal ? PROJ.ExportCsv : TABLE.TblExportCsv;
    global.exportEmail = TABLE.TblUseGlobal ? PROJ.ExportEmail : TABLE.TblExportEmail;
    global.exportPdf = TABLE.TblUseGlobal ? PROJ.ExportPDF : TABLE.TblExportPDF;
    global.exportSelectedOnly = TABLE.TblUseGlobal ? PROJ.ExportType == "SELECTED" : TABLE.TblExportType == "SELECTED";
    global.exportAll = TABLE.TblUseGlobal ? PROJ.ExportType == "ALL" : TABLE.TblExportType == "ALL";
    global.recPerRow = TABLE.TblUseGlobal ? PROJ.RecPerRow : TABLE.TblRecPerRow;
    global.multiDelete = TABLE.TblUseGlobal ? PROJ.MultiDelete : TABLE.TblMultiDelete;
    global.sortType = TABLE.TblUseGlobal ? PROJ.SortType : TABLE.TblSortType;
    let pagerStyle = TABLE.TblUseGlobal ? PROJ.PagerStyle : TABLE.TblPagerStyle;
    global.pagerClass = pagerStyle == 1 ? "NumericPager" : "PrevNextPager";
    global.topPageLink = TABLE.TblUseGlobal ? PROJ.TopPageLink : TABLE.TblTopPageLink;
    global.bottomPageLink = TABLE.TblUseGlobal ? PROJ.BottomPageLink : TABLE.TblBottomPageLink;
    global.linkOnLeft = TABLE.TblUseGlobal ? PROJ.LinkOnLeft : TABLE.TblLinkOnLeft;
    global.detailViewPaging = TABLE.TblUseGlobal ? PROJ.DetailViewPaging : TABLE.TblDetailViewPaging;
    global.detailEditPaging = TABLE.TblUseGlobal ? PROJ.DetailEditPaging : TABLE.TblDetailEditPaging;
    global.listExport = TABLE.TblUseGlobal ? PROJ.ListExport : TABLE.TblListExport;
    global.viewExport = TABLE.TblUseGlobal ? PROJ.ViewExport : TABLE.TblViewExport;

    // Use buttons for links
    global.useButtonsForLinks = TABLE.TblUseGlobal ? PROJ.UseButtonsForLinks : TABLE.TblUseButtonsForLinks;

    // Use drop down
    global.useDropDownForExport = TABLE.TblUseGlobal ? PROJ.UseDropDownForExport : TABLE.TblUseDropDownForExport;
    global.useDropDownForAction = TABLE.TblUseGlobal ? PROJ.UseDropDownForAction : TABLE.TblUseDropDownForAction;
    global.useDropDownForListOptions = TABLE.TblUseGlobal ? PROJ.UseDropDownForListOptions : TABLE.TblUseDropDownForListOptions;

    // Modal dialogs
    global.inlineDelete = TABLE.TblInlineDelete; // Inline/Modal delete
    global.useModalSearch = TABLE.TblModalSearch; // Modal search
    global.useModalAdd = TABLE.TblModalAdd; // Modal add
    global.useModalEdit = TABLE.TblModalEdit; // Modal edit
    global.useModalUpdate = TABLE.TblModalUpdate; // Modal update
    global.useModalView = TABLE.TblModalView; // Modal view

    // Multi-page type (tabs/pills/accordion)
    global.multiPageType = TABLE.TblMultiPageType || PROJ.MultiPageType || "tabs";

    // Validate settings
    if (keyFields.length == 0 || TABLE.TblType == "REPORT")
        exportSelectedOnly = false;
    if (ctrlId == "view")
        exportSelectedOnly = false;
    if (ctrlId == "list")
        exportSelectedOnly = listExport && exportSelectedOnly;
    if (ctrlId == "grid") // Not sortable for grid
        sortType = 0;
    if (ctrlId == "grid" || UseCustomTemplate)
        recPerRow = 0;
    if (useDropDownForListOptions)
        useButtonsForLinks = false;
    multiPageType = multiPageType.toLowerCase();
    if (!["tabs", "pills", "accordion"].includes(multiPageType))
        multiPageType = "tabs";

    // Accordion
    global.useAccordionForMultiPage = multiPageType == "accordion";

    // Show blank page if search enabled
    global.showBlankListPage = TABLE.TblShowBlankListPage;
    if (!TABLE.TblSearch && !TABLE.TblBasicSearch && !TABLE.TblExtendedBasicSearch)
        showBlankListPage = false;

    global.extSearchFldPerRow = TABLE.TblExtSearchFldPerRow || 1; // Extended Search column per row
    if (extSearchFldPerRow <= 0)
        extSearchFldPerRow = 1;

    // Inline options
    global.inlineAdd = TABLE.TblInlineAdd && ctrlId == "list";
    global.inlineCopy = TABLE.TblInlineCopy && ctrlId == "list";
    global.inlineEdit = TABLE.TblInlineEdit && ctrlId == "list";
    global.gridEdit = TABLE.TblGridEdit && ctrlId == "list" || ctrlId == "grid";
    global.gridAdd = TABLE.TblGridAdd && ctrlId == "list" || ctrlId == "grid";
    global.listAdd = inlineAdd || inlineCopy || gridAdd;
    global.listEdit = inlineEdit || gridEdit;
    global.gridAddOrEdit = gridAdd || gridEdit;
    global.listAddOrEdit = listAdd || listEdit;

    // Search options
    global.useBasicSearch = ctrlId == "list" && TABLE.TblBasicSearch && allFields.some(f => IsFieldBasicSearch(f));
    global.useExtendedBasicSearch = (ctrlId == "list" && TABLE.TblExtendedBasicSearch || ["summary", "crosstab"].includes(ctrlId)) && allFields.some(f => IsFieldExtendedSearch(f));
    if (!useExtendedBasicSearch)
        UseCustomTemplateSearch = false;
    global.useAdvancedSearch = TABLE.TblSearch && allFields.some(f => f.FldSearch);

    // Basic search default value
    let hasBasicSearchDefault = !IsEmpty(TABLE.TblBasicSearchDefault) && TABLE.TblBasicSearch;

    // Advanced search default values
    let hasAdvancedSearchDefault = allFields.some(f =>
        (f.FldSearch || f.FldExtendedBasicSearch) &&
        (!IsEmpty(f.FldSearchDefault) || !IsEmpty(f.FldSearchDefault2)) &&
        !(f.FldHtmlTag == "FILE" && IsBinaryField(f)));

    // Has search default
    global.hasSearchDefault = hasBasicSearchDefault || hasAdvancedSearchDefault;

    // Set up Multi Column grid count
    global.multiColumnClass = PROJ.MultiColumnGridClass || "col-sm";
    if (recPerRow > 0) { // Multiple Column
        let ar = [1, 2, 3, 4, 6, 12]; // Possible values of records per row
        if (!ar.includes(recPerRow)) { // Fix other values
            if (recPerRow > 12) {
                recPerRow = 12;
            } else if (iRecPerRow > 6) {
                recPerRow = 6;
            } else if (iRecPerRow > 4) {
                recPerRow = 4;
            }
        }
        multiColumnClass += "-" + (12 / recPerRow);
    }

    // Import
    global.isImport = TABLE.TblImport;

    // Check concurrent update
    global.checkConcurrentUpdate = TABLE.TblCheckConcurrentUpdate;

    // Multi-Update
    global.multiUpdate = TABLE.TblMultiUpdate;
    global.multiUpdateConfirm = TABLE.TblMultiUpdateConfirm;

    // Show bottom pager if not specified
    if (!PROJ.AllowNoPager) {
        if (!topPageLink && !bottomPageLink)
            bottomPageLink = true;
    }

    // Confirm Add/Edit/Register
    global.addConfirm = TABLE.TblAddConfirm;
    global.editConfirm = TABLE.TblEditConfirm;
    global.registerConfirm = PROJ.SecRegisterConfirm;
    global.confirmPage = addConfirm && ctrlId == "add" ||
        editConfirm && ctrlId == "edit" ||
        multiUpdateConfirm && ctrlId == "update" ||
        registerConfirm && ctrlId == "register";

    // Config Form
    global.jsFormName = "document." + formName;
    global.formNameSearch = GetFormObject(["search", "summary", "crosstab"].includes(ctrlId) ? "" : "extbs"); // Search form name
    global.listFormSubmit = `ew.forms.get(this).submit(event, '" . $this->pageName() . "'); return false;`;
    global.listFormGridSubmit = `ew.forms.get(this).submit(event, '" . $this->pageName() . "'); return false;`;
    global.listFormInlineSubmit = `ew.forms.get(this).submit(event, '" . UrlAddHash($this->pageName(), "r" . $this->RowCount . "_" . $this->TableVar) . "'); return false;`;
    global.confirmButtonSubmit = ` onclick="this.form.action.value='confirm';"`;
    global.cancelButtonSubmit = ` onclick="this.form.action.value='cancel';"`;
    global.reloadButtonSubmit = ` onclick="this.form.action.value='show';"`;
    global.overwriteButtonSubmit = ` onclick="this.form.action.value='overwrite';"`;

    // Export urls
    let exportUrl = "";
    global.exportPrintUrl = "";
    global.exportHtmlUrl = "";
    global.exportExcelUrl = "";
    global.exportWordUrl = "";
    global.exportXmlUrl = "";
    global.exportCsvUrl = "";
    global.exportPdfUrl = "";
    global.customExportExcelUrl = "";
    global.customExportWordUrl = "";
    global.customExportPdfUrl = "";
    global.exportEndTag = "</a>";
    if (exportSelectedOnly) {
        exportUrl = '"' + Quote(`<a href="#" class="%s" title="%c" data-caption="%c" onclick="return ew.export(%f, '%p', '%e', %b, true);">`) + '"';
        exportUrl = exportUrl.replace(/%f/g, jsFormName);
        exportUrl = exportUrl.replace(/%p/g, '" . ScriptName() . "');
        exportUrl = exportUrl.replace(/%c/g, '" . HtmlEncode($Language->phrase("%p")) . "');
        exportPrintUrl = exportUrl.replace(/%e/g, "print").replace(/%s/g, "ew-print").replace(/%p/g, "PrinterFriendlyText").replace(/%b/g, "false");
        exportHtmlUrl = exportUrl.replace(/%e/g, "html").replace(/%s/g, "ew-html").replace(/%p/g, "ExportToHtmlText").replace(/%b/g, "false");
        exportExcelUrl = exportUrl.replace(/%e/g, "excel").replace(/%s/g, "ew-excel").replace(/%p/g, "ExportToExcelText").replace(/%b/g, "false");
        exportWordUrl = exportUrl.replace(/%e/g, "word").replace(/%s/g, "ew-word").replace(/%p/g, "ExportToWordText").replace(/%b/g, "false");
        exportXmlUrl = exportUrl.replace(/%e/g, "xml").replace(/%s/g, "ew-xml").replace(/%p/g, "ExportToXmlText").replace(/%b/g, "false");
        exportCsvUrl = exportUrl.replace(/%e/g, "csv").replace(/%s/g, "ew-csv").replace(/%p/g, "ExportToCsvText").replace(/%b/g, "false");
        exportPdfUrl = exportUrl.replace(/%e/g, "pdf").replace(/%s/g, "ew-pdf").replace(/%p/g, "ExportToPDFText").replace(/%b/g, "false");
        customExportExcelUrl = exportUrl.replace(/%e/g, "excel").replace(/%s/g, "ew-excel").replace(/%p/g, "ExportToExcelText").replace(/%b/g, "true");
        customExportWordUrl = exportUrl.replace(/%e/g, "word").replace(/%s/g, "ew-word").replace(/%p/g, "ExportToWordText").replace(/%b/g, "true");
        customExportPdfUrl = exportUrl.replace(/%e/g, "pdf").replace(/%s/g, "ew-pdf").replace(/%p/g, "ExportToPDFText").replace(/%b/g, "true");
    } else {
        exportUrl = '"' + Quote(`<a href="%u" class="ew-export-link %s" title="%c" data-caption="%c">`) + '"';
        exportUrl = exportUrl.replace(/%u/g, '" . $this->%u . "');
        exportUrl = exportUrl.replace(/%c/g, '" . HtmlEncode($Language->phrase("%c")) . "');
        exportPrintUrl = exportUrl.replace(/%u/g, "ExportPrintUrl").replace(/%s/g, "ew-print").replace(/%e/g, "print").replace(/%c/g, "PrinterFriendlyText");
        exportHtmlUrl = exportUrl.replace(/%u/g, "ExportHtmlUrl").replace(/%s/g, "ew-html").replace(/%e/g, "html").replace(/%c/g, "ExportToHtmlText");
        exportExcelUrl = exportUrl.replace(/%u/g, "ExportExcelUrl").replace(/%s/g, "ew-excel").replace(/%e/g, "excel").replace(/%c/g, "ExportToExcelText");
        exportWordUrl = exportUrl.replace(/%u/g, "ExportWordUrl").replace(/%s/g, "ew-word").replace(/%e/g, "word").replace(/%c/g, "ExportToWordText");
        exportXmlUrl = exportUrl.replace(/%u/g, "ExportXmlUrl").replace(/%s/g, "ew-xml").replace(/%e/g, "xml").replace(/%c/g, "ExportToXmlText");
        exportCsvUrl = exportUrl.replace(/%u/g, "ExportCsvUrl").replace(/%s/g, "ew-csv").replace(/%e/g, "csv").replace(/%c/g, "ExportToCsvText");
        exportPdfUrl = exportUrl.replace(/%u/g, "ExportPdfUrl").replace(/%s/g, "ew-pdf").replace(/%e/g, "pdf").replace(/%c/g, "ExportToPDFText");
        exportUrl = '"' + Quote(`<a href="#" class="ew-export-link %s" title="%c" data-caption="%c" onclick="return ew.export(%f, '%u', '%e', true);">`) + '"';
        exportUrl = exportUrl.replace(/%f/g, jsFormName);
        exportUrl = exportUrl.replace(/%u/g, '" . $this->%u . "');
        exportUrl = exportUrl.replace(/%c/g, '" . HtmlEncode($Language->phrase("%c")) . "');
        customExportExcelUrl = exportUrl.replace(/%u/g, "ExportExcelUrl").replace(/%s/g, "ew-excel").replace(/%e/g, "excel").replace(/%c/g, "ExportToExcelText");
        customExportWordUrl = exportUrl.replace(/%u/g, "ExportWordUrl").replace(/%s/g, "ew-word").replace(/%e/g, "word").replace(/%c/g, "ExportToWordText");
        customExportPdfUrl = exportUrl.replace(/%u/g, "ExportPdfUrl").replace(/%s/g, "ew-pdf").replace(/%e/g, "pdf").replace(/%c/g, "ExportToPDFText");
    }

    global.hasFileField = HasFileField();

    // Security
    global.hasUserIdFld = false;
    global.userIdFld = "";
    global.userIdFldParm = "";
    if (isSecurityEnabled) {
        isStaticUserLevel = isStaticUserLevel && !IsEmpty(TABLE.TblSecurity);
        isUserLevel = isStaticUserLevel || isDynamicUserLevel;
        hasUserIdFld = hasUserId && !IsEmpty(TABLE.TblUserIDFld);
        if (hasUserIdFld) {
            let userIdField = GetFieldObject(TABLE, TABLE.TblUserIDFld);
            userIdFld = FieldSqlName(userIdField, tblDbId);
            userIdFldParm = userIdField.FldParm;
        }
    }

    // Master table has User ID
    global.masterTableHasUserIdFld = MasterDetails.some(md => {
        if (md.DetailTable == TABLE.TblName) {
            let mt = GetTableObject(md.MasterTable);
            if (mt && mt.TblGen && !IsEmpty(mt.TblUserIDFld))
                return true;
        }
        return false;
    });

    // Show detail record count
    global.showDetailCount = TABLE.TblDetailShowCount;

    // Master/detail tables
    global.masterTables = MasterDetails.filter(md => {
        if (md.DetailTable == TABLE.TblName) {
            let mt = GetTableObject(md.MasterTable);
            return mt && mt.TblGen;
        }
        return false;
    });
    global.detailTables = MasterDetails.filter(md => {
        if (md.MasterTable == TABLE.TblName) {
            let dt = GetTableObject(md.DetailTable);
            return dt && dt.TblGen;
        }
        return false;
    });

    global.isDetailAdd = detailTables.length > 0 && keyFields.length > 0;
    global.isDetailEdit = detailTables.length > 0 && keyFields.length > 0;
    global.isDetailView = detailTables.length > 0 && keyFields.length > 0;

    // Show detail as tab/accordion
    showMultiPageForDetails = TABLE.TblShowMultipleDetails && detailTables.length > 1;

    // Audit trail
    global.auditTrailOnAdd = false;
    global.auditTrailOnEdit = false;
    global.auditTrailOnDelete = false;
    global.auditTrailOnView = false;
    global.auditTrailOnViewData = false;
    global.auditTrailOnSearch = false;
    if (TABLE.TblAuditTrail) {
        let extName = "Audit Trail",
            ext = GetExtensionObject(extName),
            extTable;
        if (ext && ext.Enabled)
            extTable = GetExtensionTable(extName, TABLE.TblName);
        auditTrailOnAdd = extTable ? extTable.Add : true;
        auditTrailOnEdit = extTable ? extTable.Edit : true;
        auditTrailOnDelete = extTable ? extTable.Delete : true;
        auditTrailOnView = extTable ? extTable.View : false;
        auditTrailOnViewData = extTable ? extTable.ViewData : false;
        auditTrailOnSearch = extTable ? extTable.Search : false;
    }

    // Multi page settings
    global.pageCount = 1;
    global.hasMultiPageZero = false;
    global.pageList = {};
    global.activateFldName = "";
    if (ctrlId == "register" && PROJ.SecRegisterActivate && !IsEmpty(PROJ.SecRegisterActivateFld)) {
        let activateField = GetFieldObject(TABLE, PROJ.SecRegisterActivateFld);
        activateFldName = activateField.FldName;
    }
    if (ctrlId == "add" && TABLE.TblMultiPageAdd ||
        ctrlId == "edit" && TABLE.TblMultiPageEdit ||
        ctrlId == "view" && TABLE.TblMultiPageView ||
        ctrlId == "search" && TABLE.TblMultiPageSearch ||
        ctrlId == "register" && PROJ.RegisterMultiPage) {
        for (let f of currentFields) {
            if (ctrlId == "add" && IsEmpty(f.FldAutoUpdateValue) && !IsHiddenField(TABLE, f, ctrlId) ||
                ctrlId == "edit" && IsEmpty(f.FldAutoUpdateValue) && !IsHiddenField(TABLE, f, ctrlId) ||
                ctrlId == "view" || ctrlId == "search" ||
                ctrlId == "register" && !f.FldAutoIncrement && IsEmpty(f.FldAutoUpdateValue) && f.FldName != DB.SecUserLevelFld && f.FldName != activateFldName) {
                let pageIndex = f.FldPageIndex, prp = String(pageIndex);
                if (pageIndex > 1 || pageIndex == 0)
                    useMultiPage = true;
                if (pageIndex == 0)
                    hasMultiPageZero = true;
                if (pageIndex > pageCount)
                    pageCount = pageIndex;
                if (!pageList.hasOwnProperty(prp))
                    pageList[prp] = [];
                pageList[prp].push(f.FldVar);
                if (ctrlId == "register" && TABLE.TblName == PROJ.SecTbl && f.FldName == PROJ.SecPasswdFld) {
                    let fldVar = "c_" + f.FldParm;
                    pageList[prp].push(fldVar);
                }
            }
        } // Field
    }

    // PDF
    global.pdfPageBreakRecordCount = 0; // PDF page break count
    global.pdfPageOrientation = "portrait"; // PDF page orientation
    global.pdfPageSize = "a4"; // PDF page size

    // PhpSpreadSheet
    global.excelPageOrientation = '""'; // PhpSpreadsheet page orientation
    global.excelPageSize = '""'; // PhpSpreadsheet page size
    global.wordPageOrientation = "portrait"; // PHPWord page orientation
    global.wordColumnWidth = 0; // PHPWord column width

    // Grid add row count
    global.gridAddRowCount = PROJ.GridAddRowCount;
    if (!gridAddRowCount || gridAddRowCount <= 0)
        gridAddRowCount = 5;

    // CAPTCHA
    if (extCaptcha && extCaptcha.Enabled) {
        if (ctrlId == "register") {
            if (PROJ.SecRegisterCaptcha) {
                useCaptcha = true;
                confirmCaptcha = PROJ.SecRegisterConfirm;
            }
        } else if (ctrlId == "reset_password") {
            if (PROJ.SecForgotPwdCaptcha) {
                useCaptcha = true;
                confirmCaptcha = false;
            }
        } else if (ctrlId == "change_password") {
            if (PROJ.SecChangePwdCaptcha) {
                useCaptcha = true;
                confirmCaptcha = false;
            }
        } else if (ctrlId == "add") {
            if (TABLE.TblAddCaptcha) {
                useCaptcha = true;
                confirmCaptcha = TABLE.TblAddConfirm;
            }
        } else if (ctrlId == "edit") {
            if (TABLE.TblEditCaptcha) {
                useCaptcha = true;
                confirmCaptcha = TABLE.TblEditConfirm;
            }
        }
    }

    // Set up chart
    let charts = TABLE.Charts || [];
    global.allCharts = charts.filter(c => c.ShowChart && !IsEmpty(c.ChartXFldName) && !IsEmpty(c.ChartYFldName)).slice();
    global.topCharts = allCharts.filter(c => c.ChartPosition == 1).slice();
    global.leftCharts = allCharts.filter(c => c.ChartPosition == 2).slice();
    global.rightCharts = allCharts.filter(c => c.ChartPosition == 3).slice();
    global.bottomCharts = allCharts.filter(c => c.ChartPosition == 4).slice();
    global.chartIndex = 0;

    global.showCharts = allCharts.length > 0;
    global.chartsOnLeft = leftCharts.length > 0;
    global.chartsOnRight = rightCharts.length > 0;

    global.hasDrillDownFields = allCharts.some(c => IsChartDrillDown(c));
    global.dynamicSortCharts = allCharts.filter(c => c.ChartSortType == 5);
    global.exportCharts = showCharts;

    // Show report
    global.showReport = ["summary", "crosstab"].includes(ctrlId) ? TABLE.TblShowReport : true; // Show report

    // Set up chart page break types
    global.chartPageBreakTypes = {};
    let bottomChartCount = 0,
        sortedCharts = allCharts.slice().sort((c1, c2) => c1.ChartPosition - c2.ChartPosition); // ChartPosition ASC
    sortedCharts.forEach((c, i) => {
        if (c.ChartPosition > 2) {
            bottomChartCount++;
            if (bottomChartCount == 1 && !showReport) // No need to page break for first bottom chart if no report
                chartPageBreakTypes[c.ChartVar] = "";
            else
                chartPageBreakTypes[c.ChartVar] = "before";
        } else {
            chartPageBreakTypes[c.ChartVar] = "after";
        }
    });

    // Common names
    global.formClassName = Code.getName(pageObj, Code.FormClassName);
    global.formKeyCountName = Code.getName(pageObj, Code.FormKeyCountName);
    global.keyCount = Code.getName(pageObj, Code.KeyCount);
    global.isConfirm = Code.getName(pageObj, Code.IsConfirm);
    global.startRec = Code.getName(pageObj, Code.StartRecord);
    global.stopRec = Code.getName(pageObj, Code.StopRecord);
    global.totalRecs = Code.getName(pageObj, Code.TotalRecords);
    global.displayRecs = Code.getName(pageObj, Code.DisplayRecords);
    global.currentAction = Code.getName(pageObj, Code.CurrentAction);
    global.currentMode = Code.getName(pageObj, Code.CurrentMode);
    global.currentMasterTable = Code.getName(pageObj, Code.CurrentMasterTable);
    global.returnUrl = Code.getName(pageObj, Code.ReturnUrl);
    global.rowAttributes = Code.getName(pageObj, Code.RowAttributes);
    global.pageLeftColumnClass = Code.getName(pageObj, Code.LeftColumnClass);
    global.pageRightColumnClass = Code.getName(pageObj, Code.RightColumnClass);
    global.pageOffsetColumnClass = Code.getName(pageObj, Code.OffsetColumnClass);
    global.pageTableLeftColumnClass = Code.getName(ctrlId == "master" ? tblVar : pageObj, Code.TableLeftColumnClass);
    global.rowIndex = Code.getName(pageObj, Code.RowIndex);
    global.rowCnt = Code.getName(pageObj, Code.RowCount);
    global.rowType = Code.getName(pageObj, Code.RowType);
    global.recCnt = Code.getName(pageObj, Code.RecordCount);
    global.isAddOrEdit = Code.getName(pageObj, Code.IsAddOrEdit);
    global.isAdd = Code.getName(pageObj, Code.IsAdd);
    global.isCopy = Code.getName(pageObj, Code.IsCopy);
    global.isEdit = Code.getName(pageObj, Code.IsEdit);
    global.isGridAdd = Code.getName(pageObj, Code.IsGridAdd);
    global.isGridEdit = Code.getName(pageObj, Code.IsGridEdit);
    global.isExport = Code.getName(pageObj, Code.IsExport);
    global.isExportPrint = Code.getName(pageObj, Code.IsExportPrint);
    global.isExportPdf = Code.getName(pageObj, Code.IsExportPdf);
    global.isExportWord = Code.getName(pageObj, Code.IsExportWord);
    global.isExportExcel = Code.getName(pageObj, Code.IsExportExcel);
    global.renderPager = Code.getName(pageObj, "Pager", Code.Render);

    // Export
    global.exportStart = `<?php if (!${isExport}) { ?>`;
    global.exportEnd = Code.end;

    global.showVerticalMasterRecord = PROJ.ShowVerticalMasterRecord;
    global.masterExportStart = `<?php if (!${isExport} || Config("EXPORT_MASTER_RECORD") && ${isExportPrint}) { ?>`;
    global.masterExportEnd = Code.end;
#>