@extends('layouts.app')
@section('title', 'Crear usuario')
@section('content')
<div class="mb-6"><h1 class="page-title">Crear usuario</h1><p class="page-subtitle">Identidad, acceso y permisos de una nueva cuenta.</p></div>
<form action="{{ route('settings.users.store') }}" method="POST" enctype="multipart/form-data">@csrf @include('settings.users._form')</form>
@endsection
