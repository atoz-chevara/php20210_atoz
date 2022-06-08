<#
    // Table options
    global.groupPerPage = (TABLE.TblUseGlobal ? PROJ.GrpPerPage : TABLE.TblGrpPerPage) || 1;
    global.groupPerPageList = (TABLE.TblUseGlobal ? PROJ.GrpPerPageList : TABLE.TblGrpPerPageList) || (TABLE.TblUseGlobal ? PROJ.RecPerPageList : TABLE.TblRecPerPageList);
    groupPerPageList = RecordsPerPageList(groupPerPageList, groupPerPage);

    // Export option (override config-table.php)
    listExport = TABLE.TblUseGlobal ? PROJ.ListExport : true;

    // Group / detail fields
    global.groupFields = [];
    global.groupIndex = 0;
    global.firstGroupField = null;
    global.firstGroupFldObj = "";
    global.detailFields = [];
    global.hasSummaryFields = false;
    global.summaryTypes = [];
    global.showDetails = false;
    global.showSummaryView = false;
    global.columnField = null;
    global.columnFieldParm = "";
    global.columnFieldObject = "";
    global.columnDateField = null;
    global.columnDateFieldName = "";
    global.columnDateFieldParm = "";
    global.columnDateFieldType = 0;
    global.columnDateSelect = false;
    global.columnDateType = "";
    global.summaryFields = [];
    global.summaryFieldIndex = 0;
    global.summaryFieldIndexAgg = 0;
    global.showYearSelection = false;

    if (TABLE.TblType == "REPORT") {

        // Summary report
        if (TABLE.TblReportType == "summary") {

            groupFields = allFields.filter(f => f.FldGroupBy > 0)
                .slice()
                .sort((f1, f2) => f1.FldGroupBy - f2.FldGroupBy); // FldGroupBy ASC
            if (groupFields.length > 0) {
                firstGroupField = groupFields[0];
                firstGroupFldObj = Code.fldObj(firstGroupField);
            }
            detailFields = currentFields.filter(f => f.FldGroupBy <= 0).slice();
            detailFields.forEach(f => {
                let t = [];
                if (f.FldRptAggSum) t.push("Sum");
                if (f.FldRptAggAvg) t.push("Avg");
                if (f.FldRptAggMin) t.push("Min");
                if (f.FldRptAggMax) t.push("Max");
                if (f.FldRptAggCnt) t.push("Cnt");
                f.SummaryTypes = t;
            });
            hasSummaryFields = detailFields.some(f => f.FldRptAggSum || f.FldRptAggAvg || f.FldRptAggMin || f.FldRptAggMax || f.FldRptAggCnt);
            if (detailFields.some(f => f.FldRptAggSum)) // Sum
                summaryTypes.push("Sum");
            if (detailFields.some(f => f.FldRptAggAvg)) // Avg
                summaryTypes.push("Avg");
            if (detailFields.some(f => f.FldRptAggMin)) // Min
                summaryTypes.push("Min");
            if (detailFields.some(f => f.FldRptAggMax)) // Max
                summaryTypes.push("Max");
            if (detailFields.some(f => f.FldRptAggCnt)) // Cnt
                summaryTypes.push("Cnt");
            showDetails = TABLE.TblRptShowDetails;
            if (!showDetails && hasSummaryFields)
                showSummaryView = TABLE.TblRptShowSummaryView; // Use summary view

            // If no grouping fields, show details
            if (groupFields.length == 0) {
                showDetails = true;
                showSummaryView = false;
            }

            // Remove grouping fields without show summary
            if (showSummaryView) {
                while (groupFields.length > 0 && !groupFields[groupFields.length - 1].FldGroupByShowSummary)
                    groupFields.splice(-1, 1); // Remove last group
                if (groupFields.length == 0) { // Restore and show details
                    showDetails = true;
                    showSummaryView = false;
                }
            }

        // Crosstab report
        } else if (TABLE.TblReportType == "crosstab") {

            groupFields = allFields.filter(f => f.FldRowID > 0)
                .slice()
                .sort((f1, f2) => f1.FldRowID - f2.FldRowID); // FldRowID ASC
            if (groupFields.length > 0) {
                firstGroupField = groupFields[0];
                firstGroupFldObj = Code.fldObj(firstGroupField);
            }
            summaryFieldIndex = groupFields.length;

            // Column field variables
            let columnFields = allFields.filter(f => f.FldColumnID > 0).slice();
            if (columnFields.length == 0)
                throw new Error("No column field defined");
            columnField = columnFields[0]; // Column field
            columnFieldParm = columnField.FldParm;
            columnFieldObject = Code.fldObj(columnField);
            columnDateType = GetFieldType(columnField.FldType) == 2 ? columnField.FldColumnDateType : "";
            columnDateSelect = columnField.FldColumnDateSelect && ["q", "m"].includes(columnDateType);

            // Set up column date field
            if (["y", "q", "m"].includes(columnDateType)) {
                columnDateFieldName = "YEAR__" + columnFieldParm;
                columnDateFieldParm = columnDateFieldName;
                columnDateFieldType = 3; // Integer type
                if (["q", "m"].includes(columnDateType)) {
                    summaryFieldIndex += 1;
                    if (columnDateSelect)
                        summaryFieldIndexAgg += 1;
                    if (!columnDateSelect)
                        groupFields.push({
                            FldName: columnDateFieldName,
                            FldParm: columnDateFieldParm,
                            FldVar: "x_" + columnDateFieldParm,
                            FldGroupByShowSummary: false
                        });
                }
            }
            showYearSelection = columnDateSelect && !IsEmpty(columnDateFieldName);
            if (showYearSelection)
                useExtendedBasicSearch = true;

            // Disable show summary for last group
            groupFields[groupFields.length - 1].FldGroupByShowSummary = false;

            let smryFldNames = TABLE.CrosstabSummaryFields,
                smryFldTypes = TABLE.CrosstabSummaryTypes,
                arSmrys = smryFldNames.split("||"),
                arSmryTypes = smryFldTypes.split("||");

            summaryFields = arSmrys.map((name, i) => {
                return { name: name, type: arSmryTypes[i] };
            });
            hasSummaryFields = summaryFields.length > 0;
            if (!hasSummaryFields)
                throw new Error("No summary field defined");
        }
    }

    // Dashboard report
    global.isDashBoard = TABLE.TblReportType == "dashboard";

    // Report classes
    global.reportContainerClass = "container-fluid";
    global.reportTopContainerClass = "col-sm-12 ew-top";
    global.reportBottomContainerClass = "col-sm-12 ew-bottom";
    let leftClass = "", centerClass = "", rightClass = "",
        chartLeftOrRightColumnClass = PROJ.ChartLeftOrRightColumnClass || "col-sm-6",
        chartLeftAndRightColumnClass = PROJ.ChartLeftAndRightColumnClass || "col-sm-4";
    if (showReport) { // Show report
        if (chartsOnLeft && chartsOnRight) { // Charts on left and right
            leftClass = "col-sm-4"; centerClass = "col-sm-4"; rightClass = "col-sm-4";
            let match = chartLeftAndRightColumnClass.match(/^col\-(\w+)\-(\d+)$/);
            if (match) {
                leftClass = chartLeftAndRightColumnClass;
                centerClass = "col-" + match[1] + "-" + (12 - 2*parseInt(match[2], 10));
                rightClass = chartLeftAndRightColumnClass;
            }
        } else if (chartsOnLeft) { // Charts on left
            leftClass = "col-sm-6"; centerClass = "col-sm-6"; rightClass = "";
            let match = chartLeftOrRightColumnClass.match(/^col\-(\w+)\-(\d+)$/);
            if (match) {
                leftClass = chartLeftOrRightColumnClass;
                centerClass = "col-" + match[1] + "-" + (12 - parseInt(match[2], 10));
                rightClass = "";
            }
        } else if (chartsOnRight) { // Charts on right
            leftClass = ""; centerClass = "col-sm-6"; rightClass = "col-sm-6";
            let match = chartLeftOrRightColumnClass.match(/^col\-(\w+)\-(\d+)$/);
            if (match) {
                leftClass = "";
                centerClass = "col-" + match[1] + "-" + (12 - parseInt(match[2], 10));
                rightClass = chartLeftOrRightColumnClass;
            }
        } else { // No charts
            leftClass = ""; centerClass = "col-sm-12"; rightClass = "";
        }
    } else { // No report, charts only
        if (chartsOnLeft && chartsOnRight) { // Charts on left and right
            leftClass = "col-sm-6"; centerClass = ""; rightClass = "col-sm-6";
        } else if (chartsOnLeft) { // Charts on left
            leftClass = "col-sm-12"; centerClass = ""; rightClass = "";
        } else if (chartsOnRight) { // Charts on right
            leftClass = ""; centerClass = ""; rightClass = "col-sm-12";
        } else { // No charts
            leftClass = ""; centerClass = "col-sm-12"; rightClass = "";
        }
    }
    global.reportLeftContainerClass = (leftClass + " ew-left").trim();
    global.reportCenterContainerClass = (centerClass + " ew-center").trim();
    global.reportRightContainerClass = (rightClass + " ew-right").trim();
    global.reportTopContentClass = Code.getName(pageObj, "TopContentClass");
    global.reportLeftContentClass = Code.getName(pageObj, "LeftContentClass");
    global.reportCenterContentClass = Code.getName(pageObj, "CenterContentClass");
    global.reportRightContentClass = Code.getName(pageObj, "RightContentClass");
    global.reportBottomContentClass = Code.getName(pageObj, "BottomContentClass");

    global.reportPageBreakRecordCount = TABLE.TblExportPageBreakCount || 0;

    global.reportPagerExportStart = `<?php if (!${isExport} && !($${pageObj}->DrillDown && $${pageObj}->TotalGroups > 0)) { ?>`;
    global.reportPagerExportEnd = Code.end;

    // Export
    global.reportExportStart = `<?php if (!${isExport} && !$${pageObj}->DrillDown && !$DashboardReport) { ?>`;
    global.reportExportEnd = Code.end;

    // Search
    global.reportSearchStart = reportExportStart;
    global.reportSearchEnd = Code.end;

    global.showReportContainerStart = `<?php if ((!${isExport} || ${isExportPrint}) && !$DashboardReport) { ?>`;
    global.showReportContainerEnd = Code.end;
    global.skipPdfExportStart = "";
    global.skipPdfExportEnd = "";
    if (exportPdf) {
        skipPdfExportStart = `<?php if (!${isExportPdf}) { ?>`;
        skipPdfExportEnd = Code.end;
    }

    // Report has drilldown fields
    hasDrillDownFields = hasDrillDownFields || allFields.some(f => IsFieldDrillDown(f));

    // Parameter Fields variables
    global.parmFields = allFields.filter(f => f.FldDrillParameter).slice(); // List of parameter field names

    // Common names
    global.startGrp = Code.getName(pageObj, Code.StartGroup);
    global.stopGrp = Code.getName(pageObj, Code.StopGroup);
    global.totalGrps = Code.getName(pageObj, Code.TotalGroups);
    global.displayGrps = Code.getName(pageObj, Code.DisplayGroups);
    global.grpCount = Code.getName(pageObj, Code.GroupCount);
    global.showHeader = Code.getName(pageObj, Code.ShowHeader);
    global.recIndex = Code.getName(pageObj, Code.RecordIndex);
    global.pageBreakContent = Code.getName(pageObj, Code.PageBreakContent);
#>