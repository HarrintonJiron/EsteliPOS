@extends('layouts.app')
@section('title', 'Editar usuario')
@section('content')
<div class="mb-6"><h1 class="page-title">Editar a {{ $user->name }}</h1><p class="page-subtitle">Actualiza sus datos, roles y permisos especiales.</p></div>
<form action="{{ route('settings.users.update', $user) }}" method="POST" enctype="multipart/form-data">@csrf @method('PATCH') @include('settings.users._form')</form>
@endsection
