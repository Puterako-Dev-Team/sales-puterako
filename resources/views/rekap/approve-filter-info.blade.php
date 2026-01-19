<div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
    <div class="flex items-center justify-between">
        <span class="text-sm text-blue-800">
            <x-lucide-info class="w-4 h-4 inline mr-1" />
            Menampilkan {{ $from ?? 0 }} sampai {{ $to ?? 0 }} dari {{ $count }} data (Total: {{ $total }} data)
        </span>
        <span class="text-xs text-blue-600">
            Filter aktif: {{ $filters }} | Halaman {{ $currentPage ?? 1 }} dari {{ $lastPage ?? 1 }}
        </span>
    </div>
</div>
