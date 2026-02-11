import jspreadsheet from "jspreadsheet-ce";
import "jspreadsheet-ce/dist/jspreadsheet.css";
import "jsuites/dist/jsuites.css";

/**
 * Survey Spreadsheet Module for Presales Rekap Survey
 * Uses jspreadsheet-ce v5 for Excel-like editing experience
 *
 * Template Structure:
 * - Multiple areas, each with:
 *   - Nama Area (title)
 *   - Header Groups (Lokasi, Dimensi, Kabel, etc.) with sub-columns
 *   - Data rows
 *   - Total Kebutuhan (auto-calculated totals row)
 *
 * Formula Support:
 * - Dynamic formulas loaded from server
 * - Auto-calculation when dependent columns change
 * - Formula columns are highlighted and auto-calculated
 */

class SurveySpreadsheet {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.rekapId = options.rekapId;
        this.csrfToken = options.csrfToken;
        this.baseUrl = options.baseUrl || "";
        this.version = options.version !== undefined ? options.version : null;

        // Multi-area support - each area has its own spreadsheet
        this.areas = [];
        this.areaCounter = 0;

        // Comments storage: { areaId: { 'row,col': 'comment text' } }
        this.comments = {};

        // Satuan storage: { areaId: { 'columnKey': satuanId } }
        this.satuans = {};

        // Available satuans list - can be passed from options or loaded via API
        this.satuanList =
            options.satuans && Array.isArray(options.satuans)
                ? options.satuans
                : [];
        console.log(
            "📦 SurveySpreadsheet initialized with satuans:",
            this.satuanList,
        );

        // Formula support
        this.formulas = []; // Array of formula configurations from server
        this.formulasByColumn = {}; // Formulas indexed by target column_key
        this.columnKeyToIndex = {}; // Maps column_key to column index for each area

        // Colors for header groups
        this.groupColors = {
            lokasi: "#3b82f6",
            dimensi: "#8b5cf6",
            kabel: "#22c55e",
            pipa: "#f59e0b",
            box: "#06b6d4",
            accessories: "#ec4899",
            default: "#6b7280",
        };

        // Inject styles
        this.injectStyles();

        // Create comment tooltip element
        this.createCommentTooltip();
    }

    // Create floating tooltip for showing comments
    createCommentTooltip() {
        const tooltip = document.createElement("div");
        tooltip.id = "survey-comment-tooltip";
        tooltip.style.cssText = `
            position: fixed;
            background: #fffde7;
            border: 1px solid #fbc02d;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 12px;
            max-width: 250px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 10000;
            display: none;
            pointer-events: none;
            word-wrap: break-word;
        `;
        document.body.appendChild(tooltip);
        this.commentTooltip = tooltip;

        // Create comment modal
        this.createCommentModal();
    }

    // Create comment modal for add/edit/view comments
    createCommentModal() {
        // Remove existing modal if any
        const existingModal = document.getElementById("surveyCommentModal");
        if (existingModal) existingModal.remove();

        const modal = document.createElement("div");
        modal.id = "surveyCommentModal";
        modal.className =
            "fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50";
        modal.style.display = "none";
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-4 shadow-xl">
                <h2 id="surveyCommentModalTitle" class="text-lg font-bold mb-4 text-gray-800">Insert Comment</h2>
                <textarea id="surveyCommentModalTextarea" 
                    class="w-full border border-gray-300 rounded p-3 mb-4 resize-y focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    rows="6" 
                    placeholder="Enter your comment here..."></textarea>
                <div id="surveyCommentModalButtons" class="flex justify-end gap-2">
                    <button id="surveyCommentModalCancel" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                        Batal
                    </button>
                    <button id="surveyCommentModalOk" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        OK
                    </button>
                </div>
                <div id="surveyCommentModalCloseOnly" class="hidden flex justify-end">
                    <button id="surveyCommentModalClose" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Store modal reference
        this.commentModal = modal;
        this.commentModalCallback = null;

        // Bind events
        const cancelBtn = document.getElementById("surveyCommentModalCancel");
        const okBtn = document.getElementById("surveyCommentModalOk");
        const closeBtn = document.getElementById("surveyCommentModalClose");
        const textarea = document.getElementById("surveyCommentModalTextarea");

        cancelBtn.addEventListener("click", () => this.closeCommentModal());
        okBtn.addEventListener("click", () => {
            const value = textarea.value;
            if (this.commentModalCallback) {
                this.commentModalCallback(value);
            }
            this.closeCommentModal();
        });
        closeBtn.addEventListener("click", () => this.closeCommentModal());

        // Close on backdrop click
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                this.closeCommentModal();
            }
        });

        // Keyboard shortcuts
        textarea.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                e.preventDefault();
                this.closeCommentModal();
            } else if (e.key === "Enter" && e.ctrlKey) {
                e.preventDefault();
                okBtn.click();
            }
        });
    }

    // Open comment modal for add/edit
    openCommentModal(title, initialValue, callback) {
        const modal = document.getElementById("surveyCommentModal");
        const titleEl = document.getElementById("surveyCommentModalTitle");
        const textarea = document.getElementById("surveyCommentModalTextarea");
        const buttonsDiv = document.getElementById("surveyCommentModalButtons");
        const closeOnlyDiv = document.getElementById(
            "surveyCommentModalCloseOnly",
        );

        titleEl.textContent = title;
        textarea.value = initialValue || "";
        textarea.readOnly = false;
        textarea.classList.remove("bg-gray-100");
        buttonsDiv.classList.remove("hidden");
        closeOnlyDiv.classList.add("hidden");

        this.commentModalCallback = callback;

        modal.style.display = "flex";
        modal.classList.remove("hidden");
        setTimeout(() => textarea.focus(), 100);
    }

    // View comment modal (read-only)
    viewCommentModal(comment) {
        const modal = document.getElementById("surveyCommentModal");
        const titleEl = document.getElementById("surveyCommentModalTitle");
        const textarea = document.getElementById("surveyCommentModalTextarea");
        const buttonsDiv = document.getElementById("surveyCommentModalButtons");
        const closeOnlyDiv = document.getElementById(
            "surveyCommentModalCloseOnly",
        );

        titleEl.textContent = "View Comment";
        textarea.value = comment || "";
        textarea.readOnly = true;
        textarea.classList.add("bg-gray-100");
        buttonsDiv.classList.add("hidden");
        closeOnlyDiv.classList.remove("hidden");
        closeOnlyDiv.style.display = "flex";

        this.commentModalCallback = null;

        modal.style.display = "flex";
        modal.classList.remove("hidden");
    }

    // Close comment modal
    closeCommentModal() {
        const modal = document.getElementById("surveyCommentModal");
        const textarea = document.getElementById("surveyCommentModalTextarea");

        modal.style.display = "none";
        modal.classList.add("hidden");
        textarea.value = "";
        this.commentModalCallback = null;
    }

    // Get comment for a cell
    getComment(areaId, row, col) {
        const key = `${row},${col}`;
        return this.comments[areaId]?.[key] || null;
    }

    // Set comment for a cell
    setComment(areaId, row, col, comment) {
        if (!this.comments[areaId]) {
            this.comments[areaId] = {};
        }
        const key = `${row},${col}`;
        if (comment && comment.trim()) {
            this.comments[areaId][key] = comment.trim();
        } else {
            delete this.comments[areaId][key];
        }
        this.updateCommentIndicators(areaId);
    }

    // Delete comment for a cell
    deleteComment(areaId, row, col) {
        const key = `${row},${col}`;
        if (this.comments[areaId]) {
            delete this.comments[areaId][key];
        }
        this.updateCommentIndicators(areaId);
    }

    // Update visual indicators for cells with comments
    updateCommentIndicators(areaId) {
        const area = this.areas.find((a) => a.id === areaId);
        if (!area || !area.container) return;

        const cells = area.container.querySelectorAll("tbody td");
        cells.forEach((cell) => {
            // Remove existing indicator
            const existingIndicator = cell.querySelector(".comment-indicator");
            if (existingIndicator) existingIndicator.remove();

            // Get cell row/col
            const row = cell.dataset.y;
            const col = cell.dataset.x;
            if (row === undefined || col === undefined) return;

            const comment = this.getComment(
                areaId,
                parseInt(row),
                parseInt(col),
            );
            if (comment) {
                // Add red triangle indicator
                const indicator = document.createElement("div");
                indicator.className = "comment-indicator";
                indicator.style.cssText = `
                    position: absolute;
                    top: 0;
                    right: 0;
                    width: 0;
                    height: 0;
                    border-left: 8px solid transparent;
                    border-top: 8px solid #dc2626;
                    pointer-events: none;
                `;
                cell.style.position = "relative";
                cell.appendChild(indicator);
            }
        });
    }

    // =====================================================
    // FORMULA SUPPORT METHODS
    // =====================================================

    // Load formulas from the server API
    async loadFormulas() {
        try {
            const response = await fetch(`${this.baseUrl}/survey-formulas/api`);
            const result = await response.json();
            if (result.success && result.formulas) {
                this.formulas = result.formulas;
                // Index formulas by column_key for quick lookup
                this.formulasByColumn = {};
                this.formulas.forEach((f) => {
                    this.formulasByColumn[f.column_key] = f;
                });
                console.log("Loaded formulas:", this.formulas);
            }
        } catch (err) {
            console.warn("Could not load formulas:", err);
            this.formulas = [];
            this.formulasByColumn = {};
        }
    }

    // Load satuans from the server API
    async loadSatuans() {
        try {
            const url = `${this.baseUrl}/api/satuans`;
            console.log("📦 Fetching satuans from:", url);
            const response = await fetch(url);
            console.log("📦 Response status:", response.status);

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const result = await response.json();
            console.log("📦 Satuans response:", result);

            if (result.success && result.data) {
                this.satuanList = Array.isArray(result.data) ? result.data : [];
                console.log(
                    `✅ Loaded ${this.satuanList.length} satuans:`,
                    this.satuanList,
                );
            } else if (Array.isArray(result)) {
                // Handle direct array response
                this.satuanList = result;
                console.log(
                    `✅ Loaded ${this.satuanList.length} satuans (direct array):`,
                    this.satuanList,
                );
            } else {
                console.warn("⚠️ Unexpected satuans response format:", result);
                this.satuanList = [];
            }
        } catch (err) {
            console.error("❌ Failed to load satuans:", err);
            this.satuanList = [];
        }
    }

    // Get satuan for a column
    getSatuan(areaId, columnKey) {
        return this.satuans[areaId]?.[columnKey] || null;
    }

    // Set satuan for a column
    setSatuan(areaId, columnKey, satuanId) {
        if (!this.satuans[areaId]) {
            this.satuans[areaId] = {};
        }
        this.satuans[areaId][columnKey] = satuanId;
    }

    // Build column key to index mapping for an area
    buildColumnKeyMap(area) {
        const map = {};
        let colIdx = 0;
        area.headers.forEach((group) => {
            group.columns.forEach((col) => {
                map[col.key] = colIdx;
                colIdx++;
            });
        });
        return map;
    }

    // Get column key from index for an area
    getColumnKeyFromIndex(area, colIndex) {
        let idx = 0;
        for (const group of area.headers) {
            for (const col of group.columns) {
                if (idx === colIndex) {
                    return col.key;
                }
                idx++;
            }
        }
        return null;
    }

    // Check if a column has a formula
    hasFormula(columnKey) {
        return !!this.formulasByColumn[columnKey];
    }

    // Get formulas that depend on a given column
    getDependentFormulas(columnKey) {
        return this.formulas.filter(
            (f) => f.dependencies && f.dependencies.includes(columnKey),
        );
    }

    // Evaluate a formula expression with given row values
    evaluateFormula(formula, rowValues) {
        try {
            let expression = formula.formula;

            // Replace column keys with their values
            for (const [key, value] of Object.entries(rowValues)) {
                const numValue = parseFloat(value) || 0;
                // Use word boundary to prevent partial replacements
                expression = expression.replace(
                    new RegExp("\\b" + key + "\\b", "g"),
                    numValue,
                );
            }

            // Safety check - only allow numbers, operators, and parentheses
            if (!/^[\d\s\+\-\*\/\(\)\.]+$/.test(expression)) {
                console.warn(
                    "Formula contains invalid characters after substitution:",
                    expression,
                );
                return 0;
            }

            // Evaluate the expression
            const result = Function(
                '"use strict"; return (' + expression + ")",
            )();
            return isNaN(result) || !isFinite(result) ? 0 : result;
        } catch (err) {
            console.error("Error evaluating formula:", err, formula);
            return 0;
        }
    }

    // Apply all formulas to a specific row in an area
    applyFormulasToRow(area, rowIndex) {
        const keyMap = this.buildColumnKeyMap(area);
        const arrayData = this.getAreaData(area);
        if (!arrayData || !arrayData[rowIndex]) return;

        const row = arrayData[rowIndex];

        // Build row values object from current data
        const rowValues = {};
        Object.entries(keyMap).forEach(([key, idx]) => {
            rowValues[key] = row[idx] || 0;
        });

        // Apply formulas in order
        let changed = false;
        for (const formula of this.formulas) {
            if (keyMap[formula.column_key] !== undefined) {
                const targetIdx = keyMap[formula.column_key];
                const newValue = this.evaluateFormula(formula, rowValues);
                const roundedValue = Math.round(newValue * 100) / 100; // Round to 2 decimal places

                // Update the value if it changed
                if (parseFloat(row[targetIdx]) !== roundedValue) {
                    // Update in spreadsheet
                    if (
                        area.worksheet &&
                        typeof area.worksheet.setValueFromCoords === "function"
                    ) {
                        area.worksheet.setValueFromCoords(
                            targetIdx,
                            rowIndex,
                            roundedValue,
                            true,
                        );
                    } else if (
                        area.spreadsheetInstance &&
                        area.spreadsheetInstance[0]
                    ) {
                        area.spreadsheetInstance[0].setValueFromCoords(
                            targetIdx,
                            rowIndex,
                            roundedValue,
                            true,
                        );
                    }
                    // Update row values for cascading formulas
                    rowValues[formula.column_key] = roundedValue;
                    changed = true;
                }
            }
        }

        return changed;
    }

    // Apply formulas to all rows in an area
    applyFormulasToAllRows(area) {
        const arrayData = this.getAreaData(area);
        if (!arrayData) return;

        for (let i = 0; i < arrayData.length; i++) {
            this.applyFormulasToRow(area, i);
        }
    }

    // Handle cell change and apply dependent formulas
    handleCellChange(area, x, y, value) {
        const columnKey = this.getColumnKeyFromIndex(area, x);
        if (!columnKey) return;

        // Get formulas that depend on this column
        const dependentFormulas = this.getDependentFormulas(columnKey);

        if (dependentFormulas.length > 0) {
            // Apply formulas to this row
            // Use setTimeout to ensure the value is committed first
            setTimeout(() => {
                this.applyFormulasToRow(area, y);
            }, 10);
        }
    }

    // Style formula columns with a distinct background
    styleFormulaColumns(area) {
        if (this.formulas.length === 0) return;

        const keyMap = this.buildColumnKeyMap(area);
        const formulaColumns = new Set();

        // Find column indices that have formulas
        for (const formula of this.formulas) {
            if (keyMap[formula.column_key] !== undefined) {
                formulaColumns.add(keyMap[formula.column_key]);
            }
        }

        // Apply styling to formula column cells
        setTimeout(() => {
            const cells = area.container.querySelectorAll("tbody td");
            cells.forEach((cell) => {
                const col = parseInt(cell.dataset.x);
                if (formulaColumns.has(col)) {
                    cell.style.backgroundColor = "#f0fdf4"; // Light green
                    cell.style.fontStyle = "italic";
                }
            });

            // Style header cells too
            const headerCells = area.container.querySelectorAll(
                "thead tr:last-child td",
            );
            headerCells.forEach((cell, idx) => {
                // idx 0 is usually the row number column
                if (formulaColumns.has(idx - 1)) {
                    cell.title = "Formula column (auto-calculated)";
                }
            });
        }, 100);
    }

    // =====================================================
    // END FORMULA SUPPORT METHODS
    // =====================================================

    // Bind comment events to area cells (hover tooltip only, context menu is handled by jspreadsheet)
    bindCommentEvents(area) {
        const self = this;

        setTimeout(() => {
            const cells = area.container.querySelectorAll("tbody td");

            cells.forEach((cell) => {
                // Show tooltip on hover
                cell.addEventListener("mouseenter", function (e) {
                    const row = this.dataset.y;
                    const col = this.dataset.x;
                    if (row === undefined || col === undefined) return;

                    const comment = self.getComment(
                        area.id,
                        parseInt(row),
                        parseInt(col),
                    );
                    if (comment) {
                        self.commentTooltip.textContent = comment;
                        self.commentTooltip.style.display = "block";
                        self.commentTooltip.style.left = e.clientX + 10 + "px";
                        self.commentTooltip.style.top = e.clientY + 10 + "px";
                    }
                });

                cell.addEventListener("mousemove", function (e) {
                    if (self.commentTooltip.style.display === "block") {
                        self.commentTooltip.style.left = e.clientX + 10 + "px";
                        self.commentTooltip.style.top = e.clientY + 10 + "px";
                    }
                });

                cell.addEventListener("mouseleave", function () {
                    self.commentTooltip.style.display = "none";
                });
            });

            // Update indicators
            self.updateCommentIndicators(area.id);
        }, 200);
    }

    // Scroll to selected cell horizontally (similar to penawaran tab)
    scrollToSelectedCell(area, colIndex, rowIndex) {
        const scrollContainer = document.getElementById(
            `area-${area.id}-scroll`,
        );
        if (!scrollContainer) return;

        const table = area.container.querySelector("table");
        if (!table) return;

        // Get the cell at specified column index
        const cell = table.querySelector(`td[data-x="${colIndex}"]`);
        if (!cell) return;

        // Get cell position relative to table
        const cellLeft = cell.offsetLeft;
        const cellRight = cellLeft + cell.offsetWidth;

        // Get scroll container viewport
        const wrapperScrollLeft = scrollContainer.scrollLeft;
        const wrapperVisibleWidth = scrollContainer.clientWidth;

        // Padding for better visibility
        const padding = 50;

        // Scroll right if cell is beyond right edge
        if (cellRight > wrapperScrollLeft + wrapperVisibleWidth) {
            scrollContainer.scrollTo({
                left: cellRight - wrapperVisibleWidth + padding,
                behavior: "smooth",
            });
        }
        // Scroll left if cell is before left edge
        else if (cellLeft < wrapperScrollLeft) {
            scrollContainer.scrollTo({
                left: Math.max(0, cellLeft - padding),
                behavior: "smooth",
            });
        }
    }

    // Helper to generate consistent column key from title
    // Rules: lowercase, remove decimal point (.), other non-alphanumeric become underscore
    // Example: "UP 0.8" → "up_08", "NYY 3 X 1,5" → "nyy_3_x_1_5"
    generateColumnKey(title) {
        return title
            .toLowerCase()
            .replace(/\./g, "")
            .replace(/[^a-z0-9]/g, "_")
            .replace(/_+/g, "_")
            .replace(/^_|_$/g, "");
    }

    // Default headers template
    getDefaultHeaders() {
        return [
            {
                group: "Lokasi",
                color: "lokasi",
                columns: [
                    { key: "lantai", title: "Lantai", type: "text", width: 80 },
                    { key: "nama", title: "Nama", type: "text", width: 100 },
                    { key: "dari", title: "Dari", type: "text", width: 80 },
                    { key: "ke", title: "Ke", type: "text", width: 80 },
                ],
            },
            {
                group: "Dimensi",
                color: "dimensi",
                columns: [
                    {
                        key: "horizon",
                        title: "Horizon",
                        type: "numeric",
                        width: 80,
                    },
                    {
                        key: "vertical",
                        title: "Vertical",
                        type: "numeric",
                        width: 80,
                    },
                    {
                        key: "up_08",
                        title: "UP 0.8",
                        type: "numeric",
                        width: 80,
                    },
                ],
            },
            {
                group: "Kabel",
                color: "kabel",
                columns: [
                    { key: "utp", title: "UTP", type: "numeric", width: 80 },
                    {
                        key: "face_plate_1_hole",
                        title: "Face Plate 1 hole",
                        type: "numeric",
                        width: 100,
                    },
                    {
                        key: "modular_jack",
                        title: "modular jack",
                        type: "numeric",
                        width: 100,
                    },
                    {
                        key: "outbow",
                        title: "Outbow",
                        type: "numeric",
                        width: 80,
                    },
                    {
                        key: "patchcord_utp_1_meter",
                        title: "Patchcord UTP 1 meter",
                        type: "numeric",
                        width: 120,
                    },
                    {
                        key: "wiring_management",
                        title: "wiring management",
                        type: "numeric",
                        width: 120,
                    },
                    { key: "ap", title: "AP", type: "numeric", width: 80 },
                ],
            },
        ];
    }

    // Build columns from headers
    buildColumns(headers) {
        const columns = [];
        headers.forEach((group) => {
            group.columns.forEach((col) => {
                columns.push({
                    title: col.title,
                    width: col.width || 100,
                    type: col.type === "numeric" ? "numeric" : "text",
                    name: col.key,
                    align: "center",
                });
            });
        });
        return columns;
    }

    // Build nested headers for groups
    buildNestedHeaders(headers) {
        const nestedHeaders = [];
        headers.forEach((group) => {
            nestedHeaders.push({
                title: group.group,
                colspan: group.columns.length,
            });
        });
        return [nestedHeaders];
    }

    // Convert row data to array format
    convertDataToArray(data, headers) {
        if (!data || !data.length) return [];
        return data.map((row) => {
            const rowArray = [];
            headers.forEach((group) => {
                group.columns.forEach((col) => {
                    rowArray.push(row[col.key] || "");
                });
            });
            return rowArray;
        });
    }

    // Convert array data back to object format
    convertArrayToData(arrayData, headers) {
        if (!arrayData) return [];
        return arrayData.map((row) => {
            const rowObj = {};
            let colIdx = 0;
            headers.forEach((group) => {
                group.columns.forEach((col) => {
                    rowObj[col.key] = row[colIdx] || "";
                    colIdx++;
                });
            });
            return rowObj;
        });
    }

    // Calculate totals for an area
    calculateTotals(area) {
        const totals = {};
        const data = this.getAreaData(area);

        if (!data || !data.length) return totals;

        let colIdx = 0;
        area.headers.forEach((group) => {
            group.columns.forEach((col) => {
                if (col.type === "numeric") {
                    let sum = 0;
                    data.forEach((row) => {
                        const value = parseFloat(row[colIdx]) || 0;
                        sum += value;
                    });
                    totals[col.key] = sum;
                }
                colIdx++;
            });
        });

        return totals;
    }

    // Get data from area's spreadsheet
    getAreaData(area) {
        if (area.worksheet && typeof area.worksheet.getData === "function") {
            return area.worksheet.getData();
        }
        if (
            area.spreadsheetInstance &&
            area.spreadsheetInstance[0] &&
            typeof area.spreadsheetInstance[0].getData === "function"
        ) {
            return area.spreadsheetInstance[0].getData();
        }
        return area.currentData || [];
    }

    // Update totals display for an area
    updateTotalsDisplay(area) {
        const totals = this.calculateTotals(area);

        // Find or create totals container
        const scrollContainer = document.getElementById(
            `area-${area.id}-scroll`,
        );
        if (!scrollContainer) return;

        let totalsContainer = document.getElementById(
            `area-${area.id}-totals-container`,
        );
        if (!totalsContainer) {
            totalsContainer = document.createElement("div");
            totalsContainer.id = `area-${area.id}-totals-container`;
            // Append to scroll container's inner wrapper
            const innerWrapper = scrollContainer.querySelector("div");
            if (innerWrapper) {
                innerWrapper.appendChild(totalsContainer);
            } else {
                scrollContainer.appendChild(totalsContainer);
            }
        }

        // Get jspreadsheet table
        const jssTable = area.container.querySelector("table");
        if (!jssTable) return;

        // Get colgroup from jspreadsheet to copy exact widths
        const jssColgroup = jssTable.querySelector("colgroup");

        // Groups that should NOT have totals
        const excludedGroups = ["lokasi", "dimensi"];

        // Build totals table HTML
        let html = `<table style="border-collapse: collapse; margin-top: -1px; width: ${jssTable.offsetWidth}px; table-layout: fixed;">`;

        // Copy colgroup if exists
        if (jssColgroup) {
            html += jssColgroup.outerHTML;
        }

        html += "<tbody><tr>";

        // Cell base style
        const cellStyle =
            "font-weight: bold; background: #fef3c7; padding: 6px 8px; border: 1px solid #ccc; text-align: center; box-sizing: border-box;";

        // First column (row number column) - use Σ symbol
        html += `<td style="${cellStyle}" title="Total">Σ</td>`;

        // Each column matches the header column above
        area.headers.forEach((group) => {
            const groupName = (group.group || "").toLowerCase();
            const isExcludedGroup = excludedGroups.includes(groupName);

            group.columns.forEach((col) => {
                if (col.type === "numeric" && !isExcludedGroup) {
                    const value = totals[col.key] || 0;
                    html += `<td style="${cellStyle}">${value}</td>`;
                } else {
                    html += `<td style="${cellStyle}"></td>`;
                }
            });
        });

        html += "</tr></tbody></table>";
        totalsContainer.innerHTML = html;
    }

    // Update satuan display for an area - create a row with satuan dropdowns
    updateSatuanDisplay(area) {
        console.log(`📊 updateSatuanDisplay called for area ${area.id}`);
        console.log(`📊 Current satuanList:`, this.satuanList);
        console.log(
            `📊 satuanList length:`,
            this.satuanList ? this.satuanList.length : 0,
        );

        const scrollContainer = document.getElementById(
            `area-${area.id}-scroll`,
        );
        if (!scrollContainer) {
            console.warn(`⚠️ Scroll container not found for area ${area.id}`);
            return;
        }

        let satuanContainer = document.getElementById(
            `area-${area.id}-satuan-container`,
        );
        if (!satuanContainer) {
            satuanContainer = document.createElement("div");
            satuanContainer.id = `area-${area.id}-satuan-container`;
            const innerWrapper = scrollContainer.querySelector("div");
            if (innerWrapper) {
                innerWrapper.appendChild(satuanContainer);
            } else {
                scrollContainer.appendChild(satuanContainer);
            }
        }

        // Get jspreadsheet table
        const jssTable = area.container.querySelector("table");
        if (!jssTable) return;

        // Get colgroup from jspreadsheet to copy exact widths
        const jssColgroup = jssTable.querySelector("colgroup");

        // Groups that should NOT have satuan dropdowns
        const excludedGroups = ["lokasi", "dimensi"];

        // Build satuan table HTML
        let html = `<table style="border-collapse: collapse; margin-top: -1px; width: ${jssTable.offsetWidth}px; table-layout: fixed;">`;

        // Copy colgroup if exists
        if (jssColgroup) {
            html += jssColgroup.outerHTML;
        }

        html += '<tbody><tr id="satuan-row-' + area.id + '">';

        // Cell base style
        const cellStyle =
            "font-weight: bold; background: #dbeafe; padding: 2px; border: 1px solid #ccc; text-align: center; box-sizing: border-box; height: 40px;";

        // First column (row number column) - label
        html += `<td style="${cellStyle}">Satuan</td>`;

        // Each column
        area.headers.forEach((group) => {
            const groupName = (group.group || "").toLowerCase();
            const isExcludedGroup = excludedGroups.includes(groupName);

            group.columns.forEach((col) => {
                if (col.type === "numeric" && !isExcludedGroup) {
                    const selectId = `satuan-${area.id}-${col.key}`;
                    const currentSatuan =
                        this.getSatuan(area.id, col.key) || "";

                    html += `<td style="${cellStyle}"><select id="${selectId}" class="satuan-select" data-area-id="${area.id}" data-col-key="${col.key}" style="width: 100%; padding: 4px; border: 1px solid #999; border-radius: 3px; font-size: 12px;">`;
                    html += '<option value="">-- Pilih --</option>';

                    // Add satuan options
                    console.log(
                        `  📊 Adding satuan options for column ${col.key}, satuanList has ${this.satuanList.length} items`,
                    );
                    this.satuanList.forEach((satuan) => {
                        console.log(
                            `    - Option: id=${satuan.id}, nama=${satuan.nama}`,
                        );
                        const selected =
                            currentSatuan == satuan.id ? "selected" : "";
                        html += `<option value="${satuan.id}" ${selected}>${satuan.nama}</option>`;
                    });
                    console.log(
                        `  📊 Added options count: ${this.satuanList.length}`,
                    );

                    html += "</select></td>";
                } else {
                    // Empty cell for non-numeric columns or excluded groups
                    html += `<td style="${cellStyle}"></td>`;
                }
            });
        });

        html += "</tr></tbody></table>";
        satuanContainer.innerHTML = html;

        // Bind satuan select events
        const self = this;
        satuanContainer.querySelectorAll(".satuan-select").forEach((select) => {
            select.addEventListener("change", function () {
                const areaId = this.dataset.areaId;
                const colKey = this.dataset.colKey;
                const satuanId = this.value || null;
                self.setSatuan(areaId, colKey, satuanId);
                console.log("Satuan updated:", { areaId, colKey, satuanId });
            });
        });
    }

    // Initialize with data from server
    async init() {
        // Clear container
        this.container.innerHTML = "";

        // Load formulas first (satuans already loaded from options)
        await this.loadFormulas();

        // Load saved areas
        const savedAreas = await this.loadData();

        if (savedAreas.length === 0) {
            // Create one default area
            this.addArea();
        } else {
            // Create areas from saved data
            savedAreas.forEach((areaData) => {
                this.addArea(
                    areaData.area_name,
                    areaData.headers,
                    areaData.data,
                    areaData.id,
                    areaData.comments,
                    areaData.satuans,
                );
            });
        }

        // Apply formulas to all areas after initial load
        this.areas.forEach((area) => {
            this.applyFormulasToAllRows(area);
            this.styleFormulaColumns(area);
        });
    }

    // Add a new area
    addArea(
        areaName = "",
        headers = null,
        data = null,
        serverId = null,
        comments = null,
        satuans = null,
    ) {
        const areaId = ++this.areaCounter;
        const areaHeaders = headers || this.getDefaultHeaders();

        // Load comments for this area
        if (comments) {
            this.comments[areaId] = comments;
        }

        // Load satuans for this area
        if (satuans) {
            this.satuans[areaId] = satuans;
        } else {
            this.satuans[areaId] = {};
        }

        const area = {
            id: areaId,
            serverId: serverId,
            areaName: areaName,
            headers: areaHeaders,
            spreadsheetInstance: null,
            worksheet: null,
            currentData: null,
        };

        // Create area container
        const areaWrapper = document.createElement("div");
        areaWrapper.id = `area-wrapper-${areaId}`;
        areaWrapper.className = "area-wrapper";
        areaWrapper.style.cssText =
            "margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 2px solid #e2e8f0;";

        // Area header with name input and delete button
        const areaHeader = document.createElement("div");
        areaHeader.style.cssText =
            "display: flex; align-items: center; gap: 15px; margin-bottom: 15px;";
        areaHeader.innerHTML = `
            <label style="font-weight: bold; color: #374151;">Nama Area:</label>
            <input type="text" id="area-${areaId}-name" 
                   value="${areaName}" 
                   placeholder="Contoh: ACCESS POINT, CCTV, dll"
                   style="padding: 8px 12px; border: 2px solid #3b82f6; border-radius: 6px; font-weight: bold; font-size: 16px; width: 300px; flex-shrink: 0;">
            <div style="flex-grow: 1;"></div>
            <button type="button" class="btn-delete-area" data-area-id="${areaId}"
                    style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Hapus Area
            </button>
        `;
        areaWrapper.appendChild(areaHeader);

        // Column management buttons
        const columnButtons = document.createElement("div");
        columnButtons.style.cssText =
            "display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;";
        columnButtons.innerHTML = `
            <button type="button" class="btn-add-group" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #8b5cf6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                + Tambah Grup
            </button>
            <button type="button" class="btn-add-col" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                + Tambah Kolom
            </button>
            <button type="button" class="btn-rename-group" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #14b8a6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                ✎ Rename Grup
            </button>
            <button type="button" class="btn-remove-group" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #f97316; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                - Hapus Grup
            </button>
            <button type="button" class="btn-remove-col" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                - Hapus Kolom
            </button>
        `;
        areaWrapper.appendChild(columnButtons);

        // Scrollable spreadsheet container
        const scrollContainer = document.createElement("div");
        scrollContainer.id = `area-${areaId}-scroll`;
        scrollContainer.style.cssText =
            "overflow-x: auto; overflow-y: visible; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; background: white;";

        // Inner wrapper to keep spreadsheet together
        const innerWrapper = document.createElement("div");
        innerWrapper.style.cssText = "min-width: fit-content;";

        // Spreadsheet container
        const spreadsheetContainer = document.createElement("div");
        spreadsheetContainer.id = `area-${areaId}-spreadsheet`;
        innerWrapper.appendChild(spreadsheetContainer);

        scrollContainer.appendChild(innerWrapper);
        areaWrapper.appendChild(scrollContainer);

        this.container.appendChild(areaWrapper);
        area.container = spreadsheetContainer;

        // Initialize spreadsheet
        this.initAreaSpreadsheet(area, data);

        // Bind events
        this.bindAreaEvents(area);

        this.areas.push(area);
        return area;
    }

    // Initialize spreadsheet for an area
    initAreaSpreadsheet(area, data = null) {
        const columns = this.buildColumns(area.headers);
        const nestedHeaders = this.buildNestedHeaders(area.headers);
        let arrayData = this.convertDataToArray(data || [], area.headers);

        if (arrayData.length === 0) {
            arrayData = [new Array(columns.length).fill("")];
        }

        area.currentData = arrayData;
        area.container.innerHTML = "";

        const self = this;

        try {
            area.spreadsheetInstance = jspreadsheet(area.container, {
                worksheets: [
                    {
                        data: arrayData,
                        columns: columns,
                        nestedHeaders: nestedHeaders,
                        minDimensions: [columns.length, 1],
                        allowInsertRow: true,
                        allowInsertColumn: false,
                        allowDeleteRow: true,
                        allowDeleteColumn: false,
                        allowComments: false,
                        columnResize: true,
                        tableOverflow: false,
                        defaultColWidth: 100,
                    },
                ],
                contextMenu: function (obj, x, y, e, items) {
                    // Filter out comments and add our custom comment option
                    const filteredItems = [];

                    // Add our custom comment options first
                    const existingComment = self.getComment(area.id, y, x);
                    if (existingComment) {
                        filteredItems.push({
                            title: "View Comment",
                            onclick: function () {
                                self.viewCommentModal(existingComment);
                            },
                        });
                        filteredItems.push({
                            title: "Edit Comment",
                            onclick: function () {
                                self.openCommentModal(
                                    "Edit Comment",
                                    existingComment,
                                    function (newComment) {
                                        if (newComment !== null) {
                                            self.setComment(
                                                area.id,
                                                y,
                                                x,
                                                newComment,
                                            );
                                        }
                                    },
                                );
                            },
                        });
                        filteredItems.push({
                            title: "Delete Comment",
                            onclick: function () {
                                if (confirm("Hapus komentar ini?")) {
                                    self.deleteComment(area.id, y, x);
                                }
                            },
                        });
                    } else {
                        filteredItems.push({
                            title: "Insert Comment",
                            onclick: function () {
                                self.openCommentModal(
                                    "Insert Comment",
                                    "",
                                    function (comment) {
                                        if (comment && comment.trim()) {
                                            self.setComment(
                                                area.id,
                                                y,
                                                x,
                                                comment,
                                            );
                                        }
                                    },
                                );
                            },
                        });
                    }

                    // Add separator
                    filteredItems.push({ type: "line" });

                    // Add standard items except comments
                    items.forEach((item) => {
                        if (
                            item &&
                            item.title &&
                            item.title.toLowerCase().includes("comment")
                        ) {
                            return;
                        }
                        filteredItems.push(item);
                    });

                    return filteredItems;
                },
                onchange: function (instance, cell, x, y, value) {
                    self.updateTotalsDisplay(area);
                    // Apply formulas when a cell changes
                    self.handleCellChange(
                        area,
                        parseInt(x),
                        parseInt(y),
                        value,
                    );
                },
                onafterchanges: function (instance, records) {
                    self.updateTotalsDisplay(area);
                    // Apply formulas for all changed rows
                    if (records && records.length > 0) {
                        const affectedRows = new Set();
                        records.forEach((record) => {
                            if (record.y !== undefined) {
                                affectedRows.add(parseInt(record.y));
                            }
                        });
                        affectedRows.forEach((rowIdx) => {
                            self.applyFormulasToRow(area, rowIdx);
                        });
                    }
                    // Re-style formula columns
                    self.styleFormulaColumns(area);
                },
                oninsertrow: function (
                    instance,
                    rowNumber,
                    numOfRows,
                    insertBefore,
                ) {
                    self.updateTotalsDisplay(area);
                    // Apply formulas to new rows
                    for (let i = 0; i < numOfRows; i++) {
                        const rowIdx = insertBefore
                            ? rowNumber + i
                            : rowNumber + 1 + i;
                        self.applyFormulasToRow(area, rowIdx);
                    }
                    self.styleFormulaColumns(area);
                },
                ondeleterow: function () {
                    self.updateTotalsDisplay(area);
                },
                onresizecolumn: function () {
                    // Delay to allow DOM to update
                    setTimeout(() => self.updateTotalsDisplay(area), 10);
                },
                onselection: function (instance, x1, y1, x2, y2, origin) {
                    // Auto-scroll to selected cell horizontally
                    self.scrollToSelectedCell(area, x2, y2);
                },
            });

            if (area.spreadsheetInstance && area.spreadsheetInstance[0]) {
                area.worksheet = area.spreadsheetInstance[0];
            }
        } catch (err) {
            console.error("Error creating jspreadsheet:", err);
            this.createFallbackTable(area, arrayData, columns, nestedHeaders);
        }

        this.styleAreaHeaders(area);
        // Delay initial totals display and satuan row to ensure DOM is rendered
        setTimeout(() => {
            this.updateTotalsDisplay(area);
            this.updateSatuanDisplay(area);
        }, 50);
        // Bind comment events after DOM is ready
        this.bindCommentEvents(area);
    }

    // Style headers for an area
    styleAreaHeaders(area) {
        setTimeout(() => {
            const nestedCells = area.container.querySelectorAll(
                ".jss_nested thead td, .jexcel_nested thead td",
            );
            let groupIdx = 0;

            nestedCells.forEach((cell) => {
                if (groupIdx < area.headers.length) {
                    const group = area.headers[groupIdx];
                    const color =
                        this.groupColors[group.color] ||
                        this.groupColors.default;
                    cell.style.backgroundColor = color;
                    cell.style.color = "white";
                    cell.style.fontWeight = "bold";
                    cell.style.textAlign = "center";
                    cell.style.borderRight = "2px solid rgba(255,255,255,0.3)";
                    groupIdx++;
                }
            });

            const headerCells = area.container.querySelectorAll(
                ".jss > thead td, .jexcel > thead td",
            );
            headerCells.forEach((cell) => {
                cell.style.textAlign = "center";
                cell.style.fontWeight = "600";
            });
        }, 100);
    }

    // Bind events for area buttons
    bindAreaEvents(area) {
        const wrapper = document.getElementById(`area-wrapper-${area.id}`);
        if (!wrapper) return;

        const self = this;

        // Delete area button
        wrapper
            .querySelector(".btn-delete-area")
            ?.addEventListener("click", () => {
                if (this.areas.length <= 1) {
                    alert("Minimal harus ada satu area!");
                    return;
                }
                if (!confirm(`Hapus area "${area.areaName || "Tanpa Nama"}"?`))
                    return;
                this.removeArea(area.id);
            });

        // Add group button
        wrapper
            .querySelector(".btn-add-group")
            ?.addEventListener("click", () => {
                const groupName = prompt("Masukkan nama grup kolom baru:");
                if (!groupName) return;

                const columnName = prompt("Masukkan nama kolom pertama:");
                if (!columnName) return;

                const isNumeric = confirm(
                    "Kolom berisi angka? (OK = Ya, Cancel = Teks)",
                );
                this.addColumnGroup(area, groupName, columnName, isNumeric);
            });

        // Add column button
        wrapper.querySelector(".btn-add-col")?.addEventListener("click", () => {
            const groups = area.headers
                .map((g, i) => `${i + 1}. ${g.group}`)
                .join("\n");
            if (area.headers.length === 0) {
                alert("Belum ada grup kolom!");
                return;
            }

            const input = prompt(`Pilih nomor grup:\n${groups}`);
            if (!input) return;

            const groupIdx = parseInt(input) - 1;
            if (
                isNaN(groupIdx) ||
                groupIdx < 0 ||
                groupIdx >= area.headers.length
            ) {
                alert("Nomor tidak valid!");
                return;
            }

            const columnName = prompt("Masukkan nama kolom:");
            if (!columnName) return;

            const isNumeric = confirm(
                "Kolom berisi angka? (OK = Ya, Cancel = Teks)",
            );
            this.addColumnToGroup(area, groupIdx, columnName, isNumeric);
        });

        // Rename group button
        wrapper
            .querySelector(".btn-rename-group")
            ?.addEventListener("click", () => {
                if (area.headers.length === 0) {
                    alert("Belum ada grup kolom!");
                    return;
                }

                const groups = area.headers
                    .map((g, i) => `${i + 1}. ${g.group}`)
                    .join("\n");
                const input = prompt(
                    `Pilih nomor grup yang akan direname:\n${groups}`,
                );
                if (!input) return;

                const groupIdx = parseInt(input) - 1;
                if (
                    isNaN(groupIdx) ||
                    groupIdx < 0 ||
                    groupIdx >= area.headers.length
                ) {
                    alert("Nomor tidak valid!");
                    return;
                }

                const currentName = area.headers[groupIdx].group;
                const newName = prompt(
                    `Masukkan nama grup baru (sebelumnya: "${currentName}"):`,
                );
                if (!newName || newName.trim() === "") return;

                this.renameColumnGroup(area, groupIdx, newName);
            });

        // Remove group button
        wrapper
            .querySelector(".btn-remove-group")
            ?.addEventListener("click", () => {
                if (area.headers.length <= 1) {
                    alert("Minimal harus ada satu grup kolom!");
                    return;
                }

                const groups = area.headers
                    .map((g, i) => `${i + 1}. ${g.group}`)
                    .join("\n");
                const input = prompt(
                    `Pilih nomor grup yang akan dihapus:\n${groups}`,
                );
                if (!input) return;

                const groupIdx = parseInt(input) - 1;
                if (
                    isNaN(groupIdx) ||
                    groupIdx < 0 ||
                    groupIdx >= area.headers.length
                ) {
                    alert("Nomor tidak valid!");
                    return;
                }

                if (!confirm(`Hapus grup "${area.headers[groupIdx].group}"?`))
                    return;
                this.removeColumnGroup(area, groupIdx);
            });

        // Remove column button
        wrapper
            .querySelector(".btn-remove-col")
            ?.addEventListener("click", () => {
                // Build list of all columns with their group
                let columnList = [];
                let colNumber = 1;
                area.headers.forEach((group, gIdx) => {
                    group.columns.forEach((col, cIdx) => {
                        columnList.push({
                            number: colNumber,
                            groupIdx: gIdx,
                            colIdx: cIdx,
                            groupName: group.group,
                            colName: col.title,
                        });
                        colNumber++;
                    });
                });

                if (columnList.length <= 1) {
                    alert("Minimal harus ada satu kolom!");
                    return;
                }

                const listText = columnList
                    .map((c) => `${c.number}. [${c.groupName}] ${c.colName}`)
                    .join("\n");
                const input = prompt(
                    `Pilih nomor kolom yang akan dihapus:\n${listText}`,
                );
                if (!input) return;

                const selectedNum = parseInt(input);
                const selected = columnList.find(
                    (c) => c.number === selectedNum,
                );
                if (!selected) {
                    alert("Nomor tidak valid!");
                    return;
                }

                if (
                    !confirm(
                        `Hapus kolom "${selected.colName}" dari grup "${selected.groupName}"?`,
                    )
                )
                    return;
                this.removeColumnFromGroup(
                    area,
                    selected.groupIdx,
                    selected.colIdx,
                );
            });
    }

    // Remove an area
    removeArea(areaId) {
        const idx = this.areas.findIndex((a) => a.id === areaId);
        if (idx === -1) return;

        const area = this.areas[idx];

        // Destroy spreadsheet
        if (
            area.spreadsheetInstance &&
            typeof area.spreadsheetInstance.destroy === "function"
        ) {
            area.spreadsheetInstance.destroy();
        }

        // Remove DOM
        const wrapper = document.getElementById(`area-wrapper-${areaId}`);
        if (wrapper) wrapper.remove();

        // Remove from array
        this.areas.splice(idx, 1);
    }

    // Add column group to area
    addColumnGroup(area, groupName, columnName, isNumeric = true) {
        const key = this.generateColumnKey(columnName);
        const colorKeys = Object.keys(this.groupColors);
        const colorIdx = area.headers.length % colorKeys.length;
        const color = colorKeys[colorIdx];

        area.headers.push({
            group: groupName,
            color: color,
            columns: [
                {
                    key: key,
                    title: columnName,
                    type: isNumeric ? "numeric" : "text",
                    width: 100,
                },
            ],
        });

        this.reinitializeArea(area);
    }

    // Add column to existing group
    addColumnToGroup(area, groupIndex, columnName, isNumeric = true) {
        if (groupIndex < 0 || groupIndex >= area.headers.length) return;

        const key = this.generateColumnKey(columnName);
        area.headers[groupIndex].columns.push({
            key: key,
            title: columnName,
            type: isNumeric ? "numeric" : "text",
            width: 100,
        });

        this.reinitializeArea(area);
    }

    // Rename column group
    renameColumnGroup(area, groupIndex, newName) {
        if (groupIndex < 0 || groupIndex >= area.headers.length) return false;
        if (!newName || newName.trim() === "") return false;

        const oldName = area.headers[groupIndex].group;
        area.headers[groupIndex].group = newName.trim();
        console.log(`📝 Group renamed: "${oldName}" → "${newName}"`);
        this.reinitializeArea(area);
        return true;
    }

    // Remove column group
    removeColumnGroup(area, groupIndex) {
        if (area.headers.length <= 1) return false;
        if (groupIndex < 0 || groupIndex >= area.headers.length) return false;

        area.headers.splice(groupIndex, 1);
        this.reinitializeArea(area);
        return true;
    }

    // Remove a single column from a group
    removeColumnFromGroup(area, groupIndex, columnIndex) {
        if (groupIndex < 0 || groupIndex >= area.headers.length) return false;

        const group = area.headers[groupIndex];
        if (columnIndex < 0 || columnIndex >= group.columns.length)
            return false;

        // Count total columns
        const totalColumns = area.headers.reduce(
            (sum, g) => sum + g.columns.length,
            0,
        );
        if (totalColumns <= 1) return false;

        // Get column key before removing it
        const removedColumn = group.columns[columnIndex];
        const removedKey = removedColumn.key;

        // If this is the last column in the group, remove the entire group
        if (group.columns.length === 1) {
            return this.removeColumnGroup(area, groupIndex);
        }

        // Remove the column
        group.columns.splice(columnIndex, 1);

        // Clean up satuan data for removed column
        if (this.satuans[area.id] && this.satuans[area.id][removedKey]) {
            delete this.satuans[area.id][removedKey];
        }

        this.reinitializeArea(area);
        return true;
    }

    // Reinitialize area's spreadsheet
    reinitializeArea(area) {
        // Get current data
        const currentArrayData = this.getAreaData(area);
        const currentObjData = this.convertArrayToData(
            currentArrayData,
            area.headers,
        );

        // Destroy current
        if (
            area.spreadsheetInstance &&
            typeof area.spreadsheetInstance.destroy === "function"
        ) {
            area.spreadsheetInstance.destroy();
        } else if (
            area.worksheet &&
            typeof area.worksheet.destroy === "function"
        ) {
            area.worksheet.destroy();
        }

        // Rebuild
        this.initAreaSpreadsheet(area, currentObjData);

        // Re-apply formulas after reinitialize
        setTimeout(() => {
            this.applyFormulasToAllRows(area);
            this.styleFormulaColumns(area);
        }, 100);
    }

    // Fallback table if jspreadsheet fails
    createFallbackTable(area, data, columns, nestedHeaders) {
        console.log("Using fallback HTML table for area", area.id);

        let tableHtml =
            '<table class="survey-fallback-table" style="width: 100%; border-collapse: collapse;">';

        tableHtml += "<thead><tr>";
        nestedHeaders[0].forEach((nh) => {
            tableHtml += `<th colspan="${nh.colspan}" style="background: #6b7280; color: white; padding: 8px; border: 1px solid #ddd; text-align: center;">${nh.title}</th>`;
        });
        tableHtml += "</tr>";

        tableHtml += "<tr>";
        columns.forEach((col) => {
            tableHtml += `<th style="background: #f3f4f6; padding: 8px; border: 1px solid #ddd; text-align: center;">${col.title}</th>`;
        });
        tableHtml += "</tr></thead>";

        tableHtml += "<tbody>";
        data.forEach((row, rowIdx) => {
            tableHtml += "<tr>";
            row.forEach((cell, colIdx) => {
                const col = columns[colIdx];
                const inputType = col.type === "numeric" ? "number" : "text";
                tableHtml += `<td style="padding: 4px; border: 1px solid #ddd;"><input type="${inputType}" value="${cell}" data-row="${rowIdx}" data-col="${colIdx}" style="width: 100%; padding: 4px; border: 1px solid #ccc; border-radius: 3px; text-align: center;"></td>`;
            });
            tableHtml += "</tr>";
        });
        tableHtml += "</tbody></table>";

        area.container.innerHTML = tableHtml;

        const self = this;
        area.container.querySelectorAll("input").forEach((input) => {
            input.addEventListener("change", function () {
                const row = parseInt(this.dataset.row);
                const col = parseInt(this.dataset.col);
                if (!area.currentData[row]) area.currentData[row] = [];
                area.currentData[row][col] = this.value;
                self.updateTotalsDisplay(area);
            });
        });
    }

    // Load data from server - returns array of areas
    async loadData() {
        try {
            // Include version parameter if set
            let url = `${this.baseUrl}/rekap/${this.rekapId}/surveys`;
            if (this.version !== null) {
                url += `?version=${this.version}`;
            }
            const response = await fetch(url);
            const contentType = response.headers.get("content-type");

            if (!contentType || !contentType.includes("application/json")) {
                return [];
            }

            const result = await response.json();
            if (result.success && result.surveys) {
                return result.surveys;
            }
        } catch (err) {
            console.error("Error loading surveys:", err);
        }
        return [];
    }

    // Save all areas to server
    // Sync column headers from jspreadsheet back to area.headers
    syncColumnHeadersFromSpreadsheet(area) {
        if (!area.worksheet || !area.headers) return false;

        // Get the columns configuration from jspreadsheet
        const worksheetColumns = area.worksheet.getConfig().columns || [];

        let colIdx = 0;
        let headerUpdateCount = 0;

        area.headers.forEach((group) => {
            group.columns.forEach((col) => {
                if (colIdx < worksheetColumns.length) {
                    const jssCol = worksheetColumns[colIdx];
                    const newTitle = jssCol.title || col.title;

                    // Update column title if it changed
                    if (newTitle !== col.title) {
                        console.log(
                            `📝 Syncing column title: "${col.title}" → "${newTitle}"`,
                        );
                        col.title = newTitle;
                        headerUpdateCount++;
                    }
                }
                colIdx++;
            });
        });

        if (headerUpdateCount > 0) {
            console.log(
                `✅ Synced ${headerUpdateCount} column header(s) from spreadsheet`,
            );
        }

        return headerUpdateCount > 0;
    }

    // Sync all areas' column headers before saving
    syncAllColumnHeaders() {
        this.areas.forEach((area) => {
            this.syncColumnHeadersFromSpreadsheet(area);
        });
    }

    async save() {
        // Sync column headers from spreadsheet before saving
        this.syncAllColumnHeaders();

        const areasData = this.areas.map((area) => {
            const areaNameInput = document.getElementById(
                `area-${area.id}-name`,
            );
            const areaName = areaNameInput ? areaNameInput.value : "";

            const arrayData = this.getAreaData(area);
            const objData = this.convertArrayToData(arrayData, area.headers);

            // Filter empty rows
            const filteredData = objData.filter((row) => {
                return Object.values(row).some(
                    (v) => v !== "" && v !== null && v !== undefined,
                );
            });

            return {
                id: area.serverId,
                area_name: areaName,
                headers: area.headers,
                data: filteredData.length > 0 ? filteredData : [],
                comments: this.comments[area.id] || {},
                satuans: this.satuans[area.id] || {},
            };
        });

        try {
            const response = await fetch(
                `${this.baseUrl}/rekap/${this.rekapId}/surveys`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": this.csrfToken,
                    },
                    body: JSON.stringify({
                        areas: areasData,
                        version: this.version,
                    }),
                },
            );

            const result = await response.json();

            // Update server IDs
            if (result.success && result.area_ids) {
                result.area_ids.forEach((serverId, idx) => {
                    if (this.areas[idx]) {
                        this.areas[idx].serverId = serverId;
                    }
                });
            }

            return result.success;
        } catch (err) {
            console.error("Error saving surveys:", err);
            return false;
        }
    }

    // Get list of groups for a specific area
    getGroupList(areaId = null) {
        if (areaId === null && this.areas.length > 0) {
            // Return first area's groups for backward compatibility
            return this.areas[0].headers.map((h, i) => ({
                index: i,
                name: h.group,
            }));
        }
        const area = this.areas.find((a) => a.id === areaId);
        if (!area) return [];
        return area.headers.map((h, i) => ({ index: i, name: h.group }));
    }

    // Get all areas info
    getAllAreas() {
        return this.areas.map((a) => ({
            id: a.id,
            serverId: a.serverId,
            areaName:
                document.getElementById(`area-${a.id}-name`)?.value ||
                a.areaName,
        }));
    }

    // Inject CSS styles
    injectStyles() {
        const styleId = "survey-spreadsheet-styles";
        if (document.getElementById(styleId)) return;

        const style = document.createElement("style");
        style.id = styleId;
        style.textContent = `
            /* Nested header group styles */
            .jss_nested thead td,
            .jexcel_nested thead td {
                text-align: center !important;
                font-weight: bold !important;
                font-size: 13px !important;
                padding: 10px 8px !important;
                vertical-align: middle !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }
            
            /* Column header styles */
            .jss thead td,
            .jexcel thead td {
                text-align: center !important;
                font-weight: 600 !important;
                background-color: #f8fafc !important;
                border-bottom: 2px solid #e2e8f0 !important;
                padding: 8px 6px !important;
                font-size: 12px !important;
            }
            
            /* Cell data alignment */
            .jss tbody td,
            .jexcel tbody td {
                text-align: center !important;
                padding: 6px 8px !important;
            }
            
            /* Table styling */
            .jss,
            .jexcel {
                border-radius: 8px !important;
                overflow: visible !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            }
            
            /* Row hover effect */
            .jss tbody tr:hover td,
            .jexcel tbody tr:hover td {
                background-color: #f0f9ff !important;
            }
            
            /* Scroll container styling */
            .area-wrapper > div::-webkit-scrollbar {
                height: 10px;
            }
            
            .area-wrapper > div::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 5px;
            }
            
            .area-wrapper > div::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 5px;
            }
            
            .area-wrapper > div::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
            
            /* Delete button hover */
            .btn-delete-area:hover {
                background: #dc2626 !important;
            }
            
            /* Button hover effects */
            .btn-add-group:hover { background: #7c3aed !important; }
            .btn-add-col:hover { background: #4f46e5 !important; }
            .btn-remove-group:hover { background: #ea580c !important; }
            .btn-remove-col:hover { background: #dc2626 !important; }
            
            /* Formula column styling */
            .formula-cell {
                background-color: #f0fdf4 !important;
                font-style: italic !important;
            }
            
            .formula-cell:hover {
                background-color: #dcfce7 !important;
            }
            
            /* Formula column header indicator */
            .formula-header {
                position: relative;
            }
            
            .formula-header::after {
                content: 'ƒ';
                position: absolute;
                top: 2px;
                right: 4px;
                font-size: 10px;
                color: #16a34a;
                font-weight: bold;
            }
            
            /* Totals row styling */
            [id$="-totals-container"] table {
                border-collapse: collapse !important;
            }
            
            [id$="-totals-container"] td {
                box-sizing: border-box !important;
            }
        `;
        document.head.appendChild(style);
    }
}

// Export for global access
window.SurveySpreadsheet = SurveySpreadsheet;
export default SurveySpreadsheet;
