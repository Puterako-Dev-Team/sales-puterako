<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-green-500 text-white">
                <th class="px-4 py-3 text-left font-semibold rounded-tl-lg">No</th>
                <th class="px-4 py-3 text-left font-semibold">No Penawaran</th>
                <th class="px-4 py-3 text-left font-semibold">Perihal</th>
                <th class="px-4 py-3 text-center font-semibold">Siklus</th>
                <th class="px-4 py-3 text-center font-semibold">Tahapan</th>
                <th class="px-4 py-3 text-center font-semibold">Tanggal Siklus Dibuat</th>
                <th class="px-4 py-3 text-center font-semibold">Tanggal Reminder Berikutnya</th>
                <th class="px-4 py-3 text-center font-semibold">Interval</th>
                <th class="px-4 py-3 text-center font-semibold">Status</th>
                <th class="px-4 py-3 text-center font-semibold rounded-tr-lg">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($schedules as $index => $schedule)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">{{ $schedules->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ $schedule->penawaran->no_penawaran }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $schedule->penawaran->perihal }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-100 text-green-800">
                            Tahap {{ $schedule->cycle_number }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-semibold text-gray-900">
                            {{ $schedule->current_reminder_count }}/{{ $schedule->max_reminders_per_cycle }}
                        </span>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                            <div class="bg-green-500 h-1.5 rounded-full" 
                                 style="width: {{ ($schedule->current_reminder_count / $schedule->max_reminders_per_cycle) * 100 }}%">
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">
                        {{ $schedule->cycle_start_date ? $schedule->cycle_start_date->format('Y/m/d') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($schedule->next_reminder_date)
                            <span class="text-gray-900 font-medium">
                                {{ $schedule->next_reminder_date->format('Y/m/d') }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $schedule->interval_days }} Hari
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($schedule->status === 'running')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-green-600 rounded-full"></span>
                                Aktif
                            </span>
                        @elseif($schedule->status === 'paused')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-yellow-600 rounded-full"></span>
                                Berhenti
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-gray-600 rounded-full"></span>
                                Selesai
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-1 justify-center">
                            @if($schedule->status === 'completed')
                                <button class="btn-new-cycle px-3 py-1.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-xs"
                                        data-penawaran-id="{{ $schedule->penawaran_id }}"
                                        title="Start New Cycle">
                                    <x-lucide-refresh-cw class="w-4 h-4 inline mr-1" />
                                    New Cycle
                                </button>
                            @else
                                <a href="{{ route('followup.show', $schedule->penawaran_id) }}"
                                   class="px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-xs"
                                   title="Lihat Detail">
                                    <x-lucide-calendar-days class="w-4 h-4 inline" /> 
                                </a>

                                <button class="btn-edit px-3 py-1.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition text-xs"
                                        data-penawaran-id="{{ $schedule->penawaran_id }}"
                                        data-interval="{{ $schedule->interval_days }}"
                                        data-max-reminders="{{ $schedule->max_reminders_per_cycle }}"
                                        data-notes="{{ $schedule->notes }}"
                                        title="Edit Config">
                                    <x-lucide-pencil class="w-4 h-4 inline" />
                                </button>
                                
                                @if($schedule->is_active)
                                    <button class="btn-pause px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-xs"
                                            data-penawaran-id="{{ $schedule->penawaran_id }}"
                                            title="Pause">
                                        <x-lucide-pause class="w-4 h-4 inline" />
                                    </button>
                                @else
                                    <button class="btn-resume px-3 py-1.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-xs"
                                            data-penawaran-id="{{ $schedule->penawaran_id }}"
                                            title="Resume">
                                        <x-lucide-play class="w-4 h-4 inline" />
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center">
                        <div class="flex flex-col items-center text-gray-500">
                            <x-lucide-inbox class="w-12 h-12 mb-2" />
                            <span>Belum ada follow-up schedule</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>