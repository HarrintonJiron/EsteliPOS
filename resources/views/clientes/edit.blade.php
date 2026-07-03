@extends('layouts.app')

@section('content')
<h1>Editar Cliente</h1>

<form action="{{ route('clientes.update', $client->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div>
        <label>Nombre</label>
        <input name="name" value="{{ old('name', $client->name) }}" required />
    </div>

    <div>
        <label>Teléfono</label>
        <input name="phone" value="{{ old('phone', $client->phone) }}" />
    </div>

    <div>
        <label>Email</label>
        <input name="email" value="{{ old('email', $client->email) }}" />
    </div>

    <div>
        <label>Dirección</label>
        <textarea name="address">{{ old('address', $client->address) }}</textarea>
    </div>

    <button type="submit">Guardar</button>
</form>

@endsection
