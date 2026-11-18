@extends('layouts.app')

@section('title', $editing ? 'Ubah Peran' : 'Peran Baru')
@section('breadcrumb', 'Administrasi · ' . ($editing ? 'Ubah peran' : 'Peran baru'))

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Kontrol akses</p>
            <h1>{{ $editing ? 'Ubah peran '.$editing->name : 'Peran baru' }}</h1>
            <p class="sub">{{ $editing ? 'Perbarui nama atau hak akses peran.' : 'Buat peran khusus sesuai kebutuhan tim.' }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><div><h2>Detail peran</h2><p>Nama, slug, dan hak akses</p></div></div>
        <div class="card-body pad">
            @include('admin.roles._form')
        </div>
    </div>
@endsection
