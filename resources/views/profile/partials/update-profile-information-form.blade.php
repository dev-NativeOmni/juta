@php
    $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
@endphp

<section>
    <header>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
            {{ __('Informasi Profil') }}
        </h2>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Kelola foto profil dan lihat data akun Anda.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Profile Photo -->
        <div x-data="{ hasPhoto: {{ $user->avatar ? 'true' : 'false' }}, photoPreview: null }">
            <!-- Photo File Input -->
            <input type="file" id="photo" name="avatar" class="hidden"
                        x-ref="photo"
                        x-on:change="
                                hasPhoto = true;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    photoPreview = e.target.result;
                                };
                                reader.readAsDataURL($refs.photo.files[0]);
                                $refs.removeAvatarInput.value = 0;
                        " />

            <x-input-label for="photo" :value="__('Foto Profil')" />

            <!-- Current Profile Photo -->
            <div class="mt-2" x-show="hasPhoto && ! photoPreview">
                @if ($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="rounded-full h-20 w-20 object-cover shadow-md border border-zinc-200 dark:border-zinc-700">
                @endif
            </div>

            <!-- New Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
                <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center shadow-md border border-zinc-200 dark:border-zinc-700"
                      x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </span>
            </div>

            <!-- Fallback Default Initial Profile -->
            <div class="mt-2" x-show="! hasPhoto && ! photoPreview" style="display: none;">
                <div class="rounded-full h-20 w-20 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 flex items-center justify-center font-bold text-2xl shadow-inner uppercase border border-indigo-200 dark:border-indigo-800">
                    {{ substr($user->name, 0, 1) }}
                </div>
            </div>

            <input type="hidden" name="remove_avatar" x-ref="removeAvatarInput" value="0">

            <div class="mt-3 flex items-center gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl font-bold text-xs text-zinc-700 dark:text-zinc-300 uppercase tracking-widest shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150 active:scale-95 min-h-[36px]" 
                        x-on:click.prevent="$refs.photo.click()">
                    {{ __('Pilih Foto Baru') }}
                </button>

                <button type="button" x-show="hasPhoto" class="inline-flex items-center px-4 py-2 bg-rose-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-rose-700 active:scale-95 transition duration-150 min-h-[36px]"
                        x-on:click.prevent="hasPhoto = false; photoPreview = null; $refs.photo.value = null; $refs.removeAvatarInput.value = 1;"
                        style="display: none;">
                    {{ __('Hapus Foto') }}
                </button>
            </div>
            
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" />
            <x-text-input 
                id="name" 
                name="name" 
                type="text" 
                class="mt-1 block w-full {{ ! $isAdmin ? 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-500 dark:text-zinc-400 cursor-not-allowed border-zinc-200 dark:border-zinc-800' : '' }}" 
                :value="old('name', $user->name)" 
                :disabled="! $isAdmin"
                required 
                autocomplete="name" 
            />
            @if (! $isAdmin)
                <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5">
                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5 shrink-0" />
                    <span>Nama hanya dapat diubah oleh Admin atau Super Admin.</span>
                </p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input 
                id="username" 
                name="username" 
                type="text" 
                class="mt-1 block w-full {{ ! $isAdmin ? 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-500 dark:text-zinc-400 cursor-not-allowed border-zinc-200 dark:border-zinc-800' : '' }}" 
                :value="old('username', $user->username)" 
                :disabled="! $isAdmin" 
                required 
                autocomplete="username" 
            />
            @if (! $isAdmin)
                <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5">
                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5 shrink-0" />
                    <span>Username hanya dapat diubah oleh Admin atau Super Admin.</span>
                </p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150 min-h-[38px]">
                Simpan Perubahan Profil
            </button>

            @if (session('status') === 'profile-updated')
                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-bold flex items-center gap-2">
                    <x-heroicon-o-check class="w-4 h-4 shrink-0" />
                    <span>Profil berhasil diperbarui!</span>
                </div>
            @endif
        </div>
    </form>
</section>
