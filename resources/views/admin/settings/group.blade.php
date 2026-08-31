@extends('layouts.console')

@section('title', $groupLabel.' settings — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Settings</span>
        <h1>{{ $groupLabel }}</h1>
        @if ($lastChange)
            <p>Last changed {{ $lastChange->occurred_at?->diffForHumans() }}
               @if ($lastChange->actor) by {{ $lastChange->actor->name }} @endif</p>
        @endif
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-tabs">
            @foreach (\App\Services\Settings\SettingsSchema::GROUPS as $key => $label)
                <a href="{{ route('admin.settings.group', $key) }}"
                   class="chip {{ $group === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('admin.settings.flags') }}" class="chip">Feature flags</a>
        </div>

        <form method="POST" action="{{ route('admin.settings.group.update', $group) }}" class="stack-form">
            @csrf
            @method('PUT')

            @foreach ($fields as $key => $field)
                <div class="field setting-field">
                    <label for="{{ $key }}">{{ $field['label'] }}</label>

                    @if ($field['type'] === 'bool')
                        <label class="checkline">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input type="checkbox" id="{{ $key }}" name="{{ $key }}" value="1"
                                   @checked($field['value'])>
                            <span>Enabled</span>
                        </label>

                    @elseif ($field['type'] === 'enum' && $field['options'])
                        <select id="{{ $key }}" name="{{ $key }}">
                            @foreach ($field['options'] as $option)
                                <option value="{{ $option }}" @selected($field['value'] === $option)>{{ $option }}</option>
                            @endforeach
                        </select>

                    @elseif (in_array($field['type'], ['text', 'json'], true))
                        <textarea id="{{ $key }}" name="{{ $key }}" rows="5">{{ is_array($field['value']) ? json_encode($field['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $field['value'] }}</textarea>

                    @elseif ($field['sensitive'])
                        {{-- Sensitive values are never sent to the browser. The field
                             shows whether one is set, and an empty submit leaves it
                             untouched rather than blanking it. --}}
                        <input type="password" id="{{ $key }}" name="{{ $key }}" value=""
                               placeholder="{{ $field['has_value'] ? '•••••• (set — leave blank to keep)' : 'not set' }}"
                               autocomplete="new-password">

                    @else
                        <input type="{{ in_array($field['type'], ['int', 'cents', 'percent'], true) ? 'number' : 'text' }}"
                               id="{{ $key }}" name="{{ $key }}" value="{{ $field['value'] }}">
                    @endif

                    @if ($field['help'])
                        <span class="field-hint">{{ $field['help'] }}</span>
                    @endif
                    <span class="field-hint"><code>{{ $key }}</code>@if ($field['public']) · public @endif</span>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Save {{ $groupLabel }}</button>
        </form>
    </div>
</section>

@endsection
