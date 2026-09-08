@extends('layouts.app')
@section('title','Editar rol')
@section('content')
<div class="mb-6"><h1 class="page-title">Editar {{ $role->name }}</h1><p class="page-subtitle">Actualiza el rol y su acceso efectivo.</p></div>
<form method="POST" action="{{ route('settings.roles.update',$role) }}">@csrf @method('PATCH') @include('settings.roles._form')</form>
@endsection
