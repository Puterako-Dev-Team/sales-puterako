<div class="overflow-x-auto">
<table class="min-w-full text-sm approval-table">
    <colgroup>
        <col style="width: 6%">
        <col style="width: 18%">
        <col style="width: 8%">
        <col style="width: 20%">
        <col style="width: 16%">
        <col style="width: 14%">
        <col style="width: 10%">
        <col style="width: 8%">
    </colgroup>
    <thead>
        <tr class="bg-green-500 text-white">
            <th class="px-3 py-3 font-semibold text-center rounded-tl-md">No</th>
            <th class="px-3 py-3 font-semibold text-left">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="no_penawaran" data-direction="{{ request('sort') == 'no_penawaran' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    No Penawaran
                    @if(request('sort') == 'no_penawaran')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-3 py-3 font-semibold text-left">Versi</th>
            <th class="px-3 py-3 font-semibold text-left">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="nama_perusahaan" data-direction="{{ request('sort') == 'nama_perusahaan' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Perusahaan
                    @if(request('sort') == 'nama_perusahaan')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-3 py-3 font-semibold text-left">Diminta Oleh</th>
            <th class="px-3 py-3 font-semibold text-left">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="requested_at" data-direction="{{ request('sort') == 'requested_at' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Dibuat
                    @if(request('sort') == 'requested_at')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-3 py-3 font-semibold text-left">Status</th>
            <th class="px-3 py-3 font-semibold text-center rounded-tr-md">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requests as $index => $req)
                <tr class="border-b transition hover:bg-gray-50 text-gray-800">
                    <td class="px-3 py-3 text-center">{{ $requests->firstItem() + $index }}</td>
                    <td class="px-3 py-3">
                        @if($req->penawaran)
                            <a href="{{ route('penawaran.show', ['id' => $req->penawaran_id, 'version' => $req->version->version ?? 0, 'referrer' => 'approval']) }}" class="text-green-600 hover:underline">
                                {{ $req->penawaran->no_penawaran }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-3 py-3">{{ $req->version->version ?? '-' }}</td>
                    <td class="px-3 py-3">{{ $req->penawaran->nama_perusahaan ?? '-' }}</td>
                    <td class="px-3 py-3">{{ $req->requestedBy->name ?? '-' }}</td>
                    <td class="px-3 py-3">{{ $req->requested_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                            @if($req->status === 'fully_approved') bg-green-100 text-green-800
                            @elseif($req->status === 'manager_approved') bg-blue-100 text-blue-800
                            @elseif($req->status === 'supervisor_approved') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ str_replace('_', ' ', ucfirst($req->status)) }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-center">
                        @php
                            $canApprove = false;
                            $approveRoute = null;
                            $canRevise = false;
                            $reviseRoute = null;
                            $isRepresentative = false; // Flag untuk supervisor mewakili direktur
                            $buttonText = 'Approve';
                            $buttonColor = 'bg-green-500';
                            $buttonHoverColor = 'hover:bg-green-600';
                            
                            // Supervisor approval tahap 1 (normal)
                            if ($userRole === 'supervisor' && !$req->approved_by_supervisor) {
                                $canApprove = true;
                                $approveRoute = route('export-approval.approve-supervisor', $req->id);
                            }
                            // Supervisor mewakili direktur (tahap 3) - setelah manager approve
                            elseif ($userRole === 'supervisor' && $req->approved_by_supervisor && $req->approved_by_manager && !$req->approved_by_direktur) {
                                $canApprove = true;
                                $isRepresentative = true;
                                $approveRoute = route('export-approval.approve-direktur', $req->id);
                                $buttonText = 'Wakili Approval';
                                $buttonColor = 'bg-yellow-500';
                                $buttonHoverColor = 'hover:bg-yellow-600';
                            }
                            
                            // Manager approval
                            if (in_array($userRole, ['manager']) && $req->approved_by_supervisor && !$req->approved_by_manager) {
                                $canApprove = true;
                                $approveRoute = route('export-approval.approve-manager', $req->id);
                            }
                            
                            // Direktur approval
                            if ($userRole === 'direktur' && $req->approved_by_manager && !$req->approved_by_direktur) {
                                $canApprove = true;
                                $approveRoute = route('export-approval.approve-direktur', $req->id);
                            }
                            
                            // Manager can revise (when supervisor has approved and waiting for manager decision)
                            // Shows alongside Approve button so manager can choose to approve OR revise
                            $canRevise = in_array($userRole, ['manager']) && $req->approved_by_supervisor && !$req->approved_by_direktur && $req->status !== 'fully_approved';
                            $reviseRoute = $canRevise ? route('export-approval.revisi-manager', $req->id) : null;
                        @endphp

                        <div class="flex items-center justify-center gap-2">
                            {{-- Export Excel Button - Always visible for management --}}
                            <a href="{{ route('export-approval.export-excel', $req->id) }}"
                                class="bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600 transition text-xs font-semibold inline-flex items-center"
                                title="Export Excel">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Excel
                            </a>

                            @if($canApprove)
                                <button type="button"
                                    class="approve-btn {{ $buttonColor }} text-white px-3 py-2 rounded {{ $buttonHoverColor }} transition text-xs font-semibold {{ $isRepresentative ? 'ring-2 ring-yellow-300' : '' }}"
                                    data-url="{{ $approveRoute }}"
                                    data-id="{{ $req->id }}"
                                    data-no="{{ $req->penawaran->no_penawaran ?? '-' }}"
                                    data-company="{{ $req->penawaran->nama_perusahaan ?? '-' }}"
                                    data-version="{{ $req->version->version ?? '-' }}"
                                    data-is-representative="{{ $isRepresentative ? 'true' : 'false' }}">
                                    @if($isRepresentative)
                                        <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                    {{ $buttonText }}
                                </button>
                            @endif
                            
                            {{-- Revisi Button for Manager --}}
                            @if($canRevise)
                                <button type="button"
                                    class="revisi-btn bg-orange-500 text-white px-3 py-2 rounded hover:bg-orange-600 transition text-xs font-semibold"
                                    data-url="{{ $reviseRoute }}"
                                    data-id="{{ $req->id }}"
                                    data-no="{{ $req->penawaran->no_penawaran ?? '-' }}"
                                    data-company="{{ $req->penawaran->nama_perusahaan ?? '-' }}"
                                    data-version="{{ $req->version->version ?? '-' }}"
                                    title="Revisi dan kembalikan ke supervisor">
                                    <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Revisi
                                </button>
                            @endif
                        </div>
                    </td>
                    </tr>
        @empty
            <tr>
                <td colspan="8" class="py-8">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        <x-lucide-search-x class="w-8 h-8" />
                        <span>Belum ada permintaan verifikasi</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>
