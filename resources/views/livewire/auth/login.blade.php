<div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-slate-200 p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Sign in to your account</h2>
            <p class="mt-2 text-sm text-slate-600">
                Or <a href="/register" class="font-medium text-brand-600 hover:text-brand-500 transition-colors">register a new company</a>
            </p>
        </div>

        <form wire:submit="login" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <div class="mt-1">
                    <input id="email" wire:model="email" type="email" required autofocus
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                </div>
                @error('email') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <div class="mt-1">
                    <input id="password" wire:model="password" type="password" required
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                </div>
                @error('password') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                    Sign in
                </button>
            </div>
        </form>
    </div>
</div>
