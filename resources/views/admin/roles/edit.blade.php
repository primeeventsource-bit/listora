@extends('layouts.console')

@section('title', 'Edit '.$role->name.' — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow"><a href="{{ route('admin.roles.index') }}">Roles</a></span>
        <h1>{{ $role->name }}</h1>
        <p><code>{{ $role->key }}</code> · level {{ $role->level }}</p>
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

        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="stack-form">
            @method('PUT')
            @include('admin.roles._form')
            <button type="submit" class="btn btn-primary">Save role</button>
        </form>
    </div>
</section>

@endsection
