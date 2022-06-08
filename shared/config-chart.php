<#
    let chartTable = TABLE.TblReportType == "dashboard" ? SOURCETABLE : TABLE;
    global.chartTblVar = chartTable.TblVar;
    global.chartVar = CHART.ChartVar;
    global.chartClickUrl = "#";
    global.chartClickCaption = "";
    global.hasSourceReport = chartTable.TblGen;
    global.customViewSrcTable = false;
    global.chartDivName = chartTblVar + "_" + chartVar;

    // Current chart object = CHART
    global.chartXFldName = CHART.ChartXFldName; // Chart X-axis Field Name
    global.chartYFldName = "";
    global.chartYFldNameList = CHART.ChartYFldName.trim().replace(/\|$/, ""); // Chart Y-axis Field Names (separated by |)
    global.chartXFldSql = "";
    global.arChartYFlds = [];
    global.chartYFldSql = "";
    if (!IsEmpty(chartYFldNameList)) {
        arChartYFlds = chartYFldNameList.split("|");
        chartYFldName = arChartYFlds[0];
    }
    global.chartSeriesRenderAs = CHART.ChartSeriesRenderAs; // Chart series renderAs
    global.chartYAxis = "1";
    global.chartYAxisList = CHART.ChartSeriesYAxis.trim().replace(/,$/, ""); // Chart Y-axis (comma separated)
    global.arChartYAxis = [];
    if (!IsEmpty(chartYAxisList)) {
        arChartYAxis = chartYAxisList.split(",");
        chartYAxis = arChartYAxis[0];
    }
    global.chartNFldName = CHART.ChartNameFldName; // Chart name field (Candlestick only, not supported)
    global.chartSFldName = CHART.ChartSeriesFldName; // Chart Series Field Name
    global.chartSFldSqlOrder = CHART.ChartSeriesFldOrder; // Series Field Order
    global.chartSFldSql = "";

    // Chart variables
    global.chartName = CHART.ChartName;
    global.chartObj = "this->" + chartVar;
    global.chartType = CHART.ChartType;

    // Chart series type
    global.chartSeriesType = CHART.ChartSeriesType;
    global.chartSummaryType = "SUM";
    global.chartSummaryTypeList = CHART.ChartSummaryType.trim().replace(/,$/, "");
    global.arChartSummaryType = [];
    if (!IsEmpty(chartSummaryTypeList)) {
        arChartSummaryType = chartSummaryTypeList.split(",");
        chartSummaryType = arChartSummaryType[0];
    }

    // Check chart type
    if (IsEmpty(chartType) || isNaN(chartType))
        chartType = 1001; // Default column2d
    if (String(chartType).startsWith("1")) { // Clear Series field if single series chart
        chartSFldName = "";
        chartSeriesType = 0;
    } else if (IsEmpty(chartSFldName) && arChartYFlds.length <= 1) { // Degrade if not multi-series
        chartType = parseInt("1" + String(chartType).substr(1, 1) + "0" + String(chartType).substr(3, 1)); // Downgrade to Single Series "1b0n"
        chartSeriesType = 0;
    } else if (chartType == 5099 && arChartYFlds.length != 4) { // Degrade Candlestick to Line 2D if not 4 Y fields
        chartType = 1002;
    }
    if (chartSeriesType == 1) { // Multi-column, set series field = Y fields
        chartSFldName = chartYFldNameList;
        chartSFldSqlOrder = "";
    } else if (!IsEmpty(chartSFldName) && chartSeriesType == 0) { // Series field, use single Y field
        arChartYFlds = [chartYFldName];
    }

    // Chart width
    global.chartWidth = parseInt(CHART.ChartWidth);
    if (chartWidth <= 0)
        chartWidth = DefaultChartSize.width;

    // Chart height
    global.chartHeight = parseInt(CHART.ChartHeight);
    if (chartHeight <= 0)
        chartHeight = DefaultChartSize.height;

    // Chart bg color
    global.chartBgColor = CHART.ChartBgColor;

    // Chart caption
    global.chartCaption = CHART.ChartCaption;

    // Chart X Axis Name
    global.chartXAxisName = CHART.ChartXAxisName;

    // Chart Y Axis Name
    global.chartYDefaultDecimalPrecision = -1;
    global.chartPYAxisName = "";
    global.chartSYAxisName = "";
    global.chartYAxisName = "";
    if (String(chartType).startsWith("4")) { // Combination
        let p1 = -1, p2 = -1;
        chartPYAxisName = CHART.ChartPYAxisName;
        if (!IsEmpty(chartPYAxisName)) {
            let yfld = GetFieldObject(chartTable, chartPYAxisName);
            if (yfld && (yfld.FldFmtType == "Currency" || yfld.FldFmtType == "Number"))
                p1 = yfld.FldNumDigits;
        }
        chartSYAxisName = CHART.ChartSYAxisName;
        if (!IsEmpty(chartSYAxisName)) {
            let yfld = GetFieldObject(chartTable, chartSYAxisName);
            if (yfld && (yfld.FldFmtType == "Currency" || yfld.FldFmtType == "Number"))
                p2 = yfld.FldNumDigits;
        }
        if (p1 == p2 && p1 > -1)
            chartYDefaultDecimalPrecision = p1;
    } else {
        chartYAxisName = CHART.ChartYAxisName;
        if (!IsEmpty(chartYAxisName)) {
            let yfld = GetFieldObject(chartTable, chartYAxisName);
            if (yfld && (yfld.FldFmtType == "Currency" || yfld.FldFmtType == "Number"))
                chartYDefaultDecimalPrecision = yfld.FldNumDigits;
        }
    }

    global.chartYAxisMinValue = CHART.ChartYAxisMinValue;
    global.chartYAxisMaxValue = CHART.ChartYAxisMaxValue;

    // Chart show names
    global.chartShowNames = CHART.ChartShowNames ? 1 : 0;

    // Chart show values
    global.chartShowValues = CHART.ChartShowValues ? 1 : 0;

    // Chart show hover
    global.chartShowHover = CHART.ChartShowHover ? 1 : 0;

    // Chart alpha
    global.chartAlpha = CHART.ChartAlpha || 50;
    if (chartAlpha < 0 || chartAlpha > 100)
        chartAlpha = 50; // Default alpha

    // Chart color palette
    global.chartColorPalette = CHART.ChartColorPalette || PROJ.ChartColorPalette || "";

    global.chartSortType = CHART.ChartSortType;
    if (IsEmpty(chartSortType))
        chartSortType = 0; // Default no sort
    global.chartXFldSqlOrder = "";
    if (chartSortType == 1) {
        chartXFldSqlOrder = "ASC";
    } else if (chartSortType == 2) {
        chartXFldSqlOrder = "DESC";
    }

    global.chartSortSeq = CHART.ChartSortSeq.trim();
    if (!IsArrayString(chartSortSeq))
        chartSortSeq = `"${Quote(chartSortSeq)}"`;

    let fldSql;
    global.xAxisDateFormat = "";
    global.nameDateFormat = "";
    global.chartFldSql = "";
    global.chartFldSqlOrderBy = "";
    global.chartFldDateType = "";
    global.chartXDateFldType = "";
    global.chartXDateFldName = "";
    global.chartXDateFldCaption = "";

    if (chartType == 5099) { // Candlestick (not supported)

        if (!IsEmpty(chartXFldName)) {
            let chartXField = GetFieldObject(chartTable, chartXFldName);
            if (GetFieldType(chartXField.FldType) == 2) {
                xAxisDateFormat = chartXField.FldDtFormat;
            }
            if (customViewSrcTable) {
                fldSql = QuotedName(chartXField.FldName, tblDbId); // Use field name
            } else {
                fldSql = FieldSqlName(chartXField, tblDbId); // Get Chart X Field
            }
            chartFldSql = fldSql + ", ''";
            if (chartSortType == 2)
                chartFldSqlOrderBy = fldSql + " DESC";
            else
                chartFldSqlOrderBy = fldSql + " ASC";
        } else {
            chartFldSql = "'', ''";
            chartFldSqlOrderBy = "";
        }
        for (let fldName of arChartYFlds) {
            chartFldSql += ", ";
            if (IsEmpty(fldName)) {
                chartFldSql += "0";
            } else {
                let chartYField = GetFieldObject(chartTable, fldName);
                if (customViewSrcTable) {
                    fldSql = QuotedName(chartYField.FldName, tblDbId); // Use field name
                } else {
                    fldSql = FieldSqlName(chartYField, tblDbId); // Get Chart Y field
                }
                chartFldSql += fldSql;
            }
        }
        if (!IsEmpty(chartNFldName)) {
            let chartNField = GetFieldObject(chartTable, chartNFldName);
            if (GetFieldType(chartNField.FldType) == 2) {
                nameDateFormat = chartNField.FldDtFormat;
            }
            if (customViewSrcTable) {
                fldSql = QuotedName(chartNField.FldName, tblDbId); // Use field name
            } else {
                fldSql = FieldSqlName(chartNField, tblDbId); // Get Chart name field
            }
            chartFldSql += ", " + fldSql;
        }

    } else { // Non candle-stick

        let chartXField = GetFieldObject(chartTable, chartXFldName);
        if (GetFieldType(chartXField.FldType) == 2)
            xAxisDateFormat = chartXField.FldDtFormat;

        chartYFldSql = "";
        arChartYFlds.forEach((fldName, j) => {
            if (j > 0)
                chartYFldSql += ", ";
            if (IsEmpty(fldName)) {
                fldSql = "0";
            } else {
                let chartYField = GetFieldObject(chartTable, fldName);
                if (customViewSrcTable) {
                    fldSql = QuotedName(chartYField.FldName, tblDbId); // Use field name
                } else {
                    fldSql = FieldSqlName(chartYField, tblDbId); // Get Chart Y Field
                }
                if (!IsAggregateSql(fldSql)) {
                    let chartSmryType = chartSummaryType;
                    if (j <= arChartSummaryType.length - 1)
                        chartSmryType = arChartSummaryType[j];
                    if (chartYField.FldRptSkipNull && GetFieldType(chartYField.FldType) == 1)
                        fldSql = NullIfFunction(fldSql, tblDbId);
                    if (!IsEmpty(chartSmryType))
                        fldSql = chartSmryType + "(" + fldSql + ")";
                }
            }
            chartYFldSql += fldSql;
        });

        if (chartTable.TblReportType == "crosstab" && columnField && chartXFldName == columnField.FldName) { // Handle date type if equal to column field
            chartXDateFldType = columnDateType;
            chartXDateFldName = columnDateFieldName;
            chartXDateFldCaption = ["y", "q", "m"].includes(columnDateType) ? "Year" : "";
            if (customViewSrcTable) {
                chartXFldSql = QuotedName(columnField.FldName, tblDbId);
            } else {
                chartXFldSql = FieldSqlName(columnField, tblDbId);
            }
            if (columnDateType == "y") {
                xAxisDateFormat = `"y"`;
                chartXFldSql = DbGroupSql("y", 0, tblDbId).replace(/%s/g, chartXFldSql);
            } else if (columnDateType == "q") {
                if (columnDateSelect) {
                    chartFldDateType = "xq";
                    chartXFldSql = DbGroupSql("xq", 0, tblDbId).replace(/%s/g, chartXFldSql);
                } else {
                    chartFldDateType = "xyq";
                    chartXFldSql = DbGroupSql("q", 0, tblDbId).replace(/%s/g, chartXFldSql);
                }
            } else if (columnDateType == "m") {
                if (columnDateSelect) {
                    chartFldDateType = "xm";
                    chartXFldSql = DbGroupSql("xm", 0, tblDbId).replace(/%s/g, chartXFldSql);
                } else {
                    chartFldDateType = "xym";
                    chartXFldSql = DbGroupSql("m", 0, tblDbId).replace(/%s/g, chartXFldSql);
                }
            }
        } else if (!IsEmpty(chartXFldName)) {
            if (customViewSrcTable) {
                chartXFldSql = QuotedName(chartXField.FldName, tblDbId);
            } else {
                chartXFldSql = FieldSqlName(chartXField, tblDbId);
            }
        }

        if (!IsEmpty(chartSFldName) && chartSeriesType == 0) {
            let chartSFld = GetFieldObject(chartTable, chartSFldName);
            if (customViewSrcTable) {
                chartSFldSql = QuotedName(chartSFld.FldName, tblDbId);
            } else {
                chartSFldSql = FieldSqlName(chartSFld, tblDbId);
            }
            if (chartTable.TblReportType == "crosstab" && columnField && chartSFldName == columnField.FldName) { // Handle date type if equal to column field
                if (columnDateType == "y") {
                    chartSFldSql = DbGroupSql("y", 0, tblDbId).replace(/%s/g, chartSFldSql);
                } else if (columnDateType == "q") {
                    if (columnDateSelect) {
                        chartFldDateType = "sq";
                        chartSFldSql = DbGroupSql("xq", 0, tblDbId).replace(/%s/g, chartSFldSql);
                    } else {
                        chartFldDateType = "syq";
                        chartSFldSql = DbGroupSql("q", 0, tblDbId).replace(/%s/g, chartSFldSql);
                    }
                } else if (columnDateType == "m") {
                    if (columnDateSelect) {
                        chartFldDateType = "sm";
                        chartSFldSql = DbGroupSql("xm", 0, tblDbId).replace(/%s/g, chartSFldSql);
                    } else {
                        chartFldDateType = "sym";
                        chartSFldSql = DbGroupSql("m", 0, tblDbId).replace(/%s/g, chartSFldSql);
                    }
                }
            }
        }

    }

    global.chartXFldSqlOrderBy = "";
    global.chartSFldSqlOrderBy = "";
    if (chartSortType == 5) {
        chartXFldSqlOrderBy = chartXFldSql;
    } else if (!IsEmpty(chartXFldSqlOrder)) {
        chartXFldSqlOrderBy = chartXFldSql + " " + chartXFldSqlOrder;
    }
    if (!IsEmpty(chartSFldSqlOrder)) {
        chartSFldSqlOrderBy = chartSFldSql + " " + chartSFldSqlOrder;
    }
    if (!IsEmpty(chartSFldSql)) {
        if (!IsEmpty(chartXFldSqlOrderBy) || !IsEmpty(chartSFldSqlOrderBy)) {
            if (IsEmpty(chartXFldSqlOrderBy))
                chartXFldSqlOrderBy = chartXFldSql;
            if (IsEmpty(chartSFldSqlOrderBy))
                chartSFldSqlOrderBy = chartSFldSql;
            chartXFldSqlOrderBy += ", " + chartSFldSqlOrderBy;
        }
    }
#>
