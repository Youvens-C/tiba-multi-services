@extends('errors._layout')

@section('code', '403')
@section('heading', 'Accès non autorisé')
@section('message', $exception->getMessage() ?: "Vous n'avez pas les permissions nécessaires pour accéder à cette page.")
@section('actions')
    <a href="{{ url('/') }}" class="btn-tiba-primary d-inline-block text-decoration-none">
        <i class="bi bi-house me-1"></i> Retour à l'accueil
    </a>
@endsection
