{{--
    Shared role editor. The permission grid renders from PermissionCatalog, so
    adding a key to the catalog surfaces it here with no template change.

    Keys the actor does not hold themselves render disabled: a user may never
    grant a capability they do not have. The server enforces this too — this is
    the affordance, not the enforcement.
--}}
@csrf

<div class="frow">
    <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required maxlength="96">
    </div>
    <div class="field">
        <label for="key">Key</label>
        <input type="text" id="key" name="key" value="{{ old('key', $role->key) }}"
               required maxlength="64" @disabled($role->exists && $role->is_system)>
        <span class="field-hint">Lowercase identifier, e.g. <code>listing_reviewer</code>.</span>
    </div>
</div>

<div class="field">
    <label for="description">Description</label>
    <input type="text" id="description" name="description"
           value="{{ old('description', $role->description) }}" maxlength="255">
</div>

<div class="field">
    <label for="level">Level</label>
    <input type="number" id="level" name="level" value="{{ old('level', $role->level) }}"
           min="1" max="100" required>
    <span class="field-hint">
        Higher outranks lower. You can't create a role at or above your own level.
    </span>
</div>

<h2>Permissions</h2>

<div class="perm-grid">
    @foreach ($catalog as $module => $group)
        <fieldset class="perm-module">
            <legend>{{ $group['label'] }}</legend>

            @foreach ($group['permissions'] as $key => $meta)
                @php($grantable = in_array($key, $grantableKeys, true))
                <label class="checkline {{ $grantable ? '' : 'is-locked' }}">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                           @checked(in_array($key, old('permissions', $granted), true))
                           @disabled(! $grantable)>
                    <span>
                        <strong>{{ $meta['label'] }}</strong>
                        <span class="muted">{{ $meta['description'] }}</span>
                        @unless ($grantable)
                            <span class="muted">You don't hold this permission, so you can't grant it.</span>
                        @endunless
                    </span>
                </label>
            @endforeach
        </fieldset>
    @endforeach
</div>
