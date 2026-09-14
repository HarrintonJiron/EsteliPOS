@extends('layouts.app')
@section('title','Crear rol')
@section('content')
<div class="mb-6"><h1 class="page-title">Crear rol</h1><p class="page-subtitle">Define una identidad única y su matriz de acceso.</p></div>
<form method="POST" action="{{ route('settings.roles.store') }}">@csrf @include('settings.roles._form')</form>
@endsection
