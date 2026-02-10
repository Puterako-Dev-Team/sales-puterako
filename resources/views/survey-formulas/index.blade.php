{{-- filepath: resources/views/survey-formulas/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <style>
        .filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }

        .formula-table {
            width: 100%;
            border-collapse: collapse;
        }

        .formula-table th,
        .formula-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .formula-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .formula-table tr:hover {
            background: #f9fafb;
        }

        .formula-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .formula-badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .formula-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .dependency-tag {
            display: inline-block;
            padding: 2px 8px;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .formula-expression {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .action-btn.edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-btn.edit:hover {
            background: #bfdbfe;
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn.delete:hover {
            background: #fecaca;
        }

        .action-btn.toggle {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btn.toggle:hover {
            background: #fde68a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            color: #d1d5db;
        }

        #formPanel {
            max-height: 100vh;
            overflow-y: auto;
        }

        #formPanel::-webkit-scrollbar {
            width: 6px;
        }

        #formPanel::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #formPanel::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .help-text {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .formula-help {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
        }

        .formula-help h4 {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e40af;
        }

        .formula-help ul {
            margin: 0;
            padding-left: 20px;
            font-size: 0.875rem;
            color: #374151;
        }

        .formula-help li {
            margin-bottom: 4px;
        }

        .formula-help code {
            background: #dbeafe;
            padding: 1px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>

    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Formula Survey Presales</h1>
                <p class="text-gray-600 mt-1">Kelola rumus kalkulasi otomatis untuk kolom survey</p>
            </div>
            <div class="flex gap-3">
                <button id="btnSeedDefaults" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Load Default Formulas
                </button>
                <button id="btnTambah" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Formula
                </button>
            </div>
        </div>

        <!-- Info Card -->
        <div class="filter-card">
            <div class="formula-help">
                <h4>Panduan Penggunaan Formula:</h4>
                <ul>
                    <li>Gunakan <code>column_key</code> dari kolom sebagai variabel (contoh: <code>horizon</code>, <code>vertical</code>)</li>
                    <li>Operator yang didukung: <code>+</code> (tambah), <code>-</code> (kurang), <code>*</code> (kali), <code>/</code> (bagi)</li>
                    <li>Gunakan tanda kurung <code>()</code> untuk mengatur urutan operasi</li>
                    <li>Contoh: <code>(horizon + vertical) / 0.8</code> untuk menghitung UP 0.8</li>
                    <li>Formula dapat bergantung pada kolom lain yang juga memiliki formula (cascade)</li>
                </ul>
            </div>
            
            <!-- Available Column Keys Reference -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg border">
                <h4 class="font-semibold text-gray-700 mb-3">Cara Menentukan Column Key:</h4>
                <div class="text-sm text-gray-600 space-y-3">
                    <p>Column key dibuat otomatis dari <strong>nama kolom</strong> yang dibuat user di spreadsheet survey:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Semua huruf menjadi <strong>huruf kecil</strong></li>
                        <li><strong>Titik desimal (.) dihapus</strong> (contoh: <code>0.8</code> → <code>08</code>)</li>
                        <li>Spasi, koma, dan karakter khusus lainnya diganti dengan <strong>underscore (_)</strong></li>
                    </ul>
                    
                    <div class="bg-white p-3 rounded border mt-3">
                        <p class="font-medium text-gray-700 mb-2">Contoh konversi nama kolom → column_key:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                            <div><span class="text-gray-500">Horizon</span> → <code>horizon</code></div>
                            <div><span class="text-gray-500">Vertical</span> → <code>vertical</code></div>
                            <div><span class="text-gray-500">UP 0.8</span> → <code>up_08</code></div>
                            <div><span class="text-gray-500">Face Plate 1 hole</span> → <code>face_plate_1_hole</code></div>
                            <div><span class="text-gray-500">modular jack</span> → <code>modular_jack</code></div>
                            <div><span class="text-gray-500">NYY 3 X 1,5</span> → <code>nyy_3_x_1_5</code></div>
                            <div><span class="text-gray-500">Pipa Conduit</span> → <code>pipa_conduit</code></div>
                            <div><span class="text-gray-500">Tes</span> → <code>tes</code></div>
                            <div><span class="text-gray-500">Kabel ABC</span> → <code>kabel_abc</code></div>
                        </div>
                    </div>
                    
                    <p class="text-amber-600 mt-2">
                        <strong>Tips:</strong> Lihat nama kolom di spreadsheet survey Anda, lalu konversi ke column_key menggunakan aturan di atas.
                    </p>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white shadow rounded-lg overflow-hidden" id="tableContainer">
            @if($formulas->count() > 0)
                <table class="formula-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Kolom Target</th>
                            <th>Formula</th>
                            <th>Dependensi</th>
                            <th>Deskripsi</th>
                            <th>Grup</th>
                            <th width="80">Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="formulaTableBody">
                        @foreach($formulas as $index => $formula)
                        <tr data-id="{{ $formula->id }}">
                            <td>{{ $formula->order + 1 }}</td>
                            <td><strong>{{ $formula->column_key }}</strong></td>
                            <td><span class="formula-expression">{{ $formula->formula }}</span></td>
                            <td>
                                @if($formula->dependencies)
                                    @foreach($formula->dependencies as $dep)
                                        <span class="dependency-tag">{{ $dep }}</span>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>{{ $formula->description ?? '-' }}</td>
                            <td>{{ $formula->group_name ?? '-' }}</td>
                            <td>
                                <span class="formula-badge {{ $formula->is_active ? 'active' : 'inactive' }}">
                                    {{ $formula->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button class="action-btn toggle" onclick="toggleFormula({{ $formula->id }})" title="{{ $formula->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        {{ $formula->is_active ? 'Off' : 'On' }}
                                    </button>
                                    <button class="action-btn edit" onclick="editFormula({{ $formula->id }}, {{ json_encode($formula) }})" title="Edit">
                                        Edit
                                    </button>
                                    <button class="action-btn delete" onclick="deleteFormula({{ $formula->id }})" title="Hapus">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada formula</h3>
                    <p class="text-gray-500 mb-4">Klik "Load Default Formulas" untuk memuat formula bawaan atau "Tambah Formula" untuk membuat formula baru.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Slide-over Form -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white shadow-lg transition-transform transform translate-x-full" id="formPanel">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="formTitle" class="text-lg font-semibold text-gray-900">Tambah Formula</h2>
                    <button id="closeForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="formulaForm" class="flex-1 flex flex-col">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="">
                    <input type="hidden" id="editId" name="id" value="">

                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kolom Target <span class="text-red-500">*</span></label>
                            <input type="text" id="f_column_key" name="column_key" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                placeholder="contoh: up_08">
                            <p class="help-text">Nama kolom yang akan dihitung otomatis (gunakan huruf kecil dan underscore)</p>
                            <div id="errorColumnKey" class="text-red-500 text-sm mt-2 hidden"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Formula <span class="text-red-500">*</span></label>
                            <input type="text" id="f_formula" name="formula" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent font-mono" 
                                placeholder="contoh: (horizon + vertical) / 0.8">
                            <p class="help-text">Ekspresi rumus menggunakan nama kolom sebagai variabel</p>
                            <div id="errorFormula" class="text-red-500 text-sm mt-2 hidden"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                            <input type="text" id="f_description" name="description" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                placeholder="contoh: UP 0.8 = (Horizon + Vertical) / 0.8">
                            <p class="help-text">Penjelasan singkat tentang formula ini</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Grup (Opsional)</label>
                            <input type="text" id="f_group_name" name="group_name" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                                placeholder="contoh: Dimensi">
                            <p class="help-text">Batasi formula hanya untuk grup kolom tertentu</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Urutan Eksekusi</label>
                            <input type="number" id="f_order" name="order" min="0" value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <p class="help-text">Formula dengan urutan lebih kecil akan dieksekusi lebih dulu</p>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="f_is_active" name="is_active" value="1" checked
                                    class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <span class="text-sm font-medium text-gray-700">Aktifkan formula ini</span>
                            </label>
                        </div>

                        <!-- Test Formula Section -->
                        <div class="border-t pt-4 mt-4">
                            <h4 class="font-medium text-gray-700 mb-3">Test Formula</h4>
                            <div id="testInputsContainer" class="space-y-2 mb-3">
                                <!-- Dynamic test inputs will be added here -->
                            </div>
                            <button type="button" id="btnTestFormula" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                                Test Formula
                            </button>
                            <div id="testResult" class="mt-3 p-3 rounded-lg hidden"></div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 p-6">
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors">
                                Simpan
                            </button>
                            <button type="button" id="cancelForm" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg transition-colors">
                                Batal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Form elements
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const formulaForm = document.getElementById('formulaForm');
        const formTitle = document.getElementById('formTitle');
        const editIdField = document.getElementById('editId');
        const methodField = document.getElementById('methodField');

        // Open form
        function openForm(title = 'Tambah Formula', isEdit = false) {
            formTitle.textContent = title;
            methodField.value = isEdit ? 'PUT' : '';
            formSlide.classList.remove('hidden');
            setTimeout(() => {
                formPanel.classList.remove('translate-x-full');
            }, 10);
        }

        // Close form
        function closeForm() {
            formPanel.classList.add('translate-x-full');
            setTimeout(() => {
                formSlide.classList.add('hidden');
                resetForm();
            }, 300);
        }

        // Reset form
        function resetForm() {
            formulaForm.reset();
            editIdField.value = '';
            document.getElementById('f_is_active').checked = true;
            document.getElementById('testInputsContainer').innerHTML = '';
            document.getElementById('testResult').classList.add('hidden');
            // Clear errors
            document.querySelectorAll('[id^="error"]').forEach(el => el.classList.add('hidden'));
        }

        // Button event listeners
        document.getElementById('btnTambah').addEventListener('click', () => openForm());
        document.getElementById('closeForm').addEventListener('click', closeForm);
        document.getElementById('cancelForm').addEventListener('click', closeForm);
        formSlide.addEventListener('click', (e) => {
            if (e.target === formSlide) closeForm();
        });

        // Edit formula
        function editFormula(id, data) {
            openForm('Edit Formula', true);
            editIdField.value = id;
            document.getElementById('f_column_key').value = data.column_key;
            document.getElementById('f_formula').value = data.formula;
            document.getElementById('f_description').value = data.description || '';
            document.getElementById('f_group_name').value = data.group_name || '';
            document.getElementById('f_order').value = data.order || 0;
            document.getElementById('f_is_active').checked = data.is_active;
            
            // Generate test inputs
            generateTestInputs(data.dependencies || []);
        }

        // Delete formula
        async function deleteFormula(id) {
            if (!confirm('Yakin ingin menghapus formula ini?')) return;

            try {
                const response = await fetch(`{{ url('survey-formulas') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Gagal menghapus formula');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        }

        // Toggle formula active status
        async function toggleFormula(id) {
            try {
                const response = await fetch(`{{ url('survey-formulas') }}/${id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Gagal mengubah status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        }

        // Seed default formulas
        document.getElementById('btnSeedDefaults').addEventListener('click', async () => {
            if (!confirm('Muat formula default? Formula yang sudah ada tidak akan ditimpa.')) return;

            try {
                const response = await fetch('{{ route("survey-formulas.seed-defaults") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message || 'Gagal memuat formula default');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        });

        // Submit form
        formulaForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                column_key: document.getElementById('f_column_key').value,
                formula: document.getElementById('f_formula').value,
                description: document.getElementById('f_description').value,
                group_name: document.getElementById('f_group_name').value,
                order: parseInt(document.getElementById('f_order').value) || 0,
                is_active: document.getElementById('f_is_active').checked
            };

            const id = editIdField.value;
            const url = id ? `{{ url('survey-formulas') }}/${id}` : '{{ route("survey-formulas.store") }}';
            const method = id ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    // Show validation errors
                    if (response.status === 422) {
                        if (result.message) {
                            document.getElementById('errorFormula').textContent = result.message;
                            document.getElementById('errorFormula').classList.remove('hidden');
                        }
                    } else {
                        alert(result.message || 'Gagal menyimpan formula');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        });

        // Generate test inputs based on formula dependencies
        function generateTestInputs(dependencies) {
            const container = document.getElementById('testInputsContainer');
            container.innerHTML = '';
            
            dependencies.forEach(dep => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-2';
                div.innerHTML = `
                    <label class="text-sm text-gray-600 w-24">${dep}:</label>
                    <input type="number" id="test_${dep}" class="flex-1 px-2 py-1 border rounded text-sm" value="0" step="any">
                `;
                container.appendChild(div);
            });
        }

        // Parse formula to extract dependencies on input change
        document.getElementById('f_formula').addEventListener('input', function() {
            const formula = this.value;
            // Extract variable names (simple regex)
            const matches = formula.match(/\b([a-z][a-z0-9_]*)\b/gi) || [];
            const excluded = ['Math', 'abs', 'ceil', 'floor', 'round', 'max', 'min', 'pow', 'sqrt'];
            const deps = [...new Set(matches.filter(m => !excluded.includes(m) && isNaN(parseFloat(m))))];
            generateTestInputs(deps);
        });

        // Test formula button
        document.getElementById('btnTestFormula').addEventListener('click', async function() {
            const formula = document.getElementById('f_formula').value;
            const testInputs = document.querySelectorAll('#testInputsContainer input');
            const testValues = {};
            
            testInputs.forEach(input => {
                const key = input.id.replace('test_', '');
                testValues[key] = parseFloat(input.value) || 0;
            });

            try {
                const response = await fetch('{{ route("survey-formulas.test") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ formula, test_values: testValues })
                });

                const result = await response.json();
                const resultDiv = document.getElementById('testResult');
                resultDiv.classList.remove('hidden');
                
                if (result.success) {
                    resultDiv.className = 'mt-3 p-3 rounded-lg bg-green-100 text-green-800';
                    resultDiv.innerHTML = `<strong>Hasil:</strong> ${result.result}<br><small>Expression: ${result.expression}</small>`;
                } else {
                    resultDiv.className = 'mt-3 p-3 rounded-lg bg-red-100 text-red-800';
                    resultDiv.textContent = result.message;
                }
            } catch (error) {
                console.error('Error:', error);
                const resultDiv = document.getElementById('testResult');
                resultDiv.classList.remove('hidden');
                resultDiv.className = 'mt-3 p-3 rounded-lg bg-red-100 text-red-800';
                resultDiv.textContent = 'Terjadi kesalahan saat testing';
            }
        });
    </script>
@endsection
