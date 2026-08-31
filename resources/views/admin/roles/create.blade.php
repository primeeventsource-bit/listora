@extends('layouts.console')

@section('title', 'New role — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow"><a href="{{ route('admin.roles.index') }}">Roles</a></span>
        <h1>New role</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.roles.store') }}" class="stack-form">
            @include('admin.roles._form')
            <button type="submit" class="btn btn-primary">Create role</button>
        </form>
    </div>
</section>

@endsection
