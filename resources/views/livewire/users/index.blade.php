<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
            <p class="mt-2 text-sm text-slate-600">Manage members in your company.</p>
        </div>
        <div>
            <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Member
            </button>
        </div>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        {{ session('message') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Joined At</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-xs">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-slate-900">{{ $user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">
                                No members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create User Modal -->
    @if($showCreateModal)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="store">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">
                                        Add New Member
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                                            <input type="text" wire:model="name" id="name" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                                            <input type="email" wire:model="email" id="email" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                                            <input type="password" wire:model="password" id="password" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                                            <input type="password" wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Member
                            </button>
                            <button type="button" wire:click="$set('showCreateModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
