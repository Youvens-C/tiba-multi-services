@extends('errors._layout')

@section('code', '404')
@section('heading', 'Page introuvable')
@section('message', "La page que vous cherchez n'existe pas ou a été déplacée.")
@section('actions')
    <a href="{{ url('/') }}" class="btn-tiba-primary d-inline-block text-decoration-none">
        <i class="bi bi-house me-1"></i> Retour à l'accueil
    </a>
@endsection
