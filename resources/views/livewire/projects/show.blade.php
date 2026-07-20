<div>
    <div class="mb-8">
        <a href="/" class="text-sm font-medium text-brand-600 hover:text-brand-500 mb-4 inline-block">&larr; Back to Projects</a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $project->name }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $project->description }}</p>
    </div>

    @can('create', [App\Models\Task::class, $project])
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Add New Task</h3>
        <form wire:submit="createTask" class="space-y-4 sm:space-y-0 sm:flex sm:items-start sm:space-x-4">
            <div class="flex-1">
                <input type="text" wire:model="title" placeholder="Task Title" required
                    class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                @error('title') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div class="flex-1">
                <input type="text" wire:model="description" placeholder="Description (optional)"
                    class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
            </div>
            <div class="w-full sm:w-48">
                <select wire:model="assigned_to" class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    <option value="">Unassigned</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <button type="submit"
                class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                Add Task
            </button>
        </form>
        @if (session()->has('message'))
            <div class="mt-3 text-sm text-green-600">{{ session('message') }}</div>
        @endif
    </div>
    @endcan

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-4 py-5 border-b border-slate-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-slate-900">Tasks</h3>
        </div>
        <ul role="list" class="divide-y divide-slate-200">
            @forelse($tasks as $task)
            <li class="px-4 py-4 sm:px-6 hover:bg-slate-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <p class="text-sm font-medium text-brand-600 truncate">{{ $task->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $task->description }}</p>
                        @if($task->reassignmentLogs->isNotEmpty() && $task->reassignmentLogs->first()->note)
                            <div class="mt-2 text-xs bg-amber-50 text-amber-800 p-2 rounded border border-amber-200">
                                <span class="font-semibold">Reassignment Note (by {{ optional($task->reassignmentLogs->first()->admin)->name }}):</span>
                                {{ $task->reassignmentLogs->first()->note }}
                            </div>
                        @endif
                    </div>
                    <div class="ml-4 flex-shrink-0 flex items-center space-x-4">
                        @if(auth()->user()->isAdmin())
                            <div class="flex items-center">
                                <svg class="mr-1.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <select wire:change="initiateReassign({{ $task->id }}, $event.target.value)" class="text-xs py-1 px-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 bg-white text-slate-700 font-medium cursor-pointer">
                                    <option value="">Unassigned</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}" {{ $task->assigned_to == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <span class="text-xs text-slate-500 flex items-center">
                                <svg class="mr-1.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                            </span>
                        @endif
                        
                        @can('update', $task)
                            <select wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)" class="text-xs py-1 px-2 border border-slate-300 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 font-medium
                                {{ $task->status === 'done' ? 'bg-green-50 text-green-700' : ($task->status === 'in_progress' ? 'bg-yellow-50 text-yellow-700' : 'bg-slate-50 text-slate-700') }}">
                                <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>To Do</option>
                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Done</option>
                            </select>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $task->status === 'done' ? 'bg-green-100 text-green-800' : ($task->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-800') }}">
                                {{ str_replace('_', ' ', ucfirst($task->status)) }}
                            </span>
                        @endcan
                    </div>
                </div>
            </li>
            @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">
                No tasks available for this project yet.
            </li>
            @endforelse
        </ul>
    </div>

    <!-- Reassign Modal -->
    @if($showReassignModal)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="confirmReassign">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">
                                Reassign Active/Completed Task
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 mb-4">
                                    This task is marked as "In Progress" or "Done". Please provide a reason for reassigning it to someone else.
                                </p>
                                <textarea wire:model="reassignNote" rows="3" class="shadow-sm focus:ring-brand-500 focus:border-brand-500 block w-full sm:text-sm border-slate-300 rounded-md p-2 border" placeholder="Enter reason for reassignment..." required></textarea>
                                @error('reassignNote') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Confirm Reassignment
                            </button>
                            <button type="button" wire:click="resetReassign" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
