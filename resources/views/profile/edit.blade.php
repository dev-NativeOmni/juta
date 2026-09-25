<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
            {{ __('Pengaturan Profil & Akun') }}
        </h2>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
            @if ($user->assignedRoles()->count() > 1)
                <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                    @include('profile.partials.role-switcher-card')
                </div>
            @endif

            <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            @if ($canSign)
                <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                    <div class="max-w-xl">
                        @include('profile.partials.update-signature-form')
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
