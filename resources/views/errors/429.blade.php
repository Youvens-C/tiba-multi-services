@extends('errors._layout')

@section('code', '429')
@section('heading', 'Trop de tentatives')
@section('message', "Vous avez effectué trop de tentatives en peu de temps. Patientez une minute, puis réessayez.")
@section('actions')
    <a href="{{ route('login') }}" class="btn-tiba-primary d-inline-block text-decoration-none">
        <i class="bi bi-arrow-clockwise me-1"></i> Réessayer
    </a>
@endsection
