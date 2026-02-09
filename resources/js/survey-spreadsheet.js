import jspreadsheet from 'jspreadsheet-ce';
import 'jspreadsheet-ce/dist/jspreadsheet.css';
import 'jsuites/dist/jsuites.css';

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
 */

class SurveySpreadsheet {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.rekapId = options.rekapId;
        this.csrfToken = options.csrfToken;
        this.baseUrl = options.baseUrl || '';
        
        // Multi-area support - each area has its own spreadsheet
        this.areas = [];
        this.areaCounter = 0;
        
        // Comments storage: { areaId: { 'row,col': 'comment text' } }
        this.comments = {};
        
        // Colors for header groups
        this.groupColors = {
            'lokasi': '#3b82f6',
            'dimensi': '#8b5cf6', 
            'kabel': '#22c55e',
            'pipa': '#f59e0b',
            'box': '#06b6d4',
            'accessories': '#ec4899',
            'default': '#6b7280'
        };
        
        // Inject styles
        this.injectStyles();
        
        // Create comment tooltip element
        this.createCommentTooltip();
    }

    // Create floating tooltip for showing comments
    createCommentTooltip() {
        const tooltip = document.createElement('div');
        tooltip.id = 'survey-comment-tooltip';
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
    }

    // Get comment for a cell
    getComment(areaId, row, col) {
        const key = `${row},${col}`;
        return this.comments[areaId]?.[key] || null;
    }

    // Set comment for a cell
    setComment(areaId, row, col, comment) {
        console.log('setComment called:', { areaId, row, col, comment });
        if (!this.comments[areaId]) {
            this.comments[areaId] = {};
        }
        const key = `${row},${col}`;
        if (comment && comment.trim()) {
            this.comments[areaId][key] = comment.trim();
        } else {
            delete this.comments[areaId][key];
        }
        console.log('Comments after set:', JSON.stringify(this.comments));
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
        const area = this.areas.find(a => a.id === areaId);
        if (!area || !area.container) return;
        
        const cells = area.container.querySelectorAll('tbody td');
        cells.forEach(cell => {
            // Remove existing indicator
            const existingIndicator = cell.querySelector('.comment-indicator');
            if (existingIndicator) existingIndicator.remove();
            
            // Get cell row/col
            const row = cell.dataset.y;
            const col = cell.dataset.x;
            if (row === undefined || col === undefined) return;
            
            const comment = this.getComment(areaId, parseInt(row), parseInt(col));
            if (comment) {
                // Add red triangle indicator
                const indicator = document.createElement('div');
                indicator.className = 'comment-indicator';
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
                cell.style.position = 'relative';
                cell.appendChild(indicator);
            }
        });
    }

    // Bind comment events to area cells (hover tooltip only, context menu is handled by jspreadsheet)
    bindCommentEvents(area) {
        const self = this;
        
        setTimeout(() => {
            const cells = area.container.querySelectorAll('tbody td');
            
            cells.forEach(cell => {
                // Show tooltip on hover
                cell.addEventListener('mouseenter', function(e) {
                    const row = this.dataset.y;
                    const col = this.dataset.x;
                    if (row === undefined || col === undefined) return;
                    
                    const comment = self.getComment(area.id, parseInt(row), parseInt(col));
                    if (comment) {
                        self.commentTooltip.textContent = comment;
                        self.commentTooltip.style.display = 'block';
                        self.commentTooltip.style.left = (e.clientX + 10) + 'px';
                        self.commentTooltip.style.top = (e.clientY + 10) + 'px';
                    }
                });
                
                cell.addEventListener('mousemove', function(e) {
                    if (self.commentTooltip.style.display === 'block') {
                        self.commentTooltip.style.left = (e.clientX + 10) + 'px';
                        self.commentTooltip.style.top = (e.clientY + 10) + 'px';
                    }
                });
                
                cell.addEventListener('mouseleave', function() {
                    self.commentTooltip.style.display = 'none';
                });
            });
            
            // Update indicators
            self.updateCommentIndicators(area.id);
        }, 200);
    }

    // Default headers template
    getDefaultHeaders() {
        return [
            {
                group: 'Lokasi',
                color: 'lokasi',
                columns: [
                    { key: 'lantai', title: 'Lantai', type: 'text', width: 80 },
                    { key: 'nama', title: 'Nama', type: 'text', width: 100 },
                    { key: 'dari', title: 'Dari', type: 'text', width: 80 },
                    { key: 'ke', title: 'Ke', type: 'text', width: 80 }
                ]
            },
            {
                group: 'Dimensi',
                color: 'dimensi',
                columns: [
                    { key: 'horizon', title: 'Horizon', type: 'numeric', width: 80 },
                    { key: 'vertical', title: 'Vertical', type: 'numeric', width: 80 },
                    { key: 'up_08', title: 'UP 0.8', type: 'numeric', width: 80 }
                ]
            },
            {
                group: 'Kabel',
                color: 'kabel',
                columns: [
                    { key: 'utp', title: 'UTP', type: 'numeric', width: 80 },
                    { key: 'face_plate', title: 'Face Plate 1 hole', type: 'numeric', width: 100 },
                    { key: 'modular_jack', title: 'modular jack', type: 'numeric', width: 100 },
                    { key: 'outbow', title: 'Outbow', type: 'numeric', width: 80 },
                    { key: 'patchcord', title: 'Patchcord UTP 1 meter', type: 'numeric', width: 120 },
                    { key: 'wiring_mgmt', title: 'wiring management', type: 'numeric', width: 120 },
                    { key: 'ap', title: 'AP', type: 'numeric', width: 80 }
                ]
            }
        ];
    }

    // Build columns from headers
    buildColumns(headers) {
        const columns = [];
        headers.forEach(group => {
            group.columns.forEach(col => {
                columns.push({
                    title: col.title,
                    width: col.width || 100,
                    type: col.type === 'numeric' ? 'numeric' : 'text',
                    name: col.key,
                    align: 'center'
                });
            });
        });
        return columns;
    }

    // Build nested headers for groups
    buildNestedHeaders(headers) {
        const nestedHeaders = [];
        headers.forEach(group => {
            nestedHeaders.push({
                title: group.group,
                colspan: group.columns.length
            });
        });
        return [nestedHeaders];
    }

    // Convert row data to array format
    convertDataToArray(data, headers) {
        if (!data || !data.length) return [];
        return data.map(row => {
            const rowArray = [];
            headers.forEach(group => {
                group.columns.forEach(col => {
                    rowArray.push(row[col.key] || '');
                });
            });
            return rowArray;
        });
    }

    // Convert array data back to object format
    convertArrayToData(arrayData, headers) {
        if (!arrayData) return [];
        return arrayData.map(row => {
            const rowObj = {};
            let colIdx = 0;
            headers.forEach(group => {
                group.columns.forEach(col => {
                    rowObj[col.key] = row[colIdx] || '';
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
        area.headers.forEach(group => {
            group.columns.forEach(col => {
                if (col.type === 'numeric') {
                    let sum = 0;
                    data.forEach(row => {
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
        if (area.worksheet && typeof area.worksheet.getData === 'function') {
            return area.worksheet.getData();
        }
        if (area.spreadsheetInstance && area.spreadsheetInstance[0] && typeof area.spreadsheetInstance[0].getData === 'function') {
            return area.spreadsheetInstance[0].getData();
        }
        return area.currentData || [];
    }

    // Update totals display for an area
    updateTotalsDisplay(area) {
        const totals = this.calculateTotals(area);
        const totalsTable = document.getElementById(`area-${area.id}-totals-table`);
        const totalsRow = document.getElementById(`area-${area.id}-totals`);
        
        if (!totalsRow || !totalsTable) return;
        
        // Get jspreadsheet table and read actual cell widths
        const jssTable = area.container.querySelector('.jss, .jexcel');
        let columnWidths = [];
        
        if (jssTable) {
            // Get widths from the last header row (column names row)
            const headerRows = jssTable.querySelectorAll('thead tr');
            const lastHeaderRow = headerRows[headerRows.length - 1];
            if (lastHeaderRow) {
                const cells = lastHeaderRow.querySelectorAll('td');
                cells.forEach(cell => {
                    columnWidths.push(cell.offsetWidth);
                });
            }
            
            // Match table width exactly
            totalsTable.style.width = jssTable.offsetWidth + 'px';
            totalsTable.style.tableLayout = 'fixed';
            totalsTable.style.borderCollapse = 'collapse';
        }
        
        // Remove old colgroup if exists
        const existingColgroup = totalsTable.querySelector('colgroup');
        if (existingColgroup) {
            existingColgroup.remove();
        }
        
        // Groups that should NOT have totals
        const excludedGroups = ['lokasi', 'dimensi'];
        
        // Build cells with explicit widths
        let html = '';
        let colIdx = 0;
        
        // First column (row number column) - label
        const firstWidth = columnWidths[colIdx] || 50;
        html += `<td style="font-weight: bold; background: #fef3c7; padding: 4px 8px; border: 1px solid #ccc; text-align: center; white-space: nowrap; width: ${firstWidth}px;">TOTAL</td>`;
        colIdx++;
        
        // Each column matches the header column above
        area.headers.forEach(group => {
            const groupName = (group.group || '').toLowerCase();
            const isExcludedGroup = excludedGroups.includes(groupName);
            
            group.columns.forEach(col => {
                const width = columnWidths[colIdx] || 100;
                if (col.type === 'numeric' && !isExcludedGroup) {
                    const value = totals[col.key] || 0;
                    html += `<td style="font-weight: bold; background: #fef3c7; padding: 4px 8px; border: 1px solid #ccc; text-align: center; width: ${width}px;">${value}</td>`;
                } else {
                    // Empty cell for non-numeric columns or excluded groups
                    html += `<td style="font-weight: bold; background: #fef3c7; padding: 4px 8px; border: 1px solid #ccc; width: ${width}px;"></td>`;
                }
                colIdx++;
            });
        });
        
        totalsRow.innerHTML = html;
    }

    // Initialize with data from server
    async init() {
        // Clear container
        this.container.innerHTML = '';
        
        // Load saved areas
        const savedAreas = await this.loadData();
        
        if (savedAreas.length === 0) {
            // Create one default area
            this.addArea();
        } else {
            // Create areas from saved data
            savedAreas.forEach(areaData => {
                this.addArea(areaData.area_name, areaData.headers, areaData.data, areaData.id, areaData.comments);
            });
        }
    }

    // Add a new area
    addArea(areaName = '', headers = null, data = null, serverId = null, comments = null) {
        const areaId = ++this.areaCounter;
        const areaHeaders = headers || this.getDefaultHeaders();
        
        // Load comments for this area
        if (comments) {
            console.log('Loading comments for area', areaId, ':', comments);
            this.comments[areaId] = comments;
        }
        
        const area = {
            id: areaId,
            serverId: serverId,
            areaName: areaName,
            headers: areaHeaders,
            spreadsheetInstance: null,
            worksheet: null,
            currentData: null
        };
        
        // Create area container
        const areaWrapper = document.createElement('div');
        areaWrapper.id = `area-wrapper-${areaId}`;
        areaWrapper.className = 'area-wrapper';
        areaWrapper.style.cssText = 'margin-bottom: 30px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 2px solid #e2e8f0;';
        
        // Area header with name input and delete button
        const areaHeader = document.createElement('div');
        areaHeader.style.cssText = 'display: flex; align-items: center; gap: 15px; margin-bottom: 15px;';
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
        const columnButtons = document.createElement('div');
        columnButtons.style.cssText = 'display: flex; gap: 8px; margin-bottom: 10px;';
        columnButtons.innerHTML = `
            <button type="button" class="btn-add-group" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #8b5cf6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                + Tambah Grup
            </button>
            <button type="button" class="btn-add-col" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                + Tambah Kolom
            </button>
            <button type="button" class="btn-remove-group" data-area-id="${areaId}"
                    style="padding: 6px 12px; background: #f97316; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                - Hapus Grup
            </button>
        `;
        areaWrapper.appendChild(columnButtons);
        
        // Scrollable spreadsheet container
        const scrollContainer = document.createElement('div');
        scrollContainer.id = `area-${areaId}-scroll`;
        scrollContainer.style.cssText = 'overflow-x: auto; overflow-y: visible; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; background: white;';
        
        // Inner wrapper to keep spreadsheet and totals together
        const innerWrapper = document.createElement('div');
        innerWrapper.style.cssText = 'min-width: fit-content;';
        
        // Spreadsheet container
        const spreadsheetContainer = document.createElement('div');
        spreadsheetContainer.id = `area-${areaId}-spreadsheet`;
        innerWrapper.appendChild(spreadsheetContainer);
        
        // Totals row - inside the same scroll container
        const totalsContainer = document.createElement('div');
        totalsContainer.id = `area-${areaId}-totals-container`;
        totalsContainer.innerHTML = `<table id="area-${areaId}-totals-table" style="border-collapse: collapse; margin-top: -1px; table-layout: fixed;"><tbody><tr id="area-${areaId}-totals"></tr></tbody></table>`;
        innerWrapper.appendChild(totalsContainer);
        
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
            arrayData = [new Array(columns.length).fill('')];
        }
        
        area.currentData = arrayData;
        area.container.innerHTML = '';
        
        const self = this;
        
        try {
            area.spreadsheetInstance = jspreadsheet(area.container, {
                worksheets: [{
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
                    defaultColWidth: 100
                }],
                contextMenu: function(obj, x, y, e, items) {
                    // Filter out comments and add our custom comment option
                    const filteredItems = [];
                    
                    // Add our custom comment option first
                    const existingComment = self.getComment(area.id, y, x);
                    if (existingComment) {
                        filteredItems.push({
                            title: '✏️ Edit Comment',
                            onclick: function() {
                                const newComment = prompt('Edit comment:', existingComment);
                                if (newComment !== null) {
                                    self.setComment(area.id, y, x, newComment);
                                }
                            }
                        });
                        filteredItems.push({
                            title: '🗑️ Delete Comment',
                            onclick: function() {
                                if (confirm('Delete this comment?')) {
                                    self.deleteComment(area.id, y, x);
                                }
                            }
                        });
                    } else {
                        filteredItems.push({
                            title: '💬 Add Comment',
                            onclick: function() {
                                const comment = prompt('Enter comment:');
                                if (comment) {
                                    self.setComment(area.id, y, x, comment);
                                }
                            }
                        });
                    }
                    
                    // Add separator
                    filteredItems.push({ type: 'line' });
                    
                    // Add standard items except comments
                    items.forEach(item => {
                        if (item && item.title && item.title.toLowerCase().includes('comment')) {
                            return;
                        }
                        filteredItems.push(item);
                    });
                    
                    return filteredItems;
                },
                onchange: function() {
                    self.updateTotalsDisplay(area);
                },
                onafterchanges: function() {
                    self.updateTotalsDisplay(area);
                },
                oninsertrow: function() {
                    self.updateTotalsDisplay(area);
                },
                ondeleterow: function() {
                    self.updateTotalsDisplay(area);
                },
                onresizecolumn: function() {
                    // Delay to allow DOM to update
                    setTimeout(() => self.updateTotalsDisplay(area), 10);
                }
            });
            
            if (area.spreadsheetInstance && area.spreadsheetInstance[0]) {
                area.worksheet = area.spreadsheetInstance[0];
            }
        } catch (err) {
            console.error('Error creating jspreadsheet:', err);
            this.createFallbackTable(area, arrayData, columns, nestedHeaders);
        }
        
        this.styleAreaHeaders(area);
        // Delay initial totals display to ensure DOM is rendered
        setTimeout(() => this.updateTotalsDisplay(area), 50);
        // Bind comment events after DOM is ready
        this.bindCommentEvents(area);
    }

    // Style headers for an area
    styleAreaHeaders(area) {
        setTimeout(() => {
            const nestedCells = area.container.querySelectorAll('.jss_nested thead td, .jexcel_nested thead td');
            let groupIdx = 0;
            
            nestedCells.forEach(cell => {
                if (groupIdx < area.headers.length) {
                    const group = area.headers[groupIdx];
                    const color = this.groupColors[group.color] || this.groupColors.default;
                    cell.style.backgroundColor = color;
                    cell.style.color = 'white';
                    cell.style.fontWeight = 'bold';
                    cell.style.textAlign = 'center';
                    cell.style.borderRight = '2px solid rgba(255,255,255,0.3)';
                    groupIdx++;
                }
            });
            
            const headerCells = area.container.querySelectorAll('.jss > thead td, .jexcel > thead td');
            headerCells.forEach(cell => {
                cell.style.textAlign = 'center';
                cell.style.fontWeight = '600';
            });
        }, 100);
    }

    // Bind events for area buttons
    bindAreaEvents(area) {
        const wrapper = document.getElementById(`area-wrapper-${area.id}`);
        if (!wrapper) return;
        
        const self = this;
        
        // Delete area button
        wrapper.querySelector('.btn-delete-area')?.addEventListener('click', () => {
            if (this.areas.length <= 1) {
                alert('Minimal harus ada satu area!');
                return;
            }
            if (!confirm(`Hapus area "${area.areaName || 'Tanpa Nama'}"?`)) return;
            this.removeArea(area.id);
        });
        
        // Add group button
        wrapper.querySelector('.btn-add-group')?.addEventListener('click', () => {
            const groupName = prompt('Masukkan nama grup kolom baru:');
            if (!groupName) return;
            
            const columnName = prompt('Masukkan nama kolom pertama:');
            if (!columnName) return;
            
            const isNumeric = confirm('Kolom berisi angka? (OK = Ya, Cancel = Teks)');
            this.addColumnGroup(area, groupName, columnName, isNumeric);
        });
        
        // Add column button
        wrapper.querySelector('.btn-add-col')?.addEventListener('click', () => {
            const groups = area.headers.map((g, i) => `${i + 1}. ${g.group}`).join('\n');
            if (area.headers.length === 0) {
                alert('Belum ada grup kolom!');
                return;
            }
            
            const input = prompt(`Pilih nomor grup:\n${groups}`);
            if (!input) return;
            
            const groupIdx = parseInt(input) - 1;
            if (isNaN(groupIdx) || groupIdx < 0 || groupIdx >= area.headers.length) {
                alert('Nomor tidak valid!');
                return;
            }
            
            const columnName = prompt('Masukkan nama kolom:');
            if (!columnName) return;
            
            const isNumeric = confirm('Kolom berisi angka? (OK = Ya, Cancel = Teks)');
            this.addColumnToGroup(area, groupIdx, columnName, isNumeric);
        });
        
        // Remove group button
        wrapper.querySelector('.btn-remove-group')?.addEventListener('click', () => {
            if (area.headers.length <= 1) {
                alert('Minimal harus ada satu grup kolom!');
                return;
            }
            
            const groups = area.headers.map((g, i) => `${i + 1}. ${g.group}`).join('\n');
            const input = prompt(`Pilih nomor grup yang akan dihapus:\n${groups}`);
            if (!input) return;
            
            const groupIdx = parseInt(input) - 1;
            if (isNaN(groupIdx) || groupIdx < 0 || groupIdx >= area.headers.length) {
                alert('Nomor tidak valid!');
                return;
            }
            
            if (!confirm(`Hapus grup "${area.headers[groupIdx].group}"?`)) return;
            this.removeColumnGroup(area, groupIdx);
        });
    }

    // Remove an area
    removeArea(areaId) {
        const idx = this.areas.findIndex(a => a.id === areaId);
        if (idx === -1) return;
        
        const area = this.areas[idx];
        
        // Destroy spreadsheet
        if (area.spreadsheetInstance && typeof area.spreadsheetInstance.destroy === 'function') {
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
        const key = columnName.toLowerCase().replace(/[^a-z0-9]/g, '_');
        const colorKeys = Object.keys(this.groupColors);
        const colorIdx = area.headers.length % colorKeys.length;
        const color = colorKeys[colorIdx];
        
        area.headers.push({
            group: groupName,
            color: color,
            columns: [{
                key: key,
                title: columnName,
                type: isNumeric ? 'numeric' : 'text',
                width: 100
            }]
        });
        
        this.reinitializeArea(area);
    }

    // Add column to existing group
    addColumnToGroup(area, groupIndex, columnName, isNumeric = true) {
        if (groupIndex < 0 || groupIndex >= area.headers.length) return;
        
        const key = columnName.toLowerCase().replace(/[^a-z0-9]/g, '_');
        area.headers[groupIndex].columns.push({
            key: key,
            title: columnName,
            type: isNumeric ? 'numeric' : 'text',
            width: 100
        });
        
        this.reinitializeArea(area);
    }

    // Remove column group
    removeColumnGroup(area, groupIndex) {
        if (area.headers.length <= 1) return false;
        if (groupIndex < 0 || groupIndex >= area.headers.length) return false;
        
        area.headers.splice(groupIndex, 1);
        this.reinitializeArea(area);
        return true;
    }

    // Reinitialize area's spreadsheet
    reinitializeArea(area) {
        // Get current data
        const currentArrayData = this.getAreaData(area);
        const currentObjData = this.convertArrayToData(currentArrayData, area.headers);
        
        // Destroy current
        if (area.spreadsheetInstance && typeof area.spreadsheetInstance.destroy === 'function') {
            area.spreadsheetInstance.destroy();
        } else if (area.worksheet && typeof area.worksheet.destroy === 'function') {
            area.worksheet.destroy();
        }
        
        // Rebuild
        this.initAreaSpreadsheet(area, currentObjData);
    }

    // Fallback table if jspreadsheet fails
    createFallbackTable(area, data, columns, nestedHeaders) {
        console.log('Using fallback HTML table for area', area.id);
        
        let tableHtml = '<table class="survey-fallback-table" style="width: 100%; border-collapse: collapse;">';
        
        tableHtml += '<thead><tr>';
        nestedHeaders[0].forEach(nh => {
            tableHtml += `<th colspan="${nh.colspan}" style="background: #6b7280; color: white; padding: 8px; border: 1px solid #ddd; text-align: center;">${nh.title}</th>`;
        });
        tableHtml += '</tr>';
        
        tableHtml += '<tr>';
        columns.forEach(col => {
            tableHtml += `<th style="background: #f3f4f6; padding: 8px; border: 1px solid #ddd; text-align: center;">${col.title}</th>`;
        });
        tableHtml += '</tr></thead>';
        
        tableHtml += '<tbody>';
        data.forEach((row, rowIdx) => {
            tableHtml += '<tr>';
            row.forEach((cell, colIdx) => {
                const col = columns[colIdx];
                const inputType = col.type === 'numeric' ? 'number' : 'text';
                tableHtml += `<td style="padding: 4px; border: 1px solid #ddd;"><input type="${inputType}" value="${cell}" data-row="${rowIdx}" data-col="${colIdx}" style="width: 100%; padding: 4px; border: 1px solid #ccc; border-radius: 3px; text-align: center;"></td>`;
            });
            tableHtml += '</tr>';
        });
        tableHtml += '</tbody></table>';
        
        area.container.innerHTML = tableHtml;
        
        const self = this;
        area.container.querySelectorAll('input').forEach(input => {
            input.addEventListener('change', function() {
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
            const response = await fetch(`${this.baseUrl}/rekap/${this.rekapId}/surveys`);
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                return [];
            }
            
            const result = await response.json();
            console.log('Loaded surveys from server:', result);
            if (result.success && result.surveys) {
                return result.surveys;
            }
        } catch (err) {
            console.error('Error loading surveys:', err);
        }
        return [];
    }

    // Save all areas to server
    async save() {
        console.log('save() called, this.comments:', JSON.stringify(this.comments));
        const areasData = this.areas.map(area => {
            const areaNameInput = document.getElementById(`area-${area.id}-name`);
            const areaName = areaNameInput ? areaNameInput.value : '';
            
            const arrayData = this.getAreaData(area);
            const objData = this.convertArrayToData(arrayData, area.headers);
            
            // Filter empty rows
            const filteredData = objData.filter(row => {
                return Object.values(row).some(v => v !== '' && v !== null && v !== undefined);
            });
            
            console.log('Area', area.id, 'comments:', this.comments[area.id]);
            
            return {
                id: area.serverId,
                area_name: areaName,
                headers: area.headers,
                data: filteredData.length > 0 ? filteredData : [],
                comments: this.comments[area.id] || {}
            };
        });
        
        console.log('areasData to send:', JSON.stringify(areasData, null, 2));
        
        try {
            const response = await fetch(`${this.baseUrl}/rekap/${this.rekapId}/surveys`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ areas: areasData })
            });
            
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
            console.error('Error saving surveys:', err);
            return false;
        }
    }

    // Get list of groups for a specific area
    getGroupList(areaId = null) {
        if (areaId === null && this.areas.length > 0) {
            // Return first area's groups for backward compatibility
            return this.areas[0].headers.map((h, i) => ({ index: i, name: h.group }));
        }
        const area = this.areas.find(a => a.id === areaId);
        if (!area) return [];
        return area.headers.map((h, i) => ({ index: i, name: h.group }));
    }

    // Get all areas info
    getAllAreas() {
        return this.areas.map(a => ({
            id: a.id,
            serverId: a.serverId,
            areaName: document.getElementById(`area-${a.id}-name`)?.value || a.areaName
        }));
    }

    // Inject CSS styles
    injectStyles() {
        const styleId = 'survey-spreadsheet-styles';
        if (document.getElementById(styleId)) return;
        
        const style = document.createElement('style');
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
        `;
        document.head.appendChild(style);
    }
}

// Export for global access
window.SurveySpreadsheet = SurveySpreadsheet;
export default SurveySpreadsheet;
