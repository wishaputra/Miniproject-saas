<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Activity Logs</h1>
            <p class="mt-2 text-sm text-slate-600">Audit trail of all activities in your company.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date & Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Action</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-xs">
                                        {{ substr(optional($log->user)->name ?? 'System', 0, 2) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-slate-900">{{ optional($log->user)->name ?? 'System' }}</p>
                                        <p class="text-xs text-slate-500">{{ optional($log->user)->email ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $log->action === 'created' ? 'bg-green-100 text-green-800' : 
                                       ($log->action === 'updated' ? 'bg-blue-100 text-blue-800' : 
                                       ($log->action === 'deleted' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800')) }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-900">
                                {{ $log->description }}
                                
                                @if($log->action === 'updated' && $log->new_values)
                                    <div class="mt-2 text-xs border-l-2 border-slate-200 pl-3 py-1 space-y-1">
                                        @foreach($log->new_values as $key => $newValue)
                                            @if($key !== 'updated_at')
                                                @if($key !== 'assigned_to')
                                                    <div class="flex items-start">
                                                        <span class="font-semibold text-slate-600 mr-2">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <div class="flex flex-wrap items-center gap-1 text-slate-500">
                                                            @if(isset($log->old_values[$key]))
                                                                <span class="line-through bg-red-50 text-red-600 px-1 rounded">{{ is_array($log->old_values[$key]) ? json_encode($log->old_values[$key]) : $log->old_values[$key] }}</span>
                                                                <span class="text-slate-400">&rarr;</span>
                                                            @endif
                                                            <span class="bg-green-50 text-green-700 px-1 rounded">{{ is_array($newValue) ? json_encode($newValue) : $newValue }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($key === 'assigned_to' && $log->loggable_type === \App\Models\Task::class)
                                                    @php
                                                        $reassignLog = \App\Models\TaskReassignmentLog::where('task_id', $log->loggable_id)
                                                            ->whereBetween('created_at', [
                                                                $log->created_at->copy()->subSeconds(2), 
                                                                $log->created_at->copy()->addSeconds(2)
                                                            ])
                                                            ->latest()
                                                            ->first();
                                                    @endphp
                                                    @if($reassignLog && $reassignLog->note)
                                                        <div class="mt-2 text-xs italic text-slate-600 bg-amber-50 border-l-2 border-amber-400 p-2 rounded-r">
                                                            <strong class="font-semibold">Admin Note:</strong> {{ $reassignLog->note }}
                                                        </div>
                                                    @endif
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">
                                No activity logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 sm:px-6">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
