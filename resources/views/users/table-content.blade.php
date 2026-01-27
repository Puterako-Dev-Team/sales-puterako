
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-green-500 text-white">
                <th class="px-4 py-3 text-left font-semibold rounded-tl-md">
                    <button class="sort-button flex items-center gap-1 hover:bg-green-600 rounded px-2 py-1 transition" 
                            data-column="name" data-direction="{{ request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                        Nama
                        @if(request('sort') == 'name')
                            @if(request('direction') == 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3v18l7-7 7 7V3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17 17V3l-7 7-7-7v14z"/>
                                </svg>
                            @endif
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 12l5-5 5 5H5z"/>
                            </svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-semibold">
                    <button class="sort-button flex items-center gap-1 hover:bg-green-600 rounded px-2 py-1 transition" 
                            data-column="email" data-direction="{{ request('sort') == 'email' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                        Email
                        @if(request('sort') == 'email')
                            @if(request('direction') == 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3v18l7-7 7 7V3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17 17V3l-7 7-7-7v14z"/>
                                </svg>
                            @endif
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 12l5-5 5 5H5z"/>
                            </svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-semibold">
                    <button class="sort-button flex items-center gap-1 hover:bg-green-600 rounded px-2 py-1 transition" 
                            data-column="role" data-direction="{{ request('sort') == 'role' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                        Role
                        @if(request('sort') == 'role')
                            @if(request('direction') == 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3v18l7-7 7 7V3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17 17V3l-7 7-7-7v14z"/>
                                </svg>
                            @endif
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 12l5-5 5 5H5z"/>
                            </svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-semibold">Departemen</th>
                <th class="px-4 py-3 text-left font-semibold">Kantor</th>
                <th class="px-4 py-3 text-left font-semibold">No. HP</th>
                <th class="px-4 py-3 text-center font-semibold rounded-tr-md">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-b hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                <span class="text-white text-xs font-semibold">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                            </div>
                            <span class="font-medium">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                            @if($user->role === 'administrator') bg-red-100 text-red-800
                            @elseif($user->role === 'direktur') bg-purple-100 text-purple-800
                            @elseif($user->role === 'manager') bg-blue-100 text-blue-800
                            @elseif($user->role === 'supervisor') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->departemen }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->kantor }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->nohp ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <button class="btn-edit bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600 transition-colors" 
                                    data-id="{{ $user->id }}" title="Edit">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            @if($user->id !== Auth::id())
                            <button class="btn-delete bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600 transition-colors" 
                                    data-id="{{ $user->id }}" title="Hapus">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            @else
                            <span class="text-gray-400 text-xs px-3 py-1">Current User</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-8 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Tidak ada user yang ditemukan</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>