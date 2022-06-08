<## config chart #>
<#= include('./config-chart.php') #>
<#
    let extName = "Chartjs",
        extChart = GetExtensionChart(extName, TABLE.TblName, CHART.ChartName);
#>
        // <#= chartName #>
        $<#= chartObj #> = new DbChart($this, '<#= chartVar #>', '<#= SingleQuote(chartName) #>', '<#= SingleQuote(chartXFldName) #>', '<#= SingleQuote(chartYFldName) #>', <#= chartType #>, '<#= SingleQuote(chartSFldName) #>', <#= chartSeriesType #>, '<#= chartSummaryType #>', <#= chartWidth #>, <#= chartHeight #>);
<# if (chartSortType == 5) { #>
        $<#= chartObj #>->RunTimeSort = true;
<# } #>
<# if (!IsEmpty(chartSeriesRenderAs)) { #>
        $<#= chartObj #>->SeriesRenderAs = '<#= SingleQuote(chartSeriesRenderAs) #>';
<# } #>
<# if (!IsEmpty(chartYAxisList)) { #>
        $<#= chartObj #>->SeriesYAxis = '<#= SingleQuote(chartYAxisList) #>';
<# } #>
        $<#= chartObj #>->SortType = <#= (chartSortType > 4 ? 0 : chartSortType) #>;
        $<#= chartObj #>->SortSequence = <#= chartSortSeq #>;
<# if (UseCustomTemplate) { #>
        $<#= chartObj #>->IsCustomTemplate = true;
<# } #>
<# if (chartType == 5099) { // Candlestick (not supported) #>
        $<#= chartObj #>->SqlSelect = $this->getQueryBuilder()->select("<#= Code.quote(chartFldSql) #>");
        $<#= chartObj #>->SqlGroupBy = "";
        $<#= chartObj #>->SqlOrderBy = "<#= Code.quote(chartFldSqlOrderBy) #>";
        $<#= chartObj #>->SeriesDateType = "";
<# } else if (!IsEmpty(chartSFldSql)) { #>
        $<#= chartObj #>->SqlSelect = $this->getQueryBuilder()->select("<#= Code.quote(chartXFldSql) #>", "<#= Code.quote(chartSFldSql) #>", "<#= Code.quote(chartYFldSql) #>");
        $<#= chartObj #>->SqlGroupBy = "<#= Code.quote(chartXFldSql) #>, <#= Code.quote(chartSFldSql) #>";
        $<#= chartObj #>->SqlOrderBy = "<#= Code.quote(chartXFldSqlOrderBy) #>";
        $<#= chartObj #>->SeriesDateType = "<#= chartFldDateType #>";
        $<#= chartObj #>->SqlSelectSeries = $this->getQueryBuilder()->select("<#= Code.quote(chartSFldSql) #>")->distinct();
        $<#= chartObj #>->SqlGroupBySeries = "<#= Code.quote(chartSFldSql) #>";
        $<#= chartObj #>->SqlOrderBySeries = "<#= Code.quote(chartSFldSqlOrderBy) #>";
<# } else { #>
        $<#= chartObj #>->SqlSelect = $this->getQueryBuilder()->select("<#= Code.quote(chartXFldSql) #>", "''", "<#= Code.quote(chartYFldSql) #>");
        $<#= chartObj #>->SqlGroupBy = "<#= Code.quote(chartXFldSql) #>";
        $<#= chartObj #>->SqlOrderBy = "<#= Code.quote(chartXFldSqlOrderBy) #>";
        $<#= chartObj #>->SeriesDateType = "<#= chartFldDateType #>";
<# } #>
<# if (!IsEmpty(xAxisDateFormat)) { #>
        $<#= chartObj #>->XAxisDateFormat = <#= xAxisDateFormat #>;
<# } #>
<# if (!IsEmpty(nameDateFormat)) { #>
        $<#= chartObj #>->NameDateFormat = <#= nameDateFormat #>;
<# } #>
<#
    let drillDownUrl = ChartDrillDownUrl(CHART);
    if (drillDownUrl != '""') {
#>
        $<#= chartObj #>->DrillDownTable = "<#= Quote(CHART.ChartDrillTable) #>";
        $<#= chartObj #>->DrillDownUrl = <#= drillDownUrl #>;
<#
    }
    if (chartYDefaultDecimalPrecision > -1) {
#>
        $<#= chartObj #>->DefaultDecimalPrecision = <#= chartYDefaultDecimalPrecision #>;
<#
    }
#>

        $<#= chartObj #>->ID = "<#= chartTblVar #>_<#= chartVar #>"; // Chart ID
        $<#= chartObj #>->setParameters([
            ["type", "<#= chartType #>"],
            ["seriestype", "<#= chartSeriesType #>"]
        ]); // Chart type / Chart series type
<# if (!IsEmpty(chartBgColor)) { #>
        $<#= chartObj #>->setParameter("bgcolor", "<#= chartBgColor #>"); // Background color
<# } #>
        $<#= chartObj #>->setParameters([
            ["caption", $<#= chartObj #>->caption()],
            ["xaxisname", $<#= chartObj #>->xAxisName()]
        ]); // Chart caption / X axis name
<# if (["4031", "4131", "4141", "4092"].includes(String(chartType))) { // Dual Y Axis Charts #>
        $<#= chartObj #>->setParameters([
            ["PYAxisName", $<#= chartObj #>->primaryYAxisName()],
            ["SYAxisName", $<#= chartObj #>->secondaryYAxisName()]
        ]); // Primary Y axis name / Secondary Y axis name
<# } else { #>
        $<#= chartObj #>->setParameter("yaxisname", $<#= chartObj #>->yAxisName()); // Y axis name
<# } #>
        $<#= chartObj #>->setParameters([
            ["shownames", "<#= chartShowNames #>"],
            ["showvalues", "<#= chartShowValues #>"],
            ["showhovercap", "<#= chartShowHover #>"]
        ]); // Show names / Show values / Show hover
<# if (chartAlpha > 0) { #>
        $<#= chartObj #>->setParameter("alpha", "<#= chartAlpha #>"); // Chart alpha
<# } #>
<# if (chartColorPalette) { #>
        $<#= chartObj #>->setParameter("colorpalette", "<#= chartColorPalette #>"); // Chart color palette
<# } #>

<#
let annotations = [];
if (extChart && extChart.Properties) {
    let parms = [],
        obj = {};
    for (let name in extChart.Properties) {
        let value = extChart[name];
        if (!IsEmpty(name) && !IsEmpty(value)) {
            if (["ChartSeq", "ChartName"].includes(name)) {
                // Skip
            } else if (name.startsWith("annotation")) {
                obj[name] = value;
            } else if (name == "dataset.borderDash") { // For Chart.js
                value = (value.startsWith("[") && value.endsWith("]")) ? ParseJson(value) : [];
            } else if (name == "dataset.borderSkipped") { // For Chart.js
                value = ["bottom", "left", "top", "right"].includes(value) ? value : false;
            }
            parms.push([name, value]);
        }
    }
    let i = 1,
        prps = Object.keys(obj).filter(key => key.startsWith("annotation" + i + "."));
    while (prps.length) {
        let annotation = {};
        for (let prp of prps) {
            let value = extChart[prp], name = prp.split(".")[1];
            annotation[name] = value;
        }
        annotations.push(annotation);
        i++;
        prps = Object.keys(obj).filter(key => key.startsWith("annotation" + i + "."));
    }
    if (parms.length > 0) {
#>
        $<#= chartObj #>->setParameters(<#= JSON.stringify(parms) #>);
<#
    }
}

// Define trend lines
for (let annotation of annotations) {
    if (annotation.show && annotation.show == "1") {
        let startValue = annotation.startValue,
            endValue = annotation.endValue,
            color = annotation.color,
            dispValue = annotation.displayValue,
            thickness = annotation.borderWidth,
            alpha = annotation.alpha,
            parentYAxis = "";
        if (IsEmpty(startValue))
            startValue = 0;
        if (IsEmpty(endValue))
            endValue = 0;
        if (IsEmpty(thickness))
            thickness = 1;
        if (IsEmpty(alpha))
            alpha = 0;
        if (String(CHART.ChartType).startsWith("4")) // Combination Chart
            parentYAxis = annotation.secondaryYAxis ? "S" : "P";
#>
        $this-><#= chartVar #>->Trends[] = [<#= startValue #>, <#= endValue #>, "<#= color #>", "<#= Quote(dispValue) #>", <#= thickness #>, <#= alpha #>, "<#= Quote(parentYAxis) #>"];
<#
    }
} // End for trend
#>
