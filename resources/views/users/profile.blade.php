@extends('layouts.app')

@section('content')
    <style>
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
            background-color: #fff;
        }

        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-input:disabled {
            background-color: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
        }

        .form-input.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .error-message {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .password-section {
            background: #fef7f0;
            border: 2px solid #fed7aa;
        }

        .security-icon {
            color: #ea580c;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
        }
    </style>

    <div class="container mx-auto p-6 max-w-4xl">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information Sidebar -->
            <div class="lg:col-span-1">
                <div class="profile-card">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Akun</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">User ID</label>
                                <p class="font-mono text-sm bg-gray-100 px-3 py-1 rounded mt-1">#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm font-medium text-gray-500">Bergabung</label>
                                <p class="mt-1">{{ $user->created_at->format('d F Y') }}</p>
                            </div>
                            
                            <div>
                                <label class="text-sm font-medium text-gray-500">Terakhir Update</label>
                                <p class="mt-1">{{ $user->updated_at->format('d F Y H:i') }}</p>
                            </div>
                            
                            @if($user->nohp)
                            <div>
                                <label class="text-sm font-medium text-gray-500">No. HP</label>
                                <p class="mt-1">{{ $user->nohp }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="profile-card">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktivitas</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Penawaran</span>
                                <span class="font-semibold">{{ $user->penawarans()->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rekap Survey</span>
                                <span class="font-semibold">{{ $user->rekaps()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Form Edit Data Diri -->
                <div class="profile-card">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Edit Data Diri
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Update informasi personal Anda</p>
                    </div>

                    <form id="profileForm" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Nama Lengkap</label>
                                <input type="text" id="name" name="name" class="form-input" value="{{ $user->name }}" required>
                                <div id="name-error" class="error-message" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input" value="{{ $user->email }}" required>
                                <div id="email-error" class="error-message" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="departemen">Departemen</label>
                                <input type="text" id="departemen" name="departemen" class="form-input" value="{{ $user->departemen }}" required>
                                <div id="departemen-error" class="error-message" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="kantor">Kantor</label>
                                <select id="kantor" name="kantor" class="form-input" required>
                                    <option value="">Pilih Kantor</option>
                                    <option value="Pusat" {{ $user->kantor === 'Pusat' ? 'selected' : '' }}>Pusat</option>
                                    <option value="Surabaya" {{ $user->kantor === 'Surabaya' ? 'selected' : '' }}>Surabaya</option>
                                </select>
                                <div id="kantor-error" class="error-message" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nohp">No. HP</label>
                                <input type="text" id="nohp" name="nohp" class="form-input" value="{{ $user->nohp }}" placeholder="Contoh: 081234567890">
                                <div id="nohp-error" class="error-message" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="role">Role</label>
                                <input type="text" class="form-input" value="{{ ucfirst($user->role) }}" disabled>
                                <p class="text-xs text-gray-500 mt-1">Role tidak dapat diubah sendiri</p>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 pt-6 border-t border-gray-200">
                            <button type="submit" class="btn-primary" id="submitProfileBtn">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Simpan Data Diri
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Form Ubah Password -->
                <div class="profile-card password-section">
                    <div class="p-6 border-b border-orange-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 security-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Ubah Password
                        </h2>
                        <p class="text-sm text-orange-700 mt-1">Pastikan menggunakan password yang kuat untuk keamanan akun</p>
                    </div>

                    <form id="passwordForm" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div class="form-group">
                                <label class="form-label" for="current_password">Password Saat Ini</label>
                                <input type="password" id="current_password" name="current_password" class="form-input" required placeholder="Masukkan password saat ini">
                                <div id="current_password-error" class="error-message" style="display: none;"></div>
                                <p class="text-xs text-orange-600 mt-1">Wajib diisi untuk verifikasi</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label" for="password">Password Baru</label>
                                    <input type="password" id="password" name="password" class="form-input" required placeholder="Minimal 6 karakter">
                                    <div id="password-error" class="error-message" style="display: none;"></div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required placeholder="Ulangi password baru">
                                    <div id="password_confirmation-error" class="error-message" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 pt-6 border-t border-orange-200">
                            <button type="submit" class="btn-danger" id="submitPasswordBtn">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out z-50 translate-x-full opacity-0">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span id="toastMessage">Berhasil diupdate!</span>
        </div>
    </div>

    <!-- Error Toast -->
    <div id="errorToast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out z-50 translate-x-full opacity-0">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span id="errorToastMessage">Terjadi kesalahan!</span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const submitProfileBtn = document.getElementById('submitProfileBtn');
    const submitPasswordBtn = document.getElementById('submitPasswordBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const successToast = document.getElementById('successToast');
    const errorToast = document.getElementById('errorToast');

    // Form Data Diri Submit
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        submitProfileBtn.disabled = true;
        submitProfileBtn.classList.add('opacity-70');
        loadingOverlay.style.display = 'flex';

        const formData = new FormData(profileForm);
        
        fetch('{{ route("profile.update") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearErrors('profile');
            if (data.notify) {
                if (data.notify.type === 'success') {
                    showSuccessToast(data.notify.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showErrorToast(data.notify.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorToast('Terjadi kesalahan saat menyimpan data');
        })
        .finally(() => {
            submitProfileBtn.disabled = false;
            submitProfileBtn.classList.remove('opacity-70');
            loadingOverlay.style.display = 'none';
        });
    });

    // Form Password Submit
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        submitPasswordBtn.disabled = true;
        submitPasswordBtn.classList.add('opacity-70');
        loadingOverlay.style.display = 'flex';

        const formData = new FormData(passwordForm);
        
        fetch('{{ route("profile.password.change") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearErrors('password');
            if (data.notify) {
                if (data.notify.type === 'success') {
                    showSuccessToast(data.notify.message);
                    passwordForm.reset();
                } else {
                    showErrorToast(data.notify.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorToast('Terjadi kesalahan saat mengubah password');
        })
        .finally(() => {
            submitPasswordBtn.disabled = false;
            submitPasswordBtn.classList.remove('opacity-70');
            loadingOverlay.style.display = 'none';
        });
    });

    // Password confirmation validation
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirmation');
    
    function validatePassword() {
        if (password.value && passwordConfirm.value) {
            if (password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Password tidak sama');
                showFieldError('password_confirmation', 'Password tidak sama');
            } else {
                passwordConfirm.setCustomValidity('');
                clearFieldError('password_confirmation');
            }
        }
    }

    password.addEventListener('input', validatePassword);
    passwordConfirm.addEventListener('input', validatePassword);

    // Helper functions
    function showSuccessToast(message) {
        document.getElementById('toastMessage').textContent = message;
        successToast.classList.remove('translate-x-full', 'opacity-0');
        successToast.classList.add('translate-x-0', 'opacity-100');
        
        setTimeout(() => {
            successToast.classList.remove('translate-x-0', 'opacity-100');
            successToast.classList.add('translate-x-full', 'opacity-0');
        }, 3000);
    }

    function showErrorToast(message) {
        document.getElementById('errorToastMessage').textContent = message;
        errorToast.classList.remove('translate-x-full', 'opacity-0');
        errorToast.classList.add('translate-x-0', 'opacity-100');
        
        setTimeout(() => {
            errorToast.classList.remove('translate-x-0', 'opacity-100');
            errorToast.classList.add('translate-x-full', 'opacity-0');
        }, 3000);
    }

    function showFieldError(fieldName, message) {
        const field = document.getElementById(fieldName);
        const errorDiv = document.getElementById(fieldName + '-error');
        
        if (field && errorDiv) {
            field.classList.add('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }

    function clearFieldError(fieldName) {
        const field = document.getElementById(fieldName);
        const errorDiv = document.getElementById(fieldName + '-error');
        
        if (field && errorDiv) {
            field.classList.remove('error');
            errorDiv.style.display = 'none';
        }
    }

    function clearErrors(formType) {
        const fields = formType === 'profile' 
            ? ['name', 'email', 'departemen', 'kantor', 'nohp']
            : ['current_password', 'password', 'password_confirmation'];
            
        fields.forEach(field => clearFieldError(field));
    }
});
</script>
@endpush