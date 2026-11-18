<form method="POST" action="{{ $editing ? route('admin.roles.update', $editing) : route('admin.roles.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <label class="field">
        <span>Nama peran</span>
        <input type="text" name="name" class="input" value="{{ old('name', $editing->name ?? '') }}" maxlength="80" required>
    </label>
    <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" class="input" value="{{ old('slug', $editing->slug ?? '') }}"
               placeholder="otomatis dari nama" maxlength="80" @if ($editing?->is_system) disabled @endif>
        @if ($editing?->is_system)
            <span class="hint">Slug peran sistem tidak dapat diubah.</span>
        @endif
    </label>
    <label class="field">
        <span>Deskripsi</span>
        <input type="text" name="description" class="input" value="{{ old('description', $editing->description ?? '') }}" maxlength="200">
    </label>

    <fieldset>
        <legend>Hak akses</legend>
        @foreach ($permissions as $key => $label)
            @php $allowed = in_array($key, $assignablePermissions, true); @endphp
            <label class="check mb-2 {{ $editing?->is_system ? 'muted' : '' }}">
                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                       @checked(in_array($key, old('permissions', $editing->permissions ?? []), true))
                       @disabled(! $allowed || $editing?->is_system)>
                <span>
                    {{ $label }}
                    @unless ($allowed)
                        <em class="tiny" style="font-style:normal">(di luar wewenangmu)</em>
                    @endunless
                </span>
            </label>
        @endforeach
        @if ($editing?->is_system)
            <p class="hint" style="margin-top:8px">Peran sistem memakai izin bawaan dan hanya dapat diubah superadmin.</p>
        @endif
    </fieldset>

    <div class="flex gap-2 justify-between mt-3">
        <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
        <button type="submit" class="btn btn-primary">
            <x-icon name="shield" :size="14" /> {{ $editing ? 'Simpan peran' : 'Buat peran' }}
        </button>
    </div>
</form>
