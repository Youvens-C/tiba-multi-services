@extends('errors._layout')

@section('code', '419')
@section('heading', 'Session expirée')
@section('message', "Pour votre sécurité, votre session a expiré après une période d'inactivité. Reconnectez-vous pour continuer.")
@section('actions')
    <a href="{{ route('login') }}" class="btn-tiba-primary d-inline-block text-decoration-none">
        <i class="bi bi-box-arrow-in-right me-1"></i> Se reconnecter
    </a>
@endsection
