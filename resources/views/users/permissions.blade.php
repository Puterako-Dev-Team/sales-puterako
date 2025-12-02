
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Atur Hak Akses</h1>
            <p class="text-gray-600 mt-1">Manage user roles and permissions</p>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium">User Roles Management</h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($users as $user)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-4">
                            <span class="text-white font-semibold text-sm">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">{{ $user->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            <p class="text-xs text-gray-400">{{ $user->departemen }} - {{ $user->kantor }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                            @if($user->role === 'administrator') bg-red-100 text-red-800
                            @elseif($user->role === 'direktur') bg-purple-100 text-purple-800
                            @elseif($user->role === 'manager') bg-blue-100 text-blue-800
                            @elseif($user->role === 'supervisor') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($user->role) }}
                        </span>
                        
                        @if($user->id !== Auth::id())
                        <select class="role-select text-sm border border-gray-300 rounded px-2 py-1" data-user-id="{{ $user->id }}">
                            <option value="administrator" {{ $user->role === 'administrator' ? 'selected' : '' }}>Administrator</option>
                            <option value="direktur" {{ $user->role === 'direktur' ? 'selected' : '' }}>Direktur</option>
                            <option value="manager" {{ $user->role === 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="supervisor" {{ $user->role === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="staff" {{ $user->role === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        @else
                        <span class="text-gray-400 text-sm">Current User</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.role-select').forEach(select => {
        select.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const newRole = this.value;
            
            fetch(`{{ url('users') }}/${userId}/permissions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    role: newRole
                })
            })
            .then(response => response.json())
            .then(data => {
                if (window.toast) {
                    toast(data.notify);
                } else {
                    console.log('Role updated:', data.notify.message);
                }
                // Reload page to update role badges
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                console.error('Error updating role:', error);
                // Revert select to original value
                this.value = this.dataset.originalValue || this.value;
            });
        });
        
        // Store original value for rollback
        select.dataset.originalValue = select.value;
    });
});
</script>
@endpush